<?php

namespace App\Http\Controllers;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\App;
use Illuminate\Http\Request;
use App\Models\User;

if(session_status() != PHP_SESSION_ACTIVE)
    session_start();

class UserController extends Controller
{
    /**
     * Data validations
     * var      array
     */
    private array $validations = [
        'name'      => 'required|min:5|max:255|string',
        'username'  => ['required', 'email', 'max:255'],
        'pass'      => 'required|string|regex:/(?=.*[a-zA-Z].*)(?=.*[0-9].*)(^[a-zA-Z0-9]{8,}$)/|min:8|confirmed'
    ];
    
    /**
     * Regular expression to validate the strong of the password
     * @var string
     */
    private string $regExPass = '/(?=.*[a-zA-Z].*)(?=.*[0-9].*)(^[a-zA-Z0-9]{8,}$)/';

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
        return redirect()->route('user.create')->with('configUser', '1');
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
            'lang_id' => $lang->lang_id,
            'id' => $lang->id
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
     * Get locale from logged user
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           
     * @return          ?string
     */
    public function getLocale() : ?string
    {
        return $this->isLogged() ? $_SESSION['sbblog']['lang']['lang_id'] : NULL;
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
                ->where('password', '=', md5($req->input('pass')));
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
     * Returns an array for fill or create
     * @return  array
     */
    private function fillArray(Request $request) : array
    {
        return [
            'name'      => $request->input('name'),
            'email'     => $request->input('username'),
            'password'  => md5($request->input('pass'))
        ];
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
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $all = User::withTrashed()->where('id', '!=', 1)->get()->sortBy('name');
        return view('users-list', ['users' => $all]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('users');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request) : \Illuminate\Http\RedirectResponse
    {
        $validations = $this->validations;
        $validations['username'] = array_push($validations['username'], Rule::unique('users'));
        $request->validate($this->validations);
        $saved = user::create($this->fillArray($request));

        if($this->isConfigUser() && $saved && $this->disableConfigUser())
            return redirect()->route('user.create')->with('saved', $saved)->with('configUser', true);

        return redirect()->route('user.create')->with('saved', $saved);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  User $user
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        return view('users', ['user' => $user]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  User $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $validations = $this->validations;
        $validations['username'] = array_push($validations['username'], Rule::unique('users')->ignoreModel($user));
        $request->validate($this->validations);
        $user->fill($this->fillArray($request));
        $saved = $user->save();

        return redirect()->route('user.edit', $user->id)->with('saved', $saved);
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
}