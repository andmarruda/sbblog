<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\General;

class GeneralController extends Controller
{
    /**
     * Allowed file extension
     * @var             array
     */
    CONST ALLOWED_EXTENSION = ['jpg', 'jpeg', 'bmp', 'gif', 'png', 'webp'];

    /**
     * Allowd max file size
     * @var             int
     */
    CONST ALLOWED_SIZE = 1500000;

    /**
     * Gererates the interface of General configuration form
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           ?string $message
     * @return          view
     */
    public function generalInterface(?string $message=NULL, ?bool $saved=NULL)
    {
        $attrs = ['gen' => General::find(1)];
        if(!is_null($message))
            $attrs['message'] = $message;

        if(!is_null($saved))
            $attrs['saved'] = $saved;

        return view('general', $attrs);
    }

    /**
     * Saves the general configuration
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           Request $req
     * @return          view
     */
    public function generalSave(Request $req)
    {
        $filepath = $req->input('registered_file');
        if($req->hasFile('brand_image')){
            if(!$req->file('brand_image')->isValid() || !in_array($req->file('brand_image')->extension(), self::ALLOWED_EXTENSION) || $req->file('brand_image')->getSize() > self::ALLOWED_SIZE)
                return $this->generalInterface('Imagem fora dos padrões permitidos. Verifique a extensão da imagem, se ela é válida e o tamanho da imagem.', false);

            $filepath = basename($req->file('brand_image')->store('public'));
        }

        $gen = General::find(1);
        $gen->fill([
            'brand_image' => $filepath,
            'slogan' => $req->input('slogan'),
            'section' => $req->input('section'),
            'active' => true
        ]);
        $saved = $gen->save();
        return $this->generalInterface(saved: $saved);
    }
}
