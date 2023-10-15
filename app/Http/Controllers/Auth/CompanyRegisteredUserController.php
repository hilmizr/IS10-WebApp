<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\CompanyLoginRequest;
use App\Models\CompanyKey;
use App\Models\CompanyUser;
use App\Models\User;
use App\Models\UserKey;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Illuminate\Support\Facades\Redirect;

class CompanyRegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.company-register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'username' => ['required', 'string', 'max:255', 'unique:users', 'regex:/^[a-zA-Z0-9_]+$/'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);
        // random generate 16 char string
        $key = Key::createNewRandomKey();
        
        $user = CompanyUser::create([
            'username' => $request->username,
            'email' => Crypto::encrypt($request->email, $key),
            'password' => Crypto::encrypt($request->password, $key),
        ]);
        
        $key = $key->saveToAsciiSafeString();
        // print to consolez
        
        $userkey = CompanyKey::create([
            'company_user_id' => $user->id,
            'key' => $key,
        ]);
        // dd($key);
        
        event(new Registered($user));

        
        Auth::guard('company_user')->login($user);
        Auth::login($user);

        // dd(Auth::user());

        return redirect(RouteServiceProvider::COMPANY_HOME);
    }

    public function login()
    {
        if (Auth::guard('company_user')->check()) {
            Auth::login(Auth::guard('company_user')->user());
            return redirect()->intended(RouteServiceProvider::COMPANY_HOME);
        }
        return view('auth.company-login');
    }

    public function login_store(CompanyLoginRequest $request): RedirectResponse
    {
        // dd($request->all());
        $request->authenticate();

        $request->session()->regenerate();

        // dd(Auth::guard('company_user')->user(), Auth::user()->username);
        return redirect()->intended(RouteServiceProvider::COMPANY_HOME);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('company_user')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
