<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        if ($request->user() instanceof \App\Models\User){
            $decrypted_current = Crypto::decrypt($request->user()->password, Key::loadFromAsciiSafeString($request->user()->userKey->key));
        }
        else{
            $decrypted_current = Crypto::decrypt($request->user()->password, Key::loadFromAsciiSafeString($request->user()->companyKey->key));
        }
        // $decrypted_current = Crypto::decrypt($request->user()->password, Key::loadFromAsciiSafeString($request->user()->companyKey->key));
        if ($request->current_password != $decrypted_current){
            return back()->withErrors(['current_password' => 'The provided password does not match your current password.']);
        }
        $validated = $request->validate([
            'current_password' => ['required', 'string', 'min:8', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);
        if ($request->user() instanceof \App\Models\User){
            $request->user()->update([
                // 'password' => Hash::make($validated['password']),
                'password' => Crypto::encrypt($validated['password'], Key::loadFromAsciiSafeString($request->user()->userKey->key))
            ]);
        }
        else{
            $request->user()->update([
                // 'password' => Hash::make($validated['password']),
                'password' => Crypto::encrypt($validated['password'], Key::loadFromAsciiSafeString($request->user()->companyKey->key))
            ]);
        }

        return back()->with('status', 'password-updated');
    }
}
