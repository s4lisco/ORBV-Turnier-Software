<?php

namespace App\Http\Controllers\Verein;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin(): \Illuminate\View\View
    {
        return view('verein.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::guard('verein')->attempt($credentials)) {
            return back()->withErrors(['username' => 'Benutzername oder Passwort falsch.'])->onlyInput('username');
        }

        $request->session()->regenerate();

        return redirect()->route('verein.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('verein')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
