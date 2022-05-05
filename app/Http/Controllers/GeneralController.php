<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GeneralController extends Controller
{
    /**
     * Gererates the interface of General configuration form
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           
     * @return          view
     */
    public function generalInterface()
    {
        return view('general');
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
        
        return $this->generalInterface();
    }
}
