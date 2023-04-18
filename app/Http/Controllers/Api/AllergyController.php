<?php

namespace App\Http\Controllers\Api;

use App\Models\Allergy;
use Illuminate\Http\Request;


class AllergyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getAllergyDetails()
    {
        try{
        $getallallergydetails = Allergy::where('status','active')->orderBy('id','desc')->get();
         return response()->json([
            'status' => true,
            'message' => "Allergy details fetched successfully",
            'data' => $getallallergydetails
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
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function saveAllergy(Request $request)
    {

         try {

         $allergy = new Allergy();
         $allergy->allergy_name = $request->name;
         $allergy->description = $request->description;

         if ($request->hasFile('image')) {
                $randomNumber = mt_rand(1000000000, 9999999999);
                $imagePath = $request->file('image');
                $imageName = $randomNumber . $imagePath->getClientOriginalName();
                $imagePath->move('images/admin/allergy', $imageName);
                $allergy->image = $imageName;
            } 

            $allergy->save();

            if($allergy->save())
            {  
                $getallallergydetails = Allergy::where('status','active')->orderBy('id','desc')->get();

                return response()->json(['status' => true, 'message' => 'Allergy details has been save successfully', 'data' => $getallallergydetails]);
            }
            else
            {
                return response()->json(['status' => false, 'message' => 'Menu name already exit please choose different name', 'error' => '', 'data' => '']);
            }
        }
        catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
     }

    /**
     * Display the specified resource.
     */
    public function show(Allergy $allergy)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Allergy $allergy)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Allergy $allergy)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Allergy $allergy)
    {
        //
    }
}
