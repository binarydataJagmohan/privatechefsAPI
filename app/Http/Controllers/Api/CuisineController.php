<?php

namespace App\Http\Controllers\Api;

use App\Models\Cuisine;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\Validator;

class CuisineController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function get_all_cuisine()
    {
        try{
            
        $getallcuisinedetails = Cuisine::where('status','active')->orderBy('id','desc')->get();
         return response()->json([
            'status' => true,
            'message' => "Cuisine details fetched successfully",
            'data' => $getallcuisinedetails
        ], 200);
            }
     catch (\Exception $e) {
        throw new HttpException(500, $e->getMessage());
    }

    }

    public function save_cuisine(Request $request)
    {

        $validator = Validator::make($request->all(), [
        'name' => 'required|string|unique:cuisine,name|max:50|',
        'description' => 'required|string|max:500',
        //'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
       ]);

        if ($validator->fails()) {
        return response()->json(['status' => false, 'message' => $validator->errors()->first(), 'error' => $validator->errors(), 'data' => '']);
    }

         try {


         $cuisine = new Cuisine();
         $cuisine->user_id = $request->user_id;
         $cuisine->name = $request->name;
         $cuisine->description = $request->description;

         if ($request->hasFile('image')) {
                $randomNumber = mt_rand(1000000000, 9999999999);
                $imagePath = $request->file('image');
                $imageName = $randomNumber . $imagePath->getClientOriginalName();
                $imagePath->move('images/chef/cuisine', $imageName);
                $cuisine->image = $imageName;
            } 

            $cuisine->save();

            if($cuisine->save())
            {  
                $getallcuisinedetails = Cuisine::where('status','active')->orderBy('id','desc')->get();

                return response()->json(['status' => true, 'message' => 'Cuisine details has been save successfully', 'data' => $getallcuisinedetails]);
            }
            else
            {
                return response()->json(['status' => false, 'message' => 'Cuisine name already exit please choose different name', 'error' => '', 'data' => '']);
            }
        }
        catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
     }

     public function cuisine_delete($id)
    {
        try{
        $cuisine = Cuisine::find($id);
        if (!$cuisine) 
        {
        return response()->json(['status' => 'Cuisine not found'], 404);
        }
        $cuisine->status = 'deleted'; // Change the status to 'inactive'
        $cuisine->save();
       return response()->json(['status'=> true, 'message' => 'Cuisine deleted','data'=>$cuisine]);
       }
        catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
   }

    public function get_single_cuisine($id)
    {
         try {

            $cuisine = Cuisine::find($id);
        
            if($cuisine){
                return response()->json(['status'=>true,'message' => "Single cuisine Data fetch successfully", 'data' => $cuisine], 200);
            }else {
                return response()->json(['status'=>false,'message' => "No Single cuisine data found"]);
            }
            
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

      public function update_cuisine(Request $request, $id)
{
    try {
    $cuisine = Cuisine::findOrFail($id);
    $cuisine->name = $request->input('name');
    $cuisine->description = $request->input('description');
    if ($request->hasFile('image')) {
        $randomNumber = mt_rand(1000000000, 9999999999);
        $imagePath = $request->file('image');
        $imageName = $randomNumber . $imagePath->getClientOriginalName();
        $imagePath->move('images/chef/cuisine', $imageName);
        $cuisine->image = $imageName;
    } 
    $cuisine->save();

    return response()->json(['message' => 'cuisine updated successfully', 'data' => $cuisine]);
} catch (\Exception $e) {
    throw new HttpException(500, $e->getMessage());
}
}

}
