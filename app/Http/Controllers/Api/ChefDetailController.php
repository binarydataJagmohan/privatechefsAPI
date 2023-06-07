<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ChefDetail;
use App\Models\ChefLocation;
use App\Models\Menu;
use App\Models\Dishes;
use App\Models\Notification;
use App\Models\MenuItems;
use Illuminate\Support\Facades\DB;
use App\Models\Cuisine;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\Validator;

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
            $user->profile_status = 'completed';

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

    public function update_chef_image(Request $request)
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

                // $resume = User::find($request->id);
                // if ($request->hasFile('image')) {
                //     $randomNumber = mt_rand(1000000000, 9999999999);
                //     $imagePath = $request->file('image');
                //     $imageName = $randomNumber . $imagePath->getClientOriginalName();
                //     $imagePath->move('images/chef/users', $imageName);
                //     $resume->pic = $imageName;
                // }

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

    public function getAllChefDetails(Request $request)
    {
        try {

            // $users = User::where('users.role', 'chef')
            // ->where('users.status', 'active')
            // ->leftJoin('menus', 'users.id', '=', 'menus.user_id')
            // ->leftJoin('cuisine', 'cuisine.id', '=', 'menus.cuisine_id')
            // ->select('users.id', 'users.name', 'users.address','cuisine.name')
            // ->groupBy('users.id', 'users.name', 'users.address')
            // ->get();
            $users = User::where('users.role', 'chef')
                ->where('users.status', 'active')
                ->leftJoin('menus', 'users.id', '=', 'menus.user_id')
                ->leftJoin('cuisine', 'cuisine.id', '=', 'menus.cuisine_id')
                ->select('users.id', 'users.name','users.profile_status', 'users.address','users.pic','users.approved_by_admin')
                ->selectRaw('GROUP_CONCAT(cuisine.name) as cuisine_name')
                ->groupBy('users.id', 'users.name', 'users.address')
                ->orderby('users.id','desc')
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
            $selectedCuisines = $request->input('cuisines');
            $selectedCuisinesArray = explode(',', $selectedCuisines);
            //return $selectedCuisines;  

            $users = User::join('applied_jobs','users.id','applied_jobs.chef_id')
                ->whereIn('applied_jobs.status', ['applied', 'hired'])
                ->where('users.role', 'chef')
                ->where('users.status', 'active')
                ->leftJoin('menus', 'users.id', '=', 'menus.user_id')
                ->leftJoin('cuisine', 'cuisine.id', '=', 'menus.cuisine_id')
                ->whereIn('cuisine.name', $selectedCuisinesArray)
                ->select('users.id', 'users.name', 'users.address', DB::raw('GROUP_CONCAT(cuisine.name) as cuisine_name','applied_jobs.status as appliedstatus'))
                ->groupBy('users.id', 'users.name', 'users.address')
                ->get();


            return response()->json([
                'status' => true,
                'message' => "Cuision fetched successfully",
                'data' => $users
            ], 200);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function get_cuision(Request $request)
    {
        try {
            $user = Cuisine::get();
            if ($user) {
                return response()->json(['status' => true, 'message' => "Cuisine fetched succesfully", 'data' => $user], 200);
            } else {
                return response()->json(['status' => false, 'message' => "There has been error for fetching the cuisine", 'data' => ""], 400);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function save_chef_menu_items(Request $request)
    {
        try {

            $check_dishes = MenuItems::where('dish_id', $request->dish_id)->where('menu_id', $request->menu_id)->where('status', 'active')->count();

            if ($check_dishes <= 0) {

                $dishes = new MenuItems();
                $dishes->menu_id =  $request->menu_id;
                $dishes->user_id = $request->user_id;
                $dishes->type =  $request->type;
                $dishes->dish_id =  $request->dish_id;
                $dishes->save();

                if ($dishes->save()) {

                    $Dishes = MenuItems::Select('menu_items.id as menu_item_id', 'item_name', 'menu_items.type')->where('menu_id', $request->menu_id)->where('menu_items.user_id', $request->user_id)->where('menu_items.status', 'active')->orderBy('menu_items.id', 'desc')->join('dishes', 'menu_items.dish_id', '=', 'dishes.id')->get();

                    return response()->json(['status' => true, 'message' => 'Menu items has been save successfully', 'error' => '', 'dishes' => $Dishes]);
                } else {

                    return response()->json(['status' => false, 'message' => 'There has been for saving the Menu items', 'error' => '', 'data' => '']);
                }
            } else {

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
    public function save_chef_location(Request $request)
{
    try {
        $validator = Validator::make($request->all(), [
            'address' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $locationCount = ChefLocation::where('user_id', $request->user_id)->count();

        if ($locationCount < 10) {
            $location = new ChefLocation();
            $location->user_id = $request->user_id;
            $location->address = $request->address;
            $location->lat = $request->lat;
            $location->lng = $request->lng;
            $savedata = $location->save();

            if ($savedata) {
                return response()->json(['status' => true, 'message' => 'Location has been stored successfully', 'data' => $location], 200);
            } else {
                return response()->json(['status' => false, 'message' => 'There was an error storing the location', 'data' => null], 400);
            }
        } else {
            return response()->json(['status' => false, 'message' => 'Maximum limit of locations reached'], 400);
        }
    } catch (\Exception $e) {
        throw new HttpException(500, $e->getMessage());
    }
}

    public function update_chef_location(Request $request)
    {
        try {
            $location = ChefLocation::find($request->id);
            $location->user_id = $request->user_id;
            $location->address = $request->address;
            $location->lat = $request->lat;
            $location->lng = $request->lng;
            $savedata = $location->save();
            if ($savedata) {
                return response()->json(['status' => true, 'message' => "location has been updated successfully", 'data' => $location], 200);
            } else {
                return response()->json(['status' => false, 'message' => "There was an error updating the location", 'data' => ""], 400);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function get_chef_location(Request $request)
    {
        try {
            $user = ChefLocation::where('user_id', $request->id)->where('status', 'active')->orderby('id','DESC')->get();
            if ($user) {
                return response()->json(['status' => true, 'message' => "Chef location fetched succesfully", 'data' => $user], 200);
            } else {
                return response()->json(['status' => false, 'message' => "There has been error for fetching the chef location", 'data' => ""], 400);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function get_current_location(Request $request)
    {
        try {
            $user = User::where('id', $request->id)->select('id','address')->where('status', 'active')->first();
            if ($user) {
                return response()->json(['status' => true, 'message' => "Chef location fetched succesfully", 'data' => $user], 200);
            } else {
                return response()->json(['status' => false, 'message' => "There has been error for fetching the chef location", 'data' => ""], 400);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function update_location_status(Request $request)
    {
        try {
            $location = ChefLocation::find($request->id);
            $location->location_status = $request->location_status;
            $location->save();
            if ($location) {
                return response()->json(['status' => true, 'message' => "Chef location status updated succesfully", 'data' => $location], 200);
            } else {
                return response()->json(['status' => false, 'message' => "There has been error for updating the chef location status", 'data' => ""], 400);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function get_single_location(Request $request)
    {
        try {
            $user = ChefLocation::where('id', $request->id)->where('status', 'active')->first();
            if ($user) {
                return response()->json(['status' => true, 'message' => "Chef location fetched succesfully", 'data' => $user], 200);
            } else {
                return response()->json(['status' => false, 'message' => "There has been error for fetching the chef location", 'data' => ""], 400);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function delete_single_location(Request $request)
    {
        try {
            $user = ChefLocation::find($request->id);
            $user->status = 'deleted';
            $user->save();
            if ($user) {
                return response()->json(['status' => true, 'message' => "Single location deleted succesfully", 'data' => $user], 200);
            } else {
                return response()->json(['status' => false, 'message' => "There has been error for deleting the chef location", 'data' => ""], 400);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function approve_chef_profile(Request $request)
    {
        try {
            $user = User::find($request->id);
            $user->approved_by_admin = $request->approved_by_admin;
            $user->save();
            
            if($user){
            $notification = new Notification();
            $notification->notify_to = $user->id;
            $notification->description = "Attention, Chef! Your profile has been approved. Unlock all tabs to unleash your culinary prowess and delight food enthusiasts!";
            $notification->type = 'approve_status';
            $notification->save();
            }

            if ($user) {
                return response()->json(['status' => true, 'message' => "Profile Approved successfully", 'data' => $user], 200);
            } else {
                return response()->json(['status' => false, 'message' => "There has been error for approving the chef profile", 'data' => ""], 400);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function get_chef_approval(Request $request)
    {
        try {
            $user = User::select('id','approved_by_admin')->where('id',$request->id)->first();

            if ($user) {
                return response()->json(['status' => true, 'message' => "Profile Approved fetched successfully", 'data' => $user], 200);
            } else {
                return response()->json(['status' => false, 'message' => "There has been error for fetching the record", 'data' => ""], 400);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function approval_msg(Request $request)
    {
        try {
            $user = User::find($request->id);
            $user->approval_msg = "no";
            $user->save();

            if ($user) {
                return response()->json(['status' => true, 'data' => $user], 200);
            } else {
                return response()->json(['status' => false, 'data' => ""], 400);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
}
