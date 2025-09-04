<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailDraft extends Model
{
    protected $fillable = [
        'user_id',
        'account_id',
        'to_email',
        'cc_email',
        'bcc_email',
        'subject',
        'message',
        'attachments',
    ];

    protected $casts = [
        'attachments' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function emailAccount(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class, 'account_id');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}