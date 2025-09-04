<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'email',
        'password',
        'access_token',
        'refresh_token',
        'last_connection_error',
        'last_connection_attempt',
        'connection_status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'last_connection_attempt' => 'datetime',
        'connection_status' => 'boolean',
    ];

    /**
     * Get the user that owns the email account.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the emails for the email account.
     */
    public function emails(): HasMany
    {
        return $this->hasMany(Email::class, 'account_id');
    }

    /**
     * Get the provider name in a formatted way.
     */
    public function getProviderNameAttribute(): string
    {
        return ucfirst($this->provider);
    }

    /**
     * Get the authentication method.
     */
    public function getAuthMethodAttribute(): string
    {
        return $this->access_token ? 'OAuth' : 'Password';
    }

    /**
     * Get the total email count for this account.
     */
    public function getEmailCountAttribute(): int
    {
        return $this->emails()->count();
    }

    /**
     * Get recent emails count (last 7 days).
     */
    public function getRecentEmailCountAttribute(): int
    {
        return $this->emails()->where('created_at', '>=', now()->subDays(7))->count();
    }

    /**
     * Get the last sync time.
     */
    public function getLastSyncAttribute(): ?string
    {
        $lastEmail = $this->emails()->latest()->first();
        return $lastEmail ? $lastEmail->created_at->diffForHumans() : null;
    }

    /**
     * Get the connection status display.
     */
    public function getConnectionStatusDisplayAttribute(): string
    {
        if ($this->connection_status) {
            return 'Connected ✓';
        }
        
        if ($this->last_connection_error) {
            return 'Failed ✗';
        }
        
        return 'Not tested';
    }

    /**
     * Get the connection error display.
     */
    public function getConnectionErrorDisplayAttribute(): ?string
    {
        if (!$this->last_connection_error) {
            return null;
        }
        
        // Truncate long error messages
        if (strlen($this->last_connection_error) > 100) {
            return substr($this->last_connection_error, 0, 100) . '...';
        }
        
        return $this->last_connection_error;
    }

    /**
     * Get storage statistics for this email account
     */
    public function getStorageStats(): array
    {
        $folderService = new \App\Services\EmailFolderService();
        return $folderService->getAccountStorageStats($this);
    }

    /**
     * Get local folder path for this account
     */
    public function getLocalFolderPath(): string
    {
        $folderService = new \App\Services\EmailFolderService();
        return $folderService->getAccountPath($this);
    }
}


