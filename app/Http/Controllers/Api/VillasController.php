<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Villas;
use App\Models\VillaImages;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\DB;

class VillasController extends Controller
{
    public function save_villa(Request $request)
    {
        try {
            $villa = new Villas();
            $villa->name = $request->input('name');
            $villa->user_id = $request->input('user_id');
            $villa->email = $request->input('email');
            $villa->phone = $request->input('phone');
            $villa->address = $request->input('address');
            $villa->lat = $request->input('lat');
            $villa->lng = $request->input('lng');
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
            throw new HttpException(500, $e->getMessage());
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
            $villas->lat = $request->input('lat');
            $villas->lng = $request->input('lng');
            $villas->save();


            if ($request->hasFile('image')) {

                // $villa_img = VillaImages::where('villa_id',$request->id)->delete();

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
            $villas = Villas::orderBy('id', 'DESC')->where('status','active')->get();
            return response()->json([
                'status' => true,
                'message' => 'All Villas fetched successfully.',
                'data' => $villas
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
    public function get_concierge_villas(Request $request)
    {
        try {
            $villas = Villas::where('user_id',$request->id)->orderBy('id', 'DESC')->where('status','!=','deleted')->get();
            return response()->json([
                'status' => true,
                'message' => 'All Villas fetched successfully.',
                'data' => $villas
            ]);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

       public function AssignedVillaByBooking(Request $request)
    {
        try {
            if ($request->booking_id) {
                $booking = Booking::where('id', $request->booking_id)->update([
                    'assigned_to_villa_id' => $request->assigned_to_villa_id,
                ]);

                if ($booking) {
                    return response()->json(['status' => true, 'message' => 'Villa has been assigned successfully']);
                } else {
                    return response()->json(['status' => false, 'message' => 'There has been an error in assigning the villa']);
                }
            } else {
                return response()->json(['status' => false, 'message' => 'Booking id not found']);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }


      public function get_admin_villa_by_booking(Request $request,$id)
    {
        try {
            $adminchefuserbookings = DB::table('users as u')
                ->join('bookings as b', 'u.id', '=', 'b.user_id')
                ->join('booking_meals as bm', 'b.id', '=', 'bm.booking_id')
                ->join('service_choices as sc', 'sc.id', '=', 'b.service_id')
                ->leftJoin('applied_jobs as aj', function ($join) {
                    $join->on('b.id', '=', 'aj.booking_id')
                        ->where('aj.status', '=', 'hired');
                })
                ->whereNull('aj.booking_id')
                ->where('b.status', '=', 'active') // Add the condition here
                ->groupBy(
                    'b.name',
                    'b.assigned_to_user_id',
                    'b.payment_status',
                    'u.id',
                    'b.surname',
                    'u.pic',
                    'b.location',
                    'b.booking_status',
                    'bm.category',
                    'b.id',
                    'aj.booking_id',
                    'aj.status',
                    'aj.chef_id',
                    'b.status',
                    'b.assigned_to_villa_id'
                    
                )
                ->select(
                    'b.name',
                    'b.payment_status',
                    'b.assigned_to_user_id',
                    'b.assigned_to_villa_id',
                    'u.id',
                    'b.surname',
                    'u.pic',
                    'b.location',
                    'b.booking_status',
                    'bm.category',
                    DB::raw('GROUP_CONCAT(bm.date) AS dates'),
                    DB::raw('MAX(bm.created_at) AS latest_created_at'),
                    'b.id AS booking_id',
                    'aj.status AS applied_jobs_status',
                    'aj.chef_id',
                    'b.status'
                )
                ->where('b.status', '!=', 'deleted')
                ->where('b.assigned_to_villa_id',$id)
                ->orderBy('b.id', 'DESC')
                ->get();
            if (!$adminchefuserbookings) {
                return response()->json(['message' => 'Booking not found', 'status' => true], 404);
            }
            // foreach ($adminchefuserbookings as $booking) {
            //     $dates = explode(',', $booking->dates);
            //     $bookingStatus = 'Expired';
            //     foreach ($dates as $date) {
            //         $dateObject = new \DateTime($date);
            //         $today = new \DateTime();
            //         $dateObject->setTime(0, 0, 0);
            //         $today->setTime(0, 0, 0);
            //         if ($dateObject >= $today) {
            //             $bookingStatus = 'Upcoming';
            //             break;
            //         }
            //     }
            //     $booking->booking_status = $bookingStatus;
            // }
            foreach ($adminchefuserbookings as $booking) {
                $dates = explode(',', $booking->dates);
                // $bookingStatus = 'Expired';
                foreach ($dates as $date) {
                    $dateObject = new \DateTime($date);
                    $today = new \DateTime();

                    $dateObject->setTime(0, 0, 0);
                    $today->setTime(0, 0, 0);

                    if ($dateObject >= $today) {
                        if ($dateObject->format('Y-m-d') === $today->format('Y-m-d')) {
                            $bookingStatus = 'Today';
                        } else {
                            $bookingStatus = 'Upcoming';
                        }
                        break;
                    } else {
                        $bookingStatus = 'Expired';
                    }
                }
                $booking->booking_status = $bookingStatus;
            }
            return response()->json(['status' => true, 'message' => 'Data fetched', 'data' => $adminchefuserbookings]);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
}
