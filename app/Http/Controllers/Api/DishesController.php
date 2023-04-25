<?php

namespace App\Http\Controllers\Api;
use App\Models\Dishes;

use Illuminate\Http\Request;

class DishesController extends Controller
{
    public function getDishes()
    {
        try{
            
        $categories = Dishes::get();
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


   public function dishInsert(Request $request)
{   

    try {
       // return $request->all();
        $dish = new Dishes();
        $dish->user_id = $request->userId;
        $dish->type = $request->type;
        $dish->item_name = $request->item_name;
        $dish->save();

        // Return the ID of the newly inserted dish
        return response()->json(['status' => true, 'message' => 'Data inserted', 'data' => $dish]);
    } catch (\Exception $e) {
        // Handle the exception and return an error response
        return response()->json(['status' => false, 'message' => 'Data insertion failed', 'error' => $e->getMessage()]);
    }
}

   public function dish_delete($id)
   {

    try{
        $dish = Dishes::find($id);
        if(!$dish)
        {
            return response()->json(['status'=> False,'message'=> 'Dish Not found']);
        }
        else
        {
            $dish->delete();
            return response()->json(['status'=>true,'message'=>'Dish deleted Successfully',]);
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



}
