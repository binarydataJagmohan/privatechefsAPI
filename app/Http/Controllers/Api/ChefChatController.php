<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Chat_group;
use App\Models\Chat_group_member;
use App\Models\Chat_message;

class ChefChatController extends Controller
{
    public function get_chef_message_data(Request $request)
    {
        
            $userId = $request->id;

            $userSideMessages = Chat_message::select('message', 'chat_messages.booking_id', 'sender.name AS sender_name', 'sender.pic AS sender_pic', 'sender.role AS sender_role', 'sender.id AS sender_id')
                ->join('users AS sender', 'chat_messages.sender_id', '=', 'sender.id')
                ->where(function ($query) use ($userId) {
                    $query->where('chat_messages.sender_id', $userId)
                          ->orWhere('chat_messages.receiver_id', $userId);
                })
               ->groupBy('chat_messages.booking_id')
                ->orderBy('chat_messages.id', 'desc')
                ->get();

            $senderId = $userSideMessages[0]->sender_id;
            $receiverId = $request->id;
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
                'booking_id' => $bookingId,
                'userchatdata' => $userChatMessages
            ]);
            try {
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch user messages',
            ], 500);
        }
    }

    public function contact_user_by_chef(Request $request)
    {
        try {
            
            $messgae = new Chat_message();
            $messgae->sender_id =  $request->sender_id;
            $messgae->booking_id =  $request->booking_id;
            $messgae->receiver_id =  $request->receiver_id;
            if($request->message){
                $messgae->message =  $request->message;
            }else {
                $messgae->message =  "hi chef";
            }
            

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

    public function get_click_chef_user_chat_data(Request $request)
    {
       

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
                ->where('chat_messages.booking_id', $bookingId);
                


            $userCountChatMessages = $userChatMessages->count();

            if($userCountChatMessages > 0){

                 return response()->json(['status' => true, 'message' => 'Mesage has been updated successfully','userchatdata' => $userChatMessages->get()]);
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
