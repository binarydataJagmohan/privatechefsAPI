<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Chat_group;
use App\Models\Chat_group_member;
use App\Models\Chat_message;
use DB;

class UserChatController extends Controller
{
    public function get_user_message_data(Request $request)
    {
        try {
            $userId = $request->id;

            $userSideMessages = Chat_message::select('chat_messages.booking_id', 'chat_messages.sender_id', 'chat_messages.receiver_id', 'users.name', 'users.pic', DB::raw('(SELECT COUNT(*) FROM chat_messages WHERE chat_messages.message_status = "unread" AND chat_messages.receiver_id = '.$userId.' AND chat_messages.sender_id = users.id) as unreadcount'))
              
                ->join('users', function ($join) use ($userId) {
                    $join->on('chat_messages.receiver_id', '=', 'users.id')
                        ->where('chat_messages.sender_id', $userId);
                })
                ->groupBy('chat_messages.booking_id', 'users.name', 'users.pic','users.name', 'users.pic')
                ->orderBy('chat_messages.id', 'desc')
                ->get();

            $senderId = $request->id;
            $receiverId = $userSideMessages[0]->receiver_id;
            $bookingId = $userSideMessages[0]->booking_id; 

            $userChatMessages = Chat_message::select('message', 'chat_messages.booking_id', 'sender.name AS sender_name', 'receiver.name AS receiver_name', 'sender.pic AS sender_pic', 'receiver.pic AS receiver_pic', 'sender.role AS sender_role', 'receiver.role AS receiver_role', 'sender.id AS sender_id', 'receiver.id AS receiver_id')
                ->join('users AS sender', 'chat_messages.sender_id', '=', 'sender.id')
                ->join('users AS receiver', 'chat_messages.receiver_id', '=', 'receiver.id')
                ->where(function ($query) use ($senderId, $receiverId) {
                    $query->where('chat_messages.sender_id', $senderId)
                        ->where('chat_messages.receiver_id', $receiverId);
                })
                ->orWhere(function ($query) use ($senderId, $receiverId) {
                    $query->where('chat_messages.sender_id', $receiverId)
                        ->where('chat_messages.receiver_id', $senderId);
                })
                ->where('chat_messages.booking_id', $bookingId)
                ->get();

          
            return response()->json([
                'status' => true,
                'userchatsider' => $userSideMessages,
                'booking_id' => $userSideMessages[0]->booking_id,
                'userchatdata' => $userChatMessages,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch user messages',
            ], 500);
        }
    }

    public function contact_chef_by_user(Request $request)
    {
        try {
            
            $messgae = new Chat_message();
            $messgae->sender_id =  $request->sender_id;
            $messgae->booking_id =  $request->booking_id;
            $messgae->receiver_id =  $request->receiver_id;
            $messgae->message =  $request->message;

            $messgae->save();

            $senderId = $request->sender_id;
            $receiverId = $request->receiver_id;
            $bookingId = $request->booking_id;

            $userChatMessages = Chat_message::select('message', 'chat_messages.booking_id', 'sender.name AS sender_name', 'receiver.name AS receiver_name', 'sender.pic AS sender_pic', 'receiver.pic AS receiver_pic', 'sender.role AS sender_role', 'receiver.role AS receiver_role', 'sender.id AS sender_id', 'receiver.id AS receiver_id')
                ->join('users AS sender', 'chat_messages.sender_id', '=', 'sender.id')
                ->join('users AS receiver', 'chat_messages.receiver_id', '=', 'receiver.id')
                ->where(function ($query) use ($senderId, $receiverId) {
                    $query->where('chat_messages.sender_id', $senderId)
                        ->where('chat_messages.receiver_id', $receiverId);
                })
                ->orWhere(function ($query) use ($senderId, $receiverId) {
                    $query->where('chat_messages.sender_id', $receiverId)
                        ->where('chat_messages.receiver_id', $senderId);
                })
                ->where('chat_messages.booking_id', $bookingId)
                ->get();


            if($messgae->save()){

                 return response()->json(['status' => true, 'message' => 'Mesage has been updated successfully','userchatdata' => $userChatMessages]);
             }else {

                 return response()->json(['status' => false, 'message' => 'There has been for sending the mesage']);
             }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch user messages',
            ], 500);
        }
    }

    public function get_click_user_chef_chat_data(Request $request)
    {
       

            $senderId = $request->sender_id;
            $receiverId = $request->receiver_id;
            $bookingId = $request->booking_id;

             $Chat_message = Chat_message::where('sender_id', $receiverId)
            ->where('booking_id', $bookingId)
            ->update(['message_status' => 'read']);

            $userSideMessages = Chat_message::select('chat_messages.booking_id', 'chat_messages.sender_id', 'chat_messages.receiver_id', 'users.name', 'users.pic', DB::raw('(SELECT COUNT(*) FROM chat_messages WHERE chat_messages.message_status = "unread" AND chat_messages.receiver_id = '.$senderId.' AND chat_messages.sender_id = users.id) as unreadcount'))
              
                ->join('users', function ($join) use ($senderId) {
                    $join->on('chat_messages.receiver_id', '=', 'users.id')
                        ->where('chat_messages.sender_id', $senderId);
                })
                ->groupBy('chat_messages.booking_id', 'users.name', 'users.pic','users.name', 'users.pic')
                ->orderBy('chat_messages.id', 'desc')
                ->get();

            $userChatMessages = Chat_message::select('message', 'chat_messages.booking_id', 'sender.name AS sender_name', 'receiver.name AS receiver_name', 'sender.pic AS sender_pic', 'receiver.pic AS receiver_pic', 'sender.role AS sender_role', 'receiver.role AS receiver_role', 'sender.id AS sender_id', 'receiver.id AS receiver_id')
                ->join('users AS sender', 'chat_messages.sender_id', '=', 'sender.id')
                ->join('users AS receiver', 'chat_messages.receiver_id', '=', 'receiver.id')
                ->where(function ($query) use ($senderId, $receiverId) {
                    $query->where('chat_messages.sender_id', $senderId)
                        ->where('chat_messages.receiver_id', $receiverId);
                })
                ->orWhere(function ($query) use ($senderId, $receiverId) {
                    $query->where('chat_messages.sender_id', $receiverId)
                        ->where('chat_messages.receiver_id', $senderId);
                })
                ->where('chat_messages.booking_id', $bookingId);
                


            $userCountChatMessages = $userChatMessages->count();

            if($userCountChatMessages > 0){

                 return response()->json(['status' => true, 'message' => 'Mesage has been updated successfully','userchatdata' => $userChatMessages->get(),'userchatsider' => $userSideMessages]);
             }else {

                 return response()->json(['status' => false, 'message' => 'There has been for sending the mesage']);
             }
             try{

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch user messages',
            ], 500);
        }
    }
}
