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
            
        $getservicedetails = ServiceChoice::where('status','active')->get();
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
                return response()->json(['status' => true, 'message' => 'Service details has been save successfully']);
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
    public function edit(ServiveChoice $serviveChoice)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ServiveChoice $serviveChoice)
    {
        //
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
