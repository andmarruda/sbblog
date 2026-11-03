<?php

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkspaceInvitation extends Notification
{
    use Queueable;

    /**
     * The plain token only exists here and in the link; the database keeps a hash.
     */
    public function __construct(
        public readonly Invitation $invitation,
        public readonly string $token,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $workspace = $this->invitation->workspace;
        $inviter = $this->invitation->inviter;

        return (new MailMessage)
            ->subject(__('You have been invited to :workspace', ['workspace' => $workspace->name ?? '']))
            ->line(__(':name invited you to join :workspace on :app.', [
                'name' => $inviter->name ?? __('Someone'),
                'workspace' => $workspace->name ?? '',
                'app' => config('app.name'),
            ]))
            ->action(__('Accept invitation'), route('invitations.show', $this->token))
            ->line(__('This invitation expires in :days days.', ['days' => config('sbblog.invitations.expire_days')]))
            ->line(__('If you were not expecting it, you can ignore this email.'));
    }
}
