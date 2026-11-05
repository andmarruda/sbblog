<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('sbblog:admin {email : Email of an existing account} {--revoke : Remove administrator access instead}')]
#[Description('Grant or revoke instance administrator access (for installs that already have accounts)')]
class MakeAdmin extends Command
{
    public function handle(): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->error("No account uses {$email}.");

            return self::FAILURE;
        }

        $revoke = (bool) $this->option('revoke');

        if ($revoke && User::query()->where('is_admin', true)->whereKeyNot($user->id)->doesntExist()) {
            $this->error('This is the last administrator. Grant access to someone else first.');

            return self::FAILURE;
        }

        $user->is_admin = ! $revoke;
        $user->save();

        $this->info($revoke ? "{$user->email} is no longer an administrator." : "{$user->email} is now an administrator.");

        return self::SUCCESS;
    }
}
