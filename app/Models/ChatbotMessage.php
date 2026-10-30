<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotMessage extends Model
{
    protected $table = 'chatbot_messages';

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
    ];

    /**
     * Un message appartient à une conversation.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatbotConversation::class, 'conversation_id');
    }
}
