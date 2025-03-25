<?php

use App\Models\User;
use App\Models\Notification;

function createNotificationForUserAndAdmins($notify_by, $notify_to, $description, $description1, $type)
{
    $users = User::where('role', 'admin')->orWhere('id', $notify_by)->where('status', 'active')->get();

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

function createNotificationForChefs($notify_by, $description1, $chefs, $type)
{
    // $description = "New Booking Alert: Please check in available bookings."; 
    foreach ($chefs as $chef) {
        $notification = new Notification();
        $notification->notify_by = $notify_by;
        $notification->notify_to = $chef->id;
        $notification->type = $type;
        $notification->description = $description1;
        $notification->save();
    }
}


function createNotificationForConcierge($notify_by1, $notify_to1, $description1, $type1)
{
    $notification = new Notification();
    $notification->notify_by = $notify_by1;
    $notification->notify_to = $notify_to1;
    $notification->type = $type1;
    $notification->description = $description1;
    $notification->save();
}
