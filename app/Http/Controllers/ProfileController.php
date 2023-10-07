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
        $request->user()->email = Crypto::decrypt($request->user()->email, Key::loadFromAsciiSafeString($request->user()->userKey->key));
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
            $request->user()->email = Crypto::encrypt($request->user()->email, Key::loadFromAsciiSafeString($request->user()->userKey->key));
        }
        if ($request->user()->isDirty('password')) {
            $request->user()->password = Crypto::encrypt($request->user()->password, Key::loadFromAsciiSafeString($request->user()->userKey->key));
        }
        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
