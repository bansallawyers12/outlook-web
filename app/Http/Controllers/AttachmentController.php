<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Http\Response;

class AttachmentController extends Controller
{
    public function download(int $id): Response
    {
        $attachment = Attachment::findOrFail($id);
        if (!$attachment->file_path || !file_exists($attachment->file_path)) {
            abort(404, 'Attachment not found');
        }

        $headers = [
            'Content-Type' => $attachment->content_type ?: 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . ($attachment->filename ?: 'attachment') . '"',
            'Content-Length' => (string) ($attachment->file_size ?? filesize($attachment->file_path)),
        ];

        return response()->file($attachment->file_path, $headers);
    }
}


