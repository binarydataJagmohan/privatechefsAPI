<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cuisine;
use App\Models\Menu;
use App\Models\Dishes;
use App\Models\MenuItems;

use Symfony\Component\HttpKernel\Exception\HttpException;
class MenuController extends Controller
{
    public function get_all_cuisine(Request $request)
    {
    
        try {

            $cuisine_data = Cuisine::where('status','active')->orderBy('id', 'DESC');
            $count = $cuisine_data->count();
            $cuisine = $cuisine_data->get();

            if($count > 0 ){
                return response()->json(['status'=>true,'message' => "Cuisine Data fetch successfully", 'data' => $cuisine,'status'=>true], 200);
            }else {
                return response()->json(['status'=>false,'message' => "No Cuisine data found", 'data' => ""], 200);
            }
            
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }

    }

    public function save_chef_menu(Request $request)
    {
        try {

           $checkmenuname = Menu::where('menu_name',$request->name)->where('status','active')->count();

           if($checkmenuname <= 0 ) {

            $menu = new Menu();
            $menu->menu_name =  $request->name;
            $menu->description =  $request->description;
            $menu->cuisine_id = $request->cuisineid;
            $menu->user_id = $request->user_id;

            if ($request->hasFile('image')) {
                $randomNumber = mt_rand(1000000000, 9999999999);
                $imagePath = $request->file('image');
                $imageName = $randomNumber . $imagePath->getClientOriginalName();
                $imagePath->move('images/chef/menu', $imageName);
                $menu->image = $imageName;
            } 

            $menu->save();

             if($menu->save()){

                 $getallchefmenu = Menu::where('user_id',$request->user_id)->where('status','active')->orderBy('id','desc')->get();

                 return response()->json(['status' => true, 'message' => 'Menu has been save successfully', 'error' => '', 'data' => $getallchefmenu,'save_menu_id'=> $menu->id]);
             }else {

                 return response()->json(['status' => true, 'message' => 'There has been for saving the menu', 'error' => '', 'data' => '']);
             }

           }else {

             return response()->json(['status' => false, 'message' => 'Menu name already exit please choose different name', 'error' => '', 'data' => '']);

           }
            
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }

    }

    public function get_single_chef_menu(Request $request,$id)
    {
    
        try {

            $MenuData = Menu::find($id);

             $Dishes = MenuItems::Select('menu_items.id as menu_item_id','item_name','menu_items.type')->where('menu_id',$id)->where('menu_items.status', 'active')->orderBy('menu_items.id', 'desc')->join('dishes', 'menu_items.dish_id', '=', 'dishes.id')->get();

        
            // $Dishes = Dishes::where('status','active')->where('menu_id',$id)->get();

            if($MenuData){
                return response()->json(['status'=>true,'message' => "Single menu Data fetch successfully", 'menudata' => $MenuData,'dishes'=>$Dishes,'status'=>true], 200);
            }else {
                return response()->json(['status'=>false,'message' => "No Single menu data found", 'data' => ""], 200);
            }
            
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }

    }

    public function update_chef_menu(Request $request)
    {
        try {

           $checkmenuname = Menu::where('menu_name',$request->name)->where('id','!=',$request->menu_id)->where('status','active')->count();

           if($checkmenuname <= 0 ) {

            if ($request->hasFile('image')) {

                    $randomNumber = mt_rand(1000000000, 9999999999);
                    $imagePath = $request->file('image');
                    $imageName = $randomNumber . $imagePath->getClientOriginalName();
                    $imagePath->move('images/chef/menu', $imageName);

                    $menu = Menu::find($request->menu_id);
                    $menu->menu_name =  $request->name;
                    $menu->description =  $request->description;
                    $menu->cuisine_id = $request->cuisineid;
                    $menu->user_id = $request->user_id;
                    $menu->image = $imageName;
                    $menu->save();
            }else {

                    $menu = Menu::find($request->menu_id);
                    $menu->menu_name =  $request->name;
                    $menu->description =  $request->description;
                    $menu->cuisine_id = $request->cuisineid;
                    $menu->user_id = $request->user_id;
                    $menu->save();
            }

                 if($menu->save()){

                     return response()->json(['status' => true, 'message' => 'Menu has been update successfully', 'error' => '', 'menudata' => $menu ]);
                 }else {

                     return response()->json(['status' => true, 'message' => 'There has been for saving the menu', 'error' => '', 'data' => '']);
                 }

           }else {

             return response()->json(['status' => false, 'message' => 'Menu name already exit please choose different name', 'error' => '', 'data' => '']);

           }
            
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }

    }

    public function delete_single_menu(Request $request)
    {

        try {

            $menu = Menu::where('id', $request->id)->update([
                'status' => 'deleted'
            ]);

            
            if ($menu) {

                $Dishes = MenuItems::where('menu_id', $request->id)->update([
                    'status' => 'deleted'
                ]);

                return response()->json(['status' => true, 'message' => 'Menu has been deleted successfully!','status'=>true], 200);
            } else {
                return response()->json(['status' => false, 'message' => 'There has been error for deleting the menu!','status'=>false], 200);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function update_person_price(Request $request)
    {
        try {

            $menu = Menu::find($request->menu_id);
            $menu->min_person =  $request->min_person;
            $menu->max_person =  $request->max_person;
            $menu->min_price = $request->min_price;
            $menu->max_price = $request->max_price;
            $menu->comments = $request->comments;

            $menu->save();

             if($menu->save()){

                 return response()->json(['status' => true, 'message' => 'Menu has been save successfully', 'error' => '', 'menudata' => $menu ]);
             }else {

                 return response()->json(['status' => true, 'message' => 'There has been for saving the menu', 'error' => '', 'data' => '']);
             }

           
            
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }

    }

    public function update_person_price_count(Request $request)
    {
    
        try {

            $menu = Menu::where('id', $request->menu_id)->update([
                $request->name => $request->dishcount
            ]);


            if($menu){
                return response()->json(['status'=>true,'message' => "Dish Count has been updated succesfully",'status'=>true], 200);
            }else {
                return response()->json(['status'=>false,'message' => "No Single menu data found", 'data' => ""], 200);
            }
            
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }

    }

}
