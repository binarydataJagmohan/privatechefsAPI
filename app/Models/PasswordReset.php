<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordReset extends Model
{
    use HasFactory;

    public $table = 'password_resets';
    
    protected $fillable = [

        'email',
        'user_id',
        'token',
        'created_at',
        'updated_at'
    ];
}