<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Email extends Model
{
    protected $fillable = [
        'account_id',
        'user_id',
        'message_id',
        'from_email',
        'sender_email',
        'sender_name',
        'to_email',
        'cc',
        'reply_to',
        'recipients',
        'subject',
        'body',
        'html_body',
        'html_content',
        'text_body',
        'text_content',
        'folder',
        'received_at',
        'sent_date',
        'date',
        'headers',
        'status',
        'file_path',
        'file_size',
        'is_important',
        'is_read',
        'is_flagged',
        'tags',
        'notes',
        'last_accessed_at'
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'sent_date' => 'datetime',
        'date' => 'datetime',
        'headers' => 'array',
        'recipients' => 'array',
        'is_important' => 'boolean',
        'is_read' => 'boolean',
        'is_flagged' => 'boolean',
        'last_accessed_at' => 'datetime'
    ];

    public function emailAccount(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class, 'account_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class, 'email_label');
    }
}
