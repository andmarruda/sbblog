<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Util extends Model
{
    use HasFactory;

    /**
     * Generates a random color
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           
     * @return          string
     */
    public static function randomColor() : string
    {
        return '#'. substr(str_pad(rand(0x000000, 0xFFFFFF), 6, 0, STR_PAD_LEFT), 0, 6);
    }
}
