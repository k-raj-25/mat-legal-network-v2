<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

/** "My account" for panel users (admin and editors): change your own password. */
class AccountController extends Controller
{
    public function edit()
    {
        return view('admin.account');
    }

    public function password(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);
        $request->user()->forceFill(['password' => $request->input('password')])->save();
        $request->session()->regenerate();

        return back()->with('status', 'Your password has been changed.');
    }
}
