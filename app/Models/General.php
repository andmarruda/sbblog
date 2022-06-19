<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class General extends Model
{
    use HasFactory;
    protected $fillable = ['brand_image', 'slogan', 'section', 'active', 'google_analytics'];

    /**
     * Get all social network urls for selected general
     * @version     1.0.0
     * @author      Anderson Arruda < andmarruda@gmail.com >
     * @param       
     * @return       Illuminate\Database\Eloquent\Relations\HasMany
     */
     public function socialNetworkUrls() : \Illuminate\Database\Eloquent\Relations\HasMany
     {
         return $this->hasMany(SocialNetworkUrl::class);
     }
}