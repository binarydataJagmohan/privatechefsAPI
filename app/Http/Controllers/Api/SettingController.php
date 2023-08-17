<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\Hash;
use Mail;

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
}
