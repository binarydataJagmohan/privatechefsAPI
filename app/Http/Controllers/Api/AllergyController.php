<?php

namespace App\Http\Controllers\Api;

use App\Models\Allergy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;


class AllergyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getAllergyDetails()
    {
        try {

            $getallallergydetails = Allergy::where('status', 'active')->orderBy('id', 'desc')->get();
            return response()->json([
                'status' => true,
                'message' => "Allergy details fetched successfully",
                'data' => $getallallergydetails
            ], 200);
        } catch (\Exception $e) {
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
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:service_choices,service_name',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first(), 'error' => $validator->errors(), 'data' => '']);
        }
        try {
            $allergy = new Allergy();
            $allergy->user_id = $request->user_id;
            $allergy->allergy_name = $request->name;
            $allergy->description = $request->description;
            if ($request->hasFile('image')) {
                $randomNumber = mt_rand(1000000000, 9999999999);
                $imagePath = $request->file('image');
                $imageName = $randomNumber . $imagePath->getClientOriginalName();
                $imagePath->move('public/images/admin/allergy', $imageName);
                $allergy->image = $imageName;
            }
            $allergy->save();

            if ($allergy->save()) {
                $getallallergydetails = Allergy::where('status', 'active')->orderBy('id', 'desc')->get();

                return response()->json(['status' => true, 'message' => 'Allergy details has been save successfully', 'data' => $getallallergydetails]);
            } else {
                return response()->json(['status' => false, 'message' => 'Menu name already exit please choose different name', 'error' => '', 'data' => '']);
            }
        } catch (\Exception $e) {
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
    public function getSingleAllergyDetails(Allergy $allergy, $id)
    {
        try {

            $allergy = Allergy::find($id);

            if ($allergy) {
                return response()->json(['status' => true, 'message' => "Single Allergy Data fetch successfully", 'allergy' => $allergy], 200);
            } else {
                return response()->json(['status' => false, 'message' => "No Single Allergy data found"]);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateAllergy(Request $request, $id)
    {
        try {
            $allergy = Allergy::findOrFail($id);
            $allergy->allergy_name = $request->input('allergy_name');
            $allergy->description = $request->input('description');
            if ($request->hasFile('image')) {
                $randomNumber = mt_rand(1000000000, 9999999999);
                $imagePath = $request->file('image');
                $imageName = $randomNumber . $imagePath->getClientOriginalName();
                $imagePath->move('public/images/admin/allergy', $imageName);
                $allergy->image = $imageName;
            }
            $allergy->save();
            if ($allergy) {
            return response()->json(['status' => true, 'message' => 'Allergy updated successfully', 'data' => $allergy]);
            }else{
                return response()->json(['status' => false, 'message' => "Failed to update allergy. Please try again."]);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function allergyDelete($id)
    {
        try {
            $allergy = Allergy::find($id);
            if (!$allergy) {
                return response()->json(['status' => 'Allergy not found'], 404);
            }
            $allergy->status = 'deleted'; // Change the status to 'inactive'
            $allergy->save();
            return response()->json(['status' => true, 'message' => 'Allergy deleted', 'data' => $allergy]);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
}
