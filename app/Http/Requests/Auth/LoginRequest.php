<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Models\UserKey;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'regex:/^[a-zA-Z0-9_]+$/'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate()
    {
        $this->ensureIsNotRateLimited();

        
        $user = User::where('username', $this->username)->first();
        if (!$user) {
            // User not found
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages([
                'username' => __('auth.failed'),
            ]);
        }
        
        $key = Key::loadFromAsciiSafeString(UserKey::where('user_id', $user->id)->first()->key);
        $decryptedPassword = Crypto::decrypt($user->password, $key);
        
        if ($decryptedPassword != $this->password) {
            // Password mismatch
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages([
                'username' => __('auth.failed'),
            ]);
        }
        Auth::login($user);
        
        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
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
}
