<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatbotConversation extends Model
{
    protected $table = 'chatbot_conversations';

    protected $fillable = [
        'session_id',
        'lang',
        'status',
    ];

    /**
     * Une conversation possède plusieurs messages.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatbotMessage::class, 'conversation_id');
    }
}
