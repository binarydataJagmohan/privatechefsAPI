<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ChefDetail;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ChefDetailController extends Controller
{
    public function get_chef_detail(Request $request)
    {
        try {
            $user = User::where('id', $request->id)->first();
            if ($user) {
                return response()->json(['status' => true, 'message' => "Chef detail fetched succesfully", 'data' => $user], 200);
            } else {
                return response()->json(['status' => false, 'message' => "There has been error for fetching the chef detail", 'data' => ""], 400);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function update_chef_profile(Request $request)
    {
        try {

            $user = User::find($request->id);
            $user->name = $request->name;
            $user->surname = $request->surname;
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->address = $request->address;
            $user->passport_no = $request->passport_no;
            $user->BIC = $request->BIC;
            $user->IBAN = $request->IBAN;
            $user->bank_name = $request->bank_name;
            $user->holder_name = $request->holder_name;
            $user->bank_address = $request->bank_address;

            if ($request->hasFile('image')) {
                $randomNumber = mt_rand(1000000000, 9999999999);
                $imagePath = $request->file('image');
                $imageName = $randomNumber . $imagePath->getClientOriginalName();
                $imagePath->move('images/chef', $imageName);
                $user->pic = $imageName;
            } 

            $savedata = $user->save();
            if ($savedata) {
                return response()->json(['status' => true, 'message' => "Profile has been updated succesfully", 'data' => $user], 200);
            } else {
                return response()->json(['status' => false, 'message' => "There has been error for updating the profile", 'data' => ""], 400);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function update_chef_resume(Request $request)
    {
        try {
            $chef = ChefDetail::where('user_id', $request->id)->first();
            if ($chef) {
                $resume = ChefDetail::find($chef->id);
                $resume->about = $request->about;
                $resume->description = $request->description;
                $resume->services_type = $request->services_type;
                $resume->employment_status = $request->employment_status;
                $resume->website = $request->website;
                $resume->languages = $request->languages;
                $resume->experience = $request->experience;
                $resume->skills = $request->skills;
                $resume->favorite_chef = $request->favorite_chef;
                $resume->favorite_dishes = $request->favorite_dishes;
                $resume->love_cooking = $request->love_cooking;
                $resume->facebook_link = $request->facebook_link;
                $resume->instagram_link = $request->instagram_link;
                $resume->twitter_link = $request->twitter_link;
                $resume->linkedin_link = $request->linkedin_link;
                $resume->youtube_link = $request->youtube_link;
                $savedata = $resume->save();

                $user = User::find($request->id);
                if ($request->hasFile('image')) {
                    $randomNumber = mt_rand(1000000000, 9999999999);
                    $imagePath = $request->file('image');
                    $imageName = $randomNumber . $imagePath->getClientOriginalName();
                    $imagePath->move('images/chef', $imageName);
                    $user->pic = $imageName;
                } 

                $user->save();

                if ($savedata) {
                    return response()->json(['status' => true, 'message' => "Resume has been updated successfully", 'data' => $resume], 200);
                } else {
                    return response()->json(['status' => false, 'message' => "There was an error updating the resume", 'data' => ""], 400);
                }
            } else {
                return response()->json(['status' => false, 'message' => "User not found", 'data' => ""], 400);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function get_chef_resume(Request $request)
    {
        try {
            $user = ChefDetail::where('user_id', $request->id)->first();
            if ($user) {
                return response()->json(['status' => true, 'message' => "Chef resume fetched succesfully", 'data' => $user], 200);
            } else {
                return response()->json(['status' => false, 'message' => "There has been error for fetching the chef resume", 'data' => ""], 400);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function getAllChefDetails()
{
    try { 
        $users = User::where('role', 'chef')->get();
        return response()->json([
            'status' => true,
            'message' => "Chef resume fetched successfully",
            'data' => $users
        ], 200);
    } catch (\Exception $e) {
        throw new HttpException(500, $e->getMessage());
    }
}


}

