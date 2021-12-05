<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SMSNotification extends Notification
{
    use Queueable;
    
    private $phone;
    private $message;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($phone,$message)
    {
        $this->phone=$phone;
        $this->message=$message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['firebase'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toFirebase($notifiable)
    {
        $basic  = new \Vonage\Client\Credentials\Basic(env("SMS_ID"), env("SMS_KEY")); 
        $client = new \Vonage\Client($basic);
        $response = $client->sms()->send(
            new \Vonage\SMS\Message\SMS($this->phone, "FastPay", $this->message)
        );
        
        $message = $response->current();
        
        if ($message->getStatus() == 0) {
            //later on
        } else {
            //later on
        }
        return null;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
