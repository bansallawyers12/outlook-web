<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class AttachmentController extends Controller
{
    public function download(int $id): Response
    {
        $attachment = Attachment::with('email.emailAccount')->findOrFail($id);
        
        // Check if user has access to this attachment
        if ($attachment->email->emailAccount->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to attachment');
        }

        $filePath = $this->getAttachmentPath($attachment);
        if (!$filePath || !file_exists($filePath)) {
            abort(404, 'Attachment file not found');
        }

        $headers = [
            'Content-Type' => $attachment->content_type ?: 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . ($attachment->filename ?: 'attachment') . '"',
            'Content-Length' => (string) ($attachment->file_size ?? filesize($filePath)),
        ];

        return response()->file($filePath, $headers);
    }

    public function view(int $id): Response
    {
        $attachment = Attachment::with('email.emailAccount')->findOrFail($id);
        
        // Check if user has access to this attachment
        if ($attachment->email->emailAccount->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to attachment');
        }

        // Only allow viewing of images and PDFs
        if (!$attachment->canPreview()) {
            abort(400, 'This file type cannot be previewed');
        }

        $filePath = $this->getAttachmentPath($attachment);
        if (!$filePath || !file_exists($filePath)) {
            abort(404, 'Attachment file not found');
        }

        $headers = [
            'Content-Type' => $attachment->content_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . ($attachment->filename ?: 'attachment') . '"',
            'Content-Length' => (string) ($attachment->file_size ?? filesize($filePath)),
        ];

        return response()->file($filePath, $headers);
    }

    private function getAttachmentPath(Attachment $attachment): ?string
    {
        // Try storage_path first (Laravel storage)
        if ($attachment->storage_path) {
            $storagePath = storage_path('app/public/' . $attachment->storage_path);
            if (file_exists($storagePath)) {
                return $storagePath;
            }
        }

        // Fallback to file_path (direct path)
        if ($attachment->file_path && file_exists($attachment->file_path)) {
            return $attachment->file_path;
        }

        // Try Laravel storage disk
        if ($attachment->storage_path) {
            try {
                $diskPath = Storage::disk('public')->path($attachment->storage_path);
                if (file_exists($diskPath)) {
                    return $diskPath;
                }
            } catch (\Exception $e) {
                // Continue to next option
            }
        }

        return null;
    }
}


