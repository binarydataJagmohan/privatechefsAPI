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
        
            $userId = $request->id;


            $userSideMessages = Chat_message::select(
                'chat_messages.booking_id',
                'chat_messages.unique_booking_id',
                'chat_messages.single_chat_id',
                'chat_messages.receiver_id',
                'sender.name AS sender_name',
                'sender.pic AS sender_pic',
                'sender.role AS sender_role',
                'sender.id AS sender_id',
                'receiver.name AS recevier_name',
                'receiver.pic AS recevier_pic',
                'receiver.role AS recevier_role',
                'receiver.id AS receiver_id',
                'chat_messages.group_id',
                'chat_groups.group_name',
                'chat_groups.image AS group_image',
                'chat_messages.status',
                'chat_messages.message_status',
                'chat_messages.chat_type AS latest_chat_type',
                'sender.is_online',
                    DB::raw('(SELECT COUNT(*) FROM chat_messages WHERE chat_messages.message_status = "unread" AND chat_messages.receiver_id = '.$userId.' AND chat_messages.sender_id = sender.id) as unreadcount'),
                    DB::raw('CASE 
                        WHEN chat_messages.chat_type = "single" THEN (
                            SELECT message 
                            FROM chat_messages 
                            WHERE ((chat_messages.sender_id = '.$userId.' AND chat_messages.receiver_id = sender.id) OR (chat_messages.sender_id = sender.id AND chat_messages.receiver_id = '.$userId.'))
                            ORDER BY created_at DESC 
                            LIMIT 1
                        )

                        WHEN chat_messages.chat_type = "booking" THEN (
                            SELECT message 
                            FROM chat_messages 
                            WHERE ((chat_messages.unique_booking_id = CONCAT('.$userId.', sender.id) OR chat_messages.unique_booking_id = CONCAT('.$userId.', receiver.id)))
                            ORDER BY created_at DESC 
                            LIMIT 1
                        )

                        WHEN chat_messages.chat_type = "group" THEN (
                            SELECT message
                            FROM chat_messages 
                            WHERE chat_messages.group_id IN (
                                SELECT group_id
                                FROM chat_group_members
                                WHERE member_id = '.$userId.'
                            )
                            AND chat_messages.chat_type = "group" 
                            ORDER BY created_at DESC 
                            LIMIT 1
                        )
                        ELSE NULL
                    END AS latest_message'),

                DB::raw('CASE 
                    WHEN chat_messages.chat_type = "single" THEN (
                        SELECT created_at
                        FROM chat_messages 
                        WHERE ((chat_messages.sender_id = '.$userId.' AND chat_messages.receiver_id = sender.id) OR (chat_messages.sender_id = sender.id AND chat_messages.receiver_id = '.$userId.'))
                        ORDER BY created_at DESC 
                        LIMIT 1
                    )

                     WHEN chat_messages.chat_type = "booking" THEN (
                        SELECT created_at 
                        FROM chat_messages 
                        WHERE ((chat_messages.unique_booking_id = CONCAT('.$userId.', sender.id) OR chat_messages.unique_booking_id = CONCAT('.$userId.', receiver.id)))
                        ORDER BY created_at DESC 
                        LIMIT 1
                    )

                    WHEN chat_messages.chat_type = "group" THEN (
                        SELECT created_at
                        FROM chat_messages 
                        WHERE chat_messages.group_id IN (
                            SELECT group_id
                            FROM chat_group_members
                            WHERE member_id = '.$userId.'
                        )
                        AND chat_messages.chat_type = "group" 
                        ORDER BY created_at DESC 
                        LIMIT 1
                    )
                    ELSE NULL
                 END AS latest_created_at'),

                DB::raw('CASE 
                    WHEN chat_messages.chat_type = "single" THEN (
                        SELECT type
                        FROM chat_messages 
                        WHERE ((chat_messages.sender_id = '.$userId.' AND chat_messages.receiver_id = sender.id) OR (chat_messages.sender_id = sender.id AND chat_messages.receiver_id = '.$userId.'))
                        ORDER BY created_at DESC 
                        LIMIT 1
                    )

                    WHEN chat_messages.chat_type = "booking" THEN (
                        SELECT type 
                        FROM chat_messages 
                        WHERE ((chat_messages.unique_booking_id = CONCAT('.$userId.', sender.id) OR chat_messages.unique_booking_id = CONCAT('.$userId.', receiver.id)))
                        ORDER BY created_at DESC 
                        LIMIT 1
                    )

                    WHEN chat_messages.chat_type = "group" THEN (
                        SELECT type
                        FROM chat_messages 
                        WHERE chat_messages.group_id IN (
                            SELECT group_id
                            FROM chat_group_members
                            WHERE member_id = '.$userId.'
                        )
                        AND chat_messages.chat_type = "group" 
                        ORDER BY created_at DESC 
                        LIMIT 1
                    )
                    ELSE NULL
                 END AS latest_type'),
                
            )
            ->join('users AS sender', function ($join) {
                $join->on('chat_messages.sender_id', '=', 'sender.id')
                    ->orWhere('chat_messages.receiver_id', '=', 'sender.id');
            })
            ->leftJoin('users AS receiver', function ($join) {
                 $join->on('chat_messages.receiver_id', '=', 'receiver.id')
                    ->orWhereNull('chat_messages.receiver_id');
            })

            ->leftJoin('chat_groups', 'chat_messages.group_id', '=', 'chat_groups.id')
            ->where(function ($query) use ($userId) {
                $query->where('chat_messages.unique_booking_id', '=', DB::raw("CONCAT(" . $userId . ", sender.id)"))
                    ->orWhere('chat_messages.unique_booking_id', '=', DB::raw("CONCAT(" . $userId . ", receiver.id)"))
                    ->orWhere('chat_messages.single_chat_id', '=', DB::raw('CONCAT(sender.id, '.$userId.')'))
                    ->orWhereIn('chat_messages.group_id', function ($subquery) use ($userId) {
                        $subquery->select('group_id')
                            ->from('chat_group_members')
                            ->where('member_id', $userId);
                           
                    });
            })
            ->groupBy(
                'chat_messages.unique_booking_id',
                'chat_messages.single_chat_id',
                'chat_messages.group_id'
            )
            ->orderBy('latest_created_at', 'desc')
            ->get();


            $chat_type = $userSideMessages[0]->latest_chat_type;


            if($chat_type == 'single'){

                $single_chat_id = '1'.$userId;

                $userChatMessages = Chat_message::select('message', 'chat_messages.booking_id', 'sender.name AS sender_name', 'receiver.name AS receiver_name', 'sender.pic AS sender_pic', 'receiver.pic AS receiver_pic', 'sender.role AS sender_role', 'receiver.role AS receiver_role', 'sender.id AS sender_id', 'receiver.id AS receiver_id', 'chat_messages.created_at as chatdate', 'type','bookig_send_by')
                ->join('users AS sender', 'chat_messages.sender_id', '=', 'sender.id')
                ->join('users AS receiver', 'chat_messages.receiver_id', '=', 'receiver.id')
                
                ->where('chat_messages.single_chat_id', $single_chat_id)
                ->get();

                $chat_member = 0;
            }

            
            if($chat_type == 'booking'){

                $first = $userSideMessages[0]->receiver_id;
                $second = $userSideMessages[0]->sender_id;

                $unquie = $first.$second;
                $unquie_two = $second.$first;

                $userChatMessages = Chat_message::select('message', 'chat_messages.booking_id', 'sender.name AS sender_name', 'receiver.name AS receiver_name', 'sender.pic AS sender_pic', 'receiver.pic AS receiver_pic', 'sender.role AS sender_role', 'receiver.role AS receiver_role', 'sender.id AS sender_id', 'receiver.id AS receiver_id', 'chat_messages.created_at as chatdate', 'type','bookig_send_by')
                ->join('users AS sender', 'chat_messages.sender_id', '=', 'sender.id')
                ->join('users AS receiver', 'chat_messages.receiver_id', '=', 'receiver.id')
                
                ->where('chat_messages.unique_booking_id', $unquie)
                ->orWhere('chat_messages.unique_booking_id', $unquie_two)
                ->get();

                $chat_member = 0;

            }

            if($chat_type == 'group'){

                $group_id = $userSideMessages[0]->group_id;

                $userChatMessages = Chat_message::select('message', 'chat_messages.booking_id', 'sender.name AS sender_name','sender.pic AS sender_pic', 'sender.role AS sender_role', 'sender.id AS sender_id', 'chat_messages.created_at as chatdate', 'type','bookig_send_by')
                ->join('users AS sender', 'chat_messages.sender_id', '=', 'sender.id')
                ->Where('chat_messages.group_id', $group_id)
                ->get();

                $chat_member = Chat_group_member::select(
                        'name',
                        'role',
                        'users.id as user_id',
                        'role',
                        'pic',
                        'group_admin_id'
              
                    )
                ->leftJoin('users', 'chat_group_members.member_id', '=', 'users.id')
                ->join('chat_groups', 'chat_group_members.group_id', '=', 'chat_groups.id')
                ->where('chat_group_members.group_id', $group_id)
                ->orderBy('chat_group_members.id', 'desc')
                ->get();

            }


           return response()->json([
                    'status' => true,
                    'userchatsider' => $userSideMessages,
                    'userchatdata' => $userChatMessages,
                    'chat_member' => $chat_member
                    // 'data'=> $single_chat_id
                ]);


            try {
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

            if($request->chat_type == 'single'){

                if($request->user_id != $request->receiver_id){
                   $receiver_id = $request->receiver_id;
                }
                 if($request->user_id != $request->sender_id){
                    $receiver_id = $request->sender_id;
                }
                $messgae = new Chat_message();
                $messgae->sender_id =  $request->user_id;
                $messgae->receiver_id =  $receiver_id;
                $messgae->single_chat_id =  $request->single_chat_id;
                $messgae->message =  $request->message;
                $messgae->chat_type =  $request->chat_type;
                $messgae->save();

                return response()->json([
                    'status' => true,
                    'message' => 'messgae has been sent successfully'
                ]);
            }  

            if($request->chat_type == 'booking'){

                if($request->user_id != $request->receiver_id){
                   $receiver_id = $request->receiver_id;
                }
                 if($request->user_id != $request->sender_id){
                    $receiver_id = $request->sender_id;
                }
                
                $messgae = new Chat_message();
                $messgae->sender_id =  $request->user_id;
                $messgae->receiver_id =  $receiver_id;
                $messgae->unique_booking_id =  $request->unique_booking_id;
                $messgae->booking_id =  $request->booking_id;
                $messgae->message =  $request->message;
                $messgae->chat_type =  $request->chat_type;
                $messgae->save();
            
               return response()->json([
                    'status' => true,
                    'message' => 'messgae has been sent successfully'
                ]);
            }  

            if($request->chat_type == 'group'){
                
                $messgae = new Chat_message();
                $messgae->sender_id =  $request->user_id;
                $messgae->group_id =  $request->group_id;
                $messgae->message =  $request->message;
                $messgae->chat_type =  $request->chat_type;
                $messgae->save();
            
               return response()->json([
                    'status' => true,
                    'message' => 'messgae has been sent successfully'
                ]);

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
        try {

        $userId = $request->id;
        $index = $request->index;
        $sort = $request->sort;
        $sorttype = $request->chat_sort_type;

         if(isset($index) && $request->latest_chat_type == 'single'){

            $Chat_message = Chat_message::where('sender_id',$request->sender_id)->where('single_chat_id', $request->single_chat_id)->update(['message_status' => 'read']);
        }

        if (($sort == 'asc' || $sort == 'desc') && $sorttype == 'second') {

             $userSideMessages = Chat_message::select(
                'chat_messages.booking_id',
                'chat_messages.unique_booking_id',
                'chat_messages.single_chat_id',
                'chat_messages.receiver_id',
                'sender.name AS sender_name',
                'sender.pic AS sender_pic',
                'sender.role AS sender_role',
                'sender.id AS sender_id',
                'receiver.name AS recevier_name',
                'receiver.pic AS recevier_pic',
                'receiver.role AS recevier_role',
                'receiver.id AS receiver_id',
                'chat_messages.group_id',
                'chat_groups.group_name',
                'chat_groups.image AS group_image',
                'chat_messages.status',
                'chat_messages.message_status',
                'chat_messages.chat_type AS latest_chat_type',
                'sender.is_online',
                    DB::raw('(SELECT COUNT(*) FROM chat_messages WHERE chat_messages.message_status = "unread" AND chat_messages.receiver_id = '.$userId.' AND chat_messages.sender_id = sender.id) as unreadcount'),
                    DB::raw('CASE 
                        WHEN chat_messages.chat_type = "single" THEN (
                            SELECT message 
                            FROM chat_messages 
                           WHERE ((chat_messages.sender_id = '.$userId.' AND chat_messages.receiver_id = sender.id) OR (chat_messages.sender_id = sender.id AND chat_messages.receiver_id = '.$userId.'))
                            ORDER BY created_at DESC 
                            LIMIT 1
                        )

                        WHEN chat_messages.chat_type = "booking" THEN (
                            SELECT message 
                            FROM chat_messages 
                            WHERE ((chat_messages.unique_booking_id = CONCAT('.$userId.', sender.id) OR chat_messages.unique_booking_id = CONCAT('.$userId.', receiver.id)))
                            ORDER BY created_at DESC 
                            LIMIT 1
                        )

                        WHEN chat_messages.chat_type = "group" THEN (
                            SELECT message
                            FROM chat_messages 
                            WHERE chat_messages.group_id IN (
                                SELECT group_id
                                FROM chat_group_members
                                WHERE member_id = '.$userId.'
                            )
                            AND chat_messages.chat_type = "group" 
                            ORDER BY created_at DESC 
                        LIMIT 1
                    )
                        ELSE NULL
                    END AS latest_message'),


                DB::raw('CASE 
                    WHEN chat_messages.chat_type = "single" THEN (
                        SELECT created_at
                        FROM chat_messages 
                        WHERE ((chat_messages.sender_id = '.$userId.' AND chat_messages.receiver_id = sender.id) OR (chat_messages.sender_id = sender.id AND chat_messages.receiver_id = '.$userId.'))
                        ORDER BY created_at DESC 
                        LIMIT 1
                    )
                     WHEN chat_messages.chat_type = "booking" THEN (
                        SELECT created_at 
                        FROM chat_messages 
                        WHERE ((chat_messages.unique_booking_id = CONCAT('.$userId.', sender.id) OR chat_messages.unique_booking_id = CONCAT('.$userId.', receiver.id)))
                        ORDER BY created_at DESC 
                        LIMIT 1
                    )
                    WHEN chat_messages.chat_type = "group" THEN (
                        SELECT created_at
                        FROM chat_messages 
                        WHERE chat_messages.group_id IN (
                            SELECT group_id
                            FROM chat_group_members
                            WHERE member_id = '.$userId.'
                        )
                        AND chat_messages.chat_type = "group" 
                        ORDER BY created_at DESC 
                        LIMIT 1
                    )
                    ELSE NULL
                 END AS latest_created_at'),

                DB::raw('CASE 
                    WHEN chat_messages.chat_type = "single" THEN (
                        SELECT type
                        FROM chat_messages 
                        WHERE ((chat_messages.sender_id = '.$userId.' AND chat_messages.receiver_id = sender.id) OR (chat_messages.sender_id = sender.id AND chat_messages.receiver_id = '.$userId.'))
                        ORDER BY created_at DESC 
                        LIMIT 1
                    )
                     WHEN chat_messages.chat_type = "booking" THEN (
                        SELECT type 
                        FROM chat_messages 
                        WHERE ((chat_messages.unique_booking_id = CONCAT('.$userId.', sender.id) OR chat_messages.unique_booking_id = CONCAT('.$userId.', receiver.id)))
                        ORDER BY created_at DESC 
                        LIMIT 1
                    )
                    WHEN chat_messages.chat_type = "group" THEN (
                        SELECT type
                        FROM chat_messages 
                        WHERE chat_messages.group_id IN (
                            SELECT group_id
                            FROM chat_group_members
                            WHERE member_id = '.$userId.'
                        )
                        AND chat_messages.chat_type = "group" 
                        ORDER BY created_at DESC 
                        LIMIT 1
                    )
                    ELSE NULL
                 END AS latest_type'),
                
            )
            ->join('users AS sender', function ($join) {
                $join->on('chat_messages.sender_id', '=', 'sender.id')
                    ->orWhere('chat_messages.receiver_id', '=', 'sender.id');
            })
            ->leftJoin('users AS receiver', function ($join) {
                 $join->on('chat_messages.receiver_id', '=', 'receiver.id')
                    ->orWhereNull('chat_messages.receiver_id');
            })

            ->leftJoin('chat_groups', 'chat_messages.group_id', '=', 'chat_groups.id')
            ->where(function ($query) use ($userId) {
                $query->where('chat_messages.unique_booking_id', '=', DB::raw("CONCAT(" . $userId . ", sender.id)"))
                    ->orWhere('chat_messages.unique_booking_id', '=', DB::raw("CONCAT(" . $userId . ", receiver.id)"))
                    ->orWhere('chat_messages.single_chat_id', '=', DB::raw('CONCAT(sender.id, '.$userId.')'))
                    ->orWhereIn('chat_messages.group_id', function ($subquery) use ($userId) {
                        $subquery->select('group_id')
                            ->from('chat_group_members')
                            ->where('member_id', $userId);
                           
                    });
            })
            ->groupBy(
                'chat_messages.unique_booking_id',
                'chat_messages.single_chat_id',
                'chat_messages.group_id'
            )
            ->orderBy('latest_created_at', $sort)
            ->get();



            $chat_type = $request->latest_chat_type;


            if($chat_type == 'single'){

                $userChatMessages = Chat_message::select('message', 'chat_messages.booking_id', 'sender.name AS sender_name', 'receiver.name AS receiver_name', 'sender.pic AS sender_pic', 'receiver.pic AS receiver_pic', 'sender.role AS sender_role', 'receiver.role AS receiver_role', 'sender.id AS sender_id', 'receiver.id AS receiver_id', 'chat_messages.created_at as chatdate', 'type','bookig_send_by')
                ->join('users AS sender', 'chat_messages.sender_id', '=', 'sender.id')
                ->join('users AS receiver', 'chat_messages.receiver_id', '=', 'receiver.id')
                
              
                ->Where('chat_messages.single_chat_id', $request->single_chat_id)
                ->get();

                $chat_member = 0;
            }

            
            if($chat_type == 'booking'){

                $userChatMessages = Chat_message::select('message', 'chat_messages.booking_id','chat_messages.bookig_send_by', 'sender.name AS sender_name', 'receiver.name AS receiver_name', 'sender.pic AS sender_pic', 'receiver.pic AS receiver_pic', 'sender.role AS sender_role', 'receiver.role AS receiver_role', 'sender.id AS sender_id', 'receiver.id AS receiver_id', 'chat_messages.created_at as chatdate', 'type')
                ->join('users AS sender', 'chat_messages.sender_id', '=', 'sender.id')
                ->join('users AS receiver', 'chat_messages.receiver_id', '=', 'receiver.id')
                
                ->where('chat_messages.unique_booking_id', $request->unique_booking_id)
                
                ->get();

                 $chat_member = 0 ;

            }

            if($chat_type == 'group'){

                $group_id = $request->group_id;

                $userChatMessages = Chat_message::select('message', 'chat_messages.booking_id', 'sender.name AS sender_name','sender.pic AS sender_pic', 'sender.role AS sender_role', 'sender.id AS sender_id', 'chat_messages.created_at as chatdate', 'type','bookig_send_by')
                ->join('users AS sender', 'chat_messages.sender_id', '=', 'sender.id')
                ->Where('chat_messages.group_id', $group_id)
                ->get();

                $chat_member = Chat_group_member::select(
                        'name',
                        'role',
                        'users.id as user_id',
                        'role',
                        'pic',
                        'group_admin_id'
              
                    )
                ->leftJoin('users', 'chat_group_members.member_id', '=', 'users.id')
                ->join('chat_groups', 'chat_group_members.group_id', '=', 'chat_groups.id')
                ->where('chat_group_members.group_id', $group_id)
                ->orderBy('chat_group_members.id', 'desc')
                ->get();
            }   


           return response()->json([
                    'status' => true,
                    'userchatsider' => $userSideMessages,
                    'userchatdata' => $userChatMessages,
                    'chat_member' => $chat_member
                    // 'data'=> $single_chat_id
                ]);

        }


        if ( $sort == 'unread' && $sorttype == 'second') {


             $userSideMessages = Chat_message::select(
                'chat_messages.booking_id',
                'chat_messages.unique_booking_id',
                'chat_messages.single_chat_id',
                'chat_messages.receiver_id',
                'sender.name AS sender_name',
                'sender.pic AS sender_pic',
                'sender.role AS sender_role',
                'sender.id AS sender_id',
                'receiver.name AS recevier_name',
                'receiver.pic AS recevier_pic',
                'receiver.role AS recevier_role',
                'receiver.id AS receiver_id',
                'chat_messages.group_id',
                'chat_groups.group_name',
                'chat_groups.image AS group_image',
                'chat_messages.status',
                'chat_messages.message_status',
                'chat_messages.chat_type AS latest_chat_type',
                'sender.is_online',
                    DB::raw('(SELECT COUNT(*) FROM chat_messages WHERE chat_messages.message_status = "unread" AND chat_messages.receiver_id = '.$userId.' AND chat_messages.sender_id = sender.id) as unreadcount'),
                    DB::raw('CASE 
                        WHEN chat_messages.chat_type = "single" THEN (
                            SELECT message 
                            FROM chat_messages 
                           WHERE ((chat_messages.sender_id = '.$userId.' AND chat_messages.receiver_id = sender.id) OR (chat_messages.sender_id = sender.id AND chat_messages.receiver_id = '.$userId.'))
                            ORDER BY created_at DESC 
                            LIMIT 1
                        )
                        WHEN chat_messages.chat_type = "booking" THEN (
                            SELECT message 
                            FROM chat_messages 
                            WHERE ((chat_messages.unique_booking_id = CONCAT('.$userId.', sender.id) OR chat_messages.unique_booking_id = CONCAT('.$userId.', receiver.id)))
                            ORDER BY created_at DESC 
                            LIMIT 1
                        )
                        WHEN chat_messages.chat_type = "group" THEN (
                            SELECT message
                            FROM chat_messages 
                            WHERE chat_messages.group_id IN (
                                SELECT group_id
                                FROM chat_group_members
                                WHERE member_id = '.$userId.'
                            )
                            AND chat_messages.chat_type = "group" 
                            ORDER BY created_at DESC 
                            LIMIT 1
                    )
                        ELSE NULL
                    END AS latest_message'),


                DB::raw('CASE 
                    WHEN chat_messages.chat_type = "single" THEN (
                        SELECT created_at
                        FROM chat_messages 
                        WHERE ((chat_messages.sender_id = '.$userId.' AND chat_messages.receiver_id = sender.id) OR (chat_messages.sender_id = sender.id AND chat_messages.receiver_id = '.$userId.'))
                        ORDER BY created_at DESC 
                        LIMIT 1
                    )
                    WHEN chat_messages.chat_type = "booking" THEN (
                            SELECT created_at 
                            FROM chat_messages 
                            WHERE ((chat_messages.unique_booking_id = CONCAT('.$userId.', sender.id) OR chat_messages.unique_booking_id = CONCAT('.$userId.', receiver.id)))
                            ORDER BY created_at DESC 
                            LIMIT 1
                        )
                    WHEN chat_messages.chat_type = "group" THEN (
                        SELECT created_at
                        FROM chat_messages 
                        WHERE chat_messages.group_id IN (
                            SELECT group_id
                            FROM chat_group_members
                            WHERE member_id = '.$userId.'
                        )
                        AND chat_messages.chat_type = "group" 
                        ORDER BY created_at DESC 
                        LIMIT 1
                    )
                    ELSE NULL
                 END AS latest_created_at'),

                DB::raw('CASE 
                    WHEN chat_messages.chat_type = "single" THEN (
                        SELECT type
                        FROM chat_messages 
                        WHERE ((chat_messages.sender_id = '.$userId.' AND chat_messages.receiver_id = sender.id) OR (chat_messages.sender_id = sender.id AND chat_messages.receiver_id = '.$userId.'))
                        ORDER BY created_at DESC 
                        LIMIT 1
                    )
                    WHEN chat_messages.chat_type = "booking" THEN (
                            SELECT type 
                            FROM chat_messages 
                            WHERE ((chat_messages.unique_booking_id = CONCAT('.$userId.', sender.id) OR chat_messages.unique_booking_id = CONCAT('.$userId.', receiver.id)))
                            ORDER BY created_at DESC 
                            LIMIT 1
                        )
                    WHEN chat_messages.chat_type = "group" THEN (
                        SELECT type
                        FROM chat_messages 
                        WHERE chat_messages.group_id IN (
                            SELECT group_id
                            FROM chat_group_members
                            WHERE member_id = '.$userId.'
                        )
                        AND chat_messages.chat_type = "group" 
                        ORDER BY created_at DESC 
                        LIMIT 1
                    )
                    ELSE NULL
                 END AS latest_type'),
                
            )
            ->join('users AS sender', function ($join) {
                $join->on('chat_messages.sender_id', '=', 'sender.id')
                    ->orWhere('chat_messages.receiver_id', '=', 'sender.id');
            })
            ->leftJoin('users AS receiver', function ($join) {
                 $join->on('chat_messages.receiver_id', '=', 'receiver.id')
                    ->orWhereNull('chat_messages.receiver_id');
            })

            ->leftJoin('chat_groups', 'chat_messages.group_id', '=', 'chat_groups.id')
            ->where(function ($query) use ($userId) {
                $query->where('chat_messages.unique_booking_id', '=', DB::raw("CONCAT(" . $userId . ", sender.id)"))
                    ->orWhere('chat_messages.unique_booking_id', '=', DB::raw("CONCAT(" . $userId . ", receiver.id)"))
                    ->orWhere('chat_messages.single_chat_id', '=', DB::raw('CONCAT(sender.id, '.$userId.')'))
                    ->orWhereIn('chat_messages.group_id', function ($subquery) use ($userId) {
                        $subquery->select('group_id')
                            ->from('chat_group_members')
                            ->where('member_id', $userId);
                           
                    });
            })
            ->groupBy(
                'chat_messages.unique_booking_id',
                'chat_messages.single_chat_id',
                'chat_messages.group_id'
            )
            ->orderByRaw('unreadcount DESC, latest_created_at DESC')
            ->get();

            $chat_type = $request->latest_chat_type;


            if($chat_type == 'single'){

                $userChatMessages = Chat_message::select('message', 'chat_messages.booking_id', 'sender.name AS sender_name', 'receiver.name AS receiver_name', 'sender.pic AS sender_pic', 'receiver.pic AS receiver_pic', 'sender.role AS sender_role', 'receiver.role AS receiver_role', 'sender.id AS sender_id', 'receiver.id AS receiver_id', 'chat_messages.created_at as chatdate', 'type','bookig_send_by')
                ->join('users AS sender', 'chat_messages.sender_id', '=', 'sender.id')
                ->join('users AS receiver', 'chat_messages.receiver_id', '=', 'receiver.id')
                
              
                ->Where('chat_messages.single_chat_id', $request->single_chat_id)
                ->get();

                $chat_member = 0;
            }

            
            if($chat_type == 'booking'){

                $userChatMessages = Chat_message::select('message', 'chat_messages.booking_id','chat_messages.bookig_send_by', 'sender.name AS sender_name', 'receiver.name AS receiver_name', 'sender.pic AS sender_pic', 'receiver.pic AS receiver_pic', 'sender.role AS sender_role', 'receiver.role AS receiver_role', 'sender.id AS sender_id', 'receiver.id AS receiver_id', 'chat_messages.created_at as chatdate', 'type')
                ->join('users AS sender', 'chat_messages.sender_id', '=', 'sender.id')
                ->join('users AS receiver', 'chat_messages.receiver_id', '=', 'receiver.id')
                
                ->where('chat_messages.unique_booking_id', $request->unique_booking_id)
                
                ->get();

                 $chat_member = 0 ;

            }

            if($chat_type == 'group'){

                $group_id = $request->group_id;

                $userChatMessages = Chat_message::select('message', 'chat_messages.booking_id', 'sender.name AS sender_name','sender.pic AS sender_pic', 'sender.role AS sender_role', 'sender.id AS sender_id', 'chat_messages.created_at as chatdate', 'type','bookig_send_by')
                ->join('users AS sender', 'chat_messages.sender_id', '=', 'sender.id')
                ->Where('chat_messages.group_id', $group_id)
                ->get();

                $chat_member = Chat_group_member::select(
                        'name',
                        'role',
                        'users.id as user_id',
                        'role',
                        'pic',
                        'group_admin_id'
              
                    )
                ->leftJoin('users', 'chat_group_members.member_id', '=', 'users.id')
                ->join('chat_groups', 'chat_group_members.group_id', '=', 'chat_groups.id')
                ->where('chat_group_members.group_id', $group_id)
                ->orderBy('chat_group_members.id', 'desc')
                ->get();
            }   


           return response()->json([
                    'status' => true,
                    'userchatsider' => $userSideMessages,
                    'userchatdata' => $userChatMessages,
                    'chat_member' => $chat_member
                    // 'data'=> $single_chat_id
                ]);

        }

        if (($sort == 'asc' || $sort == 'desc') && $sorttype == 'first') {

            $userSideMessages = Chat_message::select(
                'chat_messages.booking_id',
                'chat_messages.unique_booking_id',
                'chat_messages.single_chat_id',
                'chat_messages.receiver_id',
                'sender.name AS sender_name',
                'sender.pic AS sender_pic',
                'sender.role AS sender_role',
                'sender.id AS sender_id',
                'receiver.name AS recevier_name',
                'receiver.pic AS recevier_pic',
                'receiver.role AS recevier_role',
                'receiver.id AS receiver_id',
                'chat_messages.group_id',
                'chat_groups.group_name',
                'chat_groups.image AS group_image',
                'chat_messages.status',
                'chat_messages.message_status',
                'chat_messages.chat_type AS latest_chat_type',
                'sender.is_online',
                    DB::raw('(SELECT COUNT(*) FROM chat_messages WHERE chat_messages.message_status = "unread" AND chat_messages.receiver_id = '.$userId.' AND chat_messages.sender_id = sender.id) as unreadcount'),
                    DB::raw('CASE 
                        WHEN chat_messages.chat_type = "single" THEN (
                            SELECT message 
                            FROM chat_messages 
                           WHERE ((chat_messages.sender_id = '.$userId.' AND chat_messages.receiver_id = sender.id) OR (chat_messages.sender_id = sender.id AND chat_messages.receiver_id = '.$userId.'))
                            ORDER BY created_at DESC 
                            LIMIT 1
                        )
                        WHEN chat_messages.chat_type = "booking" THEN (
                            SELECT message 
                            FROM chat_messages 
                            WHERE ((chat_messages.unique_booking_id = CONCAT('.$userId.', sender.id) OR chat_messages.unique_booking_id = CONCAT('.$userId.', receiver.id)))
                            ORDER BY created_at DESC 
                            LIMIT 1
                        )
                        WHEN chat_messages.chat_type = "group" THEN (
                            SELECT message
                            FROM chat_messages 
                            WHERE chat_messages.group_id IN (
                                SELECT group_id
                                FROM chat_group_members
                                WHERE member_id = '.$userId.'
                            )
                            AND chat_messages.chat_type = "group" 
                            ORDER BY created_at DESC 
                            LIMIT 1
                    )
                        ELSE NULL
                    END AS latest_message'),


                DB::raw('CASE 
                    WHEN chat_messages.chat_type = "single" THEN (
                        SELECT created_at
                        FROM chat_messages 
                        WHERE ((chat_messages.sender_id = '.$userId.' AND chat_messages.receiver_id = sender.id) OR (chat_messages.sender_id = sender.id AND chat_messages.receiver_id = '.$userId.'))
                        ORDER BY created_at DESC 
                        LIMIT 1
                    )
                    WHEN chat_messages.chat_type = "booking" THEN (
                            SELECT created_at 
                            FROM chat_messages 
                            WHERE ((chat_messages.unique_booking_id = CONCAT('.$userId.', sender.id) OR chat_messages.unique_booking_id = CONCAT('.$userId.', receiver.id)))
                            ORDER BY created_at DESC 
                            LIMIT 1
                        )
                    WHEN chat_messages.chat_type = "group" THEN (
                        SELECT created_at
                        FROM chat_messages 
                        WHERE chat_messages.group_id IN (
                            SELECT group_id
                            FROM chat_group_members
                            WHERE member_id = '.$userId.'
                        )
                        AND chat_messages.chat_type = "group" 
                        ORDER BY created_at DESC 
                        LIMIT 1
                    )
                    ELSE NULL
                 END AS latest_created_at'),

                DB::raw('CASE 
                    WHEN chat_messages.chat_type = "single" OR chat_messages.chat_type = "booking" THEN (
                        SELECT type
                        FROM chat_messages 
                        WHERE ((chat_messages.sender_id = '.$userId.' AND chat_messages.receiver_id = sender.id) OR (chat_messages.sender_id = sender.id AND chat_messages.receiver_id = '.$userId.'))
                        ORDER BY created_at DESC 
                        LIMIT 1
                    )
                     WHEN chat_messages.chat_type = "booking" THEN (
                            SELECT type 
                            FROM chat_messages 
                            WHERE ((chat_messages.unique_booking_id = CONCAT('.$userId.', sender.id) OR chat_messages.unique_booking_id = CONCAT('.$userId.', receiver.id)))
                            ORDER BY created_at DESC 
                            LIMIT 1
                        )
                    WHEN chat_messages.chat_type = "group" THEN (
                        SELECT type
                        FROM chat_messages 
                        WHERE chat_messages.group_id IN (
                            SELECT group_id
                            FROM chat_group_members
                            WHERE member_id = '.$userId.'
                        )
                        AND chat_messages.chat_type = "group" 
                        ORDER BY created_at DESC 
                        LIMIT 1
                    )
                    ELSE NULL
                 END AS latest_type'),
                
            )
            ->join('users AS sender', function ($join) {
                $join->on('chat_messages.sender_id', '=', 'sender.id')
                    ->orWhere('chat_messages.receiver_id', '=', 'sender.id');
            })
            ->leftJoin('users AS receiver', function ($join) {
                 $join->on('chat_messages.receiver_id', '=', 'receiver.id')
                    ->orWhereNull('chat_messages.receiver_id');
            })

            ->leftJoin('chat_groups', 'chat_messages.group_id', '=', 'chat_groups.id')
            ->where(function ($query) use ($userId) {
                $query->where('chat_messages.unique_booking_id', '=', DB::raw("CONCAT(" . $userId . ", sender.id)"))
                    ->orWhere('chat_messages.unique_booking_id', '=', DB::raw("CONCAT(" . $userId . ", receiver.id)"))
                    ->orWhere('chat_messages.single_chat_id', '=', DB::raw('CONCAT(sender.id, '.$userId.')'))
                    ->orWhereIn('chat_messages.group_id', function ($subquery) use ($userId) {
                        $subquery->select('group_id')
                            ->from('chat_group_members')
                            ->where('member_id', $userId);
                           
                    });
            })
            ->groupBy(
                'chat_messages.unique_booking_id',
                'chat_messages.single_chat_id',
                'chat_messages.group_id'
            )
            ->orderBy('latest_created_at', $sort)
            ->get();



            $chat_type = $userSideMessages[0]->latest_chat_type;


            if($chat_type == 'single'){

                $first = $userSideMessages[0]->receiver_id;
                $second = $userSideMessages[0]->sender_id;

                $unquie = $first.$second;
                $unquie_two = $second.$first;

                $userChatMessages = Chat_message::select('message', 'chat_messages.booking_id', 'sender.name AS sender_name', 'receiver.name AS receiver_name', 'sender.pic AS sender_pic', 'receiver.pic AS receiver_pic', 'sender.role AS sender_role', 'receiver.role AS receiver_role', 'sender.id AS sender_id', 'receiver.id AS receiver_id', 'chat_messages.created_at as chatdate', 'type','bookig_send_by')
                ->join('users AS sender', 'chat_messages.sender_id', '=', 'sender.id')
                ->join('users AS receiver', 'chat_messages.receiver_id', '=', 'receiver.id')
                ->Where('chat_messages.single_chat_id', $unquie)
                ->orWhere('chat_messages.single_chat_id', $unquie_two)
                ->get();

                $chat_member = 0;
            }

            
            if($chat_type == 'booking'){

                $first = $userSideMessages[0]->receiver_id;
                $second = $userSideMessages[0]->sender_id;

                $unquie = $first.$second;
                $unquie_two = $second.$first;

                $userChatMessages = Chat_message::select('message', 'chat_messages.booking_id','chat_messages.bookig_send_by', 'sender.name AS sender_name', 'receiver.name AS receiver_name', 'sender.pic AS sender_pic', 'receiver.pic AS receiver_pic', 'sender.role AS sender_role', 'receiver.role AS receiver_role', 'sender.id AS sender_id', 'receiver.id AS receiver_id', 'chat_messages.created_at as chatdate', 'type')
                ->join('users AS sender', 'chat_messages.sender_id', '=', 'sender.id')
                ->join('users AS receiver', 'chat_messages.receiver_id', '=', 'receiver.id')
                ->where('chat_messages.unique_booking_id', $unquie)
                ->orWhere('chat_messages.unique_booking_id', $unquie_two)
                ->get();

                 $chat_member = 0 ;

            }

            if($chat_type == 'group'){

                $group_id = $userSideMessages[0]->group_id;

                $userChatMessages = Chat_message::select('message', 'chat_messages.booking_id', 'sender.name AS sender_name','sender.pic AS sender_pic', 'sender.role AS sender_role', 'sender.id AS sender_id', 'chat_messages.created_at as chatdate', 'type','bookig_send_by')
                ->join('users AS sender', 'chat_messages.sender_id', '=', 'sender.id')
                ->Where('chat_messages.group_id', $group_id)
                ->get();

                $chat_member = Chat_group_member::select(
                        'name',
                        'role',
                        'users.id as user_id',
                        'role',
                        'pic',
                        'group_admin_id'
              
                    )
                ->leftJoin('users', 'chat_group_members.member_id', '=', 'users.id')
                ->join('chat_groups', 'chat_group_members.group_id', '=', 'chat_groups.id')
                ->where('chat_group_members.group_id', $group_id)
                ->orderBy('chat_group_members.id', 'desc')
                ->get();
            }   


           return response()->json([
                    'status' => true,
                    'userchatsider' => $userSideMessages,
                    'userchatdata' => $userChatMessages,
                    'chat_member' => $chat_member
                    // 'data'=> $single_chat_id
                ]);

        }


        if ( $sort == 'unread' && $sorttype == 'first') {

            $userSideMessages = Chat_message::select(
                'chat_messages.booking_id',
                'chat_messages.unique_booking_id',
                'chat_messages.single_chat_id',
                'chat_messages.receiver_id',
                'sender.name AS sender_name',
                'sender.pic AS sender_pic',
                'sender.role AS sender_role',
                'sender.id AS sender_id',
                'receiver.name AS recevier_name',
                'receiver.pic AS recevier_pic',
                'receiver.role AS recevier_role',
                'receiver.id AS receiver_id',
                'chat_messages.group_id',
                'chat_groups.group_name',
                'chat_groups.image AS group_image',
                'chat_messages.status',
                'chat_messages.message_status',
                'chat_messages.chat_type AS latest_chat_type',
                'sender.is_online',
                    DB::raw('(SELECT COUNT(*) FROM chat_messages WHERE chat_messages.message_status = "unread" AND chat_messages.receiver_id = '.$userId.' AND chat_messages.sender_id = sender.id) as unreadcount'),
                    DB::raw('CASE 
                        WHEN chat_messages.chat_type = "single" THEN (
                            SELECT message 
                            FROM chat_messages 
                           WHERE ((chat_messages.sender_id = '.$userId.' AND chat_messages.receiver_id = sender.id) OR (chat_messages.sender_id = sender.id AND chat_messages.receiver_id = '.$userId.'))
                            ORDER BY created_at DESC 
                            LIMIT 1
                        )
                         WHEN chat_messages.chat_type = "booking" THEN (
                            SELECT message 
                            FROM chat_messages 
                            WHERE ((chat_messages.unique_booking_id = CONCAT('.$userId.', sender.id) OR chat_messages.unique_booking_id = CONCAT('.$userId.', receiver.id)))
                            ORDER BY created_at DESC 
                            LIMIT 1
                        )
                        WHEN chat_messages.chat_type = "group" THEN (
                            SELECT message
                            FROM chat_messages 
                            WHERE chat_messages.group_id IN (
                                SELECT group_id
                                FROM chat_group_members
                                WHERE member_id = '.$userId.'
                            )
                            AND chat_messages.chat_type = "group" 
                            ORDER BY created_at DESC 
                            LIMIT 1
                    )
                        ELSE NULL
                    END AS latest_message'),


                DB::raw('CASE 
                    WHEN chat_messages.chat_type = "single" THEN (
                        SELECT created_at
                        FROM chat_messages 
                        WHERE ((chat_messages.sender_id = '.$userId.' AND chat_messages.receiver_id = sender.id) OR (chat_messages.sender_id = sender.id AND chat_messages.receiver_id = '.$userId.'))
                        ORDER BY created_at DESC 
                        LIMIT 1
                    )
                    WHEN chat_messages.chat_type = "booking" THEN (
                            SELECT created_at 
                            FROM chat_messages 
                            WHERE ((chat_messages.unique_booking_id = CONCAT('.$userId.', sender.id) OR chat_messages.unique_booking_id = CONCAT('.$userId.', receiver.id)))
                            ORDER BY created_at DESC 
                            LIMIT 1
                        )
                    WHEN chat_messages.chat_type = "group" THEN (
                        SELECT created_at
                        FROM chat_messages 
                        WHERE chat_messages.group_id IN (
                            SELECT group_id
                            FROM chat_group_members
                            WHERE member_id = '.$userId.'
                        )
                        AND chat_messages.chat_type = "group" 
                        ORDER BY created_at DESC 
                        LIMIT 1
                    )
                    ELSE NULL
                 END AS latest_created_at'),

                DB::raw('CASE 
                    WHEN chat_messages.chat_type = "single" THEN (
                        SELECT type
                        FROM chat_messages 
                        WHERE ((chat_messages.sender_id = '.$userId.' AND chat_messages.receiver_id = sender.id) OR (chat_messages.sender_id = sender.id AND chat_messages.receiver_id = '.$userId.'))
                        ORDER BY created_at DESC 
                        LIMIT 1
                    )
                    WHEN chat_messages.chat_type = "booking" THEN (
                            SELECT type 
                            FROM chat_messages 
                            WHERE ((chat_messages.unique_booking_id = CONCAT('.$userId.', sender.id) OR chat_messages.unique_booking_id = CONCAT('.$userId.', receiver.id)))
                            ORDER BY created_at DESC 
                            LIMIT 1
                        )
                    WHEN chat_messages.chat_type = "group" THEN (
                        SELECT type
                        FROM chat_messages 
                        WHERE chat_messages.group_id IN (
                            SELECT group_id
                            FROM chat_group_members
                            WHERE member_id = '.$userId.'
                        )
                        AND chat_messages.chat_type = "group" 
                        ORDER BY created_at DESC 
                        LIMIT 1
                    )
                    ELSE NULL
                 END AS latest_type'),
                
            )
            ->join('users AS sender', function ($join) {
                $join->on('chat_messages.sender_id', '=', 'sender.id')
                    ->orWhere('chat_messages.receiver_id', '=', 'sender.id');
            })
            ->leftJoin('users AS receiver', function ($join) {
                 $join->on('chat_messages.receiver_id', '=', 'receiver.id')
                    ->orWhereNull('chat_messages.receiver_id');
            })

            ->leftJoin('chat_groups', 'chat_messages.group_id', '=', 'chat_groups.id')
            ->where(function ($query) use ($userId) {
                $query->where('chat_messages.unique_booking_id', '=', DB::raw("CONCAT(" . $userId . ", sender.id)"))
                    ->orWhere('chat_messages.unique_booking_id', '=', DB::raw("CONCAT(" . $userId . ", receiver.id)"))
                    ->orWhere('chat_messages.single_chat_id', '=', DB::raw('CONCAT(sender.id, '.$userId.')'))
                    ->orWhereIn('chat_messages.group_id', function ($subquery) use ($userId) {
                        $subquery->select('group_id')
                            ->from('chat_group_members')
                            ->where('member_id', $userId);
                           
                    });
            })
            ->groupBy(
                'chat_messages.unique_booking_id',
                'chat_messages.single_chat_id',
                'chat_messages.group_id'
            )
            ->orderByRaw('unreadcount DESC, latest_created_at DESC')
            ->get();



            $chat_type = $userSideMessages[0]->latest_chat_type;


            if($chat_type == 'single'){

                $first = $userSideMessages[0]->receiver_id;
                $second = $userSideMessages[0]->sender_id;

                $unquie = $first.$second;
                $unquie_two = $second.$first;


                $userChatMessages = Chat_message::select('message', 'chat_messages.booking_id', 'sender.name AS sender_name', 'receiver.name AS receiver_name', 'sender.pic AS sender_pic', 'receiver.pic AS receiver_pic', 'sender.role AS sender_role', 'receiver.role AS receiver_role', 'sender.id AS sender_id', 'receiver.id AS receiver_id', 'chat_messages.created_at as chatdate', 'type','bookig_send_by')
                ->join('users AS sender', 'chat_messages.sender_id', '=', 'sender.id')
                ->join('users AS receiver', 'chat_messages.receiver_id', '=', 'receiver.id')
                ->Where('chat_messages.single_chat_id', $unquie)
                ->orWhere('chat_messages.single_chat_id', $unquie_two)
                ->get();

                $chat_member = 0;
            }

            
            if($chat_type == 'booking'){

                $first = $userSideMessages[0]->receiver_id;
                $second = $userSideMessages[0]->sender_id;

                $unquie = $first.$second;
                $unquie_two = $second.$first;

                $userChatMessages = Chat_message::select('message', 'chat_messages.booking_id','chat_messages.bookig_send_by', 'sender.name AS sender_name', 'receiver.name AS receiver_name', 'sender.pic AS sender_pic', 'receiver.pic AS receiver_pic', 'sender.role AS sender_role', 'receiver.role AS receiver_role', 'sender.id AS sender_id', 'receiver.id AS receiver_id', 'chat_messages.created_at as chatdate', 'type')
                ->join('users AS sender', 'chat_messages.sender_id', '=', 'sender.id')
                ->join('users AS receiver', 'chat_messages.receiver_id', '=', 'receiver.id')
                ->where('chat_messages.unique_booking_id', $unquie)
                ->orWhere('chat_messages.unique_booking_id', $unquie_two)
                
                ->get();

                 $chat_member = 0 ;

            }

            if($chat_type == 'group'){

                $group_id = $userSideMessages[0]->group_id;

                $userChatMessages = Chat_message::select('message', 'chat_messages.booking_id', 'sender.name AS sender_name','sender.pic AS sender_pic', 'sender.role AS sender_role', 'sender.id AS sender_id', 'chat_messages.created_at as chatdate', 'type','bookig_send_by')
                ->join('users AS sender', 'chat_messages.sender_id', '=', 'sender.id')
                ->Where('chat_messages.group_id', $group_id)
                ->get();

                $chat_member = Chat_group_member::select(
                        'name',
                        'role',
                        'users.id as user_id',
                        'role',
                        'pic',
                        'group_admin_id'
              
                    )
                ->leftJoin('users', 'chat_group_members.member_id', '=', 'users.id')
                ->join('chat_groups', 'chat_group_members.group_id', '=', 'chat_groups.id')
                ->where('chat_group_members.group_id', $group_id)
                ->orderBy('chat_group_members.id', 'desc')
                ->get();
            }   


           return response()->json([
                    'status' => true,
                    'userchatsider' => $userSideMessages,
                    'userchatdata' => $userChatMessages,
                    'chat_member' => $chat_member
                    // 'data'=> $single_chat_id
                ]);

        } 
          
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch user messages',
            ], 500);
        }
    }


    
    public function contact_chef_by_user_with_share_file(Request $request)
    {   
        $randomNumber = mt_rand(1000000000, 9999999999);

        $type = $request->type;

        $file = $request->data;
       
        if($request->chat_type == 'single'){

                if($request->user_id != $request->receiver_id){
                   $receiver_id = $request->receiver_id;
                }
                 if($request->user_id != $request->sender_id){
                    $receiver_id = $request->sender_id;
                }
                $messgae = new Chat_message();
                $messgae->sender_id =  $request->user_id;
                $messgae->receiver_id =  $receiver_id;
                $messgae->single_chat_id =  $request->single_chat_id;
                $messgae->chat_type =  $request->chat_type;

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
                $messgae->type =  $type;
                $messgae->message =  $name;

                $messgae->save();

                return response()->json([
                    'status' => true,
                    'message' => 'messgae has been sent successfully'
                ]);
            }  

            if($request->chat_type == 'booking'){

                if($request->user_id != $request->receiver_id){
                   $receiver_id = $request->receiver_id;
                }
                 if($request->user_id != $request->sender_id){
                    $receiver_id = $request->sender_id;
                }
                
                $messgae = new Chat_message();
                $messgae->sender_id =  $request->user_id;
                $messgae->receiver_id =  $receiver_id;
                $messgae->unique_booking_id =  $request->unique_booking_id;
                $messgae->booking_id =  $request->booking_id;
                $messgae->chat_type =  $request->chat_type;

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
                $messgae->type =  $type;
                $messgae->message =  $name;

                $messgae->save();

                $messgae->save();
            
               return response()->json([
                    'status' => true,
                    'message' => 'messgae has been sent successfully'
                ]);
            }  

            if($request->chat_type == 'group'){
                
                $messgae = new Chat_message();
                $messgae->sender_id =  $request->user_id;
                $messgae->group_id =  $request->group_id;
                $messgae->chat_type =  $request->chat_type;

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
                $messgae->type =  $type;
                $messgae->message =  $name;

                $messgae->save();

                $messgae->save();
            
               return response()->json([
                    'status' => true,
                    'message' => 'messgae has been sent successfully'
                ]);

            }

       
            try {
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch user messages',
            ], 500);
        }

    }

    public function contact_chef_by_user_with_single_booking(Request $request)
    {
        try {
            
            $messgae = new Chat_message();
            $messgae->sender_id =  $request->sender_id;
            $messgae->receiver_id =  $request->receiver_id;
            $messgae->booking_id =  $request->booking_id;
            $messgae->unique_booking_id =  $request->unique_booking_id;
            $messgae->chat_type =  $request->chat_type;
            $messgae->message =  $request->message;

            $messgae->save();

           
            if($messgae->save()){

                 return response()->json(['status' => true, 'message' => 'Mesage has been updated successfully']);
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


}