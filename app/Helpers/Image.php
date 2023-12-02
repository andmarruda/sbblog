<?php

namespace App\Helpers;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class Image {
    /**
     * General brand image
     * @param Request $request
     * @param bool $autoconvert_webp
     * @return string
     */
    public static function generalBrandImage(Request $request, bool $autoconvert_webp) : string
    {
        $filename = $request->file('brand_image')->store('public');
        $file = storage_path('app/'. $filename);
        $btd = app('larabtd')->load($file);
        $extension = $request->file('brand_image')->extension();
        $size = getimagesize($file);
        $file = substr($file, 0, -(strlen($extension))). 'webp';
        $extension = 'webp';

        $size[1] <= 200 ? $btd->save($file, $extension) : $btd->proportional(height: 200)->save($file, $extension);

        return $file;
    }

    /**
     * Resizes file from article to srcset using bite the dust
     * @version         1.0.0
     * @param           Request $request
     * @return          string
     */
    public static function articleFolderSrcSet(Request $request) : string
    {
        $filename = $request->file('brand_image')->store('public');
        $file = storage_path('app/'. $filename);
        $btd = app('larabtd')->load($file);
        $extension = $request->file('brand_image')->extension();
        $file = substr($file, 0, -(strlen($extension))). 'webp';
        $extension = 'webp';
        $btd->resizeSrcSet('webp');

        return $filename;
    }

    /**
     * Generates srcset attribute
     * @version         1.0.0
     * @param           string $filename
     * @return          string
     */
    public static function srcSet(string $filename) : string
    {
        $btd = app('larabtd')->load(storage_path('app/'. $filename));
        return $btd->getSrcSetFiles();
    }
}