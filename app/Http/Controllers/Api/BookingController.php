<?php

namespace App\Http\Controllers\Api;

use App\Models\Booking;
use App\Models\BookingMeals;
use App\Models\User;
use App\Models\AppliedJobs;
use App\Models\Notification;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Mail;

class BookingController extends Controller
{
    public function save_booking(Request $request)
    {
        try {

            if ($request->user_id) {

                $booking = new Booking();
                $booking->user_id = $request->user_id;
                $booking->service_id = $request->service_id;
                $booking->cuisine_id = implode(",", $request->cuisine_id);

                if ($request->allergies_id) {
                    $booking->allergies_id = implode(",", $request->allergies_id);
                }

                $booking->name = $request->name;
                $booking->surname = $request->surname;
                $booking->email = $request->email;
                $booking->phone = $request->phone;
                $booking->notes = $request->notes;
                $booking->location = $request->address;
                $booking->lat = $request->lat;
                $booking->lng = $request->lng;
                $booking->adults = $request->adults ? $request->adults : 0;
                $booking->childrens = $request->childrens ? $request->childrens : 0;
                $booking->teens = $request->teens ? $request->teens : 0;

                $savebookingdata = $booking->save();

                if ($savebookingdata) {

                    $earthRadius = 3959;
                    $radius = 60;

                    $chefs = DB::table('users')
                        ->join('chef_location', 'users.id', '=', 'chef_location.user_id')
                        ->select('users.email', 'users.name', 'chef_location.lat', 'chef_location.user_id', 'chef_location.lng', 'chef_location.address')
                        ->selectRaw(
                            "($earthRadius * ACOS(
            COS(RADIANS($request->lat)) * COS(RADIANS(chef_location.lat)) * COS(RADIANS(chef_location.lng) - RADIANS($request->lng)) +
            SIN(RADIANS($request->lat)) * SIN(RADIANS(chef_location.lat))
        )) AS distance"
                        )
                        ->where('chef_location.status', 'active')
                        ->having('distance', '<=', $radius)
                        ->orderBy('distance')
                        ->get();
                    //return $chefs;

                    foreach ($chefs as $chef) {
                        $data = [
                            'name'   => $chef->name,
                            'email'   => $chef->email,
                        ];
                        Mail::send('emails.chefLocation', ["data" => $data], function ($message) use ($data) {
                            $message->from('dev3.bdpl@gmail.com', "Private Chef");
                            $message->subject('Location Notification');
                            $message->to($data['email']);
                        });

                        $notification = new Notification();
                        $notification->notify_to = $chef->user_id;
                        $notification->description = "A  $request->name has just made a booking within your area. Please make necessary arrangements and ensure a seamless experience for them. Thank you for your prompt attention.";
                        $notification->type = 'location_notification';
                        $notification->save();
                    }

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
                        $booking->name = $request->name;
                        $booking->surname = $request->surname;
                        $booking->email = $request->email;
                        $booking->phone = $request->phone;
                        $booking->notes = $request->notes;
                        $booking->location = $request->address;
                        $booking->lat = $request->lat;
                        $booking->lng = $request->lng;
                        $booking->adults = $request->adults ? $request->adults : 0;
                        $booking->childrens = $request->childrens ? $request->childrens : 0;
                        $booking->teens = $request->teens ? $request->teens : 0;

                        $savebookingdata = $booking->save();


                        if ($savebookingdata) {

                            $earthRadius = 3959;
                            $radius = 60;

                            $chefs = DB::table('users')
                                ->join('chef_location', 'users.id', '=', 'chef_location.user_id')
                                ->select('users.email', 'users.name', 'chef_location.user_id', 'chef_location.lat', 'chef_location.lng', 'chef_location.address')
                                ->selectRaw(
                                    "($earthRadius * ACOS(
            COS(RADIANS($request->lat)) * COS(RADIANS(chef_location.lat)) * COS(RADIANS(chef_location.lng) - RADIANS($request->lng)) +
            SIN(RADIANS($request->lat)) * SIN(RADIANS(chef_location.lat))
        )) AS distance"
                                )
                                ->where('chef_location.status', 'active')
                                ->having('distance', '<=', $radius)
                                ->orderBy('distance')
                                ->get();

                            //return $chefs;

                            foreach ($chefs as $chef) {
                                $data = [
                                    'name'   => $chef->name,
                                    'email'   => $chef->email,
                                ];
                                Mail::send('emails.chefLocation', ["data" => $data], function ($message) use ($data) {
                                    $message->from('dev3.bdpl@gmail.com', "Private Chef");
                                    $message->subject('Location Notification');
                                    $message->to($data['email']);
                                });

                                $notification = new Notification();
                                $notification->notify_to = $chef->user_id;
                                $notification->description = "A $request->name has just made a booking within your area. Please make necessary arrangements and ensure a seamless experience for them. Thank you for your prompt attention.";
                                $notification->type = 'location_notification';
                                $notification->save();
                            }


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

                        $data = [
                            'name'   => $user->name,
                            'password' => $password,
                            'email'   => $user->email,
                        ];

                        Mail::send('emails.loginDetails', ["data" => $data], function ($message) use ($data) {
                            $message->from('dev3.bdpl@gmail.com', "Private Chef");
                            $message->subject(' Your Account Password for Private Chef');
                            $message->to($data['email']);
                        });

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
            ->join('service_choices', 'service_choices.id', '=', 'bookings.service_id')
            ->select('users.name', 'users.id', 'users.surname', 'users.address', 'users.email', 'users.phone', 'bookings.booking_status', 'booking_meals.category', 'booking_meals.date', 'bookings.adults', 'bookings.teens', 'bookings.childrens', 'booking_meals.created_at', 'bookings.service_id', 'service_choices.service_name')
            ->get();

        if (!$user) {
            return response()->json(['message' => 'Booking not found', 'status' => true], 404);
        }
        return response()->json(['status' => true, 'message' => 'Data fetched', 'data' => $user]);
    }

    public function get_User_By_Booking_Id($id)
    {

        $booking = DB::select(
            "
                SELECT 
                    bookings.name, 
                    users.id AS user_id, 
                    bookings.surname, 
                    bookings.email, 
                    bookings.phone, 
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
                    bookings.name, 
                    users.id, 
                    bookings.surname, 
                    bookings.email, 
                    bookings.phone, 
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
            ->leftJoin('menus', function ($join) {
                $join->on(DB::raw("FIND_IN_SET(menus.id, applied_jobs.menu)"), '>', DB::raw('0'));
            })
            ->where('applied_jobs.booking_id', $id)
            ->select('users.name', 'users.id', 'users.surname', 'users.pic', 'applied_jobs.amount', 'applied_jobs.client_amount', 'applied_jobs.admin_amount', 'applied_jobs.user_show', DB::raw('GROUP_CONCAT(DISTINCT menus.menu_name SEPARATOR ",") AS menu_names'))
            ->groupBy('users.name', 'users.id', 'users.surname', 'users.pic', 'applied_jobs.amount', 'applied_jobs.client_amount', 'applied_jobs.admin_amount', 'applied_jobs.user_show')
            ->orderBy('applied_jobs.id', 'DESC')
            ->get();



        if ($booking) {
            return response()->json(['status' => true, 'message' => 'Booking Data fetched', 'booking' => $booking, 'days_booking' => $daysbooking, 'chefoffer' => $chefoffer]);
        } else {
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
            ], 200);
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
                ->leftJoin('applied_jobs', function ($join) use ($id) {
                    $join->on('bookings.id', '=', 'applied_jobs.booking_id')
                        ->where('applied_jobs.chef_id', '=', $id);
                })
                ->select('bookings.name', 'users.id', 'bookings.surname', 'users.pic', 'bookings.location', 'bookings.booking_status', 'booking_meals.category', DB::raw('GROUP_CONCAT(booking_meals.date) AS dates'), DB::raw('MAX(booking_meals.created_at) AS latest_created_at'), 'bookings.id as booking_id', 'applied_jobs.status as applied_jobs_status', 'chef_id')
                ->groupBy('bookings.name', 'users.id', 'bookings.surname', 'users.pic', 'bookings.location', 'bookings.booking_status', 'booking_meals.category', 'bookings.id', 'applied_jobs.status')->where('bookings.status', '=', 'active')
                ->orderBy('bookings.id', 'DESC')
                ->get();

            if (!$chefuserbookings) {
                return response()->json(['message' => 'Booking not found', 'status' => true], 404);
            }

            return response()->json(['status' => true, 'message' => 'Data fetched', 'data' => $chefuserbookings]);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function get_user_chef_filter_by_booking($id, $type)
    {
        try {

            $chefuserbookings = DB::table('users')
                ->join('bookings', 'users.id', '=', 'bookings.user_id')
                ->join('booking_meals', 'bookings.id', '=', 'booking_meals.booking_id')
                ->join('service_choices', 'service_choices.id', '=', 'bookings.service_id')
                ->leftJoin('applied_jobs', function ($join) use ($id) {
                    $join->on('bookings.id', '=', 'applied_jobs.booking_id')
                        ->where('applied_jobs.chef_id', '=', $id);
                })
                ->where(function ($query) use ($type) {
                    $query->where('bookings.booking_status', '=', $type);
                })
                ->select('bookings.name', 'users.id', 'bookings.surname', 'users.pic', 'bookings.location', 'bookings.booking_status', 'booking_meals.category', DB::raw('GROUP_CONCAT(booking_meals.date) AS dates'), DB::raw('MAX(booking_meals.created_at) AS latest_created_at'), 'bookings.id as booking_id', 'applied_jobs.status as applied_jobs_status', 'chef_id')
                ->groupBy('bookings.name', 'users.id', 'bookings.surname', 'users.pic', 'bookings.location', 'bookings.booking_status', 'booking_meals.category', 'bookings.id', 'applied_jobs.status')->where('bookings.status', '=', 'active')
                ->orderBy('bookings.id', 'DESC')
                ->get();


            if (!$chefuserbookings) {
                return response()->json(['message' => 'Booking not found', 'status' => true], 404);
            }

            return response()->json(['status' => true, 'message' => 'Data fetched', 'data' => $chefuserbookings]);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function get_User_Booking_id($id)
    {
        $user = DB::table('users')
            ->join('bookings', 'users.id', '=', 'bookings.user_id')
            ->join('booking_meals', 'bookings.id', '=', 'booking_meals.booking_id')
            ->join('service_choices', 'service_choices.id', '=', 'bookings.service_id')
            ->select('bookings.name', 'users.id', 'bookings.surname', 'users.pic', 'bookings.location', 'bookings.booking_status', 'booking_meals.category', DB::raw('GROUP_CONCAT(booking_meals.date) AS dates'), DB::raw('MAX(booking_meals.created_at) AS latest_created_at'), 'bookings.id as booking_id')
            ->groupBy('bookings.name', 'users.id', 'bookings.surname', 'users.pic', 'bookings.location', 'bookings.booking_status', 'booking_meals.category', 'bookings.id')->where('bookings.status', '=', 'active')
            ->orderBy('bookings.id', 'DESC')
            ->where('users.id', $id)
            ->get();

        if (!$user) {
            return response()->json(['message' => 'Booking not found', 'status' => true], 404);
        }
        return response()->json(['status' => true, 'message' => 'Booking fetched', 'data' => $user]);
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
            return response()->json(['message' => 'Booking has been applied successfully', 'status' => true]);
        } else {
            return response()->json(['status' => true, 'message' => 'There has been error in saving the booking',]);
        }
    }

    public function get_chef_applied_booking(Request $request, $id)
    {

        try {

            $chefuserbookings = DB::table('users')
                ->join('bookings', 'users.id', '=', 'bookings.user_id')
                ->join('booking_meals', 'bookings.id', '=', 'booking_meals.booking_id')
                ->join('service_choices', 'service_choices.id', '=', 'bookings.service_id')
                ->join('applied_jobs', 'bookings.id', '=', 'applied_jobs.booking_id')
                ->leftJoin('menus', function ($join) {
                    $join->on(DB::raw("FIND_IN_SET(menus.id, applied_jobs.menu)"), '>', DB::raw('0'));
                })
                ->where('applied_jobs.chef_id', $id)
                ->select('bookings.name', 'users.id', 'bookings.surname', 'users.pic', 'bookings.location', 'bookings.booking_status', 'booking_meals.category', DB::raw('GROUP_CONCAT(booking_meals.date) AS dates'), DB::raw('MAX(booking_meals.created_at) AS latest_created_at'), 'bookings.id as booking_id', 'applied_jobs.status as applied_jobs_status', 'amount', DB::raw('GROUP_CONCAT(DISTINCT menus.menu_name SEPARATOR ",") AS menu_names'))
                ->groupBy(
                    'bookings.name',
                    'users.id',
                    'bookings.surname',
                    'users.pic',
                    'bookings.location',
                    'bookings.booking_status',
                    'booking_meals.category',
                    'bookings.id',
                )->where('bookings.status', '=', 'active')
                ->orderBy('applied_jobs.id', 'DESC')
                ->get();



            if (!$chefuserbookings) {
                return response()->json(['message' => 'Booking not found', 'status' => true], 404);
            }

            return response()->json(['status' => true, 'message' => 'Data fetched', 'data' => $chefuserbookings]);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function get_chef_applied_filter_by_booking($id, $type)
    {
        try {

            $chefuserbookings = DB::table('users')
                ->join('bookings', 'users.id', '=', 'bookings.user_id')
                ->join('booking_meals', 'bookings.id', '=', 'booking_meals.booking_id')
                ->join('service_choices', 'service_choices.id', '=', 'bookings.service_id')
                ->join('applied_jobs', 'bookings.id', '=', 'applied_jobs.booking_id')
                ->leftJoin('menus', function ($join) {
                    $join->on(DB::raw("FIND_IN_SET(menus.id, applied_jobs.menu)"), '>', DB::raw('0'));
                })
                ->where('applied_jobs.chef_id', $id)
                ->select('bookings.name', 'users.id', 'bookings.surname', 'users.pic', 'bookings.location', 'bookings.booking_status', 'booking_meals.category', DB::raw('GROUP_CONCAT(booking_meals.date) AS dates'), DB::raw('MAX(booking_meals.created_at) AS latest_created_at'), 'bookings.id as booking_id', 'applied_jobs.status as applied_jobs_status', 'amount', DB::raw('GROUP_CONCAT(DISTINCT menus.menu_name SEPARATOR ",") AS menu_names'))
                ->groupBy('bookings.name', 'users.id', 'bookings.surname', 'users.pic', 'bookings.location', 'bookings.booking_status', 'booking_meals.category', 'bookings.id', 'applied_jobs.status')->where('bookings.status', '=', 'active')->where('bookings.booking_status', $type)
                ->orderBy('applied_jobs.id', 'DESC')
                ->get();

            if (!$chefuserbookings) {
                return response()->json(['message' => 'Booking not found', 'status' => true], 404);
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
                ->groupBy('users.name', 'users.id', 'users.surname', 'users.pic', 'bookings.location', 'bookings.booking_status', 'booking_meals.category', 'bookings.id')->where('bookings.status', '=', 'active')->where('bookings.user_id', $id)
                ->orderBy('bookings.id', 'DESC')
                ->get();

            if (!$userbookings) {
                return response()->json(['message' => 'Booking not found', 'status' => true], 404);
            }

            return response()->json(['status' => true, 'message' => 'Data fetched', 'data' => $userbookings]);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function get_user_filter_by_booking($id, $type)
    {
        try {

            $userbookings = DB::table('users')
                ->join('bookings', 'users.id', '=', 'bookings.user_id')
                ->join('booking_meals', 'bookings.id', '=', 'booking_meals.booking_id')
                ->join('service_choices', 'service_choices.id', '=', 'bookings.service_id')

                ->select('users.name', 'users.id', 'users.surname', 'users.pic', 'bookings.location', 'bookings.booking_status', 'booking_meals.category', DB::raw('GROUP_CONCAT(booking_meals.date) AS dates'), DB::raw('MAX(booking_meals.created_at) AS latest_created_at'), 'bookings.id as booking_id')
                ->groupBy('users.name', 'users.id', 'users.surname', 'users.pic', 'bookings.location', 'bookings.booking_status', 'booking_meals.category', 'bookings.id')
                ->orderBy('bookings.id', 'DESC')->where('bookings.status', '=', 'active')->where('bookings.user_id', $id)->where('bookings.booking_status', '=', $type)
                ->get();

            if (!$userbookings) {
                return response()->json(['message' => 'Booking not found', 'status' => true], 404);
            }

            return response()->json(['status' => true, 'message' => 'Data fetched', 'data' => $userbookings]);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function get_single_user_assign_booking(Request $request, $id)
    {

        $user = DB::table('users')
            ->join('applied_jobs', 'users.id', '=', 'applied_jobs.chef_id')
            ->leftJoin('menus', function ($join) {
                $join->on(DB::raw("FIND_IN_SET(menus.id, applied_jobs.menu)"), '>', DB::raw('0'));
            })
            ->select('users.name', 'users.id', 'users.surname', DB::raw('GROUP_CONCAT(DISTINCT menus.menu_name SEPARATOR ",") AS menu_names'), 'booking_id', 'chef_id', 'client_amount', 'admin_amount', 'user_show', 'applied_jobs.status as applied_jobs_status', 'amount', 'applied_jobs.id as applied_jobs_id')
            ->groupBy('users.name', 'users.id', 'users.surname', 'booking_id', 'chef_id', 'client_amount', 'admin_amount', 'user_show', 'amount')->where('applied_jobs.booking_id', $id)
            ->orderBy('applied_jobs.id', 'DESC')
            ->get();


        if (!$user) {
            return response()->json(['message' => 'Booking not found', 'status' => true], 404);
        }
        return response()->json(['status' => true, 'message' => 'Data fetched', 'data' => $user]);
    }

    public function updated_applied_booking_by_key_value(Request $request)
    {


        if ($request->booking_id) {

            AppliedJobs::where('booking_id', $request->booking_id)->update([
                'status' => 'applied',
            ]);

            $updatebooking = AppliedJobs::where('id', $request->id)->update([
                $request->key => $request->value,
            ]);
        } else {

            $updatebooking = AppliedJobs::where('id', $request->id)->update([
                $request->key => $request->value,
            ]);
        }

        if ($updatebooking && $request->message == 'data') {
            return response()->json(['status' => true, 'message' => 'Data has been updated successfully']);
        } elseif ($updatebooking && $request->message == 'assign') {
            return response()->json(['status' => true, 'message' => 'Booking has been successfully assign to user.',]);
        } else {
            return response()->json(['status' => false, 'message' => 'There has been error in saving the booking',]);
        }
    }

    public function get_admin_chef_by_booking(Request $request)
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
                    'b.status'
                )
                ->select(
                    'b.name',
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
                ->where('b.status','!=','deleted')
                ->orderBy('b.id', 'DESC')
                ->get();

            if (!$adminchefuserbookings) {
                return response()->json(['message' => 'Booking not found', 'status' => true], 404);
            }

            return response()->json(['status' => true, 'message' => 'Data fetched', 'data' => $adminchefuserbookings]);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function get_admin_chef_filter_by_booking(Request $request, $type)
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
                ->where('b.booking_status', $type)
                ->where('b.status', '=', 'active')
                ->groupBy(
                    'b.name',
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
                    'b.status'
                )
                ->select(
                    'b.name',
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
                ->where('b.status','!=','deleted')
                ->orderBy('b.id', 'DESC')
                ->get();

            if (!$adminchefuserbookings) {
                return response()->json(['message' => 'Booking not found', 'status' => true], 404);
            }

            return response()->json(['status' => true, 'message' => 'Data fetched', 'data' => $adminchefuserbookings]);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function get_admin_assigned_booking(Request $request)
    {

        try {

            $chefuserbookings = DB::table('users')
                ->join('bookings', 'users.id', '=', 'bookings.user_id')
                ->join('booking_meals', 'bookings.id', '=', 'booking_meals.booking_id')
                ->join('service_choices', 'service_choices.id', '=', 'bookings.service_id')
                ->join('applied_jobs', 'bookings.id', '=', 'applied_jobs.booking_id')
                ->leftJoin('menus', function ($join) {
                    $join->on(DB::raw("FIND_IN_SET(menus.id, applied_jobs.menu)"), '>', DB::raw('0'));
                })
                ->select('bookings.name', 'users.id', 'bookings.surname', 'users.pic', 'bookings.location', 'bookings.booking_status', 'booking_meals.category', DB::raw('GROUP_CONCAT(booking_meals.date) AS dates'), DB::raw('MAX(booking_meals.created_at) AS latest_created_at'), 'bookings.id as booking_id', 'applied_jobs.status as applied_jobs_status', 'amount', 'client_amount', 'admin_amount', DB::raw('GROUP_CONCAT(DISTINCT menus.menu_name SEPARATOR ",") AS menu_names'))
                ->groupBy(
                    'bookings.name',
                    'users.id',
                    'bookings.surname',
                    'users.pic',
                    'bookings.location',
                    'bookings.booking_status',
                    'booking_meals.category',
                    'bookings.id',
                )->where('bookings.status', '=', 'active')->where('applied_jobs.status', 'hired')
                ->orderBy('applied_jobs.id', 'DESC')
                ->get();

            if (!$chefuserbookings) {
                return response()->json(['message' => 'Booking not found', 'status' => true], 404);
            }

            return response()->json(['status' => true, 'message' => 'Data fetched', 'data' => $chefuserbookings]);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function get_admin_applied_filter_by_booking($type)
    {
        try {

            $chefuserbookings = DB::table('users')
                ->join('bookings', 'users.id', '=', 'bookings.user_id')
                ->join('booking_meals', 'bookings.id', '=', 'booking_meals.booking_id')
                ->join('service_choices', 'service_choices.id', '=', 'bookings.service_id')
                ->join('applied_jobs', 'bookings.id', '=', 'applied_jobs.booking_id')
                ->leftJoin('menus', function ($join) {
                    $join->on(DB::raw("FIND_IN_SET(menus.id, applied_jobs.menu)"), '>', DB::raw('0'));
                })
                ->select('bookings.name', 'users.id', 'bookings.surname', 'users.pic', 'bookings.location', 'bookings.booking_status', 'booking_meals.category', DB::raw('GROUP_CONCAT(booking_meals.date) AS dates'), DB::raw('MAX(booking_meals.created_at) AS latest_created_at'), 'bookings.id as booking_id', 'applied_jobs.status as applied_jobs_status', 'amount', 'client_amount', 'admin_amount', DB::raw('GROUP_CONCAT(DISTINCT menus.menu_name SEPARATOR ",") AS menu_names'))
                ->groupBy('bookings.name', 'users.id', 'bookings.surname', 'users.pic', 'bookings.location', 'bookings.booking_status', 'booking_meals.category', 'bookings.id', 'applied_jobs.status')->where('bookings.status', '=', 'active')->where('applied_jobs.status', 'hired')->where('bookings.booking_status', $type)
                ->orderBy('applied_jobs.id', 'DESC')
                ->get();

            if (!$chefuserbookings) {
                return response()->json(['message' => 'Booking not found', 'status' => true], 404);
            }

            return response()->json(['status' => true, 'message' => 'Data fetched', 'data' => $chefuserbookings]);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function delete_booking($id)
    {
        try {

            if ($id) {

                $booking = Booking::where('id', $id)->update([
                    'status' => 'deleted',
                ]);

                if ($booking) {

                    $BookingMeals = BookingMeals::where('booking_id', $id)->update([
                        'status' => 'deleted',
                    ]);

                    if ($BookingMeals) {

                        $AppliedJobs = AppliedJobs::where('booking_id', $id)->update([
                            'jobs_status' => 'deleted',
                        ]);

                        return response()->json(['status' => true, 'message' => 'Booking has been deleted successfully']);
                    } else {
                        return response()->json(['status' => false, 'message' => 'There has been error in deleting the booking']);
                    }
                } else {

                    return response()->json(['status' => false, 'message' => 'There has been error in deleting the booking']);
                }
            } else {

                return response()->json(['status' => false, 'message' => 'Booking id not found']);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function get_edit_booking_data(Request $request, $id)
    {

        try {


            $bookings = User::select('users.role', 'users.name', 'users.surname', 'users.email', 'users.phone', 'booking_meals.*', 'bookings.*')
                ->join('bookings', 'users.id', '=', 'bookings.user_id')
                ->leftJoin('booking_meals', 'booking_meals.booking_id', '=', 'bookings.id')
                ->where('booking_meals.booking_id', $id)
                ->get();

            if (!$bookings) {
                return response()->json(['message' => 'Booking not found', 'status' => false]);
            }

            return response()->json(['status' => true, 'message' => 'Data fetched', 'data' => $bookings]);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function update_booking(Request $request)
    {
        try {

            $checkemail  = User::where('email', $request->email)->count();

            if ($checkemail <= 0) {

                $booking =  Booking::find($request->bookingid);


                $booking->service_id = $request->service_id;
                $booking->cuisine_id = implode(",", $request->cuisine_id);

                if ($request->allergies_id) {
                    $booking->allergies_id = implode(",", $request->allergies_id);
                }
                $booking->name = $request->name;
                $booking->surname = $request->surname;
                $booking->email = $request->email;
                $booking->phone = $request->phone;
                $booking->notes = $request->notes;
                $booking->location = $request->address;
                $booking->lat = $request->lat;
                $booking->lng = $request->lng;
                $booking->adults = $request->adults ? $request->adults : 0;
                $booking->childrens = $request->childrens ? $request->childrens : 0;
                $booking->teens = $request->teens ? $request->teens : 0;

                $savebookingdata = $booking->save();


                if ($savebookingdata) {

                    if ($request->category == 'onetime') {

                        $bookingmeals = BookingMeals::where('booking_id', $request->bookingid)->delete();

                        $dateString = $request->date;
                        $timezoneStart = strpos($dateString, '(');
                        $timezoneEnd = strpos($dateString, ')');
                        $dateString = substr_replace($dateString, '', $timezoneStart, $timezoneEnd - $timezoneStart + 1);
                        $date = Carbon::parse($dateString);
                        $formattedDate = $date->format('Y-m-d');


                        $bookingmeals = new BookingMeals();
                        $bookingmeals->booking_id = $request->bookingid;
                        $bookingmeals->date = $formattedDate;
                        $bookingmeals->breakfast = $request->meals['breakfast'] == '1' ? 'yes' : 'no';
                        $bookingmeals->lunch = $request->meals['lunch'] == '1' ? 'yes' : 'no';
                        $bookingmeals->dinner = $request->meals['dinner'] == '1' ? 'yes' : 'no';
                        $bookingmeals->category = $request->category;
                        $savebookingmeals  = $bookingmeals->save();
                    } else {

                        $bookingmeals = BookingMeals::where('booking_id', $request->bookingid)->delete();

                        foreach ($request->meals as $meals) {


                            $bookingmeals = new BookingMeals();
                            $bookingmeals->booking_id = $request->bookingid;
                            $bookingmeals->date = \Carbon\Carbon::createFromFormat('d/m/Y', $meals['date'])->format('Y-m-d');
                            $bookingmeals->breakfast = $meals['breakfast'] == '1' ? 'yes' : 'no';
                            $bookingmeals->lunch = $meals['lunch'] == '1' ? 'yes' : 'no';
                            $bookingmeals->dinner = $meals['dinner'] == '1' ? 'yes' : 'no';
                            $bookingmeals->category = $request->category;
                            $savebookingmeals  = $bookingmeals->save();
                        }
                    }
                }

                return response()->json(['status' => true, 'message' => "Booking has been update successfully"], 200);
            } else {

                return response()->json(['status' => false, 'message' => "Email already exits", 'data' => ""], 200);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

    public function get_user_chef_offer($id)
    {

        $chefoffer = DB::table('bookings')
            ->join('applied_jobs', 'bookings.id', '=', 'applied_jobs.booking_id')
            ->join('users', 'users.id', '=', 'applied_jobs.chef_id')
            ->leftJoin('menus', function ($join) {
                $join->on(DB::raw("FIND_IN_SET(menus.id, applied_jobs.menu)"), '>', DB::raw('0'));
            })
            ->where('bookings.id', $id)
            ->where('applied_jobs.status', 'applied')
            ->select('bookings.id as booking_id', 'bookings.name', 'bookings.location', 'bookings.surname', 'bookings.location', 'applied_jobs.amount', 'users.name as userName','users.surname as userSurname','applied_jobs.chef_id', 'menus.id as menu_id', DB::raw('GROUP_CONCAT(DISTINCT menus.menu_name SEPARATOR ",") AS menu_names'))
            ->groupBy('bookings.name', 'bookings.surname', 'bookings.location', 'applied_jobs.amount', 'applied_jobs.chef_id', 'bookings.id','bookings.location')
            ->orderBy('applied_jobs.id', 'DESC')
            ->get();

        if ($chefoffer) {
            return response()->json(['status' => true, 'message' => 'Booking Data fetched', 'chefoffer' => $chefoffer]);
        } else {
            return response()->json(['status' => false, 'message' => 'There has been for saving the menu', 'error' => '', 'data' => '']);
        }
    }

    public function get_all_bookings(Request $request)
    {
        try {
            $currentDate = Carbon::now()->toDateString();
            $todayBookings = Booking::join('applied_jobs', 'bookings.id', 'applied_jobs.booking_id')->where('bookings.booking_status', 'completed')->whereDate('applied_jobs.created_at', $currentDate)->count();
            $totalChef = AppliedJobs::join('users', 'applied_jobs.chef_id', 'users.id')
            ->join('bookings','applied_jobs.booking_id','bookings.id')
            ->where('bookings.booking_status','completed')
            ->where('users.role','chef')
            ->distinct('users.id')
            ->whereDate('applied_jobs.created_at', $currentDate)
            ->count();    
            $totalamount = Booking::join('applied_jobs', 'bookings.id', 'applied_jobs.booking_id')->where('bookings.booking_status', 'completed')->whereIn('applied_jobs.status', ['applied', 'hired'])->whereDate('applied_jobs.created_at', $currentDate)->sum('applied_jobs.amount');
            $pendingBooking = Booking::select('applied_jobs.created_at as orderDate', 'applied_jobs.amount', 'bookings.id as bookingId')
                ->join('applied_jobs', 'bookings.id', '=', 'applied_jobs.booking_id')
                ->where('bookings.booking_status', 'pending')
                ->where('bookings.status', 'active')
                ->whereIn('applied_jobs.status', ['applied', 'hired'])
                ->orderby('bookings.id', 'desc')
                ->get();
            $completedBooking = Booking::select('bookings.id as bookingId', 'users.address', 'users.name', 'applied_jobs.created_at as ordertime')
                ->join('applied_jobs', 'bookings.id', '=', 'applied_jobs.booking_id')
                ->join('users', 'applied_jobs.chef_id', '=', 'users.id')
                ->where('bookings.status', 'active')
                ->where('bookings.booking_status', 'completed')
                ->whereIn('applied_jobs.status', ['applied', 'hired'])
                ->whereDate('applied_jobs.created_at', $currentDate)
                ->orderby('bookings.id', 'desc')
                ->get();
            $startDate = Carbon::now()->subDays(7)->startOfDay();
            $endDate = Carbon::now()->endOfDay();
            $weeklyUsers = Booking::join('users', 'bookings.user_id', 'users.id')->where('users.role', 'user')->whereBetween('bookings.created_at', [$startDate, $endDate])->groupBy('users.id')->count();
            $weeklyBooking = Booking::whereBetween('created_at', [$startDate, $endDate])->count();
            if ($completedBooking) {
                return response()->json(['status' => true, 'message' => 'All booking data', 'totalChef' => $totalChef, 'pendingBooking' => $pendingBooking, 'completedBooking' => $completedBooking, 'weeklyUsers' => $weeklyUsers, 'weeklyBooking' => $weeklyBooking, 'todayBookings' => $todayBookings, 'totalChef' => $totalChef, 'totalamount' => $totalamount], 200);
            } else {
                return response()->json(['status' => false, 'message' => 'All booking'], 400);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function get_chef_bookings(Request $request)
    {
        try {
            $currentDate = Carbon::now()->toDateString();
            $todayBookings = Booking::join('applied_jobs', 'bookings.id', 'applied_jobs.booking_id')->where('bookings.booking_status', 'completed')->where('applied_jobs.chef_id', $request->id)->whereDate('applied_jobs.created_at', $currentDate)->count();
            $pendingBookingCount = Booking::select('applied_jobs.created_at as orderDate', 'applied_jobs.amount', 'bookings.id as bookingId')
                ->join('applied_jobs', 'bookings.id', '=', 'applied_jobs.booking_id')
                ->where('bookings.booking_status', 'pending')
                ->where('bookings.status', 'active')
                ->whereIn('applied_jobs.status', ['applied', 'hired'])
                ->where('applied_jobs.chef_id', $request->id)
                ->orderby('bookings.id', 'desc')
                ->whereDate('applied_jobs.created_at', $currentDate)
                ->count();
            $totalamount = Booking::join('applied_jobs', 'bookings.id', 'applied_jobs.booking_id')->where('bookings.booking_status', 'completed')->whereIn('applied_jobs.status', ['applied', 'hired'])->where('applied_jobs.chef_id', $request->id)->whereDate('applied_jobs.created_at', $currentDate)->sum('applied_jobs.amount');
            $pendingBooking = Booking::select('applied_jobs.created_at as orderDate', 'applied_jobs.amount', 'bookings.id as bookingId')
                ->join('applied_jobs', 'bookings.id', '=', 'applied_jobs.booking_id')
                ->where('bookings.booking_status', 'pending')
                ->where('bookings.status', 'active')
                ->whereIn('applied_jobs.status', ['applied', 'hired'])
                ->where('applied_jobs.chef_id', $request->id)
                ->orderby('bookings.id', 'desc')
                ->get();
            $completedBooking = Booking::select('bookings.id as bookingId', 'users.address', 'users.name', 'applied_jobs.created_at as ordertime')
                ->join('applied_jobs', 'bookings.id', '=', 'applied_jobs.booking_id')
                ->join('users', 'applied_jobs.chef_id', '=', 'users.id')
                ->where('bookings.status', 'active')
                ->where('bookings.booking_status', 'completed')
                ->whereIn('applied_jobs.status', ['applied', 'hired'])
                ->whereDate('applied_jobs.created_at', $currentDate)
                ->where('applied_jobs.chef_id', $request->id)
                ->orderby('applied_jobs.id', 'desc')
                ->get();
            $startDate = Carbon::now()->subDays(7)->startOfDay();
            $endDate = Carbon::now()->endOfDay();
            $weeklyBooking = Booking::join('applied_jobs', 'bookings.id', '=', 'applied_jobs.booking_id')->where('applied_jobs.chef_id', $request->id)->whereBetween('applied_jobs.created_at', [$startDate, $endDate])->count();
            return response()->json(['status' => true, 'message' => 'All booking data', 'todayBookings' => $todayBookings, 'totalamount' => $totalamount, 'pendingBookingCount' => $pendingBookingCount, 'pendingBooking' => $pendingBooking, 'weeklyBooking' => $weeklyBooking, 'completedBooking' => $completedBooking], 200);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function change_booking_status(Request $request)
    {
        try {
            $user = Booking::find($request->id);
            $user->booking_status = $request->booking_status;
            $user->save();
            
            if ($user) {
                return response()->json(['status' => true, 'message' => "booking status changed", 'data' => $user], 200);
            } else {
                return response()->json(['status' => false, 'message' => "There has been error for changing the booking status", 'data' => ""], 400);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
}
