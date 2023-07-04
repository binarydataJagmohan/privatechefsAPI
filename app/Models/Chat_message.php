<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat_message extends Model
{
    use HasFactory;
    protected $fillable = [
        'sender_id',
        'booking_id',
        'receiver_id',
        'group_id',
        'message',
        'status',
        'message_status',
        'read_msg_status'
    ];
}
