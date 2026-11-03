<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the previous address, so an unexpected change doesn't go unnoticed.
 */
class EmailChanged extends Notification
{
    use Queueable;

    public function __construct(public readonly string $newEmail) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Your email address was changed'))
            ->line(__('The email address of your :app account was changed to :email.', ['app' => config('app.name'), 'email' => $this->newEmail]))
            ->line(__('If you did not do this, reset your password right away and contact us.'));
    }
}
