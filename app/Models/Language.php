<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

class Language extends Model
{
    use HasFactory;

    protected $fillable = ['lang_id', 'label', 'icon'];

    /**
     * Get default locale from client-side
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           
     * @return          string
     */
    public static function defaultLocale() : string
    {
        $browserLang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        if(App::isLocale($browserLang))
            return $browserLang;

        return config('app.locale');
    }

    /**
     * Get user language icon and label
     * @return ['icon' => string, 'label' => string]
     */
    public static function getSelectedLang()
    {
        if(auth()->check())
            return auth()->user()->language->toArray();

        return ['icon' => '', 'label' => ''];
    }
}
