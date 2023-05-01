<?php

namespace App\Http\Controllers\Api;

use App\Models\Booking;
use App\Models\BookingMeals;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use DB;

class BookingController extends Controller
{
    public function save_booking(Request $request)
    {
        try {

            if ($request->user_id) {

                $user = User::find($request->user_id);
                $user->name = $request->name;
                $user->surname = $request->surname;
                $user->email = $request->email;
                $user->phone = $request->phone;
                $savedata = $user->save();

                if ($savedata) {

                    $booking = new Booking();
                    $booking->user_id = $request->user_id;
                    $booking->service_id = $request->service_id;
                    $booking->cuisine_id = implode(",", $request->cuisine_id);

                    if ($request->allergies_id) {
                        $booking->allergies_id = implode(",", $request->allergies_id);
                    }

                    $booking->notes = $request->notes;
                    $booking->location = $request->location;
                    $booking->adults = $request->adults ? $request->adults : 0;
                    $booking->childrens = $request->childrens ? $request->childrens : 0;
                    $booking->teens = $request->teens ? $request->teens : 0;

                    $savebookingdata = $booking->save();

                    if ($savebookingdata) {

                        if ($request->category == 'onetime') {

                            $dateString = $request->date;
                            $timezoneStart = strpos($dateString, '(');
                            $timezoneEnd = strpos($dateString, ')');
                            $dateString = substr_replace($dateString, '', $timezoneStart, $timezoneEnd - $timezoneStart + 1);
                            $date = Carbon::parse($dateString);
                            $formattedDate = $date->format('Y-m-d');


                            $bookingmeals = new BookingMeals();
                            $bookingmeals->booking_id = $booking->id;
                            $bookingmeals->date = $formattedDate;
                            $bookingmeals->breakfast = $request->meals['breakfast'] == '1' ? 'yes' : 'no';
                            $bookingmeals->lunch = $request->meals['lunch'] == '1' ? 'yes' : 'no';
                            $bookingmeals->dinner = $request->meals['dinner'] == '1' ? 'yes' : 'no';
                            $bookingmeals->category = $request->category;
                            $savebookingmeals  = $bookingmeals->save();
                        } else {

                            foreach ($request->meals as $meals) {

                                $bookingmeals = new BookingMeals();
                                $bookingmeals->booking_id = $booking->id;
                                $bookingmeals->date = \Carbon\Carbon::createFromFormat('d/m/Y', $meals['date'])->format('Y-m-d');
                                $bookingmeals->breakfast = $meals['breakfast'] == '1' ? 'yes' : 'no';
                                $bookingmeals->lunch = $meals['lunch'] == '1' ? 'yes' : 'no';
                                $bookingmeals->dinner = $meals['dinner'] == '1' ? 'yes' : 'no';
                                $bookingmeals->category = $request->category;
                                $savebookingmeals  = $bookingmeals->save();
                            }
                        }
                    }

                    return response()->json(['status' => true, 'message' => "booking done successfully", 'bookingid' => $booking->id], 200);
                }
            } else {


                $checkemail  = User::where('email', $request->email)->count();

                if ($checkemail <= 0) {


                    $password = Str::random(10);
                    $user = new User();
                    $user->name = $request->name;
                    $user->surname = $request->surname;
                    $user->email = $request->email;
                    $user->phone = $request->phone;
                    $user->password = Hash::make($password);
                    $user->view_password = $password;
                    $user->role = 'user';
                    $savedata = $user->save();

                    if ($savedata) {

                        $booking = new Booking();
                        $booking->user_id = $user->id;
                        $booking->service_id = $request->service_id;
                        $booking->cuisine_id = implode(",", $request->cuisine_id);

                        if ($request->allergies_id) {
                            $booking->allergies_id = implode(",", $request->allergies_id);
                        }

                        $booking->notes = $request->notes;
                        $booking->location = $request->location;
                        $booking->adults = $request->adults ? $request->adults : 0;
                        $booking->childrens = $request->childrens ? $request->childrens : 0;
                        $booking->teens = $request->teens ? $request->teens : 0;

                        $savebookingdata = $booking->save();


                        if ($savebookingdata) {

                            if ($request->category == 'onetime') {

                                $dateString = $request->date;
                                $timezoneStart = strpos($dateString, '(');
                                $timezoneEnd = strpos($dateString, ')');
                                $dateString = substr_replace($dateString, '', $timezoneStart, $timezoneEnd - $timezoneStart + 1);
                                $date = Carbon::parse($dateString);
                                $formattedDate = $date->format('Y-m-d');


                                $bookingmeals = new BookingMeals();
                                $bookingmeals->booking_id = $booking->id;
                                $bookingmeals->date = $formattedDate;
                                $bookingmeals->breakfast = $request->meals['breakfast'] == '1' ? 'yes' : 'no';
                                $bookingmeals->lunch = $request->meals['lunch'] == '1' ? 'yes' : 'no';
                                $bookingmeals->dinner = $request->meals['dinner'] == '1' ? 'yes' : 'no';
                                $bookingmeals->category = $request->category;
                                $savebookingmeals  = $bookingmeals->save();
                            } else {

                                foreach ($request->meals as $meals) {

                                    $bookingmeals = new BookingMeals();
                                    $bookingmeals->booking_id = $booking->id;
                                    $bookingmeals->date = \Carbon\Carbon::createFromFormat('d/m/Y', $meals['date'])->format('Y-m-d');
                                    $bookingmeals->breakfast = $meals['breakfast'] == '1' ? 'yes' : 'no';
                                    $bookingmeals->lunch = $meals['lunch'] == '1' ? 'yes' : 'no';
                                    $bookingmeals->dinner = $meals['dinner'] == '1' ? 'yes' : 'no';
                                    $bookingmeals->category = $request->category;
                                    $savebookingmeals  = $bookingmeals->save();
                                }
                            }
                        }

                        return response()->json(['status' => true, 'message' => "booking done successfully", 'bookingid' => $booking->id], 200);
                    }
                } else {

                    return response()->json(['status' => false, 'message' => "Email already exits", 'data' => ""], 200);
                }
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

   public function get_User_By_Booking()
{
    $user = DB::table('users')
        ->join('bookings', 'users.id', '=', 'bookings.user_id')
        ->join('booking_meals', 'bookings.id', '=', 'booking_meals.booking_id')
        ->join('service_choices','service_choices.id','=','bookings.service_id')
        ->select('users.name','users.id','users.surname','users.address','users.email','users.phone','bookings.booking_status','booking_meals.category','booking_meals.date','bookings.adults','bookings.teens','bookings.childrens','booking_meals.created_at','bookings.service_id','service_choices.service_name')
        //->groupBy('users.id')
        ->get();
        
    if (!$user) {
        return response()->json(['message' => 'Booking not found','status'=>true], 404);
    }

    public function get_User_By_Booking_Id($id)
    {
        $user = DB::table('users')
            ->join('bookings', 'users.id', '=', 'bookings.user_id')
            ->join('booking_meals', 'bookings.id', '=', 'booking_meals.booking_id')
            ->select('users.name', 'users.id', 'users.surname', 'users.address', 'users.email', 'users.phone', 'bookings.booking_status', 'booking_meals.category', 'booking_meals.date', 'bookings.adults', 'bookings.teens', 'bookings.childrens', 'booking_meals.created_at', 'bookings.id')
            ->where('users.id', '=', $id)
            ->first();

        if (!$user) {
            return response()->json(['message' => 'Booking not found', 'status' => true], 404);
        }

        return response()->json(['status' => true, 'message' => 'Data fetched', 'data' => $user]);
    }
    public function get_all_booking()
    {
        try {
            $bookings = Booking::where('status', 'active')->get();
            return response()->json([
                'status' => true,
                'message' => 'All Bookings fetched successfully.',
                'data' => $bookings
            ],200);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Booking not found',
                'data' => ''
            ], 404);
        }
    }
}
