<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat_group_member extends Model
{
    use HasFactory;
    protected $fillable = [
        'group_id',
        'member_id',
        'status'
    ];

    // public static function add_member($group_id, $member_id){
    //     $newData = Chat_group_member::where('group_id', $group_id)->where('member_id',$member_id)->updateOrCreate([
    //         'group_id'=>$group_id,
    //         'member_id'=> $member_id,
    //         'status'=> 'accept'
    //     ]);
    //     return $newData->id;
    // }


    // public static function update_member($group_id, $members_ids){
    //     Chat_group_member::where('group_id', $group_id)->whereNotIn('member_id', $members_ids)->update(['status'=>'removed']);
    //     Chat_group_member::where('group_id', $group_id)->whereIn('member_id', $members_ids)->update(['status'=>'accept']);
    //     return;
    // }
    // public static function update_add_member($group_id, $members_ids, $single_member_id){
    //     Chat_group_member::where('group_id', $group_id)->whereIn('member_id', $members_ids)->updateOrCreate(['group_id'=>$group_id, 'member_id'=> $single_member_id, 'status'=> 'accept']);
    //     return;
    // }


}
