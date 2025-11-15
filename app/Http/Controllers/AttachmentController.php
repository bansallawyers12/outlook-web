<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class AttachmentController extends Controller
{
    public function download(int $id): BinaryFileResponse
    {
        try {
            $attachment = Attachment::with('email.emailAccount')->findOrFail($id);
            
            // Check if user has access to this attachment
            if ($attachment->email->emailAccount->user_id !== Auth::id()) {
                abort(403, 'Unauthorized access to attachment');
            }

            $filePath = $this->getAttachmentPath($attachment);
            if (!$filePath || !file_exists($filePath)) {
                \Log::error('Attachment file not found for download', [
                    'attachment_id' => $id,
                    'filename' => $attachment->filename,
                    'file_path' => $attachment->file_path,
                    'storage_path' => $attachment->storage_path,
                    'resolved_path' => $filePath,
                ]);
                abort(404, 'Attachment file not found');
            }

            $headers = [
                'Content-Type' => $attachment->content_type ?: 'application/octet-stream',
                'Content-Disposition' => 'attachment; filename="' . ($attachment->filename ?: 'attachment') . '"',
                'Content-Length' => (string) ($attachment->file_size ?? filesize($filePath)),
            ];

            return response()->file($filePath, $headers);
        } catch (\Exception $e) {
            \Log::error('Attachment download error', [
                'attachment_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            abort(500, 'Error downloading attachment');
        }
    }

    public function view(int $id): BinaryFileResponse
    {
        try {
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
                \Log::error('Attachment file not found for view', [
                    'attachment_id' => $id,
                    'filename' => $attachment->filename,
                    'file_path' => $attachment->file_path,
                    'storage_path' => $attachment->storage_path,
                    'resolved_path' => $filePath,
                ]);
                abort(404, 'Attachment file not found');
            }

            $headers = [
                'Content-Type' => $attachment->content_type ?: 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="' . ($attachment->filename ?: 'attachment') . '"',
                'Content-Length' => (string) ($attachment->file_size ?? filesize($filePath)),
            ];

            return response()->file($filePath, $headers);
        } catch (\Exception $e) {
            \Log::error('Attachment view error', [
                'attachment_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            abort(500, 'Error viewing attachment');
        }
    }

    private function getAttachmentPath(Attachment $attachment): ?string
    {
        // Try file_path first (legacy import path)
        if ($attachment->file_path) {
            // Normalize path separators for cross-platform compatibility
            $normalizedPath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $attachment->file_path);
            
            // Try the path as-is first
            if (file_exists($normalizedPath)) {
                return $normalizedPath;
            }
            
            // Try with base_path if it starts with storage
            if (str_starts_with($normalizedPath, 'storage')) {
                $basePath = base_path($normalizedPath);
                if (file_exists($basePath)) {
                    return $basePath;
                }
            }
            
            // Try converting to absolute path if it's relative
            if (!str_starts_with($normalizedPath, storage_path())) {
                $absolutePath = storage_path($normalizedPath);
                if (file_exists($absolutePath)) {
                    return $absolutePath;
                }
            }
            
            // If the exact path doesn't exist, try to find the file by filename in the attachments directory
            $filename = $attachment->filename;
            if ($filename) {
                $attachmentsDir = storage_path('app/attachments');
                if (is_dir($attachmentsDir)) {
                    $iterator = new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator($attachmentsDir, \RecursiveDirectoryIterator::SKIP_DOTS)
                    );
                    
                    foreach ($iterator as $file) {
                        if ($file->isFile() && $file->getFilename() === $filename) {
                            return $file->getPathname();
                        }
                    }
                }
            }
        }

        // Try storage_path (Laravel storage for new attachments)
        if ($attachment->storage_path) {
            // Try different possible storage paths
            $possiblePaths = [
                storage_path('app/public/' . $attachment->storage_path),
                storage_path('app/' . $attachment->storage_path),
                base_path('storage/app/public/' . $attachment->storage_path),
                base_path('storage/app/' . $attachment->storage_path),
            ];

            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    return $path;
                }
            }

            // Try Laravel storage disk
            try {
                $diskPath = Storage::disk('public')->path($attachment->storage_path);
                if (file_exists($diskPath)) {
                    return $diskPath;
                }
            } catch (\Exception $e) {
                // Continue to next option
            }
        }

        // Log the issue for debugging
        \Log::warning('Attachment file not found', [
            'attachment_id' => $attachment->id,
            'filename' => $attachment->filename,
            'file_path' => $attachment->file_path,
            'storage_path' => $attachment->storage_path,
        ]);

        return null;
    }
}


