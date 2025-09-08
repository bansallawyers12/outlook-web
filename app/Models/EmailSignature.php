<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailSignature extends Model
{
    protected $fillable = [
        'user_id',
        'account_id',
        'name',
        'content',
        'html_content',
        'images',
        'template_type',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'images' => 'array',
    ];

    /**
     * Get the user that owns the signature.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the email account that owns the signature.
     */
    public function emailAccount(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class, 'account_id');
    }

    /**
     * Scope to get signatures for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get signatures for a specific account.
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope to get active signatures.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get default signatures.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Get the appropriate content based on whether HTML is needed.
     */
    public function getContent($useHtml = false): string
    {
        return $useHtml ? ($this->html_content ?: $this->content) : $this->content;
    }

    /**
     * Set as default signature and unset others for the same user/account.
     */
    public function setAsDefault(): void
    {
        // Unset other default signatures for the same user and account
        static::where('user_id', $this->user_id)
            ->where('account_id', $this->account_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        // Set this signature as default
        $this->update(['is_default' => true]);
    }

    /**
     * Add an image to the signature.
     */
    public function addImage(string $path, string $alt = ''): void
    {
        $images = $this->images ?? [];
        $images[] = [
            'path' => $path,
            'alt' => $alt,
            'uploaded_at' => now()->toISOString(),
        ];
        $this->update(['images' => $images]);
    }

    /**
     * Remove an image from the signature.
     */
    public function removeImage(string $path): void
    {
        $images = $this->images ?? [];
        $images = array_filter($images, function ($image) use ($path) {
            return $image['path'] !== $path;
        });
        $this->update(['images' => array_values($images)]);
    }

    /**
     * Get all image paths for this signature.
     */
    public function getImagePaths(): array
    {
        return collect($this->images ?? [])->pluck('path')->toArray();
    }

    /**
     * Get the full URL for an image path.
     */
    public function getImageUrl(string $path): string
    {
        return asset('storage/signatures/' . $path);
    }

    /**
     * Get all image URLs for this signature.
     */
    public function getImageUrls(): array
    {
        return collect($this->images ?? [])->map(function ($image) {
            return [
                'url' => $this->getImageUrl($image['path']),
                'alt' => $image['alt'] ?? '',
                'path' => $image['path'],
            ];
        })->toArray();
    }
}