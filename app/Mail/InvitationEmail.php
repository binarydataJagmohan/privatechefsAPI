<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvitationEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;
    public $dateMeals;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data, $dateMeals)
    {
        $this->data = $data;
        $this->dateMeals = $dateMeals;
    }

    public function build()
    {
        return $this->view('emails.chefLocation')
            ->from(config('mail.from.address'), 'Private Chefs')
            ->subject("New Booking Alert: Opportunity in {$this->data['booking_location']}")
            ->to($this->data['email'])
            // ->bcc([$this->data['admin_email'], 'info@privatechefsworld.com'])
            ->with([
                'data' => $this->data,
                'dateMeals' => $this->dateMeals,
            ]);
    }
}

