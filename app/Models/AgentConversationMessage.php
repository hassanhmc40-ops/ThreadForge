<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentConversationMessage extends Model
{
    protected $table = 'agent_conversation_messages';

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'user_id',
    ];
}
