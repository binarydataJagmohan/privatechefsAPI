<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Auth;

class Chat_group extends Model
{
    use HasFactory;
    protected $fillable = [
        'group_name',
        'group_admin'
    ];

    // public static function crete_group($name){
    // 	$Input['group_name'] = $name;
    // 	$Input['group_admin'] = Auth::user()->id;
    // 	return Chat_group::insertGetId($Input);
    // }

    // public static function update_group($name, $id){
    //     $Input['group_name'] = $name;
    //     return Chat_group::where('id', $id)->update($Input);
    // }
}