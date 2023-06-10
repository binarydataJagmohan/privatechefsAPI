<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Auth;

class Chat_group extends Model
{   
    use HasFactory;

    protected $table = "chat_groups";
}