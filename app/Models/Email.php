<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Email extends Model
{
    protected $fillable = [
        'account_id',
        'message_id',
        'from_email',
        'subject',
        'body',
        'folder',
        'received_at',
        'date'
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'date' => 'datetime'
    ];

    public function emailAccount(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class, 'account_id');
    }
}
