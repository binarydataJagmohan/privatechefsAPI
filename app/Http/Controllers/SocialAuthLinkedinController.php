<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Exception;

class SocialAuthLinkedinController extends Controller
{

    public function redirect()
    {
        return Socialite::driver('linkedin')->redirect();
    }
    public function callback()
    {
        try {
           

            $user = Socialite::driver('linkedin')->user();
           
            $finduser = User::where('linkedin_id', $user->id)->first();
            if ($finduser) {
                Auth::login($finduser);
                // return redirect()->intended('dashboard');

            } else {
                return "hii";
                $newUser = User::create([
                    'name' => $user->name,
                    'email' => $user->email,
                    'linkedin_id' => $user->id,
                    'password' => encrypt('123456'),
                    'view_password' => 123456,
                ]);

                Auth::login($newUser);
            }
        } catch (\Exception $e) {
            dd($e->getMessage());
        }
    }
}
