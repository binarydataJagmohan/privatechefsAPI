<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Notification;
use Symfony\Component\HttpKernel\Exception\HttpException;
use DB;

class NotificationController extends Controller
{
    public function notification_for_user_admin(Request $request)
    {
        try{
            $count = Notification::where('notify_to',$request->id)->where('notifications_status','unseen')->count(); 
            $notifications = DB::table('notifications')
            ->join('users', 'notifications.notify_by', '=', 'users.id')
            ->select('notifications.*', 'users.pic')
            ->where('notifications.notify_to', $request->id)
            ->get(); 
            if ($notifications) {            
                return response()->json(['status' => true, 'message' => "Notification fetched successfully", 'data' => $notifications,'count'=>$count], 200);
            } else {
                return response()->json(['status' => false, 'message' => "There has been error for fetching the notification", 'data' => ""], 400);
            }
        }catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function notification_status(Request $request){
        try{
            $notifications = Notification::where('notify_to',$request->id)->update([
                'notifications_status' => "seen"
            ]); 
            if ($notifications) {            
                return response()->json(['status' => true, 'message' => "Notification status changed successfully", 'data' => $notifications], 200);
            } else {
                return response()->json(['status' => true, 'message' => "There has been error for changing the notification status", 'data' => ""], 200);
            }
        }catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
            return response()->json(['status' => false, 'message' => "There has been error for changing the notification status", 'data' => ""], 400);
        }
    }
    
}
