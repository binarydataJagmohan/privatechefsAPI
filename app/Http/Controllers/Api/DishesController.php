<?php

namespace App\Http\Controllers\Api;
use App\Models\Dishes;

use Illuminate\Http\Request;

class DishesController extends Controller
{
    public function getDishes()
    {
        try{
            
        $categories = Dishes::orderBy('id','ASC')->get();
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


}
