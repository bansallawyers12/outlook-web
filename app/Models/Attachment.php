<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    protected $table = 'email_attachments';

    protected $fillable = [
        'email_id',
        'filename',
        'display_name',
        'content_type',
        'file_size',
        'file_path',
        'content_id',
        'is_inline',
        'description',
        'headers',
        'extension',
    ];

    protected $casts = [
        'headers' => 'array',
        'is_inline' => 'boolean',
    ];

    public function email(): BelongsTo
    {
        return $this->belongsTo(Email::class);
    }

    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = (int) ($this->file_size ?? 0);
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes > 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function canPreview(): bool
    {
        return $this->isImage() || $this->isPdf();
    }

    public function getPreviewType(): ?string
    {
        if ($this->isImage()) return 'image';
        if ($this->isPdf()) return 'pdf';
        return null;
    }

    public function isImage(): bool
    {
        return is_string($this->content_type) && str_starts_with($this->content_type, 'image/');
    }

    public function isPdf(): bool
    {
        return $this->content_type === 'application/pdf' || ($this->extension === 'pdf');
    }

    public function isDocument(): bool
    {
        if (!$this->content_type) return false;
        return str_starts_with($this->content_type, 'application/') && !$this->isPdf();
    }
}


