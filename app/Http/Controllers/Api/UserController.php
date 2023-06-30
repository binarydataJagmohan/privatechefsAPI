<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Models\User;
use App\Models\Notification;
use App\Models\ChefDetail;
use App\Models\ChefLocation;
use App\Models\CoFounder;
use App\Models\About;
use App\Models\Contact;
use Mail;
use App\Models\VerificationCode;
use Carbon\Carbon;
use App\Models\UserVerify;
use Illuminate\Support\Str;
use App\Models\PasswordReset;
use Helpers;
use Laravel\Socialite\Facades\Socialite;
use Tymon\JWTAuth\Facades\JWTAuth;


class UserController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }
    public function handleGoogleCallback()
    {
        try {

            $user = Socialite::driver('google')->user();

            $finduser = User::where('google_id', $user->id)->first();

            if ($finduser) {

                Auth::login($finduser);

                // return redirect()->intended('dashboard');

            } else {
                $newUser = User::create([
                    'name' => $user->name,
                    'email' => $user->email,
                    'google_id' => $user->id,
                    'password' => encrypt('123456'),
                    'view_password' => 123456,
                ]);

                Auth::login($newUser);

                // return redirect()->intended('dashboard');
            }
        } catch (\Exception $e) {
            dd($e->getMessage());
        }
    }
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


                if ($request->role == 'chef') {
                    $detail = new ChefDetail();
                    $detail->user_id = $user->id;
                    $detail->save();

                    // $data = new ChefLocation();
                    // $data->user_id = $user->id;
                    // $data->save();
                }


                $token = Auth::login($user);

                $auth_user = User::select('name', 'email', 'role', 'id', 'surname', 'pic', 'phone', 'approved_by_admin','created_by')->where('id', $user->id)->first();

                // unset($user->password);
                // unset($user->view_password);

                $email_token = Str::random(64);

                UserVerify::create([
                    'user_id' => $user->id,
                    'token' => $email_token
                ]);

                $admin = User::select('id')->where('role', 'admin')->get();

                $notify_by = $user->id;
                $notify_to =  $admin;
                $description = 'Thank you for registering. We hope you enjoy using our website.';
                $description1 = $user->name . ' registered on our website. Please review their account.';
                $type = 'Register';

                createNotificationForUserAndAdmins($notify_by, $notify_to, $description, $description1, $type);

                // Mail::send('emails.emailVerificationEmail', ['token' => $email_token, 'user_id' => $user->id], function ($message) use ($request) {
                //     $message->to($request->email);
                //     $message->subject('Email Verification Mail');
                // });
                $payload = Auth::getPayload($token);
                $expirationTime = Carbon::createFromTimestamp($payload->get('exp'))->toDateTimeString();

                return response()->json(['status' => true, 'message' => 'Registration has been done successfully please verfiy your email', 'data' => ['user' => $auth_user, 'token' => $token,'expiration' => $expirationTime]], 200);
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

            if (!$token = Auth::attempt($credentials)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid email or password!',
                ]);
            }

            $payload = Auth::getPayload($token);
            $expirationTime = Carbon::createFromTimestamp($payload->get('exp'))->toDateTimeString();

            $user = Auth::user();
            $admin = User::select('id')->where('role', 'admin')->get();

            $online_status_user = User::find($user->id);
            $online_status_user->is_online = 'yes';
            $online_status_user->last_activity = Carbon::now()->format('Y-m-d H:i:s');
            $online_status_user->save();

            $notify_by = $user->id;
            $notify_to = $admin;
            $description = 'Welcome back! You have successfully logged in to your account.';
            $description2 = $user->name . ' has logged in to their account.';
            $type = 'Login';

            createNotificationForUserAndAdmins($notify_by, $notify_to, $description, $description2, $type);


            return response()->json([
                'status' => true,
                'message' => 'User logged in successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'pic' => $user->pic,
                    'surname' => $user->surname,
                    'phone' => $user->phone,
                    'approved_by_admin' => $user->approved_by_admin,
                    'profile_status' => $user->profile_status,
                    'address' => $user->address,
                    'created_by'=>$user->created_by
                ],
                'authorisation' => [
                    'token' => $token,
                    'type' => 'bearer',
                    'expiration' => $expirationTime
                ]
            ]);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function update_user_profile(Request $request)
    {
        try {
            $user = User::find($request->id);
            $user->name =  $request->name;
            $user->surname =  $request->surname;
            $user->phone = $request->phone;
            $user->birthday =  date('Y-m-d', strtotime($request->birthday));
            $user->address = $request->address;
            $user->timezone = $request->timezone;
            $user->currency = $request->currency;
            $user->city = $request->city;
            $user->country = $request->country;
            $user->post_code = $request->post_code;
            $user->business_email = $request->business_email;
            $user->business_phoneno = $request->business_phoneno;
            $user->company_name = $request->company_name;
            $user->vat_no = $request->vat_no;
            $user->tax_id = $request->tax_id;
            $user->lat = $request->lat;
            $user->lng = $request->lng;
            $user->profile_status = 'completed';

            $admin = User::select('id')->where('role', 'admin')->get();
            $concierge = User::select('id', 'created_by')->where('id', $request->id)->first();

            $notify_by = $user->id;
            $notify_to =  $admin;
            $description = 'Your profile has been successfully updated.';
            $description1 = $user->name . ' has just updated their profile.';
            $type = 'update_profile';

            createNotificationForUserAndAdmins($notify_by, $notify_to, $description, $description1, $type);

            if ($concierge->created_by) {
                $notify_by1 = $concierge->id;
                $notify_to1 =  $concierge->created_by;
                $description1 = $user->name . ' has just updated their profile.';
                $type1 = 'update_profile';

                createNotificationForConcierge($notify_by1, $notify_to1, $description1, $type1);
            }

            $savedata = $user->save();
            if ($savedata) {
                return response()->json(['status' => true, 'message' => "User profile has been updated succesfully", 'data' => $user], 200);
            } else {
                return response()->json(['status' => false, 'message' => "There has been error for updating the profile", 'data' => ""], 200);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function update_users_image(Request $request)
    {
        try {
            $user = User::find($request->id);

            if ($request->hasFile('image')) {
                $randomNumber = mt_rand(1000000000, 9999999999);
                $imagePath = $request->file('image');
                $imageName = $randomNumber . $imagePath->getClientOriginalName();
                $imagePath->move('public/images/chef/users', $imageName);
                $user->pic = $imageName;
            }

            $savedata = $user->save();
            if ($savedata) {
                return response()->json(['status' => true, 'message' => "User profile has been updated succesfully", 'data' => $user], 200);
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
                $url = $domain . '/?userid=' . $user->id . '&resettoken=' . $token;
                $data['url'] = $url;
                $data['email'] = $request->email;
                $data['title'] = "password reset";
                $data['body'] = "Please click on below link to reset your password";
                Mail::send('emails.forgetpassword', ['data' => $data], function ($message) use ($data) {
                    $message->to($data['email'])->subject($data['title']);
                });
                $datetime = Carbon::now()->format('Y-m-d H:i:s');
                PasswordReset::updateOrCreate(
                    ['email' => $user->email],
                    [
                        'email' => $user->email,
                        'user_id' => $user->id,
                        'token' => $token,
                        'created_at' => $datetime,
                    ]
                );

                $admin = User::select('id')->where('role', 'admin')->get();
                $concierge = User::select('id', 'created_by')->where('id', $request->id)->first();

                if ($concierge->created_by) {
                    $notify_by1 = $concierge->id;
                    $notify_to1 =  $concierge->created_by;
                    $description1 = $user->name . ', has just requested to reset their password.';
                    $type1 = 'forget_password';

                    createNotificationForConcierge($notify_by1, $notify_to1, $description1, $type1);
                }

                $notify_by = $user->id;
                $notify_to =  $admin;
                $description = 'Please follow the link sent to your email to reset your password';
                $description1 = $user->name . ', has just requested to reset their password.';
                $type = 'forget_password';

                createNotificationForUserAndAdmins($notify_by, $notify_to, $description, $description1, $type);

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

    public function updated_reset_password(Request $request)
    {
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
            $user->save();
    
            PasswordReset::where('user_id', $request->user_id)->delete();
    
            $admin = User::where('role', 'admin')->get();
            $concierge = User::find($request->id);
    
            if ($concierge && $concierge->created_by) {
                $notify_by1 = $concierge->id;
                $notify_to1 = $concierge->created_by;
                $description1 = $user->name . ' has just reset their password.';
                $type1 = 'forget_password';
    
                createNotificationForConcierge($notify_by1, $notify_to1, $description1, $type1);
            }
    
            $notify_by = $user->id;
            $notify_to = $admin;
            $description = 'Your password has been reset successfully.';
            $description1 = $user->name . ' has just reset their password.';
            $type = 'forget_password';
    
            createNotificationForUserAndAdmins($notify_by, $notify_to, $description, $description1, $type);
    
            return response()->json(['status' => true, 'message' => 'Password reset successful'], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Password reset failed'], 500);
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
    public function get_single_user_profile(Request $request)
    {
        try {
            $user = User::where('id', $request->id)->first();
            if ($user) {
                return response()->json(['status' => true, 'message' => "Single profile data fetched successfully", 'data' => $user], 200);
            } else {
                return response()->json(['status' => false, 'message' => "There has been error for fetching the single profile", 'data' => ""], 200);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage()]);
        }
    }
    public function get_all_users()
    {
        try {
            $users = User::orderBy('id', 'DESC')->where('role', 'user')->where('status', 'active')->get();
            return response()->json([
                'status' => true,
                'message' => 'All users fetched successfully.',
                'data' => $users
            ]);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    // public function updateAllergyCusine(Request $request,$id)
    // {
    //     try {
    //         $user = User::find($request->id);
    //         $user->cuisine_id = implode(",", $request->cuisine_id);
    //         $user->allergy_id = implode(",", $request->allergies_id);
    //         $user->save();
    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Allergies Updated',
    //             'data' => $user
    //         ]);
    //     } catch (\Exception $e) {
    //         throw new HttpException(500, $e->getMessage());
    //          return response()->json([
    //             'status' => false,
    //              'message' => $e->getMessage()
    //          ]);
    //     }
    // }

    public function updateAllergyCusine(Request $request, $id)
    {
        try {
            $user = User::find($id);

            $cuisineIds = is_array($request->selectedcuisine) ? $request->selectedcuisine : explode(',', $request->selectedcuisine);
            $allergyIds = is_array($request->selectedallergies) ? $request->selectedallergies : explode(',', $request->selectedallergies);
            // return $request->all();
            $user->cuisine_id = implode(",", $cuisineIds);
            $user->allergy_id = implode(",", $allergyIds);
            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'Allergies Updated',
                'data' => $user
            ]);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    public function social_data_save(Request $request)
    {
        try {

            $user = User::where('email', $request->email)->orwhere('name', $request->name)->first();

            if ($user) {

                $credentials = $request->only('email', 'password');
                $token = Auth::attempt($credentials);

                if (!$token) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Please enter correct credentials!.',
                        //'message' => 'Unauthorized',
                    ], 401);
                }

                $payload = Auth::getPayload($token);
                $expirationTime = Carbon::createFromTimestamp($payload->get('exp'))->toDateTimeString();


                $user = Auth::user();
                return response()->json([
                    'status' => true,
                    'message' => 'user Loggedin successfully',
                    'user' => $user,
                    'authorisation' => [
                        'token' => $token,
                        'type' => 'bearer',
                        'expiration' => $expirationTime
                    ]
                ]);
            } else {
                $data = $request->all();
                $data['password'] = Hash::make($request->password);
                $data['view_password'] = $request->password;
                $user = new User();
                $register  = $user->create($data);
                //return $register

                if ($register) {
                    $token = Auth::login($register);
                    $payload = Auth::getPayload($token);
                    $expirationTime = Carbon::createFromTimestamp($payload->get('exp'))->toDateTimeString();
                    return response()->json([
                        'status' => true,
                        'message' => 'User created successfully',
                        'user' => $data,
                        'authorisation' => [
                            'token' => $token,
                            'type' => 'bearer',
                            'expiration' => $expirationTime
                        ]
                    ]);
                } else {
                    return response()->json(['message' => "'There has been error for to register the user"], 404);
                }
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function select_role(Request $request)
    {
        try {
            $user = User::find($request->id);
            $user->role = $request->role;
            $user->save();
            if ($user) {
                return response()->json(['status' => true, 'message' => "role selected successsfully"], 200);
            } else {
                return response()->json(['status' => false, 'message' => "There has been error for to selected role"], 404);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function get_email_data($email)
    {
        try {
            $user = User::where('email', $email)->first();
            if ($user) {
                return response()->json(['status' => true, 'message' => "role selected successsfully", 'data' => $user], 200);
            } else {
                return response()->json(['status' => false, 'message' => "There has been error for to selected role"], 404);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function update_user_to_offline($id)
    {
        try {


            $online_status_user = User::find($id);
            $online_status_user->is_online = 'no';
            $online_status_user->save();

            if ($online_status_user->save()) {
                return response()->json(['status' => true, 'message' => "user offline successfully"], 200);
            } else {
                return response()->json(['status' => false, 'message' => "There has been error"]);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function create_user(Request $request)
    {
        try {
            $checkemail = User::where('email', $request->email)->count();

            if ($checkemail <= 0) {
                $password = Str::random(10);
                $user = new User();
                $user->name = $request->name;
                $user->email = $request->email;
                $user->password = Hash::make($password);
                $user->view_password = $password;
                $user->created_by = $request->created_by;
                $user->role = 'user';
                $savedata = $user->save();

                $data = [
                    'name'   => $user->name,
                    'password' => $password,
                    'email'   => $user->email,
                ];

                Mail::send('emails.chefuserRegistrationMail', ["data" => $data], function ($message) use ($data) {
                    $message->from('dev3.bdpl@gmail.com', "Private Chefs");
                    $message->subject('Your Account Password for Private Chefs');
                    $message->to($data['email']);
                });

                return response()->json(['status' => true, 'message' => "User created successfully", 'data' => $user], 200);
            } else {
                return response()->json(['status' => false, 'message' => "Email already exists", 'data' => ""], 200);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function delete_user(Request $request)
    {
        try {
            $invoice = User::find($request->id);
            if (!$invoice) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found'
                ]);
            }
            $invoice->status = 'deleted';
            $invoice->save();

            return response()->json([
                'status' => true,
                'message' => 'User deleted successfully'
            ]);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    public function get_all_concierge_users(Request $request)
    {
        try {
            $users = User::where('created_by', $request->id)->orderBy('id', 'DESC')->where('status', '!=', 'deleted')->where('role', 'user')->where('status', 'active')->get();
            return response()->json([
                'status' => true,
                'message' => 'All users fetched successfully.',
                'data' => $users
            ]);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    public function create_chef(Request $request)
    {
        try {
            $checkemail = User::where('email', $request->email)->count();

            if ($checkemail <= 0) {
                $password = Str::random(10);
                $user = new User();
                $user->name = $request->name;
                $user->email = $request->email;
                $user->password = Hash::make($password);
                $user->view_password = $password;
                $user->created_by = $request->created_by;
                $user->role = 'chef';
                $savedata = $user->save();

                $data = [
                    'name'   => $user->name,
                    'password' => $password,
                    'email'   => $user->email,
                ];

                // Mail::send('emails.chefuserRegistrationMail', ["data" => $data], function ($message) use ($data) {
                //     $message->from('dev3.bdpl@gmail.com', "Private Chef");
                //     $message->subject(' Your Account Password for Private Chef');
                //     $message->to($data['email']);
                // });

                return response()->json(['status' => true, 'message' => "User created successfully", 'data' => $user], 200);
            } else {
                return response()->json(['status' => false, 'message' => "Email already exists", 'data' => ""], 200);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function delete_chef(Request $request)
    {
        try {
            $invoice = User::find($request->id);
            if (!$invoice) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found'
                ]);
            }
            $invoice->status = 'deleted';
            $invoice->save();

            return response()->json([
                'status' => true,
                'message' => 'User deleted successfully'
            ]);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    public function get_all_concierge_chef(Request $request)
    {
        try {
            $users = User::where('created_by', $request->id)->orderBy('id', 'DESC')->where('role', 'chef')->where('status', '!=', 'deleted')->get();
            return response()->json([
                'status' => true,
                'message' => 'All chef fetched successfully.',
                'data' => $users
            ]);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
