<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Models\InviteProposal;
use App\Mail\InvitationEmail;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Notification;

class ChefReminderEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'booking:users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send an email to chefs of booking user';

    /**
     * Execute the console command.
     *
     * @return int
     */


    public function handle()
    {

        $currentDate = Carbon::now()->toDateString();

        // Fetch bookings
        $bookings = DB::table('invite_for_proposal')
            ->join('booking_meals', 'invite_for_proposal.booking_id', '=', 'booking_meals.booking_id')
            ->where('booking_meals.date', '>', $currentDate)
            ->distinct()
            ->get();

        $processedChefs = [];

        foreach ($bookings as $booking) {
            $bookingId = $booking->booking_id;

            $results = DB::table('invite_for_proposal')
                ->join('bookings', 'invite_for_proposal.booking_id', '=', 'bookings.id')
                ->join('users', 'invite_for_proposal.chef_id', '=', 'users.id')
                ->select(
                    'bookings.location',
                    'bookings.notes',
                    'bookings.adults',
                    'bookings.childrens',
                    'bookings.teens',
                    'invite_for_proposal.chef_id as chef_id',
                    'invite_for_proposal.user_id as user_id',
                    'bookings.id'
                )
                ->where('booking_id', $bookingId)
                ->where('users.role', 'chef')
                ->whereNotIn('chef_id', function ($query) use ($bookingId) {
                    $query->select('chef_id')
                        ->from('applied_jobs')
                        ->where('booking_id', $bookingId);
                })
                ->get();

            // Assuming you have code here to process the $results, like sending emails

            foreach ($results as $result) {
                // Assuming you have a User model and 'email' is the field containing the email address
                $chefId = $result->chef_id;
                if (!in_array($chefId, $processedChefs)) {
                    // Mark this chef as processed
                    $processedChefs[] = $chefId;
                    $chef = User::where('id', $result->chef_id)->first();

                    if ($chef) {
                        // Calculate total guests based on the current $result
                        $totalGuests = $result->adults + $result->childrens + $result->teens;

                        $bookingId = $result->id;

                        // Fetch service data for the booking
                        $serviceData = DB::table('bookings')
                            ->join('service_choices', 'bookings.service_id', '=', 'service_choices.id')
                            ->join('users', 'bookings.user_id', '=', 'users.id')
                            ->where('bookings.id', $bookingId)
                            ->select('service_choices.service_name', 'users.name as user_name')
                            ->first();

                        // Fetch booking meals data
                        $bookingMealsData = DB::table('booking_meals')
                            ->join('bookings', 'booking_meals.booking_id', '=', 'bookings.id')
                            ->select('booking_meals.date', 'booking_meals.breakfast', 'booking_meals.lunch', 'booking_meals.dinner')
                            ->where('bookings.id', $bookingId)
                            ->get();

                        // Fetch booking allergies data
                        $bookingAllergiesData = DB::table('bookings')
                            ->join('allergies', function ($join) {
                                $join->on('bookings.allergies_id', 'like', DB::raw("concat('%', allergies.id, '%')"));
                            })
                            ->where('bookings.id', $bookingId)
                            ->pluck('allergies.allergy_name')
                            ->toArray();
                        $allergies = $bookingAllergiesData;

                        // Initialize $dateMeals array
                        $dateMeals = [];
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
                            'booking_location' => $result->location,
                            'booking_notes' => $result->notes,
                            'total_guests' => $totalGuests,
                            'email' => $chef->email,
                            'service_name' => $serviceData->service_name,
                            'user_Name' => $serviceData->user_name,
                            'date_ranges' => $firstDate . ' to ' . $lastDate,
                            'dateMeals' => $dateMeals,
                            'allergies' => $allergies,
                            'booking_id' => $result->id,
                            'chef_id' => $chef->id,
                        ];

                        // Assuming you have an email sending function or method
                        Mail::to($chef->email)->send(new InvitationEmail($data, $dateMeals));
                        $emailSent = true;

                        $notification = new Notification();
                        $notification->notify_to = $chef->id;
                        $notification->notify_by = $result->user_id;
                        ;
                        $notification->description = "New Booking Alert!
                        There is one new job in your dashboard. Kindly review  to ensure you don't miss out on this opportunity";
                        $notification->type = 'location_notification';
                        $notification->save();

                    }
                }
            }

        }



        $this->info('Invitation emails sent successfully.');
    }
}
