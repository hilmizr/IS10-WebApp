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
            $decrypted_current = $this->DecryptAES($request->user()->password, $request->user()->userKey->key);
        }
        else{
            $decrypted_current = $this->DecryptAES($request->user()->password, $request->user()->companyKey->key);
        }
        
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
                'password' => $this->EncryptAES($validated['password'], $request->user()->userKey->key)
            ]);
        }
        else{
            $request->user()->update([
                // 'password' => Hash::make($validated['password']),
                'password' => $this->EncryptAES($validated['password'], $request->user()->companyKey->key)
            ]);
        }

        return back()->with('status', 'password-updated');
    }

    public function DecryptAES($ciphertext, $key){
        $cipher = 'aes-256-cbc';
        $iv = substr($key, 0, 16);
        $decrypted = openssl_decrypt(base64_decode($ciphertext), $cipher, $key, 0, $iv);
        return $decrypted;
    }

    public function DecryptRC4($ciphertext, $key){
        $cipher = 'rc4';
        $ciphertext = openssl_decrypt(base64_decode($ciphertext), $cipher, $key);
        return $ciphertext;
    }

    public function DecryptDES($ciphertext, $key){
        $cipher = 'des-ecb';
        $ciphertext = openssl_decrypt(base64_decode($ciphertext), $cipher, $key, $options = 0);
        return $ciphertext;
    }

    public function EncryptAES($plaintext, $key){
        $cipher = 'aes-256-cbc';
        $iv = substr($key, 0, 16);
        $encrypted = base64_encode(openssl_encrypt($plaintext, $cipher, $key, 0, $iv));
        return $encrypted;
    }

    public function EncryptRC4($plaintext, $key){
        $cipher = 'rc4';
        $encrypted = base64_encode(openssl_encrypt($plaintext, $cipher, $key));
        return $encrypted;
    }

    public function EncryptDES($plaintext, $key){
        $cipher = 'des-ecb';
        $encrypted = base64_encode(openssl_encrypt($plaintext, $cipher, $key, $options = 0));
        return $encrypted;
    }
}
