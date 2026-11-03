<?php

namespace App\Listeners;

use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Events\Dispatcher;

/**
 * Registered explicitly in AppServiceProvider. Methods are named on* so event discovery
 * (which picks up handle* methods) doesn't register them a second time.
 */
class AuthEventSubscriber
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function onLogin(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $event->user->forceFill(['last_login_at' => now()])->saveQuietly();

        $this->audit->record('auth.login', $event->user, ['remember' => $event->remember], actor: $event->user);
    }

    public function onFailed(Failed $event): void
    {
        $email = $event->credentials['email'] ?? null;

        $this->audit->record('auth.failed', $event->user instanceof User ? $event->user : null, [
            // Hashed so the log never stores addresses typed by strangers.
            'email_hash' => is_string($email) ? hash('sha256', mb_strtolower($email)) : null,
        ], actor: null);
    }

    public function onLogout(Logout $event): void
    {
        if ($event->user instanceof User) {
            $this->audit->record('auth.logout', $event->user, actor: $event->user);
        }
    }

    public function onPasswordReset(PasswordReset $event): void
    {
        if ($event->user instanceof User) {
            $this->audit->record('auth.password_reset', $event->user, actor: $event->user);
        }
    }

    public function onVerified(Verified $event): void
    {
        if ($event->user instanceof User) {
            $this->audit->record('auth.email_verified', $event->user, actor: $event->user);
        }
    }

    /**
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'onLogin',
            Failed::class => 'onFailed',
            Logout::class => 'onLogout',
            PasswordReset::class => 'onPasswordReset',
            Verified::class => 'onVerified',
        ];
    }
}
