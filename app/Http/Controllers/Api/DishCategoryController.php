<?php

namespace App\Http\Controllers\Api;

use App\Models\DishCategory;
use Illuminate\Http\Request;

class DishCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getDishecategory()
    {
        try{
            
        $categories = DishCategory::orderBy('id','ASC')->get();
         return response()->json([
            'status' => true,
            'message' => "categories details fetched successfully",
            'data' => $categories
        ], 200);
     }
     catch (\Exception $e) {
        throw new HttpException(500, $e->getMessage());
    }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(DishCategory $dishCategory)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DishCategory $dishCategory)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DishCategory $dishCategory)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DishCategory $dishCategory)
    {
        //
    }
}
