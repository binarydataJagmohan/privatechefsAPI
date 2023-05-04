<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ChefDetail;
use App\Models\Menu;
use App\Models\Dishes;
use App\Models\MenuItems;
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
                $imagePath->move('images/chef/users', $imageName);
                $user->pic = $imageName;
            }

            $admin = User::select('id')->where('role', 'admin')->get();

            $notify_by = $user->id;
            $notify_to =  $admin;
            $description = 'Your profile has been successfully updated.';
            $description1 = $user->name . ', has just updated their profile.';
            $type = 'update_profile';

            createNotificationForUserAndAdmins($notify_by, $notify_to, $description, $description1, $type);

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

                $resume = User::find($request->id);
                if ($request->hasFile('image')) {
                    $randomNumber = mt_rand(1000000000, 9999999999);
                    $imagePath = $request->file('image');
                    $imageName = $randomNumber . $imagePath->getClientOriginalName();
                    $imagePath->move('images/chef/users', $imageName);
                    $resume->pic = $imageName;
                }

                $admin = User::select('id')->where('role', 'admin')->get();

                $notify_by = $chef->id;
                $notify_to =  $admin;
                $description = 'Your resume has been successfully updated.';
                $description1 = $chef->name . ', has just updated their resume.';
                $type = 'update_profile';

                createNotificationForUserAndAdmins($notify_by, $notify_to, $description, $description1, $type);

                $resume->save();

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

    public function get_all_chef_menu(Request $request, $id)
    {
        try {
            $menu_data = Menu::where('user_id', $id)->where('status', 'active')->orderBy('id', 'DESC');
            $count = $menu_data->count();
            $menu = $menu_data->get();
            if ($count > 0) {
                return response()->json(['status' => true, 'message' => "Menu Data fetch successfully", 'data' => $menu, 'status' => true], 200);
            } else {
                return response()->json(['status' => false, 'message' => "No Menu data found", 'data' => ""], 200);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

 public function getAllChefDetails()
{
    try {
        $users = User::where('role', 'chef')
                     ->join('menus', 'users.id', '=', 'menus.user_id')
                     ->join('cuisine', 'cuisine.id', '=', 'menus.cuisine_id')
                     ->select('users.*', 'cuisine.name as cuisine_name')
                     ->get();
        return response()->json([
            'status' => true,
            'message' => "Chef resume fetched successfully",
            'data' => $users
        ], 200);
    } catch (\Exception $e) {
        throw new HttpException(500, $e->getMessage());
    }
}

    public function get_chef_by_filter(Request $request)
{
    try {
        $selectedCuisines = $request->input('cuisines', []);
       // return $selectedCuisines;  
        
        $users = User::where('role', 'chef')
                     ->join('menus', 'users.id', '=', 'menus.user_id')
                     ->join('cuisine', 'cuisine.id', '=', 'menus.cuisine_id')
                     ->whereIn('cuisine.name', $selectedCuisines)
                     ->select('users.*')
                     ->get();

        //     if ($users->isEmpty()) {
        //     return response()->json([
        //         'status' => false,
        //         'message' => "No chefs found for the selected cuisines.",
        //         'data' => []
        //     ], 200);
        // }
        
        return response()->json([
            'status' => true,
            'message' => "Chef resume fetched successfully",
            'data' => $users
        ], 200);
    } catch (\Exception $e) {
        throw new HttpException(500, $e->getMessage());
    }
}



    public function save_chef_menu_items(Request $request)
    {
        try {

           $check_dishes = MenuItems::where('dish_id',$request->dish_id)->where('menu_id',$request->menu_id)->where('status','active')->count();

           if($check_dishes <= 0){

                $dishes = new MenuItems();
                $dishes->menu_id =  $request->menu_id;
                $dishes->user_id = $request->user_id;
                $dishes->type =  $request->type;
                $dishes->dish_id =  $request->dish_id;
                $dishes->save();

                if ($dishes->save()) {

                    $Dishes = MenuItems::Select('menu_items.id as menu_item_id','item_name','menu_items.type')->where('menu_id', $request->menu_id)->where('menu_items.user_id', $request->user_id)->where('menu_items.status', 'active')->orderBy('menu_items.id', 'desc')->join('dishes', 'menu_items.dish_id', '=', 'dishes.id')->get();

                    return response()->json(['status' => true, 'message' => 'Menu items has been save successfully', 'error' => '', 'dishes' => $Dishes]);
                } else {

                    return response()->json(['status' => false, 'message' => 'There has been for saving the Menu items', 'error' => '', 'data' => '']);
                }

            }else {

            return response()->json(['status' => false, 'message' => 'Menu item already exits', 'error' => '', 'data' => '']);

           }     

            
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function delete_chef_menu_item(Request $request)
    {

        try {

            $Dishes = MenuItems::where('id', $request->id)->update([
                'status' => 'deleted'
            ]);
            if ($Dishes) {
                return response()->json(['status' => true, 'message' => 'Dish has been deleted successfully!', 'status' => true], 200);
            } else {
                return response()->json(['status' => false, 'message' => 'There has been error for deleting the dish!', 'status' => false], 200);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
}
