<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Models\User;
use App\Models\Business;
use App\Models\ChefDetail;
use App\Models\CoFounder;
use App\Models\About;
use App\Models\Contact;
use Mail;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\VerificationCode;
use Carbon\Carbon;
use App\Models\UserVerify;
use Illuminate\Support\Str;
use App\Models\PasswordReset;
use App\Models\ChefDetail;

class UserController extends Controller
{
    public function user_register(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'name' => 'required|max:255',
                'email' => 'required|email|unique:users',
                'password' => 'required|string|min:8|max:16'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 200);
            } else {
                // Store the user in the database
                $user = new User();
                $user->name = $request->name;
                $user->email = $request->email;
                $user->role = $request->role;
                $user->password = Hash::make($request->password);
                $user->view_password = $request->password;
                $data = $user->save();

<<<<<<< HEAD
                 if ($request->role == 'chef') {
=======
                if ($request->role == 'chef') {
>>>>>>> d6a25d4b8a5e075937aad41ebaf17972f9bc43c4
                    $detail = new ChefDetail();
                    $detail->user_id = $user->id;
                    $detail->save();
                }


                $token = JWTAuth::fromUser($user);

                unset($user->password);
                unset($user->view_password);

                $email_token = Str::random(64);

                UserVerify::create([
                    'user_id' => $user->id,
                    'token' => $email_token
                ]);

                Mail::send('emails.emailVerificationEmail', ['token' => $email_token, 'user_id' => $user->id], function ($message) use ($request) {
                    $message->to($request->email);
                    $message->subject('Email Verification Mail');
                });

                return response()->json(['status' => true, 'message' => 'Registration has been done successfully please verfiy your email', 'data' => ['user' => $user, 'token' => $token]], 200);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }



    public function user_login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $email = $request->input('email');
            $password = $request->input('password');

            $user = User::where('email', $email)->first();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email does not exist!',
                ]);
            }

            if (!Hash::check($password, $user->password)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Incorrect password!',
                ]);
            }

            $credentials = $request->only(['email', 'password']);

            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid email or password!',
                ]);
            }

            $user = Auth::user();

            return response()->json([
                'status' => true,
                'message' => 'User logged in successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'authorisation' => [
                    'token' => $token,
                    'type' => 'bearer',
                ]
            ]);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function update_profile(Request $request)
    {
        try {
            $user = User::find($request->id);
            $user->name =  $request->name;
            $user->phone_no = $request->phone_no;
            $user->gender =  $request->gender;
            $user->city = $request->city;
            $user->country = $request->country;
            $user->linkedin_url = $request->linkedin_url;
            if ($request->hasFile('profile_pic')) {
                $randomNumber = mt_rand(1000000000, 9999999999);
                $imagePath = $request->file('profile_pic');
                $imageName = $randomNumber . $imagePath->getClientOriginalName();
                $imagePath->move('images/profile', $imageName);
                $user->profile_pic = $imageName;
            }
            $savedata = $user->save();
            if ($savedata) {
                return response()->json(['status' => true, 'message' => "Profile has been updated succesfully", 'data' => $savedata], 200);
            } else {
                return response()->json(['status' => false, 'message' => "There has been error for updating the profile", 'data' => ""], 200);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function forget_password(Request $request)
    {
        // return $request->all();
        try {
            $user =  User::where('email', $request->email)->first();
            if ($user) {
                $token = Str::random(40);
                $domain = env('NEXT_URL');
                $url = $domain . '/?userid='.$user->id.'&resettoken=' . $token;
                $data['url'] = $url;
                $data['email'] = $request->email;
                $data['title'] = "password reset";
                $data['body'] = "Please click on below link to reset your password";
                Mail::send('emails.forgetpassword', ['data' => $data], function ($message) use ($data) {
                    $message->to($data['email'])->subject($data['title']);
                });
                $datetime = Carbon::now()->format('Y-m-d H:i:s');
                PasswordReset::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'user_id' => $user->id,
                        'token' => $token,
                        'created_at' => $datetime,
                    ]
                );
                return response()->json(['status' => true, 'message' => 'Mail has been sent please check your email!']);
            } else {
                return response()->json(['status' => false, 'message' => 'Mail doesn`t not exist']);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage()]);
        }
    }

    public function check_user_reset_password_verfication(Request $request)
    {
        $resetData = PasswordReset::where('user_id', $request->id)->where('token', $request->token)->count();

        if ($resetData > 0) {
            return response()->json(['status' => true, 'message' => 'Valid password reset id and token']);
        } else {
            return response()->json(['status' => false, 'message' => 'Invalid password reset id and token']);
        }
    }

<<<<<<< HEAD
    public function updated_reset_password(Request $request)
   
     {
=======
    public function reset_password(Request $request)

    {
>>>>>>> d6a25d4b8a5e075937aad41ebaf17972f9bc43c4
        try {
            $request->validate([
                'password' => 'required|string|min:8',
            ]);
            $user = User::find($request->user_id);
            if (!$user) {
                return response()->json(['status' => false, 'msg' => 'User not found'], 404);
            }
            $user->password = Hash::make($request->password);
            $user->view_password = $request->password;
            $user->update();
            PasswordReset::where('user_id', $request->user_id)->delete();
            return response()->json(['status' => true, 'message' => 'Password reset successful'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => true, 'message' => 'Password reset failed'], 500);
        }
    }

    public function check_user_email_verfication(Request $request)
    {
        try {

            $id = $request->id;
            $token = $request->token;

            $check = UserVerify::where('user_id', $id)->where('token', $token)->count();

            if ($check > 0) {

                $user = User::where('id', $id)->update([

                    'email_verified' => 1,
                    'email_verified_at' => Carbon::now(),
                ]);

                UserVerify::where('user_id', $id)->where('token', $token)->delete();

                return response()->json(['message' => "Email verifiy successfully", 'status' => true], 200);
            } else {

                return response()->json(['message' => "Email verfication failed", 'status' => false], 200);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => true, 'msg' => 'Password reset failed'], 500);
        }
    }
}
