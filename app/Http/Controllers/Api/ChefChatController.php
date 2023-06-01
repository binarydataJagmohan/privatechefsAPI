<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Chat_group;
use App\Models\Chat_group_member;
use App\Models\Chat_message;
use DB;
class ChefChatController extends Controller
{
    public function get_chef_message_data(Request $request)
    {
        
            $userId = $request->id;


            $userSideMessages = Chat_message::select(
                'chat_messages.booking_id',
                'sender.name AS sender_name',
                'sender.pic AS sender_pic',
                'sender.role AS sender_role',
                'sender.id AS sender_id',
                DB::raw('(SELECT COUNT(*) FROM chat_messages WHERE chat_messages.message_status = "unread" AND chat_messages.receiver_id = '.$userId.' AND chat_messages.sender_id = sender.id) as unreadcount'),
                DB::raw('(SELECT message FROM chat_messages WHERE (chat_messages.sender_id = '.$userId.' AND chat_messages.receiver_id = sender.id) OR (chat_messages.sender_id = sender.id AND chat_messages.receiver_id = '.$userId.') ORDER BY created_at DESC LIMIT 1) AS latest_message'),
                DB::raw('(SELECT created_at FROM chat_messages WHERE (chat_messages.sender_id = '.$userId.' AND chat_messages.receiver_id = sender.id) OR (chat_messages.sender_id = sender.id AND chat_messages.receiver_id = '.$userId.') ORDER BY created_at DESC LIMIT 1) AS latest_created_at'),
                DB::raw('(SELECT type FROM chat_messages WHERE (chat_messages.sender_id = '.$userId.' AND chat_messages.receiver_id = sender.id) OR (chat_messages.sender_id = sender.id AND chat_messages.receiver_id = '.$userId.') ORDER BY created_at DESC LIMIT 1) AS latest_type'),
                'sender.is_online'
            )
            ->join('users AS sender', function ($join) {
                $join->on('chat_messages.sender_id', '=', 'sender.id')
                    ->orWhere('chat_messages.receiver_id', '=', 'sender.id');
            })
            ->where(function ($query) use ($userId) {
                $query->where('chat_messages.sender_id', $userId)
                    ->orWhere('chat_messages.receiver_id', $userId);
            })
            ->groupBy('chat_messages.booking_id')
            ->orderBy('latest_created_at', 'desc')
            ->get();




            $senderId = $userSideMessages[0]->sender_id;
            $receiverId = $request->id;
            $bookingId = $userSideMessages[0]->booking_id; 

            $userChatMessages = Chat_message::select('message', 'chat_messages.booking_id', 'sender.name AS sender_name', 'receiver.name AS receiver_name', 'sender.pic AS sender_pic', 'receiver.pic AS receiver_pic', 'sender.role AS sender_role', 'receiver.role AS receiver_role', 'sender.id AS sender_id', 'receiver.id AS receiver_id','chat_messages.created_at as chatdate','type')
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
           
            $messgae->message =  $request->message;

            $messgae->save();

            $senderId = $request->sender_id;
            $receiverId = $request->receiver_id;
            $bookingId = $request->booking_id;

            $userChatMessages = Chat_message::select('message', 'chat_messages.booking_id', 'sender.name AS sender_name', 'receiver.name AS receiver_name', 'sender.pic AS sender_pic', 'receiver.pic AS receiver_pic', 'sender.role AS sender_role', 'receiver.role AS receiver_role', 'sender.id AS sender_id', 'receiver.id AS receiver_id','chat_messages.created_at as chatdate','type')
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
            $index = $request->index;
            $sort = $request->sort;


            if(isset($index)){
                $Chat_message = Chat_message::where('receiver_id', $senderId)->where('booking_id', $bookingId)->update(['message_status' => 'read']);

            }

            if($sort == 'asc' || $sort == 'desc'){

             $userSideMessages = Chat_message::select(
                'chat_messages.booking_id',
                'sender.name AS sender_name',
                'sender.pic AS sender_pic',
                'sender.role AS sender_role',
                'sender.id AS sender_id',
                DB::raw('(SELECT COUNT(*) FROM chat_messages WHERE chat_messages.message_status = "unread" AND chat_messages.receiver_id = '.$senderId.' AND chat_messages.sender_id = sender.id) as unreadcount'),
                DB::raw('(SELECT message FROM chat_messages WHERE (chat_messages.sender_id = '.$senderId.' AND chat_messages.receiver_id = sender.id) OR (chat_messages.sender_id = sender.id AND chat_messages.receiver_id = '.$senderId.') ORDER BY created_at DESC LIMIT 1) AS latest_message'),
                DB::raw('(SELECT created_at FROM chat_messages WHERE (chat_messages.sender_id = '.$senderId.' AND chat_messages.receiver_id = sender.id) OR (chat_messages.sender_id = sender.id AND chat_messages.receiver_id = '.$senderId.') ORDER BY created_at DESC LIMIT 1) AS latest_created_at'),
                DB::raw('(SELECT type FROM chat_messages WHERE (chat_messages.sender_id = '.$senderId.' AND chat_messages.receiver_id = sender.id) OR (chat_messages.sender_id = sender.id AND chat_messages.receiver_id = '.$senderId.') ORDER BY created_at DESC LIMIT 1) AS latest_type'),
                 'sender.is_online'
            )
                ->join('users AS sender', function($join) {
                    $join->on('chat_messages.sender_id', '=', 'sender.id')
                         ->orWhere('chat_messages.receiver_id', '=', 'sender.id');
                })
                ->where(function ($query) use ($senderId) {
                    $query->where('chat_messages.sender_id', $senderId)
                          ->orWhere('chat_messages.receiver_id', $senderId);
                })
                ->groupBy('chat_messages.booking_id')
                ->orderBy('latest_created_at', $sort)
                ->get();

            }else {

                $userSideMessages = Chat_message::select(
                'chat_messages.booking_id',
                'sender.name AS sender_name',
                'sender.pic AS sender_pic',
                'sender.role AS sender_role',
                'sender.id AS sender_id',
                DB::raw('(SELECT COUNT(*) FROM chat_messages WHERE chat_messages.message_status = "unread" AND chat_messages.receiver_id = '.$senderId.' AND chat_messages.sender_id = sender.id) as unreadcount'),
                DB::raw('(SELECT message FROM chat_messages WHERE (chat_messages.sender_id = '.$senderId.' AND chat_messages.receiver_id = sender.id) OR (chat_messages.sender_id = sender.id AND chat_messages.receiver_id = '.$senderId.') ORDER BY created_at DESC LIMIT 1) AS latest_message'),
                DB::raw('(SELECT created_at FROM chat_messages WHERE (chat_messages.sender_id = '.$senderId.' AND chat_messages.receiver_id = sender.id) OR (chat_messages.sender_id = sender.id AND chat_messages.receiver_id = '.$senderId.') ORDER BY created_at DESC LIMIT 1) AS latest_created_at'),
                DB::raw('(SELECT type FROM chat_messages WHERE (chat_messages.sender_id = '.$senderId.' AND chat_messages.receiver_id = sender.id) OR (chat_messages.sender_id = sender.id AND chat_messages.receiver_id = '.$senderId.') ORDER BY created_at DESC LIMIT 1) AS latest_type'),
                 'sender.is_online'
            )
                ->join('users AS sender', function($join) {
                    $join->on('chat_messages.sender_id', '=', 'sender.id')
                         ->orWhere('chat_messages.receiver_id', '=', 'sender.id');
                })
                ->where(function ($query) use ($senderId) {
                    $query->where('chat_messages.sender_id', $senderId)
                          ->orWhere('chat_messages.receiver_id', $senderId);
                })
                ->groupBy('chat_messages.booking_id')
                ->orderByRaw('unreadcount DESC, latest_created_at DESC')
                ->get();

            }

            $userChatMessages = Chat_message::select('message', 'chat_messages.booking_id', 'sender.name AS sender_name', 'receiver.name AS receiver_name', 'sender.pic AS sender_pic', 'receiver.pic AS receiver_pic', 'sender.role AS sender_role', 'receiver.role AS receiver_role', 'sender.id AS sender_id', 'receiver.id AS receiver_id','chat_messages.created_at as chatdate','type')
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

                 return response()->json(['status' => true, 'message' => 'Mesage has been updated successfully','userchatdata' => $userChatMessages->get(), 'userchatsider' => $userSideMessages,]);
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

    public function contact_user_by_chef_with_share_file(Request $request)
    {
       
        
            $data = $request->all();

            // Access the file and other parameters from the $data array
            $file = $request->file('data');
            $type = $data['type'];
            $senderId = $data['sender_id'];
            $receiverId = $data['receiver_id'];
            $bookingId = $data['booking_id'];

            // Generate a random number for the file name
            $randomNumber = mt_rand(1000000000, 9999999999);

            // Process the file based on its type
            if ($type === 'image') {
                // Handle image file
                $path = $file;
                $name = $randomNumber . $path->getClientOriginalName();
                $path->move('public/images/chat/images', $name);
               
            } elseif ($type === 'pdf') {
                // Handle PDF file
                $path = $file;
                $name = $randomNumber . $path->getClientOriginalName();
                $path->move('public/images/chat/pdf', $name);

               
            } elseif ($type === 'video') {
                // Handle video file
                $path = $file;
                $name = $randomNumber . $path->getClientOriginalName();
                $path->move('public/images/chat/video', $name);
                
            }

            $messgae = new Chat_message();
            $messgae->sender_id =  $senderId;
            $messgae->booking_id =  $bookingId;
            $messgae->receiver_id =  $receiverId;
            $messgae->message =  $name;
            $messgae->type =  $type;

            $messgae->save();


            $userChatMessages = Chat_message::select('message', 'chat_messages.booking_id', 'sender.name AS sender_name', 'receiver.name AS receiver_name', 'sender.pic AS sender_pic', 'receiver.pic AS receiver_pic', 'sender.role AS sender_role', 'receiver.role AS receiver_role', 'sender.id AS sender_id', 'receiver.id AS receiver_id','chat_messages.created_at as chatdate','type')
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

        }
}
