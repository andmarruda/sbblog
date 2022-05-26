<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialNetworkUrl extends Model
{
    use HasFactory;
    protected $fillable = ['general_id', 'social_network_id', 'url'];

    /**
     * Get instance of SocialNetwork that is parent from this model
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           
     * @return          Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function socialNetwork() : \Illuminate\Database\Eloquent\Relations\Belongsto
    {
        return $this->belongsTo(SocialNetwork::class);
    }
}
