<?php

namespace App\Http\Controllers\Api;

use App\Models\Booking;
use App\Models\BookingMeals;
use App\Models\User;
use App\Models\AppliedJobs;
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
                    $booking->lat = $request->lat;
                    $booking->lng = $request->lng;
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
                        $booking->lat = $request->lat;
                        $booking->lng = $request->lng;
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
            ->get();
            
        if (!$user) {
            return response()->json(['message' => 'Booking not found','status'=>true], 404);
        }
        return response()->json(['status' => true, 'message' => 'Data fetched', 'data' => $user]);
    }

    public function get_User_By_Booking_Id($id)
    {
      
        $booking = DB::select("
                SELECT 
                    users.name, 
                    users.id AS user_id, 
                    users.surname, 
                    users.email, 
                    users.phone, 
                    service_choices.service_name, 
                    bookings.notes, 
                    bookings.location, 
                    bookings.lat, 
                    bookings.lng, 
                    bookings.adults, 
                    bookings.childrens,
                    bookings.teens,
                    bookings.booking_status, 
                    booking_meals.category, 
                    GROUP_CONCAT(DISTINCT booking_meals.date) AS dates, 
                    MAX(booking_meals.created_at) AS latest_created_at, 
                    bookings.id AS booking_id, 
                    GROUP_CONCAT(DISTINCT cuisine.name) AS cuisines, 
                    GROUP_CONCAT(DISTINCT allergies.allergy_name) AS allergies
                FROM users
                INNER JOIN bookings ON users.id = bookings.user_id
                INNER JOIN booking_meals ON bookings.id = booking_meals.booking_id
                INNER JOIN service_choices ON service_choices.id = bookings.service_id
                LEFT JOIN (
                    SELECT 
                        SUBSTRING_INDEX(SUBSTRING_INDEX(bookings.cuisine_id, ',', numbers.n), ',', -1) AS cuisine_id,
                        bookings.id
                    FROM bookings
                    CROSS JOIN (
                        SELECT (a.N + b.N * 10 + 1) AS n
                        FROM (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) AS a
                        CROSS JOIN (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) AS b
                    ) AS numbers
                    WHERE numbers.n <= LENGTH(bookings.cuisine_id) - LENGTH(REPLACE(bookings.cuisine_id, ',', '')) + 1
                ) AS cuisine_seq ON bookings.id = cuisine_seq.id
                LEFT JOIN cuisine ON cuisine.id = cuisine_seq.cuisine_id
                LEFT JOIN (
                    SELECT 
                        SUBSTRING_INDEX(SUBSTRING_INDEX(bookings.allergies_id, ',', numbers.n), ',', -1) AS allergy_id,
                        bookings.id
                    FROM bookings
                    CROSS JOIN (
                        SELECT (a.N + b.N * 10 + 1) AS n
                        FROM (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) AS a
                        CROSS JOIN (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) AS b
                    ) AS numbers
                    WHERE numbers.n <= LENGTH(bookings.allergies_id) - LENGTH(REPLACE(bookings.allergies_id, ',', '')) + 1
                ) AS allergy_seq ON bookings.id = allergy_seq.id
                LEFT JOIN allergies ON allergies.id = allergy_seq.allergy_id
                WHERE booking_meals.booking_id = :booking_id
                GROUP BY 
                    users.name, 
                    users.id, 
                    users.surname, 
                    users.email, 
                    users.phone, 
                    bookings.notes, 
                    bookings.location, 
                    bookings.lat, 
                    bookings.lng, 
                    bookings.adults, 
                    bookings.childrens,
                    bookings.teens,
                    bookings.location, 
                    bookings.booking_status, 
                    booking_meals.category, 
                    bookings.id
                ORDER BY bookings.id DESC 
                LIMIT 1",
                ['booking_id' => $id]
            );

        $daysbooking = BookingMeals::where('status', 'active')->where('booking_id', $id)->get();

        $chefoffer = DB::table('users')
            ->join('applied_jobs', 'users.id', '=', 'applied_jobs.chef_id')
            ->leftJoin('menus', function($join) {
                $join->on(DB::raw("FIND_IN_SET(menus.id, applied_jobs.menu)"), '>', DB::raw('0'));
            })
            ->where('applied_jobs.booking_id', $id)
            ->select('users.name', 'users.id', 'users.surname', 'users.pic', 'applied_jobs.amount', DB::raw('GROUP_CONCAT(DISTINCT menus.menu_name SEPARATOR ",") AS menu_names'))
            ->groupBy('users.name', 'users.id', 'users.surname', 'users.pic', 'applied_jobs.amount')
            ->orderBy('applied_jobs.id', 'DESC')
            ->get();



        if ($booking) {
            return response()->json(['status' => true, 'message' => 'Booking Data fetched', 'booking' => $booking,'days_booking'=>$daysbooking,'chefoffer'=>$chefoffer]);
        }else {
            return response()->json(['status' => false, 'message' => 'There has been for saving the menu', 'error' => '', 'data' => '']);
        }   
        
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

    public function get_user_chef_by_booking($id)
    {
        try {

        $chefuserbookings = DB::table('users')
            ->join('bookings', 'users.id', '=', 'bookings.user_id')
            ->join('booking_meals', 'bookings.id', '=', 'booking_meals.booking_id')
            ->join('service_choices', 'service_choices.id', '=', 'bookings.service_id')
            ->leftJoin('applied_jobs', function($join) use ($id) {
                $join->on('bookings.id', '=', 'applied_jobs.booking_id')
                     ->where('applied_jobs.chef_id', '=', $id);
            })
            ->whereNull('applied_jobs.id')
            ->orWhereNull('applied_jobs.chef_id')
            ->select('users.name', 'users.id', 'users.surname', 'users.pic', 'bookings.location', 'bookings.booking_status', 'booking_meals.category', DB::raw('GROUP_CONCAT(booking_meals.date) AS dates'), DB::raw('MAX(booking_meals.created_at) AS latest_created_at'), 'bookings.id as booking_id')
            ->groupBy('users.name', 'users.id', 'users.surname', 'users.pic', 'bookings.location', 'bookings.booking_status', 'booking_meals.category', 'bookings.id')
            ->orderBy('bookings.id', 'DESC')
            ->get();

            if (!$chefuserbookings) {
                return response()->json(['message' => 'Booking not found','status'=>true], 404);
            }

            return response()->json(['status' => true, 'message' => 'Data fetched', 'data' => $chefuserbookings]);
            
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
            
        }
    }

    public function get_user_chef_filter_by_booking($id,$type)
    {
        try {

            $chefuserbookings = DB::table('users')
            ->join('bookings', 'users.id', '=', 'bookings.user_id')
            ->join('booking_meals', 'bookings.id', '=', 'booking_meals.booking_id')
            ->join('service_choices', 'service_choices.id', '=', 'bookings.service_id')
            ->leftJoin('applied_jobs', function($join) use ($id) {
                $join->on('bookings.id', '=', 'applied_jobs.booking_id')
                     ->where('applied_jobs.chef_id', '=', $id);
            })
            ->where(function($query) use ($type) {
                $query->where('bookings.booking_status', '=', $type);
            })
            ->where(function($query) {
                $query->whereNull('applied_jobs.id')
                      ->orWhereNull('applied_jobs.chef_id');
            })
            ->select('users.name', 'users.id', 'users.surname', 'users.pic', 'bookings.location', 'bookings.booking_status', 'booking_meals.category', DB::raw('GROUP_CONCAT(booking_meals.date) AS dates'), DB::raw('MAX(booking_meals.created_at) AS latest_created_at'), 'bookings.id as booking_id')
            ->groupBy('users.name', 'users.id', 'users.surname', 'users.pic', 'bookings.location', 'bookings.booking_status', 'booking_meals.category', 'bookings.id')
            ->orderBy('bookings.id', 'DESC')
            ->get();



            if (!$chefuserbookings) {
                return response()->json(['message' => 'Booking not found','status'=>true], 404);
            }

            return response()->json(['status' => true, 'message' => 'Data fetched', 'data' => $chefuserbookings]);
            
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
            
        }
    }
    public function get_User_Booking_id(Request $request)
    {

        $user = DB::table('users')
            ->join('bookings', 'users.id', '=', 'bookings.user_id')
            ->join('booking_meals', 'bookings.id', '=', 'booking_meals.booking_id')
            ->select('users.name', 'users.id', 'users.surname', 'users.pic', 'bookings.location', 'bookings.booking_status', 'booking_meals.category', DB::raw('GROUP_CONCAT(booking_meals.date) AS dates'), DB::raw('MAX(booking_meals.created_at) AS latest_created_at'), 'bookings.id as booking_id')
            ->groupBy('users.name', 'users.id', 'users.surname', 'users.pic', 'bookings.location', 'bookings.booking_status', 'booking_meals.category', 'bookings.id')->orderBy('bookings.id', 'DESC')
            ->where('users.id', $request->id)
            ->get();

        if (!$user) {
            return response()->json(['message' => 'Booking not found', 'status' => true], 404);
        }
        return response()->json(['status' => true, 'message' => 'Data fetched', 'data' => $user]);
    }

    public function save_chef_applied_booking_job(Request $request)
    {

        $booking = new AppliedJobs();
        $booking->booking_id = $request->booking_id;
        $booking->chef_id = $request->chef_id;
        $booking->amount = $request->amount;
        $booking->menu = $request->menu;
        $appliedJobs  = $booking->save();

        if ($appliedJobs) {
            return response()->json(['message' => 'Booking has been applied save successfully', 'status' => true]);
        }else {
            return response()->json(['status' => true, 'message' => 'There has been error in saving the booking', ]);
        } 
    }

    public function get_chef_applied_booking(Request $request,$id)
    {

        try {

            $chefuserbookings = DB::table('users')
            ->join('bookings', 'users.id', '=', 'bookings.user_id')
            ->join('booking_meals', 'bookings.id', '=', 'booking_meals.booking_id')
            ->join('service_choices', 'service_choices.id', '=', 'bookings.service_id')
            ->join('applied_jobs', 'bookings.id', '=', 'applied_jobs.booking_id')
            ->leftJoin('menus', function($join) {
                $join->on(DB::raw("FIND_IN_SET(menus.id, applied_jobs.menu)"), '>', DB::raw('0'));
            })
            ->where('applied_jobs.chef_id', $id)
            ->select('users.name', 'users.id', 'users.surname', 'users.pic', 'bookings.location', 'bookings.booking_status', 'booking_meals.category', DB::raw('GROUP_CONCAT(booking_meals.date) AS dates'), DB::raw('MAX(booking_meals.created_at) AS latest_created_at'), 'bookings.id as booking_id', 'applied_jobs.status as applied_jobs_status','amount', DB::raw('GROUP_CONCAT(DISTINCT menus.menu_name SEPARATOR ",") AS menu_names'))
            ->groupBy('users.name', 'users.id', 'users.surname', 'users.pic', 'bookings.location', 'bookings.booking_status', 'booking_meals.category', 'bookings.id', 'applied_jobs.status')
            ->orderBy('applied_jobs.id', 'DESC')
            ->get();



            if (!$chefuserbookings) {
                return response()->json(['message' => 'Booking not found','status'=>true], 404);
            }

            return response()->json(['status' => true, 'message' => 'Data fetched', 'data' => $chefuserbookings]);
            
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
            
        }
    }

    public function get_chef_applied_filter_by_booking($id,$type)
    {
        try {

            $chefuserbookings = DB::table('users')
            ->join('bookings', 'users.id', '=', 'bookings.user_id')
            ->join('booking_meals', 'bookings.id', '=', 'booking_meals.booking_id')
            ->join('service_choices', 'service_choices.id', '=', 'bookings.service_id')
            ->join('applied_jobs', 'bookings.id', '=', 'applied_jobs.booking_id')
            ->leftJoin('menus', function($join) {
                $join->on(DB::raw("FIND_IN_SET(menus.id, applied_jobs.menu)"), '>', DB::raw('0'));
            })
            ->where('applied_jobs.chef_id', $id)
            ->select('users.name', 'users.id', 'users.surname', 'users.pic', 'bookings.location', 'bookings.booking_status', 'booking_meals.category', DB::raw('GROUP_CONCAT(booking_meals.date) AS dates'), DB::raw('MAX(booking_meals.created_at) AS latest_created_at'), 'bookings.id as booking_id', 'applied_jobs.status as applied_jobs_status','amount', DB::raw('GROUP_CONCAT(DISTINCT menus.menu_name SEPARATOR ",") AS menu_names'))
            ->groupBy('users.name', 'users.id', 'users.surname', 'users.pic', 'bookings.location', 'bookings.booking_status', 'booking_meals.category', 'bookings.id', 'applied_jobs.status')->where('bookings.booking_status',$type)
            ->orderBy('applied_jobs.id', 'DESC')
            ->get();

            if (!$chefuserbookings) {
                return response()->json(['message' => 'Booking not found','status'=>true], 404);
            }

            return response()->json(['status' => true, 'message' => 'Data fetched', 'data' => $chefuserbookings]);
            
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
            
        }
    }

    public function get_current_user_by_booking($id)
    {
        try {

            $userbookings = DB::table('users')
                ->join('bookings', 'users.id', '=', 'bookings.user_id')
                ->join('booking_meals', 'bookings.id', '=', 'booking_meals.booking_id')
                ->join('service_choices', 'service_choices.id', '=', 'bookings.service_id')
                
                ->select('users.name', 'users.id', 'users.surname', 'users.pic', 'bookings.location', 'bookings.booking_status', 'booking_meals.category', DB::raw('GROUP_CONCAT(booking_meals.date) AS dates'), DB::raw('MAX(booking_meals.created_at) AS latest_created_at'), 'bookings.id as booking_id')
                ->groupBy('users.name', 'users.id', 'users.surname', 'users.pic', 'bookings.location', 'bookings.booking_status', 'booking_meals.category', 'bookings.id')
                ->orderBy('bookings.id', 'DESC')->where('bookings.user_id',$id)
                ->get();

            if (!$userbookings) {
                return response()->json(['message' => 'Booking not found','status'=>true], 404);
            }

            return response()->json(['status' => true, 'message' => 'Data fetched', 'data' => $userbookings]);
            
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
            
        }
    }

     public function get_user_filter_by_booking($id,$type)
    {
        try {

            $userbookings = DB::table('users')
                ->join('bookings', 'users.id', '=', 'bookings.user_id')
                ->join('booking_meals', 'bookings.id', '=', 'booking_meals.booking_id')
                ->join('service_choices', 'service_choices.id', '=', 'bookings.service_id')
                
                ->select('users.name', 'users.id', 'users.surname', 'users.pic', 'bookings.location', 'bookings.booking_status', 'booking_meals.category', DB::raw('GROUP_CONCAT(booking_meals.date) AS dates'), DB::raw('MAX(booking_meals.created_at) AS latest_created_at'), 'bookings.id as booking_id')
                ->groupBy('users.name', 'users.id', 'users.surname', 'users.pic', 'bookings.location', 'bookings.booking_status', 'booking_meals.category', 'bookings.id')
                ->orderBy('bookings.id', 'DESC')->where('bookings.user_id',$id)->where('bookings.booking_status', '=', $type)
                ->get();

            if (!$userbookings) {
                return response()->json(['message' => 'Booking not found','status'=>true], 404);
            }

            return response()->json(['status' => true, 'message' => 'Data fetched', 'data' => $userbookings]);
            
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
            
        }
    }

    public function updated_applied_booking_job(Request $request)
    {

        $updatebooking = AppliedJobs::where('booking_id',$request->booking_id)->where('chef_id',$request->chef_id)->update([
            'status' => 'hired',
        ]);;
       
        if ($appliedJobs) {
            return response()->json(['message' => 'Booking has been applied save successfully', 'status' => true]);
        }else {
            return response()->json(['status' => true, 'message' => 'There has been error in saving the booking', ]);
        } 
    }
}
