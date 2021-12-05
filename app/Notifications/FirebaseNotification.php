<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Kutia\Larafirebase\Messages\FirebaseMessage;

class FirebaseNotification extends Notification
{
    use Queueable;
    
    protected $title;
    protected $message;
    protected $token;
    protected $enabled;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($title,$message,$token,$enabled)
    {
        $this->title = $title;
        $this->message = $message;
        $this->token = $token;
        $this->enabled = $enabled;
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
        $deviceTokens = [
            $this->token
        ];
        if($this->enabled)
            return (new FirebaseMessage)
                ->withTitle($this->title)
                ->withBody($this->message)
                ->withPriority('high')
                ->asNotification($deviceTokens);
        
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
