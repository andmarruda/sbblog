<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialNetworkUrl extends Model
{
    use HasFactory;
    protected $fillable = ['general_id', 'social_network_id', 'url'];
}
