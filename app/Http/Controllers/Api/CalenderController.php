<?php

namespace App\Http\Controllers\Api;

use App\Models\Booking;
use App\Models\BookingMeals;
use App\Models\User;
use App\Models\AppliedJobs;
use App\Models\Notification;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CalenderController extends Controller
{
    public function get_admin_calender_bookings(Request $request)
    {
        try {
            $bookings = DB::table('bookings')
                ->select('users.address', 'users.pic', 'users.name', 'applied_jobs.created_at as dates')
                ->join('applied_jobs', 'bookings.id', '=', 'applied_jobs.booking_id')
                ->join('users', 'applied_jobs.chef_id', 'users.id')
                ->join('booking_meals', 'applied_jobs.booking_id', 'booking_meals.booking_id')
                ->select('users.pic', 'users.name', DB::raw('GROUP_CONCAT(booking_meals.date) AS dates'), 'bookings.id', 'booking_meals.category')
                ->groupBy('users.pic', 'bookings.location', 'users.name')
                ->where('bookings.status', '!=', 'deleted')
                ->where('users.status', '!=', 'deleted')
                ->get();

            return response()->json([
                'status' => true,
                'bookings' => $bookings
            ]);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    public function get_chef_calender_bookings(Request $request)
    {
        try {
            $bookings = DB::table('bookings')
                ->select('users.address', 'users.pic', 'users.name', 'applied_jobs.created_at as dates')
                ->join('applied_jobs', 'bookings.id', '=', 'applied_jobs.booking_id')
                ->join('users', 'applied_jobs.chef_id', 'users.id')
                ->join('booking_meals', 'applied_jobs.booking_id', 'booking_meals.booking_id')
                ->select('users.pic', 'users.name', DB::raw('GROUP_CONCAT(booking_meals.date) AS dates'), 'bookings.id', 'booking_meals.category')
                ->groupBy('users.pic', 'bookings.location', 'users.name')
                ->where('applied_jobs.chef_id', $request->id)
                ->where('applied_jobs.status', 'hired')
                ->where('bookings.status', '!=', 'deleted')
                ->where('users.status', '!=', 'deleted')
                ->get();

            return response()->json([
                'status' => true,
                'bookings' => $bookings
            ]);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
    
    public function get_concierge_calender_bookings(Request $request)
    {
        try {
            $bookings = DB::table('bookings')
                ->select('users.address', 'users.pic', 'users.name', 'applied_jobs.created_at as dates')
                ->join('applied_jobs', 'bookings.id', '=', 'applied_jobs.booking_id')
                ->join('users', 'applied_jobs.chef_id', 'users.id')
                ->join('booking_meals', 'applied_jobs.booking_id', 'booking_meals.booking_id')
                ->select('users.pic', 'users.name', DB::raw('GROUP_CONCAT(booking_meals.date) AS dates'), 'bookings.id', 'booking_meals.category')
                ->groupBy('users.pic', 'bookings.location', 'users.name')
                ->where('users.created_by', $request->id)
                ->where('bookings.status', '!=', 'deleted')
                ->where('users.status', '!=', 'deleted')
                ->get();

            return response()->json([
                'status' => true,
                'bookings' => $bookings
            ]);
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
}
