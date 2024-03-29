<?php

namespace App\Http\Controllers;

use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{   
    /**
     * Change preferred user's language
     * @version         2.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           int $language_id
     * @return          view
     */
    public function setPreferredLang(int $language_id)
    {
        User::find(auth()->user()->id)->update(['language_id' => $language_id]);
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
        if(config('app.RECAPTCHAV3_SITEKEY') != '')
        {
            $req->validate([
                'email'     => 'required|email|max:255',
                'password'  => 'required|regex:'. config('auth.password_regex'). '|min:8',
                'g-recaptcha-response' => 'required|recaptchav3:authentication,0.7'
            ]);
        }

        if(auth()->attempt($req->all(['email', 'password'])))
        {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->back()->with('message', __('sbblog.auth.loginInvalid'));
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
        if(auth()->check())
            auth()->logout();

        return redirect()->route('login');
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
        $request->validate([
            'name'      => 'required|min:5|max:255|string',
            'email'     => 'required|email|max:255|unique:users,email',
            'password'  => 'required|regex:'. config('auth.password_regex'). '|min:8|confirmed'
        ]);
        
        $saved = User::create($request->all('name', 'email', 'password'));
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
        $request->validate([
            'name'      => 'required|min:5|max:255|string',
            'email'     => 'required|email|max:255|unique:users,email,'. $user->id,
            'password'  => 'required|regex:'. config('auth.password_regex'). '|min:8|confirmed'
        ]);
        $user->update($request->all('name', 'email', 'password'));
        return redirect()->route('user.edit', $user->id)->with('saved', $saved);
    }

    /**
     * Alter logged user's password
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           Request $req
     * @return          JsonReturn
     */
    public function alterPassword(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'oldPassword'       => ['required', 'regex:'. config('auth.password_regex'), 'min:8', function($attribute, $oldPassword, $fail) {
                if(!Hash::check($oldPassword, auth()->user()->password))
                    return $fail(__('sbblog.auth.oldPasswordInvalid'));
            }],
            'newPassword'       => 'required|regex:'. config('auth.password_regex'). '|min:8|confirmed'
        ]);

        if($validator->fails())
            return response()->json($validator->errors(), 422);

        User::find(auth()->user()->id)->update(['password' => $req->input('newPassword')]);
        return response()->json(['message' => __('sbblog.auth.passwordChanged')]);
    }
}