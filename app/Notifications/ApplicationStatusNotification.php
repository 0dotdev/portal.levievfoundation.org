<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class ApplicationStatusNotification extends Notification
{
    public string $title;
    public string $message;
    public ?string $url;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $title, string $message, ?string $url = null)
    {
        $this->title = $title;
        $this->message = $message;
        $this->url = $url;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        Log::info('Sending application status notification email', [
            'to' => $notifiable->email,
            'title' => $this->title,
            'message' => $this->message
        ]);

        $mail = (new MailMessage)
            ->subject($this->title)
            ->line($this->message);

        if ($this->url) {
            $mail->action('View Details', $this->url);
        }

        return $mail;
    }

    /**
     * Get the array representation of the notification (for database).
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
        ];
    }
}
