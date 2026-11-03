<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property string $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string|null $language_id
 * @property string|null $two_factor_secret
 * @property list<string>|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $last_login_at
 * @property array<string, mixed> $preferences
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read WorkspaceMember|BlogMember|null $pivot
 */
#[Fillable(['name', 'email', 'password', 'language_id'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable implements HasLocalePreference, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasUuids, Notifiable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'preferences' => 'array',
            'password' => 'hashed',
        ];
    }

    /**
     * @return BelongsTo<Language, $this>
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    /**
     * @return BelongsToMany<Workspace, $this, WorkspaceMember>
     */
    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'workspace_members')
            ->using(WorkspaceMember::class)
            ->withPivot(['workspace_role', 'joined_at']);
    }

    /**
     * Read one UI preference (e.g. "last_blog_id").
     */
    public function preference(string $key, mixed $default = null): mixed
    {
        return ($this->preferences ?? [])[$key] ?? $default;
    }

    /**
     * Save one UI preference without touching updated_at.
     */
    public function rememberPreference(string $key, mixed $value): void
    {
        if ($this->preference($key) === $value) {
            return;
        }

        $this->forceFill(['preferences' => [...($this->preferences ?? []), $key => $value]]);
        $this->timestamps = false;
        $this->saveQuietly();
        $this->timestamps = true;
    }

    /**
     * @return HasMany<Workspace, $this>
     */
    public function ownedWorkspaces(): HasMany
    {
        return $this->hasMany(Workspace::class, 'owner_user_id');
    }

    /**
     * @return BelongsToMany<Blog, $this, BlogMember>
     */
    public function blogs(): BelongsToMany
    {
        return $this->belongsToMany(Blog::class, 'blog_members')
            ->using(BlogMember::class)
            ->withPivot(['role_id', 'invited_by']);
    }

    /**
     * @return HasMany<AuthorProfile, $this>
     */
    public function authorProfiles(): HasMany
    {
        return $this->hasMany(AuthorProfile::class);
    }

    /**
     * @return HasMany<WebauthnCredential, $this>
     */
    public function webauthnCredentials(): HasMany
    {
        return $this->hasMany(WebauthnCredential::class);
    }

    /**
     * @return HasMany<TrustedDevice, $this>
     */
    public function trustedDevices(): HasMany
    {
        return $this->hasMany(TrustedDevice::class);
    }

    /**
     * Language for mails and notifications sent to this user.
     */
    public function preferredLocale(): ?string
    {
        return $this->language?->code;
    }

    /**
     * A confirmed authenticator app or at least one passkey.
     */
    public function hasTwoFactorEnabled(): bool
    {
        return $this->hasTotpEnabled() || once(fn () => $this->webauthnCredentials()->exists());
    }

    public function hasTotpEnabled(): bool
    {
        return $this->two_factor_confirmed_at !== null && $this->two_factor_secret !== null;
    }
}
