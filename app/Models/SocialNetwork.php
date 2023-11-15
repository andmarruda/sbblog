<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialNetwork extends Model
{
    use HasFactory;
    protected $fillable = ['icon', 'name'];

    /**
     * Get instance of Social Network Urls that is parent from this model
     * @version     1.0.0
     * @author      Anderson Arruda < andmarruda@gmail.com >
     * @param       
     * @return      Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function socialNetworkUrl() : \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SocialNetworkUrl::class);
    }
}
