<?php

use App\Models\User;
use App\Models\Notification;

function createNotificationForUserAndAdmins($notify_by, $notify_to, $description,$description1, $type) 
{
    $users = User::where('role', 'admin')->orWhere('id', $notify_by)->where('status','active')->get();    
  
    foreach ($users as $user) {
        $notification = new Notification();
        $notification->notify_by = $notify_by;
        $notification->notify_to = $user->id;
        $notification->type = $type;
        
        if ($notify_by == $user->id) {
            $notification->description = $description;
        } else {
            $notification->description = $description1;
        }
        
        $notification->save();
    }
}


?>
