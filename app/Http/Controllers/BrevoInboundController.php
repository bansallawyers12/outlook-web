<?php

namespace App\Http\Controllers;

use App\Models\Email;
use App\Models\EmailAccount;
use App\Services\EmailFolderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BrevoInboundController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $secret = config('services.brevo.inbound_secret');
        if ($secret) {
            $providedSecret = $request->header('X-Brevo-Signature')
                ?? $request->header('X-Brevo-Webhook-Token')
                ?? $request->query('token');

            if (!is_string($providedSecret) || !hash_equals($secret, $providedSecret)) {
                Log::warning('Rejected Brevo inbound webhook due to invalid signature.');
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid inbound signature.',
                ], 403);
            }
        }

        $items = $request->input('items');

        if (!is_array($items) || empty($items)) {
            return response()->json([
                'success' => false,
                'message' => 'No inbound items provided.',
            ], 422);
        }

        $stored = 0;
        $skipped = 0;
        $errors = [];

        foreach ($items as $index => $payload) {
            try {
                $account = $this->resolveAccountFromPayload($payload);
                if (!$account) {
                    $skipped++;
                    continue;
                }

                $messageId = $this->extractMessageId($payload);
                if (!$messageId) {
                    $messageId = (string) Str::uuid();
                }

                $existing = Email::where('account_id', $account->id)
                    ->where('message_id', $messageId)
                    ->first();

                if ($existing) {
                    $skipped++;
                    continue;
                }

                $email = $this->persistEmail($account, $messageId, $payload);

                $this->persistAttachments($email, $payload);
                $this->persistRawMessage($account, $messageId, $payload);

                $stored++;
            } catch (\Throwable $e) {
                $errors[] = [
                    'index' => $index,
                    'error' => $e->getMessage(),
                ];

                Log::error('Failed to persist Brevo inbound email', [
                    'index' => $index,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Inbound payload processed.',
            'stored' => $stored,
            'skipped' => $skipped,
            'errors' => $errors,
        ]);
    }

    private function resolveAccountFromPayload(array $payload): ?EmailAccount
    {
        $recipient = data_get($payload, 'to.0.email')
            ?? data_get($payload, 'rcpt')
            ?? data_get($payload, 'recipient');

        if (!$recipient) {
            return null;
        }

        return EmailAccount::where('email', $recipient)->first();
    }

    private function extractMessageId(array $payload): ?string
    {
        return data_get($payload, 'headers.message-id')
            ?? data_get($payload, 'headers.Message-ID')
            ?? data_get($payload, 'messageId')
            ?? data_get($payload, 'uuid');
    }

    private function persistEmail(EmailAccount $account, string $messageId, array $payload): Email
    {
        $fromEmail = data_get($payload, 'from.email') ?? data_get($payload, 'from');
        $fromName = data_get($payload, 'from.name');

        $recipients = collect(data_get($payload, 'to', []))
            ->map(fn ($entry) => is_array($entry) ? ($entry['email'] ?? null) : $entry)
            ->filter()
            ->values()
            ->all();

        $subject = data_get($payload, 'subject');
        $textBody = data_get($payload, 'text') ?? data_get($payload, 'textBody');
        $htmlBody = data_get($payload, 'html') ?? data_get($payload, 'htmlBody');
        $headers = data_get($payload, 'headers', []);
        $date = data_get($payload, 'date') ?? data_get($payload, 'sentAt');
        $folder = data_get($payload, 'folder', 'Inbox');

        return Email::create([
            'account_id' => $account->id,
            'user_id' => $account->user_id,
            'message_id' => $messageId,
            'from_email' => $fromEmail,
            'sender_email' => $fromEmail,
            'sender_name' => $fromName,
            'to_email' => implode(',', $recipients),
            'recipients' => $recipients ?: null,
            'subject' => $subject,
            'text_body' => $textBody,
            'body' => $textBody,
            'html_body' => $htmlBody,
            'headers' => $headers,
            'folder' => $folder ?? 'Inbox',
            'received_at' => $date ? \Carbon\Carbon::parse($date) : now(),
            'status' => 'received',
        ]);
    }

    private function persistAttachments(Email $email, array $payload): void
    {
        $attachments = data_get($payload, 'attachments', []);
        if (!is_array($attachments) || empty($attachments)) {
            return;
        }

        foreach ($attachments as $attachment) {
            $content = data_get($attachment, 'content')
                ?? data_get($attachment, 'data')
                ?? data_get($attachment, 'base64');

            if (!$content) {
                continue;
            }

            $binary = base64_decode($content, true);
            if ($binary === false) {
                $binary = $content;
            }

            $filename = data_get($attachment, 'name')
                ?? data_get($attachment, 'filename')
                ?? ('attachment-' . Str::random(8));

            $safeMessageId = $this->sanitizeForPath($email->message_id ?? Str::uuid()->toString());
            $relativePath = "email-attachments/{$safeMessageId}/{$filename}";

            Storage::disk('local')->put($relativePath, $binary);

            $email->attachments()->create([
                'filename' => $filename,
                'display_name' => $filename,
                'content_type' => data_get($attachment, 'contentType') ?? data_get($attachment, 'type'),
                'file_size' => strlen($binary),
                'file_path' => storage_path('app/private/' . $relativePath),
                'storage_path' => $relativePath,
                'is_inline' => (bool) data_get($attachment, 'isInline', false),
                'headers' => data_get($attachment, 'headers'),
                'extension' => pathinfo($filename, PATHINFO_EXTENSION),
            ]);
        }
    }

    private function persistRawMessage(EmailAccount $account, string $messageId, array $payload): void
    {
        $raw = data_get($payload, 'raw');
        if (!$raw) {
            return;
        }

        $decoded = base64_decode($raw, true);
        if ($decoded === false) {
            $decoded = $raw;
        }

        $folder = data_get($payload, 'folder', 'Inbox') ?? 'Inbox';
        $folderService = new EmailFolderService();
        $folderService->saveEmailToFile($account, $folder, $messageId, $decoded);
    }

    private function sanitizeForPath(string $value): string
    {
        return preg_replace('/[^a-zA-Z0-9._-]/', '_', $value);
    }
}

