<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $request->user()->email = $this->DecryptAES($request->user()->email, $request->user()->userKey->key);
        if ($request->user()->address != null){
            $request->user()->address = $this->DecryptAES($request->user()->address, $request->user()->userKey->key);
        }
        if ($request->user()->phone != null){
            $request->user()->phone = $this->DecryptAES($request->user()->phone, $request->user()->userKey->key);
        }
        if ($request->user()->name != null){
            $request->user()->name = $this->DecryptAES($request->user()->name, $request->user()->userKey->key);
        }
        if ($request->user()->university != null){
            $request->user()->university = $this->DecryptAES($request->user()->university, $request->user()->userKey->key);
        }
        if ($request->user()->date_of_birth != null){
            $request->user()->date_of_birth = $this->DecryptAES($request->user()->date_of_birth, $request->user()->userKey->key);
        }
        if ($request->user()->id_number != null){
            $request->user()->id_number = $this->DecryptAES($request->user()->id_number, $request->user()->userKey->key);
        }
        if ($request->user()->student_id_number != null){
            $request->user()->student_id_number = $this->DecryptAES($request->user()->student_id_number, $request->user()->userKey->key);
        }
        if ($request->user()->major != null){
            $request->user()->major = $this->DecryptAES($request->user()->major, $request->user()->userKey->key);
        }
        
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
            $request->user()->email = $this->EncryptAES($request->user()->email, $request->user()->userKey->key);
        }
        if ($request->user()->isDirty('address')) {
            $request->user()->address = $this->EncryptAES($request->user()->address, $request->user()->userKey->key);
        }
        if ($request->user()->isDirty('phone')) {
            $request->user()->phone = $this->EncryptAES($request->user()->phone, $request->user()->userKey->key);
        }
        if ($request->user()->isDirty('name')) {
            $request->user()->name = $this->EncryptAES($request->user()->name, $request->user()->userKey->key);
        }
        if ($request->user()->isDirty('university')) {
            $request->user()->university = $this->EncryptAES($request->user()->university, $request->user()->userKey->key);
        }
        if ($request->user()->isDirty('date_of_birth')) {
            $request->user()->date_of_birth = $this->EncryptAES($request->user()->date_of_birth, $request->user()->userKey->key);
        }
        if ($request->user()->isDirty('id_number')) {
            $request->user()->id_number = $this->EncryptAES($request->user()->id_number, $request->user()->userKey->key);
        }
        if ($request->user()->isDirty('student_id_number')) {
            $request->user()->student_id_number = $this->EncryptAES($request->user()->student_id_number, $request->user()->userKey->key);
        }
        if ($request->user()->isDirty('major')) {
            $request->user()->major = $this->EncryptAES($request->user()->major, $request->user()->userKey->key);
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $decrypted_current = $this->DecryptAES($request->user()->password, $request->user()->userKey->key);
        
        if ($request->password != $decrypted_current){
            return back()->withErrors(['password' => 'The provided password does not match your current password.']);
        }

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
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
