<?php

namespace App\Http\Controllers\Api;

use App\Models\Cuisine;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

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

}
