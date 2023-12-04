<?php

namespace App\Http\Controllers\Api;
use App\Models\Dishes;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Http\Request;
use App\Models\DisheGallery;
use App\Models\User;

class DishesController extends Controller
{
    public function get_chef_dishes($id)
    {
        try{
            
        $categories = Dishes::where('status','active')->where('user_id',$id)->orderBy('id','desc')->get();
         return response()->json([
            'status' => true,
            'message' => "Dishes details fetched successfully",
            'data' => $categories
        ], 200);
     }
     catch (\Exception $e) {
        throw new HttpException(500, $e->getMessage());
    }
  }


   public function add_chef_dish(Request $request)
    {   

        try {


            if($request->id){

                $dish = Dishes::find($request->id);
                $dish->user_id = $request->userId;
                $dish->dish_category_id = $request->dish_category_id;

                if ($request->dish_category_id == '1') {
                    $dish->type = 'starter';
                } elseif ($request->dish_category_id == '2') {
                    $dish->type = 'firstcourse';
                } elseif ($request->dish_category_id == '3') {
                    $dish->type = 'maincourse';
                } else {
                    $dish->type = 'desert';
                }

                $dish->item_name = $request->item_name;
                $dish->save();

                return response()->json(['status' => true, 'message' => 'Dish has been update successfully', 'data' => $dish]);

            }else {

                $dish = new Dishes();
                $dish->user_id = $request->userId;
                $dish->dish_category_id = $request->dish_category_id;

                if ($request->dish_category_id == '1') {
                    $dish->type = 'starter';
                } elseif ($request->dish_category_id == '2') {
                    $dish->type = 'firstcourse';
                } elseif ($request->dish_category_id == '3') {
                    $dish->type = 'maincourse';
                } else {
                    $dish->type = 'desert';
                }


                $dish->item_name = $request->item_name;
                $dish->save();

                return response()->json(['status' => true, 'message' => 'Dish has been add successfully', 'data' => $dish]);
            }
           
        } catch (\Exception $e) {
            // Handle the exception and return an error response
            return response()->json(['status' => false, 'message' => 'There has been error for adding the dish', 'error' => $e->getMessage()]);
        }
    }

   public function dish_delete($id)
   {

    try{
        $dish = Dishes::where('id', $id)->update([
                'status' => 'deleted'
        ]);
        if ($dish) {
                return response()->json(['message' => 'Dish has been deleted successfully','status'=>true], 200);
            } else {
                return response()->json(['erroe' => 'error', 'message' => 'There has been error for deleting the dish','status'=>false], 200);
        }
    }
            catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

   public function get_single_dish(Request $request,$id)
    {
         try {

            $dish = Dishes::find($id);
        
            if($dish){
                return response()->json(['status'=>true,'message' => "Single Dish Data fetch successfully", 'data' => $dish], 200);
            }else {
                return response()->json(['status'=>false,'message' => "No Single Dish data found"]);
            }
            
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

  public function get_item_by_category($id)
   {
    try {
        $dish = Dishes::where('type', $id)->get();
        return response()->json(['data' => $dish,'statue'=>true,'message'=>'Data fetched']);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
    }

    public function fetch_dish_category_by_id(Request $request)
    {
         try {

            if($request->letter){

                if($request->dish_category_id == 'all'){

                     $categories = Dishes::where('status', 'active')
                    ->where('user_id', $request->user_id)
                    ->where('item_name', 'LIKE', $request->letter . '%')
                    ->orderBy('id', 'desc')
                    ->get();

                }else {

                         $categories = Dishes::where('status', 'active')
                        ->where('dish_category_id', $request->dish_category_id)
                        ->where('user_id', $request->user_id)
                        ->where('item_name', 'LIKE', $request->letter . '%')
                        ->orderBy('id', 'desc')
                        ->get();
                }

                return response()->json([
                        'status' => true,
                        'message' => "Dishes details fetched successfully",
                        'data' => $categories
                    ], 200);

            }else {

                if($request->dish_category_id == 'all'){

                    $categories = Dishes::where('status','active')->where('user_id',$request->user_id)->orderBy('id','desc')->get();
                    
                }else {

                    $categories = Dishes::where('status','active')->where('dish_category_id',$request->dish_category_id)->where('user_id',$request->user_id)->orderBy('id','desc')->get();
                    
                }

                 return response()->json([
                        'status' => true,
                        'message' => "Dishes details fetched successfully",
                        'data' => $categories
                    ], 200);
                 
            }
            
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function saveChefDishImages(Request $request)
    {
        try {

        if($request->id){

            $dish = DisheGallery::find($request->id);
            $dish->user_id = $request->user_id;

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $randomNumber = mt_rand(1000000000, 9999999999);
                $imageName = $randomNumber . $file->getClientOriginalName();
                $file->move(public_path('images/chef/dishes'), $imageName); // Save to 'public/images/userprofileImg'
                $dish->img = $imageName;
            }

            if($dish->save()){

                 return response()->json(['status' => true, 'message' => 'Dish Image has been updated successfully', 'error' => '']);
            }else {

                 return response()->json(['status' => true, 'message' => 'There has been error for updating the dish', 'error' => '', 'data' => '']);
            }


        }else {

            $dish = new DisheGallery();
            $dish->user_id = $request->user_id;

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $randomNumber = mt_rand(1000000000, 9999999999);
                $imageName = $randomNumber . $file->getClientOriginalName();
                $file->move(public_path('images/chef/dishes'), $imageName); // Save to 'public/images/userprofileImg'
                $dish->img = $imageName;
            }

            if($dish->save()){

                 return response()->json(['status' => true, 'message' => 'Dish Image has been save successfully', 'error' => '']);
            }else {
 
                 return response()->json(['status' => true, 'message' => 'There has been error for updating the dish', 'error' => '', 'data' => '']);
            }

        }

                       
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }

    }

    public function getAllChefDishGallery($id)
    {
        try{


         if(is_numeric($id)){
                 $chef_id = $id;
            }else {
                 $chef = User::select('id')->where('slug',$id)->first();
                 $chef_id = $chef->id;
            }
            
        $categories = DisheGallery::where('status','active')->where('user_id',$chef_id)->orderBy('id','desc')->get();
         return response()->json([
            'status' => true,
            'message' => "Dishes details fetched successfully",
            'data' => $categories
        ], 200);
     }
     catch (\Exception $e) {
        throw new HttpException(500, $e->getMessage());
    }
  }

   public function deleteChefDishImage($id)
   {

    try{
        $dish = DisheGallery::where('id', $id)->update([
                'status' => 'deleted'
        ]);
        if ($dish) {
                return response()->json(['message' => 'Dish Image has been deleted successfully','status'=>true], 200);
            } else {
                return response()->json(['erroe' => 'error', 'message' => 'There has been error for deleting the dish iamge','status'=>false], 200);
        }
    }
            catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

}
