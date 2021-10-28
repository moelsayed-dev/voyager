<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\Trip;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OfferDeclined extends Notification
{
    use Queueable;

    public $user, $trip;
    public function __construct(User $user, Trip $trip)
    {
        $this->user = $user;
        $this->trip = $trip;
    }

    public function via($notifiable)
    {
        return ['database'];
    }


    public function toArray($notifiable)
    {
        return [
            'name' => $this->user->name,
            'message' => $this->user->name . ' declined your offer on his trip to ' . $this->trip->to . '.',
            'trip' => $this->trip->id,
            'avatar' => $this->user->avatar
        ];
    }
}
