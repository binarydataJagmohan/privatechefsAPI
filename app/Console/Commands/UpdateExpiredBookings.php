<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use App\Models\BookingMeals;
use Carbon\Carbon;

class UpdateExpiredBookings extends Command
{
    protected $signature = 'bookings:update-expired';
    protected $description = 'Expire bookings with all past meal dates';

    public function handle()
    {
        try {
            $today = Carbon::today();

            $bookings = Booking::where('status', 'active')->get();

            foreach ($bookings as $booking) {
                $hasFutureMeal = BookingMeals::where('booking_id', $booking->id)
                    ->where('date', '>=', $today)
                    ->exists();
                if (!$hasFutureMeal) {
                    $booking->update(['booking_status' => 'expired']);
                    // $this->info("Booking ID {$booking->id} expired.");
                }
            }

            $this->info('Expired bookings updated successfully.');
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }
}
