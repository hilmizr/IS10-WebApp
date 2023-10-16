<?php

namespace App\Http\Requests;

use App\Models\CompanyKey;
use App\Models\CompanyUser;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CompanyLoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'regex:/^[a-zA-Z0-9_.]+$/'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate()
    {
        $this->ensureIsNotRateLimited();

        $user = CompanyUser::where('username', $this->username)->first();
        if (!$user) {
            // User not found
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages([
                'username' => __('auth.failed'),
            ]);
        }
        
        $key = CompanyKey::where('company_user_id', $user->id)->first()->key;
        $decryptedPassword = $this->DecryptAES($user->password, $key);

        if ($decryptedPassword != $this->password) {
            // Password mismatch
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages([
                'username' => __('auth.failed'),
            ]);
        }

        Auth::guard('company_user')->login($user);
        Auth::login($user);

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->input('email')).'|'.$this->ip());
    }

    public function DecryptAES($ciphertext, $key){
        $cipher = 'aes-256-cbc';
        $iv = substr($key, 0, 16);
        $ciphertext = openssl_decrypt(base64_decode($ciphertext), $cipher, $key, 0, $iv);
        return $ciphertext;
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
}
