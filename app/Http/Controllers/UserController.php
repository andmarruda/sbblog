<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\App;
use Illuminate\Http\Request;
use App\Models\User;

if(session_status() != PHP_SESSION_ACTIVE)
    session_start();

class UserController extends Controller
{
    /**
     * Regular expression to validate the strong of the password
     * @var string
     */
    private string $regExPass = '/(?=.*[a-zA-Z].*)(?=.*[0-9].*)(^[a-zA-Z0-9]{8,}$)/';

    /**
     * Generates the user form's interface
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           
     * @return view
     */
    public function userInterface(?int $id=NULL)
    {
        if(!$this->isLogged())
            return $this->redirectLoginAdmin();

        if(!is_null($id) && $id != 1){
            $u = User::find($id);
            $attrs=$u->count() > 0 ? ['user' => $u] : [];
        }
        return view('users', $attrs ?? []);
    }

    /**
     * Verify if has a session logged
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           
     * @return          bool
     */
    public function isLogged()
    {
        return isset($_SESSION['sbblog']) && isset($_SESSION['sbblog']['user_id']) && isset($_SESSION['sbblog']['name']) && isset($_SESSION['sbblog']['email']);
    }

    /**
     * Verify if the logged user is the First user registered
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           
     * @return          bool
     */
    public function isConfigUser() : bool
    {
        return $_SESSION['sbblog']['user_id'] == 1;
    }

    /**
     * Redirect to User Register to generates a new and safe user
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           
     * @return          Redirect::route
     */
    public function redirectFirstUser()
    {
        return redirect()->route('admin.user')->with('configUser', '1');
    }

    /**
     * Verify if is logged in if not redirect to Login template
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           
     * @return          Redirect::route
     */
    public function redirectLoginAdmin()
    {
        return redirect()->route('admin.login');
    }

    /**
     * Disable the configuration's user
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           
     * @return          bool
     */
    private function disableConfigUser() : bool
    {
        $um = User::find(1);
        $um->active = false;
        return $um->save();
    }

    /**
     * Setting the user preferred language
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           int $user_id
     * @return          void
     */
    public function loadPreferredLang(\App\Models\User $user) : void
    {
        $lang = $user->language()->get()->first();
        $_SESSION['sbblog']['lang'] = [
            'label' => $lang->label, 
            'icon' => $lang->icon, 
            'lang_id' => $lang->lang_id
        ];
    }

    /**
     * Change preferred user's language
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           int $language_id
     * @return          view
     */
    public function setPreferredLang(int $language_id)
    {
        $u = User::find($_SESSION['sbblog']['user_id']);
        $u->language_id = $language_id;
        $u->save();
        $this->loadPreferredLang($u);
        return redirect()->route('admin.dashboard');
    }

    /**
     * Verify if the user are correct to Login
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           Request $req
     * @return          view
     */
    public function login(Request $req)
    {
        $um = User::where('email', '=', $req->input('email'))
                ->where('password', '=', md5($req->input('pass')))
                ->where('active', '=', true);
        if($um->count() > 0)
        {
            $g = $um->first();
            $_SESSION['sbblog'] = [
                'user_id' => $g->id,
                'name'    => $g->name,
                'email'   => $g->email,
            ];

            $this->loadPreferredLang($g);
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('admin.login')->with('message', __('sbblog.auth.loginInvalid'));
    }

    /**
     * Makes logout of the system
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           
     * @return          view
     */
    public function logout()
    {
        if(session_status()==PHP_SESSION_ACTIVE)
            session_destroy();

        return redirect()->route('admin.login');
    }

    /**
     * Verify if email is already registered
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           string $email
     * @return          bool
     */
    private function emailIsRegistered(string $email, ?int $id) : bool
    {
        $u = User::where('email', '=', $email);
        if(!is_null($id))
            $u = $u->where('id', '!=', $id);

        return $u->count() > 0;
    }

    /**
     * Saves information about user on POST
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           Request $req
     * @return          view
     */
    public function userFormPost(Request $req)
    {
        if(!$this->isLogged())
            return $this->redirectLoginAdmin();

        if($this->emailIsRegistered($req->input('username'), $req->input('id')))
            return view('users', ['saved' => false, 'message' => __('sbblog.auth.userExists', ['email' => $req->input('username')])]);

        if($req->input('pass') != '' && !preg_match($this->regExPass, $req->input('pass')))
            return view('users', ['saved' => false, 'message' => __('sbblog.auth.passwordStrong')]);

        if($req->input('pass') != $req->input('confirmPass'))
            return view('users', ['saved' => false, 'message' => __('sbblog.auth.errPassConfirm')]);

        $userModel = (is_null($req->input('id'))) ? new User() : User::find($req->input('id'));
        $values = [
            'name'      => $req->input('name'),
            'email'     => $req->input('username'),
            'active'    => $req->input('active'),
            'password'  => (!is_null($req->id) && $req->input('pass')=='' ? $userModel->password : md5($req->input('pass')))
        ];

        $userModel->fill($values);
        $saved = $userModel->save();

        if($this->isConfigUser() && $saved && $this->disableConfigUser())
            return view('users', ['saved' => $saved, 'configUser' => true]);

        return view('users', ['saved' => $saved]);
    }

    /**
     * Alter logged user's password
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           Request $req
     * @return          NEVER string
     */
    public function alterPassword(Request $req)
    {
        header('Content-Type: application/json; charset=utf-8');

        if(!preg_match($this->regExPass, $req->input('newPassword'))){
            echo json_encode(['error' => true, 'message' => __('adminTemplate.password.safetyMessage')]);
            return;
        }

        if($req->input('newPassword') != $req->input('checkPassword')){
            echo json_encode(['error' => true, 'message' => __('adminTemplate.password.diffPassword')]);
            return;
        }

        $u = User::where('id', '=', $_SESSION['sbblog']['user_id'])->where('password', '=', md5($req->input('oldPassword')));
        if($u->count() == 0){
            echo json_encode(['error' => true, 'message' => __('adminTemplate.password.verifyCurrentErr')]);
            return;
        }

        $ug = $u->first();
        $ug->password = md5($req->input('newPassword'));
        $saved = $ug->save();
        
        echo json_encode(['error' => (!$saved), 'message' => __('adminTemplate.password.verifyCurrentErr')]);
    }

    /**
     * Search users
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           Request $req
     * @return          NEVER JSON
     */
    public function userSearch(Request $req)
    {
        $u = User::where('name', 'ILIKE', '%'. $req->input('userSearch'). '%')->orWhere('email', '=', $req->input('email'))->get();
        header('Content-Type: application/json; charset=utf-8');
        echo $u->toJson();
    }
}