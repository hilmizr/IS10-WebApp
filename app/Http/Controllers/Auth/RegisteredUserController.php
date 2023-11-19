<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
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

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
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

        $user = $this->UserEncryptedAES($request, $key);
        
        event(new Registered($user));

        Auth::login($user);

        return redirect(RouteServiceProvider::HOME);
    }

    public function UserEncryptedAES($request, $key)
    {
        $cipher = 'aes-256-cbc';
        
        $key = $key->saveToAsciiSafeString();
        $iv = substr($key, 0, 16);
        $user = User::create([
            'username' => $request->username,
            'email' => base64_encode(openssl_encrypt($request->email, $cipher, $key, 0, $iv)),
            'password' => base64_encode(openssl_encrypt($request->password, $cipher, $key, 0, $iv)),
        ]);

        
        $userkey = UserKey::create([
            'user_id' => $user->id,
            'key' => $key,
        ]);


        return $user;
    }

    public function UserEncryptedRC4($request, $key)
    {
        $cipher = 'rc4';

        $key = $key->saveToAsciiSafeString();
        $user = User::create([
            'username' => $request->username,
            'email' => base64_encode(openssl_encrypt($request->email, $cipher, $key)),
            'password' => base64_encode(openssl_encrypt($request->password, $cipher, $key)),
        ]);

        
        $userkey = UserKey::create([
            'user_id' => $user->id,
            'key' => $key,
        ]);

        return $user;
    }

    public function UserEncryptedDES($request, $key)
    {
        $cipher = 'des-ecb';
        
        $key = $key->saveToAsciiSafeString();
        $user = User::create([
            'username' => $request->username,
            'email' => base64_encode(openssl_encrypt($request->email, $cipher, $key, $options=0)),
            'password' => base64_encode(openssl_encrypt($request->password, $cipher, $key, $options=0)),
        ]);

        
        $userkey = UserKey::create([
            'user_id' => $user->id,
            'key' => $key,
        ]);

        return $user;
    }
}
