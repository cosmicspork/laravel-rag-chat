<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use HasFactory;

    // Specify the table name
    protected $table = 'chat_messages';

    // Allow mass assignment for these fields
    protected $fillable = [
        'user_id',
        'user_message',
        'api_response',
    ];

    /**
     * Get the user that owns the message.
     * 
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
