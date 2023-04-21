<?php

namespace App\Http\Controllers\Api;

use App\Models\ServiceChoice;
use Illuminate\Http\Request;

class ServiceChoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getServiceDetails()
    {
        try{
            
        $getservicedetails = ServiceChoice::where('status','active')->orderBy('id','desc')->get();
         return response()->json([
            'status' => true,
            'message' => "Service details fetched successfully",
            'data' => $getservicedetails
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
    public function saveService(Request $request)
    {
        try {

         $service = new ServiceChoice();
         $service->user_id = $request->user_id;
         $service->service_name = $request->name;
         $service->description = $request->description;

         if ($request->hasFile('image')) {
                $randomNumber = mt_rand(1000000000, 9999999999);
                $imagePath = $request->file('image');
                $imageName = $randomNumber . $imagePath->getClientOriginalName();
                $imagePath->move('images/admin/service', $imageName);
                $service->image = $imageName;
            } 

            $service->save();

            if($service->save())
            {  
                 $getallservicedetails = ServiceChoice::where('status','active')->orderBy('id','desc')->get();

                return response()->json(['status' => true, 'message' => 'Service details has been save successfully','data' => $getallservicedetails]);
            }
            else
            {
                return response()->json(['status' => false, 'message' => 'Service name already exit please choose different name', 'error' => '', 'data' => '']);
            }
        }
        catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ServiveChoice $serviveChoice)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function getSingleServiceDetail(Request $request,$id)
    {
        try {

            $service = ServiceChoice::find($id);
        
            if($service){
                return response()->json(['status'=>true,'message' => "Single Service Data fetch successfully", 'data' => $service], 200);
            }else {
                return response()->json(['status'=>false,'message' => "No Single Service data found"]);
            }
            
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function serviceUpdate(Request $request,$id)
    {
        try {
    $service = ServiceChoice::findOrFail($id);
    $service->service_name = $request->input('service_name');
    $service->description = $request->input('description');
    if ($request->hasFile('image')) {
        $randomNumber = mt_rand(1000000000, 9999999999);
        $imagePath = $request->file('image');
        $imageName = $randomNumber . $imagePath->getClientOriginalName();
        $imagePath->move('images/admin/service', $imageName);
        $service->image = $imageName;
    } 
    $service->save();

    return response()->json(['message' => 'Allergy updated successfully', 'data' => $service]);
} catch (\Exception $e) {
    throw new HttpException(500, $e->getMessage());
}
    }

    /**
     * Remove the specified resource from storage.
     */
    public function serviceDelete($id)
    {
        try{
        $service = ServiceChoice::find($id);
        if (!$service) 
        {
        return response()->json(['status' => 'error','message'=>'service not found'], 404);
        }
       $service->delete();
       return response()->json(['status'=> true, 'message' => 'service deleted','data'=>$service]);
       }
        catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
}
