<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Events\DisableFirstUser;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * First user email
     * @var string
     */
    const FIRST_USER_EMAIL = 'admin@admin.com';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['name', 'email', 'password', 'language_id'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::created(function () {
            event(new DisableFirstUser());
        });
    }

    /**
     * Get the preferred language parent
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           
     * @return          Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function language() : \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Language::class);
    }

    /**
     * Scope check if is the first user
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com > 
     * @param           Builder $query
     * @return          void
     */
    public function scopeFirstUser(Builder $query) : void
    {
        $query->where('email', self::FIRST_USER_EMAIL);
    }

    /**
     * Scope check if is the first user logged
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com > 
     * @param           Builder $query
     * @return          void
     */
    public function scopeFirstUserLogged(Builder $query) : void
    {
        $query->where('email', self::FIRST_USER_EMAIL)
            ->where('id', auth()->check() ? auth()->user()->id : -1);
    }
}
