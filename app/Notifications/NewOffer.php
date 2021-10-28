<?php

namespace App\Notifications;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewOffer extends Notification
{
    use Queueable;

    public $user;
    public $trip;

    public function __construct(User $user, Trip $trip)
    {
        $this->user = $user;
        $this->trip = $trip;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'name' => $this->user->name,
            'message' => $this->user->name . ' made an offer on your trip to ' . $this->trip->to . '.',
            'trip' => $this->trip->id,
            'avatar' => $this->user->avatar
        ];
    }
}
