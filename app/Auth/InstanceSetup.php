<?php

namespace App\Auth;

use App\Models\Language;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * First-run setup: while the instance has no accounts, the setup screen creates the first
 * administrator. Once any account exists (deleted ones included) it is closed for good.
 */
class InstanceSetup
{
    public function isPending(): bool
    {
        return ! User::withTrashed()->exists();
    }

    /**
     * Create the first administrator, or return null when another request got there first.
     *
     * @param  array{name: string, email: string, password: string}  $attributes
     */
    public function createAdministrator(array $attributes): ?User
    {
        // The lock serialises concurrent submissions; the check inside it decides the winner.
        return Cache::lock('sbblog:instance-setup', 10)->block(5, function () use ($attributes) {
            return DB::transaction(function () use ($attributes) {
                if (! $this->isPending()) {
                    return null;
                }

                $user = new User([
                    ...$attributes,
                    'language_id' => Language::query()->where('code', App::getLocale())->value('id'),
                ]);
                // Mail may not be configured yet on a fresh install, and the operator owns the address.
                $user->forceFill(['is_admin' => true, 'email_verified_at' => now()])->save();

                return $user;
            });
        });
    }
}
