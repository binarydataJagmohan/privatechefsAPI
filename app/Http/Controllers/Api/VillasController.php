<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Villas;
use Symfony\Component\HttpKernel\Exception\HttpException;

class VillasController extends Controller
{
    public function save_villa(Request $request)
    {
        try {
            $villas = new Villas;
            $villas->name = $request->input('name');
            $villas->address = $request->input('address');
            $villas->map_location = $request->input('map_location');
            $villas->save();

            return response()->json([
                'success' => true,
                'message' => 'Villa saved successfully.',
                'data' => $villas
            ]);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    public function update_villas(Request $request)
    {
        try {
            $villas = Villas::find($request->id);
            $villas->name = $request->input('name');
            $villas->address = $request->input('address');
            $villas->map_location = $request->input('map_location');
            $villas->save();

            return response()->json([
                'success' => true,
                'message' => 'Villa updated successfully.',
                'data' => $villas
            ]);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function get_all_villas()
    {
        try {
            $villas = Villas::all();
            return response()->json([
                'success' => true,
                'message' => 'All Villas fetched successfully.',
                'data' => $villas
            ]);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
