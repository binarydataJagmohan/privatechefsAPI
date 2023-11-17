<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Spatie\Newsletter\Facades\Newsletter;

class SettingController extends Controller
{
    public function update_setting(Request $request)
    {
        try {

            $user = User::find($request->id);
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->view_password = $request->password;
            $user->phone = $request->phone;
            $user->save();
            if ($user) {
                return response()->json(['status' => true, 'message' => "Setting has been updated succesfully",'data' => $user], 200);
            } else {
                return response()->json(['status' => false, 'message' => "There has been error for updating the setting"], 400);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function UpdateNewSetting(Request $request)
    {
        try {

            $user = User::find($request->userid);
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->view_password = $request->password;
            $user->save();
            if ($user->save()) {

                $data = [
                    'name'   => $request->name,
                    'password' => $request->password,
                    'email'   => $request->email,
                ];


                Mail::send('emails.loginDetails', ["data" => $data], function ($message) use ($data) {
                    $message->from(config('mail.from.address'), "Private Chefs");
                    $message->subject(' Your Account Password for Private Chefs');
                    $message->to($data['email']);
                });

                return response()->json(['status' => true, 'message' => "Setting has been updated succesfully"], 200);
            } else {
                return response()->json(['status' => false, 'message' => "There has been error for updating the setting"], 400);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

 
    public function Subscribe(Request $request,$email)
    {
       
        if (Newsletter::isSubscribed($email)) {
            return response()->json(['status' => false, 'message' => 'Email is already subscribed please choose different email.'], 200);
        } else {
            Newsletter::subscribe($email);
            // Mail::to($email)->send(new SubscriptionConfirmation($email));

            return response()->json(['status' => true, 'message' => 'Thanks for subscribing! 🎉 Stay tuned for the latest updates and exclusive content!'], 200);
        }
    }

}
