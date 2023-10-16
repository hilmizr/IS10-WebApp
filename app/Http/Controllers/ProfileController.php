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
        if ($request->user()->address != null){
            $request->user()->address = Crypto::decrypt($request->user()->address, Key::loadFromAsciiSafeString($request->user()->userKey->key));
        }
        if ($request->user()->phone != null){
            $request->user()->phone = Crypto::decrypt($request->user()->phone, Key::loadFromAsciiSafeString($request->user()->userKey->key));
        }
        if ($request->user()->name != null){
            $request->user()->name = Crypto::decrypt($request->user()->name, Key::loadFromAsciiSafeString($request->user()->userKey->key));
        }
        if ($request->user()->university != null){
            $request->user()->university = Crypto::decrypt($request->user()->university, Key::loadFromAsciiSafeString($request->user()->userKey->key));
        }
        if ($request->user()->date_of_birth != null){
            $request->user()->date_of_birth = Crypto::decrypt($request->user()->date_of_birth, Key::loadFromAsciiSafeString($request->user()->userKey->key));
        }
        if ($request->user()->id_number != null){
            $request->user()->id_number = Crypto::decrypt($request->user()->id_number, Key::loadFromAsciiSafeString($request->user()->userKey->key));
        }
        if ($request->user()->student_id_number != null){
            $request->user()->student_id_number = Crypto::decrypt($request->user()->student_id_number, Key::loadFromAsciiSafeString($request->user()->userKey->key));
        }
        if ($request->user()->major != null){
            $request->user()->major = Crypto::decrypt($request->user()->major, Key::loadFromAsciiSafeString($request->user()->userKey->key));
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
            $request->user()->email = Crypto::encrypt($request->user()->email, Key::loadFromAsciiSafeString($request->user()->userKey->key));
        }
        if ($request->user()->isDirty('address')) {
            $request->user()->address = Crypto::encrypt($request->user()->address, Key::loadFromAsciiSafeString($request->user()->userKey->key));
        }
        if ($request->user()->isDirty('phone')) {
            $request->user()->phone = Crypto::encrypt($request->user()->phone, Key::loadFromAsciiSafeString($request->user()->userKey->key));
        }
        if ($request->user()->isDirty('name')) {
            $request->user()->name = Crypto::encrypt($request->user()->name, Key::loadFromAsciiSafeString($request->user()->userKey->key));
        }
        if ($request->user()->isDirty('university')) {
            $request->user()->university = Crypto::encrypt($request->user()->university, Key::loadFromAsciiSafeString($request->user()->userKey->key));
        }
        if ($request->user()->isDirty('date_of_birth')) {
            $request->user()->date_of_birth = Crypto::encrypt($request->user()->date_of_birth, Key::loadFromAsciiSafeString($request->user()->userKey->key));
        }
        if ($request->user()->isDirty('id_number')) {
            $request->user()->id_number = Crypto::encrypt($request->user()->id_number, Key::loadFromAsciiSafeString($request->user()->userKey->key));
        }
        if ($request->user()->isDirty('student_id_number')) {
            $request->user()->student_id_number = Crypto::encrypt($request->user()->student_id_number, Key::loadFromAsciiSafeString($request->user()->userKey->key));
        }
        if ($request->user()->isDirty('major')) {
            $request->user()->major = Crypto::encrypt($request->user()->major, Key::loadFromAsciiSafeString($request->user()->userKey->key));
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $decrypted_current = Crypto::decrypt($request->user()->password, Key::loadFromAsciiSafeString($request->user()->userKey->key));
        
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
}
