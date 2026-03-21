<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiChatMessage extends Model
{
    protected $fillable = [
        'user_id',
        'session_key',
        'role',
        'tool_name',
        'content',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}
