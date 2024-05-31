<?php

namespace App\Http\Controllers\Api;

use App\Models\Booking;
use App\Models\BookingMeals;
use App\Models\User;
use App\Models\AppliedJobs;
use App\Models\Notification;
use App\Models\InviteProposal;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Mail;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Charge;
use Stripe\Exception\CardException;
use Illuminate\Mail\Mailable;
use App\Mail\InvitationEmail;


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
                        COS(RADIANS(?)) * COS(RADIANS(chef_location.lat)) * COS(RADIANS(chef_location.lng) - RADIANS(?)) +
                        SIN(RADIANS(?)) * SIN(RADIANS(chef_location.lat))
                    )) AS distance",
                            [$request->lat, $request->lng, $request->lat]
                        )
                        ->where('chef_location.status', 'active')
                        ->having('distance', '<=', $radius)
                        ->orderBy('distance')
                        ->get();

                    $admin = User::select('email')->where('role', 'admin')->first();

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
                        $savebookingmeals = $bookingmeals->save();
                    } else {

                        foreach ($request->meals as $meals) {
                            $bookingmeals = new BookingMeals();
                            $bookingmeals->booking_id = $booking->id;
                            $bookingmeals->date = \Carbon\Carbon::createFromFormat('d/m/Y', $meals['date'])->format('Y-m-d');
                            $bookingmeals->breakfast = $meals['breakfast'] == '1' ? 'yes' : 'no';
                            $bookingmeals->lunch = $meals['lunch'] == '1' ? 'yes' : 'no';
                            $bookingmeals->dinner = $meals['dinner'] == '1' ? 'yes' : 'no';
                            $bookingmeals->category = $request->category;
                            $savebookingmeals = $bookingmeals->save();
                        }
                        //mail send to chef when user booking with radius
                        if ($chefs) {
                            $amount = '';
                            $this->sendLocationNotification($chefs, $booking, $admin, $request, $amount);
                        }
                    }
                    $admin = User::select('id')->where('role', 'admin')->get();
                    $concierge = User::select('id', 'created_by')->where('id', $request->user_id)->first();
                    if ($concierge->created_by) {
                        $notify_by1 = $concierge->id;
                        $notify_to1 = $concierge->created_by;
                        $description1 = $booking->name . ' has successfully done booking with booking id ' . '#' . $booking->id;
                        $type1 = 'booking';
                        createNotificationForConcierge($notify_by1, $notify_to1, $description1, $type1);
                    }

                    $notify_by = $request->user_id;
                    $notify_to = $admin;
                    $description = Null;
                    $description1 = $booking->name . ' has successfully done booking with booking id ' . '#' . $booking->id;
                    $type = 'booking';
                    createNotificationForUserAndAdmins($notify_by, $notify_to, $description, $description1, $type);
                }

                //mail send to user own booking
                if ($request->category == 'onetime') {
                    Mail::send('emails.bookingConfirmation', ['booking' => $booking], function ($message) use ($booking) {
                        $message->from(config('mail.from.address'), "Private Chefs");
                        $message->subject('Booking Confirmation');
                        $message->to($booking->email);
                        $message->cc('info@privatechefsworld.com');
                        $message->bcc(User::select('email')->where('role', 'admin')->first()->email); // Add admin email as BCC
                    });
                } else {
                    Mail::send('emails.bookingConfirmationTwo', ['booking' => $booking], function ($message) use ($booking) {
                        $message->from(config('mail.from.address'), "Private Chefs");
                        $message->subject("We've Received Your Booking Request!");
                        $message->to($booking->email);
                        $message->cc('info@privatechefsworld.com');
                        $message->bcc(User::select('email')->where('role', 'admin')->first()->email); // Add admin email as BCC
                    });
                }
                return response()->json(['status' => true, 'message' => "booking done successfully", 'bookingid' => $booking->id], 200);
            } else {
                $checkemail = User::where('email', $request->email)->count();

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
                                ->select('users.email', 'users.name', 'chef_location.lat', 'chef_location.user_id', 'chef_location.lng', 'chef_location.address')
                                ->selectRaw(
                                    "($earthRadius * ACOS(
                                        COS(RADIANS(?)) * COS(RADIANS(chef_location.lat)) * COS(RADIANS(chef_location.lng) - RADIANS(?)) +
                                        SIN(RADIANS(?)) * SIN(RADIANS(chef_location.lat))
                                        )) AS distance",
                                    [$request->lat, $request->lng, $request->lat]
                                )
                                ->where('chef_location.status', 'active')
                                ->having('distance', '<=', $radius)
                                ->orderBy('distance')
                                ->get();

                            // $admindata = User::select('email')->where('role', 'admin')->first();
                            $admin = User::where('role', 'admin')->first();

                            //return $chefs;
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
                                $savebookingmeals = $bookingmeals->save();
                            } else {
                                foreach ($request->meals as $meals) {
                                    $bookingmeals = new BookingMeals();
                                    $bookingmeals->booking_id = $booking->id;
                                    $bookingmeals->date = \Carbon\Carbon::createFromFormat('d/m/Y', $meals['date'])->format('Y-m-d');
                                    $bookingmeals->breakfast = $meals['breakfast'] == '1' ? 'yes' : 'no';
                                    $bookingmeals->lunch = $meals['lunch'] == '1' ? 'yes' : 'no';
                                    $bookingmeals->dinner = $meals['dinner'] == '1' ? 'yes' : 'no';
                                    $bookingmeals->category = $request->category;
                                    $savebookingmeals = $bookingmeals->save();
                                }
                                if ($chefs) {
                                    $amount = '';
                                    $this->sendLocationNotification($chefs, $booking, $admin, $request, $amount);
                                }
                            }
                            $admin = User::select('id')->where('role', 'admin')->get();
                            $notify_by = Null;
                            $notify_to = $admin;
                            $description = Null;
                            $description1 = $booking->name . ' has successfully done booking with booking id ' . '#' . $booking->id;
                            $type = 'booking';
                            createNotificationForUserAndAdmins($notify_by, $notify_to, $description, $description1, $type);

                            $admindata = User::select('email')->where('role', 'admin')->first();

                            $data = [
                                'name' => $user->name,
                                'password' => $password,
                                'email' => $user->email,
                                'admin_email' => $admindata->email,
                            ];
                            Mail::send('emails.loginDetails', ["data" => $data], function ($message) use ($data) {
                                $message->from(config('mail.from.address'), "Private Chefs");
                                $message->bcc($data['admin_email']);
                                $message->subject(' Your Account Login Details for Private Chefs');
                                $message->to($data['email']);
                            });
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

            $today = now()->toDateString();

            $chefuserbookings = DB::table('users')
                ->join('bookings', 'users.id', '=', 'bookings.user_id')
                ->join('booking_meals', 'bookings.id', '=', 'booking_meals.booking_id')
                ->join('service_choices', 'service_choices.id', '=', 'bookings.service_id')
                ->leftJoin('applied_jobs', function ($join) use ($id) {
                    $join->on('bookings.id', '=', 'applied_jobs.booking_id')
                        ->where('applied_jobs.chef_id', '=', $id);
                })
                ->select(
                    'bookings.name',
                    'users.id',
                    'bookings.surname',
                    'users.pic',
                    'bookings.location',
                    'bookings.booking_status',
                    'booking_meals.category',
                    DB::raw('GROUP_CONCAT(booking_meals.date) AS dates'),
                    DB::raw('MAX(booking_meals.created_at) AS latest_created_at'),
                    'bookings.id as booking_id',
                    'applied_jobs.status as applied_jobs_status',
                    'chef_id'
                )
                ->groupBy('bookings.name', 'users.id', 'bookings.surname', 'users.pic', 'bookings.location', 'bookings.booking_status', 'booking_meals.category', 'bookings.id', 'applied_jobs.status')
                ->where('bookings.status', '=', 'active')
                // ->where('booking_meals.date', '>=', $today)
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('applied_jobs')
                        ->whereColumn('applied_jobs.booking_id', '=', 'bookings.id')
                        //->where('applied_jobs.status', '=', 'hired');
                        ->whereIn('applied_jobs.status', ['hired', 'discussion']);
                })
                ->orderBy('bookings.id', 'DESC')
                ->get();


            foreach ($chefuserbookings as $booking) {
                $dates = explode(',', $booking->dates);
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
        $booking->user_show = 'visible';
        $appliedJobs = $booking->save();

        if ($appliedJobs) {

            $admin = User::select('id')->where('role', 'admin')->get();
            $concierge = User::select('id', 'created_by', 'name')->where('id', $request->chef_id)->first();
            if ($concierge->created_by) {
                $notify_by1 = $concierge->id;
                $notify_to1 = $concierge->created_by;
                $description1 = $concierge->name . ' has successfully applied on booking id ' . '#' . $request->booking_id;
                $type1 = 'booking';
                createNotificationForConcierge($notify_by1, $notify_to1, $description1, $type1);
            }

            $notify_by = Null;
            $notify_to = $admin;
            $description = Null;
            $description1 = $concierge->name . ' has successfully applied on booking id ' . '#' . $request->booking_id;
            $type = 'booking';

            createNotificationForUserAndAdmins($notify_by, $notify_to, $description, $description1, $type);

            $booking = Booking::select('id', 'user_id')->where('id', $request->booking_id)->first();
            $user = User::select('id', 'email', 'name')->where('id', $booking->user_id)->first();
            $chef = User::select('id', 'email', 'name', 'phone')->where('id', $request->chef_id)->first();
            $admindata = User::select('email')->where('role', 'admin')->first();

            $data = [
                'name' => $user->name,
                'email' => $user->email,
                'booking_id' => $booking->id,
                'chef_name' => $chef->name,
                'chef_email' => $chef->email,
                'chef_phone' => $chef->phone,
                'admin_email' => $admindata->email,
            ];
            // Mail::send('emails.emailappliedbychef', ['data' => $data], function ($message) use ($data) {
            //     $message->from(config('mail.from.address'), "Private Chefs");
            //     $message->to($data['email']);
            //     $message->bcc([$data['admin_email'], 'info@privatechefsworld.com']);
            //     $message->subject('Booking Request: Chef Application Received');
            // });
        }

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
                    'applied_jobs.status'
                )
                ->where('bookings.status', '=', 'active')
                ->orderBy('applied_jobs.id', 'DESC')
                ->get();

            // $chefuserbookings = DB::table('users')
            //     ->join('bookings', 'users.id', '=', 'bookings.user_id')
            //     ->join('booking_meals', 'bookings.id', '=', 'booking_meals.booking_id')
            //     ->join('service_choices', 'service_choices.id', '=', 'bookings.service_id')
            //     ->join('applied_jobs', 'bookings.id', '=', 'applied_jobs.booking_id')
            //     ->leftJoin('menus', function ($join) {
            //         $join->on(DB::raw("FIND_IN_SET(menus.id, applied_jobs.menu)"), '>', DB::raw('0'));
            //     })
            //     ->where('applied_jobs.chef_id', $id)
            //     ->select('bookings.name', 'users.id', 'bookings.surname', 'users.pic', 'bookings.location', 'bookings.booking_status', 'booking_meals.category', DB::raw('GROUP_CONCAT(booking_meals.date) AS dates'), DB::raw('MAX(booking_meals.created_at) AS latest_created_at'), 'bookings.id as booking_id', 'applied_jobs.status as applied_jobs_status', 'amount', DB::raw('GROUP_CONCAT(DISTINCT menus.menu_name SEPARATOR ",") AS menu_names'))
            //     ->groupBy(
            //         'bookings.name',
            //         'users.id',
            //         'bookings.surname',
            //         'users.pic',
            //         'bookings.location',
            //         'bookings.booking_status',
            //         'booking_meals.category',
            //         'bookings.id',
            //     )
            //     ->where('bookings.status', '=', 'active')
            //     ->orderBy('applied_jobs.id', 'DESC')
            //     ->get();



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
                ->leftJoin('applied_jobs', 'applied_jobs.booking_id', '=', 'bookings.id')
                ->leftJoin('users as u1', 'u1.id', '=', 'applied_jobs.chef_id')
                ->join('booking_meals', 'bookings.id', '=', 'booking_meals.booking_id')
                ->join('service_choices', 'service_choices.id', '=', 'bookings.service_id')

                ->select('users.name', 'users.id', 'users.surname', 'users.pic', 'bookings.location', 'bookings.booking_status', 'booking_meals.category', DB::raw('GROUP_CONCAT(DISTINCT booking_meals.date) AS dates'), DB::raw('MAX(booking_meals.created_at) AS latest_created_at'), 'bookings.id as booking_id', 'applied_jobs.booking_id as appliedId', 'u1.id as userId', 'applied_jobs.chef_id as chefId', 'bookings.payment_status')
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
            ->select('users.name', 'users.id', 'users.surname', DB::raw('GROUP_CONCAT(DISTINCT menus.menu_name SEPARATOR ",") AS menu_names'), 'booking_id', 'chef_id', 'client_amount', 'admin_amount', 'user_show', 'applied_jobs.status as applied_jobs_status', 'amount', 'applied_jobs.id as applied_jobs_id', 'applied_jobs.status as applied_jobs_status')
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


        $updatebooking = AppliedJobs::where('id', $request->id)->update([
            $request->key => $request->value,
        ]);

        if ($updatebooking) {

            return response()->json(['message' => 'done', 'status' => true]);

        } else {
            return response()->json(['message' => 'There has been error', 'status' => false]);
        }


    }


    public function AssignedBookingByAdminWithoutDatabse(Request $request)
    {


        if ($request->booking_id) {

            $bookingdatecheck = BookingMeals::select('date')->where('booking_id', $request->booking_id)->get();

            $bokking = Booking::select('date')
                ->join('booking_meals', 'bookings.id', '=', 'booking_meals.booking_id')
                ->where('bookings.assigned_to_user_id', $request->chef_id)
                ->where('bookings.id', '!=', $request->booking_id)
                ->get();


            $datesFromBookingDateCheck = $bookingdatecheck->pluck('date')->toArray();
            $datesFromBokking = $bokking->pluck('date')->toArray();

            $intersectedDates = array_intersect($datesFromBokking, $datesFromBookingDateCheck);

            if (!empty($intersectedDates)) {

                return response()->json(['message' => 'THis Chef is already booked between these dates', 'status' => false]);

            } else {


                if ($request->payment_status == 'pending') {
                    $updatebooking = AppliedJobs::where('id', $request->id)->update([
                        'status' => 'discussion'
                    ]);
                } else {
                    $updatebooking = AppliedJobs::where('id', $request->id)->update([
                        'status' => 'hired'
                    ]);
                }



                $chef = User::select('name', 'email')->where('id', $request->chef_id)->first();
                $booking = Booking::select('name', 'location', 'id', 'email')->where('id', $request->booking_id)->first();

                $bookingDate = Booking::select('booking_meals.category', 'bookings.user_id as client_id', DB::raw('GROUP_CONCAT(DISTINCT booking_meals.date) AS dates'))
                    ->join('booking_meals', 'bookings.id', '=', 'booking_meals.booking_id')
                    ->where('bookings.id', $request->booking_id)
                    ->groupBy('booking_meals.booking_id')
                    ->first();

                $admindata = User::select('email')->where('role', 'admin')->first();

                $data = [
                    'chef_id' => $request->chef_id,
                    'chef_name' => $chef->name,
                    'email' => $chef->email,
                    'booking_id' => $request->booking_id,
                    'user_name' => $booking->name,
                    'user_location' => $booking->location,
                    'booking_type' => $bookingDate->category,
                    'booking_date' => $bookingDate->dates,
                    'admin_email' => $admindata->email,
                    'user_email' => $booking->email,
                    'client_amount' => $request->client_amount,
                    'client_id' => $bookingDate->client_id,
                    'applied_id' => $request->applied_id,
                ];

                if ($request->payment_status == 'pending') {

                    Mail::send('emails.hiredchefMailToUser', ['data' => $data], function ($message) use ($data) {
                        $message->from(config('mail.from.address'), "Private Chefs");
                        $message->to($data['user_email']);
                        $message->bcc([$data['admin_email'], 'info@privatechefsworld.com']);
                        $message->subject('Culinary Experience Tailored Just for You! ');
                    });

                    return response()->json(['message' => 'Booking mail and along with payment link  send to user', 'status' => true]);

                } else {

                    Mail::send('emails.hiredchefMail', ['data' => $data], function ($message) use ($data) {
                        $message->from(config('mail.from.address'), "Private Chefs");
                        $message->to($data['email']);
                        $message->bcc([$data['admin_email'], 'info@privatechefsworld.com']);
                        $message->subject('You have Been Chosen to Create Culinary Magic! ');
                    });

                    return response()->json(['message' => 'Hired mail has been successfully sent to chef', 'status' => true]);
                }
            }


        } else {
            return response()->json(['message' => 'There has been error', 'status' => false]);
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
                        //->where('aj.status', '=', 'hired');
                        ->whereIn('aj.status', ['hired', 'discussion']);
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
                    'b.status',
                    'aj.id as assigned_job_id'
                )
                ->where('b.status', '!=', 'deleted')
                ->orderBy('b.id', 'DESC')
                ->get();
            if (!$adminchefuserbookings) {
                return response()->json(['message' => 'Booking not found', 'status' => true], 404);
            }

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

    public function get_admin_chef_filter_by_booking(Request $request, $type)
    {

        try {

            $adminchefuserbookings = DB::table('users as u')
                ->join('bookings as b', 'u.id', '=', 'b.user_id')
                ->join('booking_meals as bm', 'b.id', '=', 'bm.booking_id')
                ->join('service_choices as sc', 'sc.id', '=', 'b.service_id')
                ->leftJoin('applied_jobs as aj', function ($join) {
                    $join->on('b.id', '=', 'aj.booking_id')
                        //->where('aj.status', '=', 'hired');
                        ->whereIn('aj.status', ['hired', 'discussion']);
                })
                ->whereNull('aj.booking_id')
                ->where('b.booking_status', $type)
                ->where('b.status', '=', 'active')
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
                    'aj.id'
                )
                ->select(
                    'b.name',
                    'b.payment_status',
                    'b.assigned_to_user_id',
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
                    'b.status',
                    'aj.id AS assigned_job_id',
                )
                ->where('b.status', '!=', 'deleted')
                ->orderBy('b.id', 'DESC')
                ->get();

            if (!$adminchefuserbookings) {
                return response()->json(['message' => 'Booking not found', 'status' => true], 404);
            }

            return response()->json(['status' => true, 'message' => 'Data fetched', 'data' => $adminchefuserbookings]);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }


        try {
            $adminchefuserbookings = DB::table('users as u')
                ->join('bookings as b', 'u.id', '=', 'b.user_id')
                ->join('booking_meals as bm', 'b.id', '=', 'bm.booking_id')
                ->join('service_choices as sc', 'sc.id', '=', 'b.service_id')
                ->leftJoin('applied_jobs as aj', function ($join) {
                    $join->on('b.id', '=', 'aj.booking_id')
                       // ->where('aj.status', '=', 'hired');
                        ->whereIn('aj.status', ['hired', 'discussion']);
                })
                ->whereNull('aj.booking_id')
                ->where('b.booking_status', $type)
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
                    'aj.id'
                )
                ->select(
                    'b.name',
                    'b.payment_status',
                    'b.assigned_to_user_id',
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
                    'b.status',
                    'aj.id As assigned_job_id',
                )
                ->where('b.status', '!=', 'deleted')
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
                ->select('bookings.name', 'users.id', 'bookings.surname', 'users.pic', 'bookings.location', 'bookings.booking_status', 'booking_meals.category', DB::raw('GROUP_CONCAT(booking_meals.date) AS dates'), DB::raw('MAX(booking_meals.created_at) AS latest_created_at'), 'bookings.id as booking_id', 'applied_jobs.status as applied_jobs_status', 'applied_jobs.chef_id as applied_chef_id', 'amount', 'client_amount', 'admin_amount', DB::raw('GROUP_CONCAT(DISTINCT menus.menu_name SEPARATOR ",") AS menu_names'), 'users_chef.name as applied_chef_name', 'bookings.payment_status', 'applied_jobs.id as assigned_job_id')
                ->groupBy(
                    'bookings.name',
                    'users.id',
                    'bookings.surname',
                    'users.pic',
                    'bookings.location',
                    'bookings.booking_status',
                    'booking_meals.category',
                    'bookings.id',
                    'applied_jobs.chef_id',
                    'users_chef.name',
                    'applied_jobs.id',
                )
                ->leftJoin('users as users_chef', 'users_chef.id', '=', 'applied_jobs.chef_id') // Join with users table to get chef's name
                ->where('bookings.status', '=', 'active')
                ->whereIn('applied_jobs.status', ['hired', 'discussion'])
                //->whereIn('aj.status', ['applied', 'hired', 'discussion']);
                //->whereIn('bookings.payment_status', ['pending', 'complete'])
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
                ->select('bookings.name', 'users.id', 'bookings.surname', 'users.pic', 'bookings.location', 'bookings.booking_status', 'booking_meals.category', DB::raw('GROUP_CONCAT(booking_meals.date) AS dates'), DB::raw('MAX(booking_meals.created_at) AS latest_created_at'), 'bookings.id as booking_id', 'applied_jobs.status as applied_jobs_status', 'amount', 'client_amount', 'admin_amount', DB::raw('GROUP_CONCAT(DISTINCT menus.menu_name SEPARATOR ",") AS menu_names'), 'bookings.payment_status', 'applied_jobs.id as assigned_job_id')
                ->groupBy('bookings.name', 'users.id', 'bookings.surname', 'users.pic', 'bookings.location', 'bookings.booking_status', 'booking_meals.category', 'bookings.id', 'applied_jobs.status', 'applied_jobs.id')->where('bookings.status', '=', 'active')->whereIn('applied_jobs.status', ['hired', 'discussion'])->where('bookings.booking_status', $type)
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
            // return $request->all();
            // $checkemail  = User::where('email', $request->email)->count();

            // if ($checkemail <= 0) {

            $booking = Booking::find($request->bookingid);

            if ($request->service_id) {
                $booking->service_id = $request->service_id;
            }
            if ($request->cuisine_id) {
                $booking->cuisine_id = implode(",", $request->cuisine_id);
            }
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
                    //return $formattedDate;

                    $bookingmeals = new BookingMeals();
                    $bookingmeals->booking_id = $request->bookingid;
                    $bookingmeals->date = $formattedDate;
                    $bookingmeals->breakfast = $request->meals['breakfast'] == '1' ? 'yes' : 'no';
                    $bookingmeals->lunch = $request->meals['lunch'] == '1' ? 'yes' : 'no';
                    $bookingmeals->dinner = $request->meals['dinner'] == '1' ? 'yes' : 'no';
                    $bookingmeals->category = $request->category;
                    $savebookingmeals = $bookingmeals->save();
                }

                if ($request->category == 'multipletimes') {


                    $bookingmeals = BookingMeals::where('booking_id', $request->bookingid)->delete();

                    foreach ($request->meals as $meals) {


                        $bookingmeals = new BookingMeals();
                        $bookingmeals->booking_id = $request->bookingid;
                        $bookingmeals->date = \Carbon\Carbon::createFromFormat('d/m/Y', $meals['date'])->format('Y-m-d');
                        $bookingmeals->breakfast = $meals['breakfast'] == '1' ? 'yes' : 'no';
                        $bookingmeals->lunch = $meals['lunch'] == '1' ? 'yes' : 'no';
                        $bookingmeals->dinner = $meals['dinner'] == '1' ? 'yes' : 'no';
                        $bookingmeals->category = $request->category;
                        $savebookingmeals = $bookingmeals->save();
                    }

                }
            }

            return response()->json(['status' => true, 'message' => "Booking has been updated successfully"], 200);
            // } else {

            //     return response()->json(['status' => false, 'message' => "Email already exits", 'data' => ""], 200);
            // }
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
            ->select('bookings.id as booking_id', 'bookings.name', 'bookings.location', 'bookings.surname', 'users.address', 'applied_jobs.amount', 'users.name as userName', 'users.surname as userSurname', 'applied_jobs.chef_id', 'applied_jobs.id as applied_jobs_id', 'bookings.payment_status', 'menus.id as menu_id', DB::raw('GROUP_CONCAT(DISTINCT menus.menu_name SEPARATOR ",") AS menu_names'))
            ->groupBy('bookings.name', 'bookings.surname', 'bookings.location', 'applied_jobs.amount', 'applied_jobs.chef_id', 'bookings.id', 'users.address')
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

            $todayBookings = User::join('applied_jobs AS aj1', 'users.id', '=', 'aj1.chef_id')
                ->join('bookings', 'bookings.id', '=', 'aj1.booking_id')
                ->join('booking_meals', 'aj1.booking_id', 'booking_meals.booking_id')
                ->where('bookings.payment_status', 'completed')
                ->where('users.status', '!=', 'deleted')
                ->where('bookings.status', '!=', 'deleted')
                ->whereDate('booking_meals.date', $currentDate)
                ->distinct('bookings.id')
                ->count();


            $totalChef = AppliedJobs::join('users', 'applied_jobs.chef_id', 'users.id')
                ->join('bookings', 'applied_jobs.booking_id', 'bookings.id')
                ->join('booking_meals', 'applied_jobs.booking_id', 'booking_meals.booking_id')
                ->where('bookings.payment_status', 'completed')
                ->where('users.role', 'chef')
                ->where('users.status', '!=', 'deleted')
                ->where('bookings.status', '!=', 'deleted')
                ->distinct('users.id')
                ->whereDate('booking_meals.date', $currentDate)
                ->count();

            $totalamount = User::join('applied_jobs AS aj1', 'users.id', '=', 'aj1.chef_id')
                ->join('bookings', 'bookings.id', '=', 'aj1.booking_id')
                ->join('booking_meals', 'bookings.id', '=', 'booking_meals.booking_id')
                ->where('users.status', '!=', 'deleted')
                ->where('bookings.status', '!=', 'deleted')
                ->where('bookings.payment_status', 'completed')
                ->whereIn('aj1.status', ['applied', 'hired'])
                ->whereDate('booking_meals.date', $currentDate)
                // ->groupBy('bookings.id') // Add this line to group the results by bookings
                ->sum('aj1.client_amount');

            // $totalamount = User::join('applied_jobs AS aj1', 'users.id', '=', 'aj1.chef_id')
            //     ->join('bookings', 'bookings.id', '=', 'aj1.booking_id')
            //     ->join('booking_meals', 'aj1.booking_id', 'bookings.id')
            //     ->where('bookings.booking_status', 'completed')
            //     ->whereIn('aj1.status', ['applied', 'hired'])
            //     ->whereDate('booking_meals.date', $currentDate)
            //     ->where('users.status', '!=', 'deleted')
            //     ->where('bookings.status', '!=', 'deleted')
            //     ->sum('aj1.amount');

            $pendingBooking = User::select('booking_meals.date as orderDate', 'aj1.amount', 'bookings.id as bookingId')
                ->join('applied_jobs AS aj1', 'users.id', '=', 'aj1.chef_id')
                ->join('bookings', 'bookings.id', '=', 'aj1.booking_id')
                ->join('booking_meals', 'aj1.booking_id', 'booking_meals.booking_id')
                ->where('bookings.booking_status', 'upcoming')
                ->whereIn('aj1.status', ['applied', 'hired'])
                ->where('users.status', '!=', 'deleted')
                ->where('bookings.status', '!=', 'deleted')
                ->orderby('bookings.id', 'desc')
                ->groupBy('bookings.id') // Group by user ID
                ->get();

            $completedBooking = Booking::select('bookings.id as bookingId', 'users.address', 'users.name', 'applied_jobs.created_at as ordertime')
                ->join('applied_jobs', 'bookings.id', '=', 'applied_jobs.booking_id')
                ->join('booking_meals', 'applied_jobs.booking_id', 'booking_meals.booking_id')
                ->join('users', 'applied_jobs.chef_id', '=', 'users.id')
                ->where('users.status', '!=', 'deleted')
                ->where('bookings.status', '!=', 'deleted')
                ->where('bookings.booking_status', 'upcoming')
                ->whereIn('applied_jobs.status', ['hired'])
                ->whereDate('booking_meals.date', $currentDate)
                ->orderby('bookings.id', 'desc')
                ->groupBy('bookings.id')
                ->get();

            //return $completedBooking;

            $startDate = Carbon::now()->subDays(7)->startOfDay();
            $endDate = Carbon::now()->endOfDay();

            $currentDate = Carbon::now();
            $previousWeekDate = Carbon::now()->copy()->subWeek();
            $previousWeekDate1 = $previousWeekDate->copy()->subWeek();

            $currentbookings = User::select('aj1.created_at')
                ->join('applied_jobs AS aj1', 'users.id', '=', 'aj1.chef_id')
                ->join('bookings', 'bookings.id', '=', 'aj1.booking_id')
                ->join('booking_meals', 'aj1.booking_id', 'booking_meals.booking_id')
                ->where('users.status', '!=', 'deleted')
                ->where('bookings.status', '!=', 'deleted')
                ->whereIn('aj1.status', ['applied', 'hired'])
                ->whereDate('booking_meals.date', '>=', $previousWeekDate)
                ->whereDate('booking_meals.date', '<=', $currentDate)
                ->count();

            $previousbookings = User::join('applied_jobs AS aj1', 'users.id', '=', 'aj1.chef_id')
                ->join('bookings', 'bookings.id', '=', 'aj1.booking_id')
                ->join('booking_meals', 'aj1.booking_id', 'booking_meals.booking_id')
                ->where('users.status', '!=', 'deleted')
                ->where('bookings.status', '!=', 'deleted')
                ->whereIn('aj1.status', ['applied', 'hired'])
                ->whereDate('booking_meals.date', '>=', $previousWeekDate1)
                ->whereDate('booking_meals.date', '<=', $previousWeekDate)
                ->count();

            $bookingprecentage = (($currentbookings + $previousbookings) / 2) * 100;

            $currentusers = Booking::join('applied_jobs', 'bookings.id', 'applied_jobs.booking_id')
                ->join('users', 'bookings.user_id', 'users.id')
                ->join('booking_meals', 'applied_jobs.booking_id', 'booking_meals.booking_id')
                ->where('users.role', 'user')
                ->where('users.status', '!=', 'deleted')
                ->where('bookings.status', '!=', 'deleted')
                ->whereIn('applied_jobs.status', ['applied', 'hired'])
                ->whereDate('booking_meals.date', '>=', $previousWeekDate1)
                ->whereDate('booking_meals.date', '<=', $currentDate)
                ->count();


            $previoususers = Booking::join('applied_jobs', 'bookings.id', 'applied_jobs.booking_id')
                ->join('users', 'bookings.user_id', 'users.id')
                ->join('booking_meals', 'applied_jobs.booking_id', 'booking_meals.booking_id')
                ->where('users.role', 'user')
                ->where('users.status', '!=', 'deleted')
                ->where('bookings.status', '!=', 'deleted')
                ->whereIn('applied_jobs.status', ['applied', 'hired'])
                ->whereDate('booking_meals.date', '>=', $previousWeekDate1)
                ->whereDate('booking_meals.date', '<=', $previousWeekDate)
                ->count();

            $usersprecentage = (($currentusers + $previoususers) / 2) * 100;

            $weeklyUsers = Booking::join('applied_jobs', 'bookings.id', 'applied_jobs.booking_id')
                ->join('users', 'bookings.user_id', 'users.id')
                ->join('booking_meals', 'bookings.id', '=', 'booking_meals.booking_id')
                ->where('users.role', 'user')
                ->where('users.status', '!=', 'deleted')
                ->where('bookings.status', '!=', 'deleted')
                ->whereBetween('booking_meals.date', [$startDate, $endDate])
                ->distinct('users.id')
                ->count();

            $weeklyBooking = User::join('applied_jobs AS aj1', 'users.id', '=', 'aj1.chef_id')
                ->join('bookings', 'bookings.id', '=', 'aj1.booking_id')
                ->join('booking_meals', 'bookings.id', '=', 'booking_meals.booking_id')
                ->where('users.status', '!=', 'deleted')
                ->where('bookings.status', '!=', 'deleted')
                ->whereIn('aj1.status', ['applied', 'hired'])
                ->whereBetween('booking_meals.date', [$startDate, $endDate])
                ->distinct('bookings.id') // Add this line to group the results by bookings
                ->count();

            if ($completedBooking) {
                return response()->json(['status' => true, 'message' => 'All booking data', 'totalChef' => $totalChef, 'pendingBooking' => $pendingBooking, 'completedBooking' => $completedBooking, 'weeklyUsers' => $weeklyUsers, 'weeklyBooking' => $weeklyBooking, 'todayBookings' => $todayBookings, 'totalChef' => $totalChef, 'totalamount' => $totalamount, 'currentbookings' => $currentbookings, 'previousbookings' => $previousbookings, 'bookingprecentage' => $bookingprecentage, 'currentusers' => $currentusers, 'previoususers' => $previoususers, 'usersprecentage' => $usersprecentage], 200);
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

            $todayBookings = User::join('applied_jobs AS aj1', 'users.id', '=', 'aj1.chef_id')
                ->join('bookings', 'bookings.id', '=', 'aj1.booking_id')
                ->join('booking_meals', 'aj1.booking_id', 'booking_meals.booking_id')
                ->where('bookings.payment_status', 'completed')
                ->where('aj1.chef_id', $request->id)
                ->where('users.status', '!=', 'deleted')
                ->where('bookings.status', '!=', 'deleted')
                ->whereDate('booking_meals.date', $currentDate)
                ->count();

            $pendingBookingCount = User::join('applied_jobs AS aj1', 'users.id', '=', 'aj1.chef_id')
                ->join('bookings', 'bookings.id', '=', 'aj1.booking_id')
                ->join('booking_meals', 'aj1.booking_id', '=', 'booking_meals.booking_id')
                ->where('bookings.booking_status', 'upcoming')
                ->where('aj1.chef_id', $request->id)
                ->where('users.status', '!=', 'deleted')
                ->where('bookings.status', '!=', 'deleted')
                ->whereDate('booking_meals.date', '>', $currentDate)
                ->distinct('bookings.id')
                ->count(); // Count the number of distinct booking_ids


            $totalamount = User::join('applied_jobs AS aj1', 'users.id', '=', 'aj1.chef_id')
                ->join('bookings', 'bookings.id', '=', 'aj1.booking_id')
                ->join('booking_meals', 'aj1.booking_id', 'booking_meals.booking_id')
                ->where('bookings.payment_status', 'completed')
                ->whereIn('aj1.status', ['applied', 'hired'])
                ->where('aj1.chef_id', $request->id)
                ->whereDate('booking_meals.date', $currentDate)
                ->where('users.status', '!=', 'deleted')
                ->where('bookings.status', '!=', 'deleted')
                ->where('bookings.status', 'active')
                ->sum('aj1.amount');


            $pendingBooking = User::select('booking_meals.date as orderDate', 'aj1.amount', 'bookings.id as bookingId')
                ->join('applied_jobs AS aj1', 'users.id', '=', 'aj1.chef_id')
                ->join('bookings', 'bookings.id', '=', 'aj1.booking_id')
                ->join('booking_meals', 'aj1.booking_id', 'booking_meals.booking_id')
                ->where('bookings.booking_status', 'upcoming')
                ->where('users.status', '!=', 'deleted')
                ->where('bookings.status', '!=', 'deleted')
                ->whereIn('aj1.status', ['applied', 'hired'])
                ->where('aj1.chef_id', $request->id)
                ->groupBy('bookings.id') // Group by user ID
                ->orderby('bookings.id', 'desc')
                ->get();

            $completedBooking = Booking::select('bookings.id as bookingId', 'users.address', 'users.name', 'applied_jobs.created_at as ordertime', 'booking_meals.date')
                ->join('applied_jobs', 'bookings.id', '=', 'applied_jobs.booking_id')
                ->join('users', 'applied_jobs.chef_id', '=', 'users.id')
                ->join('booking_meals', 'applied_jobs.booking_id', 'booking_meals.booking_id')
                ->where('bookings.booking_status', 'upcoming')
                ->where('users.status', '!=', 'deleted')
                ->where('bookings.status', '!=', 'deleted')
                ->whereIn('applied_jobs.status', ['hired'])
                ->whereDate('booking_meals.date', $currentDate)
                ->where('applied_jobs.chef_id', $request->id)
                ->orderby('applied_jobs.id', 'desc')
                ->groupBy('bookings.id')
                ->get();

            $startDate = Carbon::now()->subDays(7)->startOfDay();
            $endDate = Carbon::now()->endOfDay();

            $currentDate = Carbon::now();
            $previousWeekDate = Carbon::now()->copy()->subWeek();
            $previousWeekDate1 = $previousWeekDate->copy()->subWeek();

            $currentbookings = User::join('applied_jobs AS aj1', 'users.id', '=', 'aj1.chef_id')
                ->join('bookings', 'bookings.id', '=', 'aj1.booking_id')
                ->join('booking_meals', 'aj1.booking_id', 'booking_meals.booking_id')
                ->where('aj1.chef_id', $request->id)
                ->where('users.status', '!=', 'deleted')
                ->where('bookings.status', '!=', 'deleted')
                ->where('aj1.status', ['applied', 'hired'])
                ->whereDate('booking_meals.date', '>=', $previousWeekDate)
                ->whereDate('booking_meals.date', '<=', $currentDate)
                ->count();

            $previousbookings = User::join('applied_jobs AS aj1', 'users.id', '=', 'aj1.chef_id')
                ->join('bookings', 'bookings.id', '=', 'aj1.booking_id')
                ->join('booking_meals', 'aj1.booking_id', 'booking_meals.booking_id')
                ->where('aj1.chef_id', $request->id)
                ->where('users.status', '!=', 'deleted')
                ->where('bookings.status', '!=', 'deleted')
                ->where('aj1.status', ['applied', 'hired'])
                ->whereDate('booking_meals.date', '>=', $previousWeekDate)
                ->whereDate('booking_meals.date', '<=', $currentDate)
                ->count();

            $bookingprecentage = (($currentbookings + $previousbookings) / 2) * 100;

            $weeklyBooking = User::join('applied_jobs AS aj1', 'users.id', '=', 'aj1.chef_id')
                ->join('bookings', 'bookings.id', '=', 'aj1.booking_id')
                ->join('booking_meals', 'aj1.booking_id', 'booking_meals.booking_id')
                ->where('aj1.chef_id', $request->id)
                ->where('users.status', '!=', 'deleted')
                ->where('bookings.status', '!=', 'deleted')
                ->where('aj1.status', ['applied', 'hired'])
                ->whereBetween('booking_meals.date', [$startDate, $endDate])
                ->count();

            return response()->json(['status' => true, 'message' => 'All booking data', 'todayBookings' => $todayBookings, 'totalamount' => $totalamount, 'pendingBookingCount' => $pendingBookingCount, 'pendingBooking' => $pendingBooking, 'weeklyBooking' => $weeklyBooking, 'completedBooking' => $completedBooking, 'currentbookings' => $currentbookings, 'previousbookings' => $previousbookings, 'bookingprecentage' => $bookingprecentage], 200);
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
    public function get_chef_booking(Request $request)
    {
        try {
            $bookings = Booking::select('users.address', 'bookings.id', 'applied_jobs.booking_id', 'applied_jobs.status as applystatus', 'applied_jobs.created_at as applydate')
                ->join('applied_jobs', 'bookings.id', 'applied_jobs.booking_id')
                ->join('users', 'applied_jobs.chef_id', 'users.id')
                ->where('applied_jobs.jobs_status', 'active')
                ->where('applied_jobs.chef_id', $request->id)->get();
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
    public function get_concierge_chef_by_booking(Request $request)
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
                    'b.status'
                )
                ->select(
                    'b.name',
                    'b.payment_status',
                    'b.assigned_to_user_id',
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
                ->where('u.created_by', $request->id)
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
    public function get_concierge_assigned_booking(Request $request)
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
                )
                ->where('bookings.status', '=', 'active')
                ->where('applied_jobs.status', 'hired')
                ->where('users.created_by', $request->id)
                ->where('users.status', '!=', 'deleted')
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
    public function get_all_concierge_bookings(Request $request)
    {
        try {
            $currentDate = Carbon::now()->toDateString();
            $todayBookings = User::join('applied_jobs AS aj1', 'users.id', '=', 'aj1.chef_id')
                ->join('bookings', 'bookings.id', '=', 'aj1.booking_id')
                ->join('booking_meals', 'aj1.booking_id', 'booking_meals.booking_id')
                ->where('bookings.payment_status', 'completed')
                ->where('users.created_by', $request->id)
                ->where('users.status', '!=', 'deleted')
                ->where('bookings.status', '!=', 'deleted')
                ->whereDate('booking_meals.date', $currentDate)
                ->count();

            $totalChef = User::join('applied_jobs AS aj1', 'users.id', '=', 'aj1.chef_id')
                ->join('bookings', 'bookings.id', '=', 'aj1.booking_id')
                ->join('booking_meals', 'aj1.booking_id', 'booking_meals.booking_id')
                ->where('bookings.payment_status', 'completed')
                ->whereIn('aj1.status', ['applied', 'hired'])
                // ->distinct('users.id')
                ->where('users.created_by', $request->id)
                ->whereDate('booking_meals.date', $currentDate)
                ->where('users.status', '!=', 'deleted')
                ->where('bookings.status', '!=', 'deleted')
                ->count();

            $totalamount = User::join('applied_jobs AS aj1', 'users.id', '=', 'aj1.chef_id')
                ->join('bookings', 'bookings.id', '=', 'aj1.booking_id')
                ->join('booking_meals', 'aj1.booking_id', 'booking_meals.booking_id')
                ->where('bookings.payment_status', 'completed')
                ->whereIn('aj1.status', ['applied', 'hired'])
                ->where('users.created_by', $request->id)
                ->whereDate('booking_meals.date', $currentDate)
                ->where('users.status', '!=', 'deleted')
                ->where('bookings.status', '!=', 'deleted')
                ->sum('aj1.amount');

            $pendingBooking = User::select('booking_meals.date as orderDate', 'aj1.amount', 'bookings.id as bookingId')
                ->join('applied_jobs AS aj1', 'users.id', '=', 'aj1.chef_id')
                ->join('bookings', 'bookings.id', '=', 'aj1.booking_id')
                ->join('booking_meals', 'aj1.booking_id', 'booking_meals.booking_id')
                ->where('bookings.booking_status', 'upcoming')
                ->whereIn('aj1.status', ['applied', 'hired'])
                ->where('users.created_by', $request->id)
                ->where('users.status', '!=', 'deleted')
                ->where('bookings.status', '!=', 'deleted')
                ->orderby('bookings.id', 'desc')
                ->groupBy('bookings.id') // Group by user ID
                ->get();

            $completedBooking = Booking::select('bookings.id as bookingId', 'users.address', 'users.name', 'applied_jobs.created_at as ordertime')
                ->join('applied_jobs', 'bookings.id', '=', 'applied_jobs.booking_id')
                ->join('users', 'applied_jobs.chef_id', '=', 'users.id')
                ->join('booking_meals', 'applied_jobs.booking_id', 'booking_meals.booking_id')
                ->where('bookings.status', 'active')
                ->where('bookings.booking_status', 'upcoming')
                ->where('users.status', '!=', 'deleted')
                ->where('bookings.status', '!=', 'deleted')
                ->whereIn('applied_jobs.status', ['applied', 'hired'])
                ->whereDate('booking_meals.date', $currentDate)
                ->where('users.created_by', $request->id)
                ->orderby('bookings.id', 'desc')
                ->groupBy('bookings.id')
                ->get();

            $startDate = Carbon::now()->subDays(7)->startOfDay();
            $endDate = Carbon::now()->endOfDay();

            $weeklyUsers = Booking::join('applied_jobs', 'bookings.id', 'applied_jobs.booking_id')
                ->join('users', 'bookings.user_id', 'users.id')
                ->join('booking_meals', 'applied_jobs.booking_id', 'booking_meals.booking_id')
                ->where('users.role', 'user')
                ->where('applied_jobs.status', ['applied', 'hired'])
                ->where('users.status', '!=', 'deleted')
                ->where('bookings.status', '!=', 'deleted')
                ->where('users.created_by', $request->id)
                ->whereBetween('booking_meals.date', [$startDate, $endDate])
                ->count();

            $weeklyBooking = User::join('applied_jobs AS aj1', 'users.id', '=', 'aj1.chef_id')
                ->join('bookings', 'bookings.id', '=', 'aj1.booking_id')
                ->join('booking_meals', 'aj1.booking_id', 'booking_meals.booking_id')
                ->where('users.created_by', $request->id)
                ->where('users.status', '!=', 'deleted')
                ->where('bookings.status', '!=', 'deleted')
                ->whereIn('aj1.status', ['applied', 'hired'])
                ->whereBetween('booking_meals.date', [$startDate, $endDate])
                ->count();

            $currentDate = Carbon::now();
            $previousWeekDate = Carbon::now()->copy()->subWeek();
            $previousWeekDate1 = $previousWeekDate->copy()->subWeek();

            $currentbookings = User::select('aj1.created_at')
                ->join('applied_jobs AS aj1', 'users.id', '=', 'aj1.chef_id')
                ->join('bookings', 'bookings.id', '=', 'aj1.booking_id')
                ->join('booking_meals', 'aj1.booking_id', 'booking_meals.booking_id')
                ->where('users.status', '!=', 'deleted')
                ->where('bookings.status', '!=', 'deleted')
                ->where('users.created_by', $request->id)
                ->whereIn('aj1.status', ['applied', 'hired'])
                ->whereDate('booking_meals.date', '>=', $previousWeekDate)
                ->whereDate('booking_meals.date', '<=', $currentDate)
                ->count();

            $previousbookings = User::join('applied_jobs AS aj1', 'users.id', '=', 'aj1.chef_id')
                ->join('bookings', 'bookings.id', '=', 'aj1.booking_id')
                ->join('booking_meals', 'aj1.booking_id', 'booking_meals.booking_id')
                ->where('users.status', '!=', 'deleted')
                ->where('bookings.status', '!=', 'deleted')
                ->where('users.created_by', $request->id)
                ->whereIn('aj1.status', ['applied', 'hired'])
                ->whereDate('booking_meals.date', '>=', $previousWeekDate1)
                ->whereDate('booking_meals.date', '<=', $previousWeekDate)
                ->count();

            $bookingprecentage = (($currentbookings + $previousbookings) / 2) * 100;

            $currentusers = Booking::join('applied_jobs', 'bookings.id', 'applied_jobs.booking_id')
                ->join('users', 'bookings.user_id', 'users.id')
                ->join('booking_meals', 'applied_jobs.booking_id', 'booking_meals.booking_id')
                ->where('users.role', 'user')
                ->where('users.status', '!=', 'deleted')
                ->where('bookings.status', '!=', 'deleted')
                ->where('users.created_by', $request->id)
                ->whereDate('booking_meals.date', '>=', $previousWeekDate1)
                ->whereDate('booking_meals.date', '<=', $currentDate)
                ->count();

            $previoususers = Booking::join('applied_jobs', 'bookings.id', 'applied_jobs.booking_id')
                ->join('users', 'bookings.user_id', 'users.id')
                ->join('booking_meals', 'applied_jobs.booking_id', 'booking_meals.booking_id')
                ->where('users.role', 'user')
                ->where('users.status', '!=', 'deleted')
                ->where('bookings.status', '!=', 'deleted')
                ->where('users.created_by', $request->id)
                ->whereDate('booking_meals.date', '>=', $previousWeekDate1)
                ->whereDate('booking_meals.date', '<=', $previousWeekDate)
                ->count();

            $usersprecentage = (($currentusers + $previoususers) / 2) * 100;



            // $weeklyBooking = Booking::whereBetween('created_at', [$startDate, $endDate])
            // ->where('users.created_by',$request->id)
            // ->count();

            if ($completedBooking) {
                return response()->json(['status' => true, 'message' => 'All booking data', 'totalChef' => $totalChef, 'pendingBooking' => $pendingBooking, 'completedBooking' => $completedBooking, 'weeklyUsers' => $weeklyUsers, 'weeklyBooking' => $weeklyBooking, 'todayBookings' => $todayBookings, 'totalChef' => $totalChef, 'totalamount' => $totalamount, 'currentbookings' => $currentbookings, 'previousbookings' => $previousbookings, 'bookingprecentage' => $bookingprecentage, 'currentusers' => $currentusers, 'previoususers' => $previoususers, 'usersprecentage' => $usersprecentage], 200);
            } else {
                return response()->json(['status' => false, 'message' => 'All booking'], 400);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function get_bookings_count(Request $request)
    {
        try {
            $available_booking = Booking::join('users', 'bookings.user_id', 'users.id')
                ->leftJoin('applied_jobs', 'bookings.id', '=', 'applied_jobs.booking_id')
                ->where('users.status', '!=', 'deleted')
                ->where('bookings.status', '!=', 'deleted')
                ->whereNull('applied_jobs.booking_id')
                ->count();

            $admin_available_booking = Booking::join('users', 'bookings.user_id', 'users.id')
                ->leftJoin('applied_jobs', function ($join) {
                    $join->on('bookings.id', '=', 'applied_jobs.booking_id')
                        //->where('applied_jobs.status', '=', 'hired');
                        ->whereIn('applied_jobs.status', ['hired', 'discussion']);
                })
                ->where('users.status', '!=', 'deleted')
                ->where('bookings.status', '!=', 'deleted')
                ->whereNull('applied_jobs.booking_id')
                ->count();


            $allBookings = DB::table('users')
                ->join('bookings', 'users.id', '=', 'bookings.user_id')
                ->leftJoin('applied_jobs', function ($join) {
                    $join->on('bookings.id', '=', 'applied_jobs.booking_id')
                        //->where('applied_jobs.status', '=', 'hired');
                        ->whereIn('applied_jobs.status', ['hired', 'discussion']);
                })
                ->where('users.status', '!=', 'deleted')
                ->whereNull('applied_jobs.booking_id')
                ->count();

            $hired_booking = User::join('bookings', 'users.id', 'bookings.user_id')
                ->join('applied_jobs', 'bookings.id', 'applied_jobs.booking_id')
                ->where('users.status', '!=', 'deleted')
                ->where('bookings.status', '!=', 'deleted')
                //->where('applied_jobs.status', 'hired')
                ->whereIn('applied_jobs.status', ['hired', 'discussion'])
                ->count();

            return response()->json([
                'status' => true,
                'message' => 'All chef fetched successfully.',
                'available_booking' => $available_booking,
                'allbooking' => $allBookings,
                'hired_booking' => $hired_booking,
                'admin_available_booking' => $admin_available_booking
            ]);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    public function get_applied_chef_bookings_count(Request $request)
    {
        try {
            $today = now()->toDateString();

            $available_booking = Booking::join('users', 'bookings.user_id', 'users.id')
                ->leftJoin('applied_jobs', function ($join) use ($request) {
                    $join->on('applied_jobs.booking_id', '=', 'bookings.id')
                        ->where('applied_jobs.chef_id', '=', $request->id);
                })
                ->where('users.status', '!=', 'deleted')
                ->where('bookings.status', '!=', 'deleted')
                ->whereNull('applied_jobs.booking_id')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('applied_jobs')
                        ->whereColumn('applied_jobs.booking_id', '=', 'bookings.id')
                        //->where('applied_jobs.status', 'hired');
                        ->whereIn('applied_jobs.status', ['hired', 'discussion']);
                })
                ->count();

            $applied_booking = User::join('bookings', 'users.id', 'bookings.user_id')
                ->join('applied_jobs', function ($join) use ($request) {
                    $join->on('applied_jobs.booking_id', '=', 'bookings.id')
                        ->where('applied_jobs.chef_id', '=', $request->id);
                })
                ->where('users.status', '!=', 'deleted')
                ->where('applied_jobs.status', 'applied')
                ->where('bookings.status', '!=', 'deleted')
                ->where('applied_jobs.chef_id', $request->id)
                ->count();
            $hired_booking = User::join('bookings', 'users.id', 'bookings.user_id')
                ->join('applied_jobs', function ($join) use ($request) {
                    $join->on('applied_jobs.booking_id', '=', 'bookings.id')
                        ->where('applied_jobs.chef_id', '=', $request->id);
                })
                ->where('users.status', '!=', 'deleted')
                ->where('applied_jobs.status', 'hired')
                ->where('bookings.status', '!=', 'deleted')
                ->where('applied_jobs.chef_id', $request->id)
                ->count();
            return response()->json([
                'status' => true,
                'message' => 'All chef fetched successfully.',
                'available_booking' => $available_booking,
                'applied_booking' => $applied_booking,
                'hired_booking' => $hired_booking
            ]);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    public function concierge_bookings_count(Request $request)
    {
        try {
            $allBookings = User::join('bookings', 'users.id', '=', 'bookings.user_id')
                ->leftJoin('applied_jobs', 'bookings.id', '=', 'applied_jobs.booking_id')
                ->where('users.status', '!=', 'deleted')
                ->where(function ($query) {
                    $query->where('applied_jobs.status', 'applied')
                        ->orWhereNull('applied_jobs.status');
                })
                ->where('bookings.status', '!=', 'deleted')
                ->where('users.created_by', $request->id)
                ->count();
            $hired_booking = User::join('bookings', 'users.id', 'bookings.user_id')
                ->join('applied_jobs', 'bookings.id', 'applied_jobs.booking_id')
                ->where('users.status', '!=', 'deleted')
                ->where('applied_jobs.status', 'hired')
                ->where('bookings.status', '!=', 'deleted')
                ->where('applied_jobs.created_by', $request->id)
                ->count();
            return response()->json([
                'status' => true,
                'message' => 'All chef fetched successfully.',
                'allbooking' => $allBookings,
                'hired_booking' => $hired_booking
            ]);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    public function get_concierge_filter_by_booking($id, $type)
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
                    'b.status'
                )
                ->select(
                    'b.name',
                    'b.payment_status',
                    'b.assigned_to_user_id',
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
                ->where('u.status', '!=', 'deleted')
                ->where('u.created_by', $id)
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

    public function assigned_booking_by_admin(Request $request)
    {
        $bookingdatecheck = BookingMeals::select('date')->where('booking_id', $request->booking_id)->get();
        $bokking = Booking::select('date')
            ->join('booking_meals', 'bookings.id', '=', 'booking_meals.booking_id')
            ->where('bookings.assigned_to_user_id', $request->chef_id)
            ->where('bookings.id', '!=', $request->booking_id)
            ->get();

        // Extract dates from the result sets
        $datesFromBookingDateCheck = $bookingdatecheck->pluck('date')->toArray();
        $datesFromBokking = $bokking->pluck('date')->toArray();

        // Check if any date from $bokking matches with $bookingdatecheck
        $intersectedDates = array_intersect($datesFromBokking, $datesFromBookingDateCheck);

        if (!empty($intersectedDates)) {
            // Chef is already booked between these dates

            return response()->json(['status' => false, 'message' => 'This Chef is already booked between these date',]);


        } else {
            $booking = new AppliedJobs();
            $booking->booking_id = $request->booking_id;
            $booking->chef_id = $request->chef_id;
            $booking->amount = $request->amount;
            $booking->client_amount = $request->client_amount;
            $booking->admin_amount = $request->admin_amount;
            $booking->user_show = 'visible';
            $booking->menu = $request->menu;
            $booking->status = $request->payment_status == 'pending' ? 'applied' : 'hired';
            $appliedJobs = $booking->save();

            if ($appliedJobs) {

                $chef = User::select('name', 'email')->where('id', $request->chef_id)->first();
                $booking = Booking::select('name', 'location', 'id', 'email')->where('id', $request->booking_id)->first();

                $bookingDate = Booking::select('booking_meals.category', 'bookings.user_id as client_id', DB::raw('GROUP_CONCAT(DISTINCT booking_meals.date) AS dates'))
                    ->join('booking_meals', 'bookings.id', '=', 'booking_meals.booking_id')
                    ->where('bookings.id', $request->booking_id)
                    ->groupBy('booking_meals.booking_id')
                    ->first();

                $admindata = User::select('email')->where('role', 'admin')->first();
                // Get chef IDs or chefs data

                $appliedJob = AppliedJobs::where('booking_id', $request->booking_id)
                    ->where('chef_id', $request->chef_id)
                    ->first();
                if ($appliedJob) {
                    $amount = $appliedJob->amount;
                    $appliedId = $appliedJob->id;
                } else {
                    // Handle the case where the amount is not found or handle default amount
                    $amount = 0; // Assuming a default value
                    $appliedId = '';
                }
                $chefs = User::where('id', $request->chef_id)->get();
                $this->sendLocationNotification($chefs, $booking, $admindata, $request, $amount);

                $data = [
                    'chef_id' => $request->chef_id,
                    'chef_name' => $chef->name,
                    'email' => $chef->email,
                    'booking_id' => $request->booking_id,
                    'user_name' => $booking->name,
                    'user_location' => $booking->location,
                    'booking_type' => $bookingDate->category,
                    'booking_date' => $bookingDate->dates,
                    'admin_email' => $admindata->email,
                    'user_email' => $booking->email,
                    'client_amount' => $request->client_amount,
                    'client_id' => $bookingDate->client_id,
                    'applied_id' => $appliedId,
                ];


                if ($request->payment_status == 'pending') {

                    Mail::send('emails.hiredchefMailToUser', ['data' => $data], function ($message) use ($data) {
                        $message->from(config('mail.from.address'), "Private Chefs");
                        $message->to($data['user_email']);
                        $message->bcc([$data['admin_email'], 'confirmations@privatechefsworld.com']);
                        $message->subject('Culinary Experience Tailored Just for You! ');
                    });

                    return response()->json(['message' => 'Booking mail and along with payment link  send to user', 'status' => true]);

                } else {

                    Mail::send('emails.hiredchefMail', ['data' => $data], function ($message) use ($data) {
                        $message->from(config('mail.from.address'), "Private Chefs");
                        $message->to($data['email']);
                        $message->bcc([$data['admin_email'], 'confirmations@privatechefsworld.com']);
                        $message->subject('You have Been Chosen to Create Culinary Magic!');
                    });

                    return response()->json(['message' => 'Hired mail has been successfully sent to chef', 'status' => true]);
                }


            } else {
                return response()->json(['status' => false, 'message' => 'There has been error in saving the booking',]);
            }
        }
    }

    public function savePayment(Request $request)
    {
        //Stripe::setApiKey(env('STRIPE_SECRET'));
        Stripe::setApiKey('sk_test_8yTMfGjWta7zVzyhB6S3N2ws');
        try {
            $client = User::select('name', 'email')->where('id', $request->user_id)->first();
            $chef = User::select('name', 'email')->where('id', $request->chef_id)->first();
            $amount = $request->amount * 100;

            // Create a customer
            $customer = Customer::create([
                'name' => $client->name,
                'email' => $client->email,
            ]);

            // Create a token for the card
            $token = \Stripe\Token::create([
                'card' => [
                    'name' => $client->name,
                    'number' => $request->card_number,
                    'exp_month' => $request->exp_month,
                    'exp_year' => $request->exp_year,
                    'cvc' => $request->cvc,
                ],
            ]);
            $customer->sources->create(['source' => $token->id]);

            // Create a charge
            $charge = Charge::create([
                'amount' => $amount,
                'currency' => 'eur',
                'customer' => $customer->id,
                'source' => $customer->default_source,
            ]);

            $booking = Booking::find($request->booking_id);
            $booking->charge_id = $charge['id'];
            $booking->assigned_to_user_id = $request->chef_id;
            $booking->charge_amount = $request->amount;
            $booking->charge_receipt_url = $charge['receipt_url'];
            $booking->charge_created_date = $charge['created'];
            $booking->payment_status = 'completed';

            if ($booking->save()) {
                if ($request->applied_id) {
                    // Update the applied job status to 'hired'
                    $updatebooking = AppliedJobs::where('id', $request->applied_id)->update([
                        'status' => 'hired',
                    ]);
                }

                $bookingDate = Booking::select('booking_meals.category', 'bookings.user_id as client_id', DB::raw('GROUP_CONCAT(DISTINCT booking_meals.date) AS dates'))
                    ->join('booking_meals', 'bookings.id', '=', 'booking_meals.booking_id')
                    ->where('bookings.id', $request->booking_id)
                    ->groupBy('booking_meals.booking_id')
                    ->first();

                $admindata = User::select('email')->where('role', 'admin')->first();

                // Define the $data variable for the email
                $data = [
                    'chef_id' => $request->chef_id,
                    'chef_name' => $chef->name,
                    'email' => $chef->email,
                    'booking_id' => $request->booking_id,
                    'user_name' => $client->name,
                    'user_location' => $booking->location,
                    'booking_type' => $bookingDate->category,
                    'booking_date' => $bookingDate->dates,
                    'admin_email' => $admindata->email,
                    'user_email' => $client->email,
                    'client_amount' => $request->amount,
                    'client_id' => $bookingDate->client_id,
                ];

                // Send an email to the chef
                // Mail::send('emails.hiredchefMail', ['data' => $data], function ($message) use ($data) {
                //     $message->from(config('mail.from.address'), "You have been hired");
                //     $message->to($data['email']);
                //     $message->bcc($data['admin_email']);
                //     $message->subject('You have Been Chosen to Create Culinary Magic!');
                // });

                // Send an email to the admin
                $dataAdmin = [
                    'client_name' => $client->name,
                    'client_email' => $client->email,
                    'chef_name' => $chef->name,
                    'chef_email' => $chef->email,
                    'amount' => $request->amount,
                    'admin_email' => $admindata->email,
                ];

                Mail::send('emails.bookingPayment', ['data' => $dataAdmin], function ($message) use ($dataAdmin) {
                    $message->from(config('mail.from.address'), "Private Chefs");
                    $message->subject('Booking Payment Confirmation');
                    $message->to([$dataAdmin['admin_email'], 'confirmations@privatechefsworld.com']);
                });

                // Create and save a notification
                $notification = new Notification();
                $notification->notify_to = '1';
                $notification->description = "A user named $client->name has just made a booking payment of $$request->amount for chef $chef->name";
                $notification->type = 'booking';
                $notification->save();

                // Send a user confirmation email
                Mail::send('emails.userbookingconfirmation', ['data' => $data], function ($message) use ($data) {
                    $message->from(config('mail.from.address'), "Private Chefs");
                    $message->to($data['user_email']);  // Send it to the client's email address
                    $message->bcc([$data['admin_email'], 'confirmations@privatechefsworld.com']);
                    $message->subject(' Your Culinary Experience Awaits – Booking Confirmation');
                });

                return response()->json(['status' => true, 'message' => 'Payment successful'], 200);
            }

        } catch (CardException $e) {
            // Card error occurred
            $body = $e->getJsonBody();
            $error = $body['error'];

            return response()->json(['status' => false, 'message' => $error['message']], 422);
        } catch (\Exception $e) {
            // Other error occurred
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }


    private function sendLocationNotification($chefs, $booking, $admin, $request, $amount)
    {
        foreach ($chefs as $chef) {

            // Save to invite_for_proposal table
            $invite = new InviteProposal();
            $invite->user_id = $request->user_id;
            $invite->booking_id = $booking->id;
            $invite->chef_id = $chef->user_id;
            $invite->status = 'active';
            $invite->save();

            $totalGuests = $booking->adults + $booking->childrens + $booking->teens;
            $bookingId = $booking->id;
            // Initialize $dateMeals array
            $dateMeals = [];
            $allergies = [];
            $serviceData = DB::table('bookings')
                ->join('service_choices', 'bookings.service_id', '=', 'service_choices.id')
                ->join('users', 'bookings.user_id', '=', 'users.id')
                ->where('bookings.id', $bookingId)
                ->select('service_choices.service_name', 'users.name as user_name')
                ->first();
            $bookingMealsData = DB::table('booking_meals')
                ->join('bookings', 'booking_meals.booking_id', '=', 'bookings.id')
                ->select('booking_meals.date', 'booking_meals.breakfast', 'booking_meals.lunch', 'booking_meals.dinner')
                ->where('bookings.id', $bookingId)
                ->get();
            $bookingAllergiesData = DB::table('bookings')
                ->join('allergies', function ($join) {
                    $join->on('bookings.allergies_id', 'like', DB::raw("concat('%', allergies.id, '%')"));
                })
                ->where('bookings.id', $bookingId)
                ->pluck('allergies.allergy_name')
                ->toArray();
            $allergies = $bookingAllergiesData;

            $firstDate = null;
            $lastDate = null;
            $mealStatuses = [
                'breakfast' => [
                    'yes' => '✅',
                    'no' => '❌',
                ],
                'lunch' => [
                    'yes' => '✅',
                    'no' => '❌',
                ],
                'dinner' => [
                    'yes' => '✅',
                    'no' => '❌',
                ],
            ];
            // Loop through the booking meals data
            foreach ($bookingMealsData as $meal) {
                // Extract date and meals information
                $date = $meal->date;
                $breakfast = $meal->breakfast;
                $lunch = $meal->lunch;
                $dinner = $meal->dinner;

                // Store the first date if it hasn't been set yet
                if ($firstDate === null) {
                    $firstDate = $date;
                }
                // Always update the last date to ensure it gets the latest date from the loop
                $lastDate = $date;
                // Determine meal status strings based on meal values
                $breakfastStatus = isset($mealStatuses['breakfast'][$breakfast]) ? $mealStatuses['breakfast'][$breakfast] : '';
                $lunchStatus = isset($mealStatuses['lunch'][$lunch]) ? $mealStatuses['lunch'][$lunch] : '';
                $dinnerStatus = isset($mealStatuses['dinner'][$dinner]) ? $mealStatuses['dinner'][$dinner] : '';
                // Store meals information with meal status strings for each date if needed for other processing
                $dateMeals[$date] = [
                    'breakfast' => $breakfastStatus,
                    'lunch' => $lunchStatus,
                    'dinner' => $dinnerStatus,
                ];
            }

            $data = [
                'name' => $chef->name,
                'booking_location' => $booking->location,
                'booking_id' => $booking->id,
                'booking_notes' => $booking->notes,
                'total_guests' => $totalGuests,
                'email' => $chef->email,
                'admin_email' => $admin->email,
                'service_name' => $serviceData->service_name,
                'user_Name' => $serviceData->user_name,
                'date_ranges' => $firstDate . ' to ' . $lastDate,
                'dateMeals' => $dateMeals,
                'allergies' => $allergies,
                'chef_amount' => $amount,
            ];

            // Mail::send('emails.chefLocation', ['data' => $data, 'dateMeals' => $dateMeals], function ($message) use ($data) {
            //     $message->from(config('mail.from.address'), "Private Chefs");
            //     // $message->bcc([$data['admin_email'], 'info@privatechefsworld.com']);
            //     $message->subject("New Booking Alert: Opportunity in {$data['booking_location']}");
            //     $message->to($data['email']);
            // });
            Mail::send(new InvitationEmail($data, $dateMeals));


            $notification = new Notification();
            $notification->notify_to = $chef->user_id;
            $notification->description = "A  $request->name has just made a booking within your area. Please make necessary arrangements and ensure a seamless experience for them. Thank you for your prompt attention.";
            $notification->type = 'location_notification';
            $notification->save();
        }
    }

    public function getUserBookingPayment($id)
    {
        try {

            $userbookings = DB::table('users')
                ->join('bookings', 'users.id', '=', 'bookings.user_id')
                // ->leftJoin('applied_jobs', 'applied_jobs.booking_id', '=', 'bookings.id')
                // ->leftJoin('users as u1', 'u1.id', '=', 'applied_jobs.chef_id')
                // ->join('booking_meals', 'bookings.id', '=', 'booking_meals.booking_id')
                // ->join('service_choices', 'service_choices.id', '=', 'bookings.service_id')
                ->select('bookings.*')
                ->where('bookings.status', '=', 'active')
                ->where('bookings.payment_status', '=', 'completed')
                ->where('bookings.user_id', $id)
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

    public function ResendPaymentLink(Request $request){
        try {
            $chef = User::select('name', 'email')->where('id', $request->chef_id)->first();
            $booking = Booking::select('name', 'location', 'id', 'email')->where('id', $request->booking_id)->first();

            $bookingDate = Booking::select('booking_meals.category', 'bookings.user_id as client_id', DB::raw('GROUP_CONCAT(DISTINCT booking_meals.date) AS dates'))
                ->join('booking_meals', 'bookings.id', '=', 'booking_meals.booking_id')
                ->where('bookings.id', $request->booking_id)
                ->groupBy('booking_meals.booking_id')
                ->first();

            $admindata = User::select('email')->where('role', 'admin')->first();
            // Get chef IDs or chefs data

            $appliedJob = AppliedJobs::where('booking_id', $request->booking_id)
                ->where('chef_id', $request->chef_id)
                ->first();
            if ($appliedJob) {
                $amount = $appliedJob->amount;
            } else {
                // Handle the case where the amount is not found or handle default amount
                $amount = 0; // Assuming a default value
            }
            $chefs = User::where('id', $request->chef_id)->get();
            $this->sendLocationNotification($chefs, $booking, $admindata, $request, $amount);

            $data = [
                'chef_id' => $request->chef_id,
                'chef_name' => $chef->name,
                'email' => $chef->email,
                'booking_id' => $request->booking_id,
                'user_name' => $booking->name,
                'user_location' => $booking->location,
                'booking_type' => $bookingDate->category,
                'booking_date' => $bookingDate->dates,
                'admin_email' => $admindata->email,
                'user_email' => $booking->email,
                'client_amount' => $request->client_amount,
                'client_id' => $bookingDate->client_id,
                'applied_id' => $request->applied_id
            ];


            if ($request->payment_status == 'pending') {

                Mail::send('emails.hiredchefMailToUser', ['data' => $data], function ($message) use ($data) {
                    $message->from(config('mail.from.address'), "Private Chefs");
                    $message->to($data['user_email']);
                    $message->bcc([$data['admin_email'], 'confirmations@privatechefsworld.com']);
                    $message->subject('Culinary Experience Tailored Just for You! ');
                });

                return response()->json(['message' => 'Booking mail and along with payment link  send to user', 'status' => true]);

            } else {

                Mail::send('emails.hiredchefMail', ['data' => $data], function ($message) use ($data) {
                    $message->from(config('mail.from.address'), "Private Chefs");
                    $message->to($data['email']);
                    $message->bcc([$data['admin_email'], 'confirmations@privatechefsworld.com']);
                    $message->subject('You have Been Chosen to Create Culinary Magic!');
                });

                return response()->json(['message' => 'Hired mail has been successfully sent to chef', 'status' => true]);
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }

}