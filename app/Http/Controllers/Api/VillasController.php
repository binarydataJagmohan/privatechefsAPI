<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Villas;
use App\Models\VillaImages;
use Symfony\Component\HttpKernel\Exception\HttpException;

class VillasController extends Controller
{
    public function save_villa(Request $request)
    {
        try {
            $villa = new Villas();
            $villa->name = $request->input('name');
            $villa->email = $request->input('email');
            $villa->phone = $request->input('phone');
            $villa->address = $request->input('address');
            $villa->city = $request->input('city');
            $villa->state = $request->input('state');
            $villa->partner_owner = $request->input('partner_owner');
            $villa->capacity = $request->input('capacity');
            $villa->category = $request->input('category');
            $villa->price_per_day = $request->input('price_per_day');
            $villa->bedrooms = $request->input('bedrooms');
            $villa->bathrooms = $request->input('bathrooms');
            $villa->BBQ = $request->input('BBQ');
            $villa->type_of_stove = $request->input('type_of_stove');
            $villa->equipment = $request->input('equipment');
            $villa->consierge_phone = $request->input('consierge_phone');
            $villa->website = $request->input('website');
            $villa->facebook_link = $request->input('facebook_link');
            $villa->instagram_link = $request->input('instagram_link');
            $villa->twitter_link = $request->input('twitter_link');
            $villa->linkedin_link = $request->input('linkedin_link');
            $villa->youtube_link = $request->input('youtube_link');
            $villa->save();
    
            if ($request->hasFile('image')) {
                foreach ($request->file('image') as $image) {
                    $randomNumber = mt_rand(1000000000, 9999999999);
                    $imageName = $randomNumber . $image->getClientOriginalName();
                    $image->move('images/villas/images', $imageName);
                     $villa_img = new VillaImages();
                     $villa_img->villa_id = $villa->id;
                     $villa_img->image = $imageName;
                     $villa_img->save();
                }
            }
            return response()->json([
                'status' => true,
                'message' => 'Villa saved successfully.',
                'data' => $villa
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update_villas(Request $request)
    {
        try {
            $villas = Villas::find($request->id);
            $villas->name = $request->input('name');
            $villas->email = $request->input('email');
            $villas->phone = $request->input('phone');
            $villas->address = $request->input('address');
            $villas->city = $request->input('city');
            $villas->state = $request->input('state');
            $villas->partner_owner = $request->input('partner_owner');
            $villas->capacity = $request->input('capacity');
            $villas->category = $request->input('category');
            $villas->price_per_day = $request->input('price_per_day');
            $villas->bedrooms = $request->input('bedrooms');
            $villas->bathrooms = $request->input('bathrooms');
            $villas->BBQ = $request->input('BBQ');
            $villas->type_of_stove = $request->input('type_of_stove');
            $villas->equipment = $request->input('equipment');
            $villas->consierge_phone = $request->input('consierge_phone');
            $villas->website = $request->input('website');
            $villas->facebook_link = $request->input('facebook_link');
            $villas->instagram_link = $request->input('instagram_link');
            $villas->twitter_link = $request->input('twitter_link');
            $villas->linkedin_link = $request->input('linkedin_link');
            $villas->youtube_link = $request->input('youtube_link');
            $villas->save();


            if ($request->hasFile('image')) {

                $villa_img = VillaImages::where('villa_id',$request->id)->delete();

                foreach ($request->file('image') as $image) {
                    $randomNumber = mt_rand(1000000000, 9999999999);
                    $imageName = $randomNumber . $image->getClientOriginalName();
                    $image->move('images/villas/images', $imageName);
                     $villa_img = new VillaImages();
                     $villa_img->villa_id = $request->id;
                     $villa_img->image = $imageName;
                     $villa_img->save();

                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Villa updated successfully.',
            ]);
            
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    public function get_all_villas()
    {
        try {
            $villas_count = Villas::count();
            $villas = Villas::orderBy('id', 'DESC')->where('status','active')->get();
            return response()->json([
                'status' => true,
                'message' => 'All Villas fetched successfully.',
                'data' => $villas,
                'villas_count' => $villas_count
            ]);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    public function get_single_villas(Request $request)
    {
        try {

            $villas = Villas::find($request->id);
            $villas_img = VillaImages::where('villa_id', $request->id)->orderBy('id', 'DESC')->where('status','active')->get();

            if ($villas) {
                return response()->json([
                    'status' => true,
                    'message' => 'Single villa data fetched successfully.',
                    'data' => $villas,
                    'villaImg' =>  $villas_img
                ]);
            } else {
                return response()->json([
                    'status' => true,
                    'message' => 'There has been error for fetching the villa data',
                    'data' => ''
                ]);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    public function deleteVillas(Request $request)
    {
        try {
            $villa = Villas::find($request->id);
            if (!$villa) {
                return response()->json([
                    'status' => false,
                    'message' => 'Villa not found'
                ]);
            }
            $villa->status = 'deleted';
            $villa->save();
            VillaImages::where('villa_id', $request->id)->update([
                'status' => 'deleted'
            ]);
    
            return response()->json([
                'status' => true,
                'message' => 'Villa status changed to deleted successfully'
            ]);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
}
