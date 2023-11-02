<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Models\User;
use App\Models\ChefDetail;
use App\Models\ChefLocation;
use Mail;
use Carbon\Carbon;
use App\Models\UserVerify;
use Illuminate\Support\Str;
use App\Models\PasswordReset;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\DB;




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
            }
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

            $auth_user = User::select('name', 'email', 'role', 'id', 'surname', 'pic', 'phone', 'approved_by_admin', 'created_by')->where('id', $user->id)->first();

            $email_token = Str::random(64);

            UserVerify::create([
                'user_id' => $user->id,
                'token' => $email_token
            ]);

            $admin = User::select('id', 'email')->where('role', 'admin')->get();

            $notify_by = $user->id;
            $notify_to =  $admin;
            $description = 'Thank you for registering. We hope you enjoy using our website.';
            $description1 = $user->name . ' registered on our website. Please review their account.';
            $type = 'Register';

            createNotificationForUserAndAdmins($notify_by, $notify_to, $description, $description1, $type);

            Mail::send('emails.emailVerificationEmail', ['token' => $email_token, 'user_id' => $user->id], function ($message) use ($request) {
                $message->to($request->email);
                $message->bcc(['recipient1@example.com', 'recipient2@example.com']);
                $message->subject('Email Verification Mail');
            });

            $admindata = User::select('email')->where('role', 'admin')->first();

            $data = [
                'name'   => $request->name,
                'admin_email' =>  $admindata->email,
            ];

            if ($request->role == 'user') {
                Mail::send('emails.registerEmailforuser', ['data' => $data, 'token' => $email_token, 'user_id' => $user->id], function ($message) use ($request, $data) {
                    $message->to($request->email);
                    $message->bcc($data['admin_email']);
                    $message->subject('User Registration Confirmation');
                });
            }
            if ($request->role == 'chef') {
                Mail::send('emails.registerEmailforchef', ['data' => $data, 'token' => $email_token, 'user_id' => $user->id], function ($message) use ($request, $data) {
                    $message->to($request->email);
                    $message->bcc($data['admin_email']);
                    $message->subject('Chef Registration Confirmation');
                });
            }
            $payload = Auth::getPayload($token);
            $expirationTime = Carbon::createFromTimestamp($payload->get('exp'))->toDateTimeString();

            return response()->json(['status' => true, 'message' => 'Registration has been done successfully please verfiy your email', 'data' => ['user' => $auth_user, 'token' => $token, 'expiration' => $expirationTime]], 200);
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

            // $notify_by = $user->id;
            // $notify_to = $admin;
            // $description = 'Welcome back! You have successfully logged in to your account.';
            // $description2 = $user->name . ' has logged in to their account.';
            // $type = 'Login';

            // createNotificationForUserAndAdmins($notify_by, $notify_to, $description, $description2, $type);


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
                    'created_by' => $user->created_by
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
                $imagePath->move('/images/chef/users', $imageName);
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

                if ($concierge && $concierge->created_by) {
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


    public function get_single_chef_profile(Request $request)
    {
        try {
            // $chef = User::where('id', $request->id)->first();
            // $chef = ChefDetail::select('chef_details.*','users.name as chefname')
            // ->join('users', 'users.id', '=', 'chef_details.user_id')
            // ->where('users.id', $request->id)
            // ->first();
            $chef = User::select('users.name as chefname', 'users.pic as chefpic', 'chef_details.about as chef_about', 'chef_details.service_title_1 as service_title_one', 'chef_details.service_title_1 as service_title_one', 'chef_details.service_title_1 as service_title_one', 'chef_details.service_title_2 as service_title_two', 'chef_details.service_title_3 as service_title_three', 'chef_details.service_title_4 as service_title_four', 'chef_details.service_description_1 as service_description_one', 'chef_details.service_description_2 as service_description_two', 'chef_details.service_description_3 as service_description_three', 'chef_details.service_description_4 as service_description_four', 'chef_details.love_cooking as lovecooking', 'chef_details.experience as chefexperience', 'chef_details.skills as chefskills', 'chef_details.favorite_dishes as cheffavorite_dishes')
                ->leftJoin('chef_details', 'users.id', '=', 'chef_details.user_id')
                ->where('users.id', $request->id)
                ->first();

            if ($chef) {
                return response()->json(['status' => true, 'message' => "Single chef profile data fetched successfully", 'data' => $chef], 200);
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
                $data['name'] = $request->name;
                $data['role'] = "user";
                $data['pic'] = null;
                $data['surname'] = null;
                $data['phone'] = null;
                $data['address'] = null;
                $data['approved_by_admin'] = "no";
                $data['profile_status'] = "pending";
                $data['created_by'] = null;
                $user = new User();
                $register  = $user->create($data);
                // return $register;

                if ($register) {
                    $token = Auth::login($register);
                    $payload = Auth::getPayload($token);
                    $expirationTime = Carbon::createFromTimestamp($payload->get('exp'))->toDateTimeString();

                    return response()->json(['status' => true, 'message' => 'User created successfully', 'data' => ['user' => $register, 'token' => $token, 'expiration' => $expirationTime]], 200);
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

                $admindata = User::select('email')->where('role', 'admin')->first();

                $data = [
                    'name'   => $user->name,
                    'password' => $password,
                    'email'   => $user->email,
                    'admin_email' =>  $admindata->email,
                ];

                if ($request->created_by != '1') {
                    Mail::send('emails.chefuserRegistrationMail', ["data" => $data], function ($message) use ($data) {
                        $message->from(config('mail.from.address'), "Private Chefs");
                        $message->bcc($data['admin_email']);
                        $message->subject(' Your Account Password for Private Chef');
                        $message->to($data['email']);
                    });
                } else {
                    Mail::send('emails.invitationChefMail', ["data" => $data], function ($message) use ($data) {
                        $message->from(config('mail.from.address'), "Private Chefs");
                        $message->bcc($data['admin_email']);
                        $message->subject('Invitation to Join Private Chefs World Team!');
                        $message->to($data['email']);
                    });
                }
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
                if ($request->created_by != '1') {
                    Mail::send('emails.chefuserRegistrationMail', ["data" => $data], function ($message) use ($data) {
                        $message->from(config('mail.from.address'), "Private Chefs");
                        $message->subject(' Your Account Password for Private Chef');
                        $message->to($data['email']);
                    });
                } else {
                    Mail::send('emails.invitationChefMail', ["data" => $data], function ($message) use ($data) {
                        $message->from(config('mail.from.address'), "Private Chefs");
                        $message->subject('Invitation to Join Private Chefs World Team!');
                        $message->to($data['email']);
                    });
                }

                return response()->json(['status' => true, 'message' => "User created successfully", 'data' => $user], 200);
            } else {
                return response()->json(['status' => false, 'message' => "Email already exists", 'data' => ""], 200);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }




    public function admin_create_chef(Request $request)
    {
        try {
            $checkemail = User::where('email', $request->email)->count();

            if ($checkemail <= 0) {
                $user = new User();
                $user->name = $request->name;
                $user->surname = $request->surname;
                $user->email = $request->email;
                $user->phone = $request->phone;
                $user->address = $request->address;
                $user->passport_no = $request->passport_no;
                $user->IBAN = $request->IBAN;
                $user->BIC = $request->BIC;
                $user->bank_name = $request->bank_name;
                $user->holder_name = $request->holder_name;
                $user->bank_address = $request->bank_address;
                $user->approved_by_admin = 'yes';

                $user->password = Hash::make($request->password);
                $user->view_password = $request->password;

                $user->created_by = $request->created_by;
                $user->role = 'chef';
                if ($request->hasFile('image')) {
                    $randomNumber = mt_rand(1000000000, 9999999999);
                    $imagePath = $request->file('image');
                    $imageName = $randomNumber . $imagePath->getClientOriginalName();
                    $imagePath->move('public/images/chef/users', $imageName);
                    $user->pic = $imageName;
                }

                $savedata = $user->save();

                $data = [
                    'name'   => $user->name,
                    'password' => $user->password,
                    'email'   => $user->email,
                    'surname' => $user->surname,
                    'phone' => $user->phone,
                    'address' => $user->address,
                    'passport_no' => $user->passport_no,
                    'IBAN' => $user->IBAN,
                    'BIC' => $user->BIC,
                    'bank_name' => $user->bank_name,
                    'holder_name' => $user->holder_name,
                    'bank_address' => $user->bank_address
                ];
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
    public function get_all_chef(Request $request)
    {
        try {
            $users = User::orderBy('id', 'DESC')->where('role', 'chef')->where('status', '!=', 'deleted')->get();
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
    public function get_data(Request $request)
    {
        try {
            $accessToken = "https://www.linkedin.com/in/binary-data-580636217/";

            $resource = '/v2/me';
            $params = ['oauth2_access_token' => $accessToken];
            $url =  $accessToken;
            $options = [
                'http' => [
                    'method' => 'GET',
                    'header' => 'Content-Type: application/json',
                ],
            ];
            $context = stream_context_create($options);
            $response = file_get_contents($url, false, $context);
            $data = json_decode($response);

            return response()->json([
                'data' =>  $data
            ]);
        } catch (Exception $e) {
            return 'Unable to get user details';
        }
    }



    public function get_chef_by_location(Request $request)
    {
        try {
            $user = ChefLocation::where('address', $request->address)->where('status', 'active')->get();

            if ($user) {
                return response()->json(['status' => true, 'message' => "Chef location fetched succesfully", 'data' => $user], 200);
            } else {
                return response()->json(['status' => false, 'message' => "There has been error for fetching the chef location", 'data' => ""], 400);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function get_user_all_location(Request $request)
    {
        try {
            $users = User::select('id', 'address', 'lat')->where('role', 'user')
                ->whereNotNull('users.address')
                ->where('status', 'active')
                 ->groupBy('address')
                ->get();
            return response()->json([
                'status' => true,
                'data' => $users
            ], 200);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function user_location_filter(Request $request)
    {
        try {
            $selectedLocation = $request->input('locations');
            $selectedLocationArray = explode(',', $selectedLocation);

            $users = User::where('role', 'user')
            ->where('status', 'active')
            // ->whereIn('address', $selectedLocationArray)
            ->whereIn('users.lat', $selectedLocationArray)
            ->where('status', '!=', 'deleted')
            ->select('id', 'address', 'lat', 'name', 'profile_status')
            ->get();

            return response()->json([
                'status' => true,
                'data' => $users
            ], 200);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function get_chef_all_location_by_concierge(Request $request)
    {
        try {
            $users = User::where('created_by', $request->id)->select('id', 'address', 'lat')->where('role', 'chef')
                ->whereNotNull('users.address')
                ->where('status', 'active')
                 ->groupBy('address')
                ->get();
            return response()->json([
                'status' => true,
                'data' => $users
            ], 200);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }


     public function sendMessageToUserByAdmin(Request $request)
    {
        try {
            
            foreach ($request->user_id as $id){

                $user = User::select('email','name')->where('id', $id)->first();

                $data = [
                    'email'   => $user->email,
                    'name' =>  $user->name,
                    'message'=> $request->message,
                ];

                Mail::send('emails.SpecialMessageToUser', ["data" => $data], function ($message) use ($data) {
                        $message->from(config('mail.from.address'), "Private Chefs");
                        $message->subject('The PrivateChefs team has a special message for you:');
                        $message->to($data['email']);
                });
            }

            return response()->json([
                'status' => true,
                'message' => 'The message has been sent successfully'
            ], 200);

            
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }


    public function test(Request $request)
    {
        try {
            $users = User::where('created_by', $request->id)->select('id', 'address', 'lat')->where('role', 'chef')
                ->whereNotNull('users.address')
                ->where('status', 'active')
                 ->groupBy('address')
                ->get();
            return response()->json([
                'status' => true,
                'data' => $users
            ], 200);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }


}
