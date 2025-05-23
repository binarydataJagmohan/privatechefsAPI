<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ChefDetail;
use App\Models\ChefLocation;
use App\Models\Menu;
use App\Models\AppliedJobs;
use App\Models\Notification;
use App\Models\MenuItems;
use Illuminate\Support\Facades\DB;
use App\Models\Cuisine;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\Validator;
use Mail;
use Illuminate\Support\Str;

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

    private function generateUniqueSlug($first_name)
    {
        $baseSlug = Str::slug("$first_name");
        $slug = $baseSlug;
        $count = 1;
        while (User::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$count}";
            $count++;
        }
        return $slug;
    }

    public function update_chef_profile(Request $request)
    {
        try {

            $slug = $this->generateUniqueSlug($request->name);

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
            $user->vat_no = $request->vat_no;
            $user->tax_id = $request->tax_id;
            $user->lat = $request->lat;
            $user->lng = $request->lng;
            $user->slug = $slug;
            $user->profile_status = 'completed';

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $randomNumber = mt_rand(1000000000, 9999999999);
                $imageName = $randomNumber . $file->getClientOriginalName();
                $file->move(public_path('images/chef/users'), $imageName); // Save to 'public/images/userprofileImg'
                $user->pic = $imageName;
            }


            $admin = User::select('id')->where('role', 'admin')->get();

            $concierge = User::select('id', 'created_by')->where('id', $request->id)->first();

            if ($concierge->created_by) {
                $notify_by1 = $concierge->id;
                $notify_to1 =  $concierge->created_by;
                $description1 = $user->name . ' has just updated their profile.';
                $type1 = 'update_profile';

                createNotificationForConcierge($notify_by1, $notify_to1, $description1, $type1);
            }

            $notify_by = $user->id;
            $notify_to =  $admin;
            $description = 'Your profile has been successfully updated.';
            $description1 = $user->name . ' has just updated their profile.';
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
                $file = $request->file('image');
                $randomNumber = mt_rand(1000000000, 9999999999);
                $imageName = $randomNumber . $file->getClientOriginalName();

                $file->move(public_path('images/chef/users'), $imageName); // Save to 'public/images/userprofileImg'
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
            // $chef = ChefDetail::where('user_id', $request->id)->first();
            $chef = ChefDetail::where('user_id', $request->route('id'))->first();

            if ($chef) {
                $resume = ChefDetail::find($chef->id);
                $resume->about = $request->about;
                $resume->description = $request->description;
                $resume->services_type = $request->services_type;
                $resume->languages = $request->languages;
                $resume->experience = $request->experience;
                $resume->skills = $request->skills;
                $resume->favorite_chef = $request->favorite_chef;
                $resume->favorite_dishes = $request->favorite_dishes;
                $resume->love_cooking = $request->love_cooking;
                $resume->cooking_secret = $request->cooking_secret;
                $resume->know_me_better = $request->know_me_better;
                $resume->facebook_link = $request->facebook_link;
                $resume->instagram_link = $request->instagram_link;
                $resume->twitter_link = $request->twitter_link;
                $resume->linkedin_link = $request->linkedin_link;
                $resume->youtube_link = $request->youtube_link;
                $savedata = $resume->save();


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

            $searchTerm =  $request->name;

            $users = User::where('users.role', 'chef')
                ->leftJoin('applied_jobs', 'users.id', 'applied_jobs.chef_id')
                ->where('users.status', 'active')
                ->leftJoin('menus', 'users.id', '=', 'menus.user_id')
                ->leftJoin('cuisine', 'cuisine.id', '=', 'menus.cuisine_id')
                ->where(function ($query) use ($searchTerm) {
                    $query->where('users.name', 'LIKE', "%$searchTerm%")
                        ->orWhere('users.surname', 'LIKE', "%$searchTerm%");
                })
                ->select('users.id', 'users.name', 'users.profile_status', 'applied_jobs.amount', 'users.address', 'users.email', 'users.phone', 'users.pic', 'users.approved_by_admin', 'users.email', 'users.slug', 'menus.menu_name', 'applied_jobs.status as job_status', 'applied_jobs.client_amount as clientamount', 'applied_jobs.admin_amount as adminamount')
                ->selectRaw('GROUP_CONCAT(cuisine.name) as cuisine_name')
                ->groupBy('users.id', 'users.name', 'users.address')
                ->orderby('users.id', 'desc')
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

            $users = User::join('applied_jobs', 'users.id', 'applied_jobs.chef_id')
                ->whereIn('applied_jobs.status', ['applied', 'hired'])
                ->where('users.role', 'chef')
                ->where('users.status', 'active')
                ->leftJoin('menus', 'users.id', '=', 'menus.user_id')
                ->leftJoin('cuisine', 'cuisine.id', '=', 'menus.cuisine_id')
                ->whereIn('cuisine.name', $selectedCuisinesArray)
                ->select('users.id', 'users.name', 'users.address', 'users.profile_status', DB::raw('GROUP_CONCAT(cuisine.name) as cuisine_name', 'applied_jobs.status as appliedstatus'))
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
            $check_dishes = MenuItems::where('dish_id', $request->dish_id)
                ->where('menu_id', $request->menu_id)
                ->where('status', 'active')
                ->count();


            if ($check_dishes <= 0) {

                $dishes = new MenuItems();
                $dishes->menu_id = $request->menu_id;
                $dishes->user_id = $request->user_id;
                $dishes->type = $request->type;
                $dishes->dish_id = $request->dish_id;
                $dishes->save();


                $dishes = MenuItems::select('menu_items.id as menu_item_id', 'item_name', 'menu_items.type')
                    ->where('menu_id', $request->menu_id)
                    ->where('menu_items.user_id', $request->user_id)
                    ->where('menu_items.status', 'active')
                    ->orderBy('menu_items.id', 'desc')
                    ->join('dishes', 'menu_items.dish_id', '=', 'dishes.id')
                    ->get();

                return response()->json([
                    'status' => true,
                    'message' => 'Menu items have been saved successfully',
                    'error' => '',
                    'dishes' => $dishes
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Menu item already exists',
                    'error' => '',
                    'data' => ''
                ]);
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
            $locationCount = ChefLocation::where('user_id', $request->user_id)->count();

            if ($locationCount >= 10) {
                return response()->json(['status' => false, 'message' => 'You have reached the maximum limit of locations', 'data' => null], 400);
            }

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
            $user = ChefLocation::where('user_id', $request->id)->where('status', 'active')->orderby('id', 'DESC')->get();
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
            $user = User::where('id', $request->id)->select('id', 'address', 'lat', 'lng')->where('status', 'active')->first();
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

            if ($user) {
                $notification = new Notification();
                $notification->notify_to = $user->id;
                $notification->description = "Attention, Chef! Your profile has been approved. Unlock all tabs to unleash your culinary prowess and delight food enthusiasts!";
                $notification->type = 'approve_status';
                $notification->save();
            }

            if ($user->approved_by_admin == 'yes') {
                Mail::send('emails.chefconfirmingapproval', ['user' => $user], function ($message) use ($user) {
                    $message->from(config('mail.from.address'), "Private Chefs");
                    $message->to($user->email);
                    $message->bcc('info@privatechefsworld.com');
                    $message->subject("Welcome Aboard, " . $user->name);
                });
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
            $user = User::select('id', 'approved_by_admin')->where('id', $request->id)->first();

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
    public function get_all_chef_location()
    {
        try {
            $location = ChefLocation::select('users.pic', 'chef_location.address')
                ->join('users', 'chef_location.user_id', 'users.id')
                ->where('chef_location.status', '!=', 'deleted')
                ->get();
            $cheflocation = ChefDetail::select('users.pic', 'users.address')
                ->join('users', 'chef_details.user_id', 'users.id')
                ->where('users.role', '=', 'chef')
                ->get();
            return response()->json(['status' => true, 'message' => 'Chef location fetched succesfully', 'data' => $location, 'location' => $cheflocation]);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function get_chef_menu(Request $request)
    {
        try {
            $users = User::select('users.id as chefid', 'menus.menu_name', 'menus.id as menuid')
                ->join('menus', 'users.id', 'menus.user_id')
                ->where('users.role', 'chef')
                ->where('users.status', 'active')
                ->get();

            return response()->json([
                'status' => true,
                'data' => $users
            ], 200);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function get_all_top_rated_chef()
    {
        try {
            $activeChefs = User::where('status', 'active')->get();

            $topRatedChefIds = [];
            foreach ($activeChefs as $chef) {
                $topRatedChefIds = array_merge($topRatedChefIds, explode(',', $chef->top_rated));
            }
            $topRatedChefIds = array_unique($topRatedChefIds);

            $topRatedChefs = User::whereIn('id', $topRatedChefIds)->get();

            return response()->json(['status' => true, 'data' => $topRatedChefs], 200);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function get_chef_all_location(Request $request)
    {
        try {
            $users = User::select('id', 'address', 'lat')->where('role', 'chef')
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
    public function chef_location_filter(Request $request)
    {
        try {
            $selectedLocation = $request->input('locations');
            $selectedLocationArray = explode(',', $selectedLocation);

            $users = User::leftJoin('applied_jobs', 'users.id', 'applied_jobs.chef_id')
                ->where('users.role', 'chef')
                ->where('users.status', 'active')
                ->leftJoin('menus', 'users.id', '=', 'menus.user_id')
                ->leftJoin('cuisine', 'cuisine.id', '=', 'menus.cuisine_id')
                ->whereIn('users.lat', $selectedLocationArray)
                ->select('users.id', 'users.address', 'users.lat', 'users.name', 'users.address', 'users.profile_status', DB::raw('GROUP_CONCAT(cuisine.name) as cuisine_name', 'applied_jobs.status as appliedstatus'))
                ->where('users.status', '!=', 'deleted')
                ->groupBy('users.id', 'users.name', 'users.address')
                ->get();

            return response()->json([
                'status' => true,
                'data' => $users
            ], 200);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function chef_price_filter(Request $request)
    {
        try {
            $price = $request->input('price');

            if ($price !== null && $price < 250) {
                $users = User::leftJoin('applied_jobs', 'users.id', 'applied_jobs.chef_id')
                    ->where('users.role', 'chef')
                    ->where('users.status', 'active')
                    ->leftJoin('menus', 'users.id', '=', 'menus.user_id')
                    ->leftJoin('cuisine', 'cuisine.id', '=', 'menus.cuisine_id')
                    ->where('applied_jobs.amount', '<', 250)
                    ->select('users.id', 'users.address', 'users.lat', 'users.name', 'users.address', 'users.profile_status', 'applied_jobs.amount', DB::raw('GROUP_CONCAT(cuisine.name) as cuisine_name', 'applied_jobs.status as appliedstatus'))
                    ->where('users.status', '!=', 'deleted')
                    ->groupBy('users.id', 'users.name', 'users.address')
                    ->get();
            } elseif ($price !== null && $price >= 2000) {
                $users = User::leftJoin('applied_jobs', 'users.id', 'applied_jobs.chef_id')
                    ->where('users.role', 'chef')
                    ->where('users.status', 'active')
                    ->leftJoin('menus', 'users.id', '=', 'menus.user_id')
                    ->leftJoin('cuisine', 'cuisine.id', '=', 'menus.cuisine_id')
                    ->where('applied_jobs.amount', '>=', 2000)
                    ->select('users.id', 'users.address', 'users.lat', 'users.name', 'users.address', 'users.profile_status', 'applied_jobs.amount', DB::raw('GROUP_CONCAT(cuisine.name) as cuisine_name', 'applied_jobs.status as appliedstatus'))
                    ->where('users.status', '!=', 'deleted')
                    ->groupBy('users.id', 'users.name', 'users.address')
                    ->get();
            } elseif ($price !== null && $price >= 250 && $price <= 499) {
                $users = User::leftJoin('applied_jobs', 'users.id', 'applied_jobs.chef_id')
                    ->where('users.role', 'chef')
                    ->where('users.status', 'active')
                    ->leftJoin('menus', 'users.id', '=', 'menus.user_id')
                    ->leftJoin('cuisine', 'cuisine.id', '=', 'menus.cuisine_id')
                    ->whereBetween('applied_jobs.amount', [250, 499])
                    ->select('users.id', 'users.address', 'users.lat', 'users.name', 'users.address', 'users.profile_status', 'applied_jobs.amount', DB::raw('GROUP_CONCAT(cuisine.name) as cuisine_name', 'applied_jobs.status as appliedstatus'))
                    ->where('users.status', '!=', 'deleted')
                    ->groupBy('users.id', 'users.name', 'users.address')
                    ->get();
            } elseif ($price !== null && $price >= 500 && $price < 1000) {
                $users = User::leftJoin('applied_jobs', 'users.id', 'applied_jobs.chef_id')
                    ->where('users.role', 'chef')
                    ->where('users.status', 'active')
                    ->leftJoin('menus', 'users.id', '=', 'menus.user_id')
                    ->leftJoin('cuisine', 'cuisine.id', '=', 'menus.cuisine_id')
                    ->whereBetween('applied_jobs.amount', [500, 1000])
                    ->select('users.id', 'users.address', 'users.lat', 'users.name', 'users.address', 'users.profile_status', 'applied_jobs.amount', DB::raw('GROUP_CONCAT(cuisine.name) as cuisine_name', 'applied_jobs.status as appliedstatus'))
                    ->where('users.status', '!=', 'deleted')
                    ->groupBy('users.id', 'users.name', 'users.address')
                    ->get();
            } elseif ($price !== null && $price >= 1000 && $price <= 1099) {
                $users = User::leftJoin('applied_jobs', 'users.id', 'applied_jobs.chef_id')
                    ->where('users.role', 'chef')
                    ->where('users.status', 'active')
                    ->leftJoin('menus', 'users.id', '=', 'menus.user_id')
                    ->leftJoin('cuisine', 'cuisine.id', '=', 'menus.cuisine_id')
                    ->whereBetween('applied_jobs.amount', [1000, 1099])
                    ->select('users.id', 'users.address', 'users.lat', 'users.name', 'users.address', 'users.profile_status', 'applied_jobs.amount', DB::raw('GROUP_CONCAT(cuisine.name) as cuisine_name'), DB::raw('applied_jobs.status as appliedstatus'))
                    ->where('users.status', '!=', 'deleted')
                    ->groupBy('users.id', 'users.name', 'users.address')
                    ->get();
            } else {
                return response()->json([
                    'status' => true,
                    'data' => '',
                ], 200);
            }

            return response()->json([
                'status' => true,
                'data' => $users
            ], 200);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function get_all_location(Request $request)
    {
        try {
            // $desiredLocations = ['Greece', 'Athens', 'Mykonos', 'Oslo', 'Samos', 'Crete', 'Corfu', 'Lefkada', 'Zakynthos', 'Porto Heli', 'Paros', 'Antiparos', 'Diaporos'];

            // $desiredLocations = \App\Models\Location::where('status', 'active')->pluck('location')->toArray();
            $locations = \App\Models\Location::where('status', 'active')->get(['location', 'slug', 'image']);
            $locationNames = $locations->pluck('location')->toArray();


            $users = User::select('id', 'address', 'location_pic', 'slug', 'name', 'pic')
                ->whereIn('address', $locationNames)
                ->where('role', 'chef')
                ->where('status', '!=', 'deleted')
                ->groupBy('address')
                ->get();
            $users = $users->map(function ($user) use ($locations) {
                $matchedLocation = $locations->firstWhere('location', $user->address);
                $user->location_slug = $matchedLocation ? $matchedLocation->slug : null;
                $user->location_image = $matchedLocation ? $matchedLocation->image : null;
                $user->location_name = $matchedLocation ? $matchedLocation->location : null;
                return $user;
            });


            return response()->json([
                'status' => true,
                'data' => $users
            ], 200);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function get_location_by_slug(Request $request)
    {
        try {

            $location = \App\Models\Location::where('slug', $request->slug)
                ->where('status', 'active')
                ->first();

            if (!$location) {
                return response()->json([
                    'status' => false,
                    'message' => 'Location not found',
                ], 404);
            }
            $users = User::select('id', 'address', 'name', 'location_pic', 'slug')
                ->where('role', 'chef')
                ->where('status', '!=', 'deleted')
                // ->where('address', $request->slug)
                ->where('address', $location->location)
                ->get();
            return response()->json([
                'status' => true,
                'data' => $users,
                'location' => $location,
            ], 200);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function getChefDetailByLocation(Request $request)
    {
        try {

            $latitude = $request->lat;
            $longitude = $request->lng;
            $radius = 50; // Radius in kilometers

            $chefs = DB::table('chef_location')
                ->select('id', 'user_id', 'address', 'lat', 'lng')
                ->where('location_status', 'visible')
                ->where('status', 'active')
                ->whereRaw(
                    '(6371 * ACOS(COS(RADIANS(?)) * COS(RADIANS(lat)) * COS(RADIANS(? - lng)) + SIN(RADIANS(?)) * SIN(RADIANS(lat))) <= ?)',
                    [$latitude, $longitude, $latitude, $radius]
                )
                ->get();

            if ($chefs->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No chefs found within the specified 50km radius',
                    'data' => []
                ], 200);
            }


            return response()->json([
                'status' => true,
                'data' => $chefs
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch user messages',
            ], 500);
        }
    }
}
