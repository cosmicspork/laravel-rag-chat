<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatFeedback extends Model
{
    // Table name (defaults to 'chat_feedback')
    protected $table = 'chat_feedback';

    // Allow mass-assignment on these fields
    protected $fillable = [
        'chat_message_id',
        'user_id',
        'feedback',
    ];

    /**
     * Relationship: This feedback belongs to one chat message.
     */
    public function message()
    {
        return $this->belongsTo(ChatMessage::class, 'chat_message_id');
    }

    /**
     * Relationship: This feedback was provided by one user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
