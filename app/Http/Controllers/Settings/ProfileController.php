<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SetLocale;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\Language;
use App\Models\User;
use App\Notifications\EmailChanged;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('settings/profile', [
            'mustVerifyEmail' => ! $user->hasVerifiedEmail(),
            'currentLocale' => $user->language->code ?? App::getLocale(),
            'ownsWorkspaces' => $user->ownedWorkspaces()->exists(),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function update(ProfileUpdateRequest $request, AuditLogger $audit): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'language_id' => Language::query()->where('code', $validated['locale'])->value('id'),
        ]);

        $emailChanged = $user->isDirty('email');
        $previousEmail = (string) $user->getOriginal('email');

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $changed = array_keys($user->getDirty());
        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
            Notification::route('mail', $previousEmail)->notify((new EmailChanged($user->email))->locale(App::getLocale()));
        }

        if ($changed !== []) {
            $audit->record('account.updated', $user, ['fields' => $changed]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated.', locale: $validated['locale'])]);

        return to_route('profile.edit')->withCookie(cookie()->forever(SetLocale::COOKIE, $validated['locale']));
    }

    /**
     * Soft-delete the account. Owners must transfer or delete their workspaces first.
     */
    public function destroy(Request $request, AuditLogger $audit): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        /** @var User $user */
        $user = $request->user();

        if ($user->ownedWorkspaces()->exists()) {
            throw ValidationException::withMessages([
                'password' => __('Transfer or delete the workspaces you own before deleting your account.'),
            ]);
        }

        $audit->record('account.deleted', $user);

        DB::transaction(function () use ($user) {
            $user->tokens()->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->delete();
        });

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
