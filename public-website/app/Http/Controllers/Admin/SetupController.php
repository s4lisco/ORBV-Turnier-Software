<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Rules\StrongPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SetupController extends Controller
{
    public static function adminExists(): bool
    {
        return Admin::query()->exists();
    }

    public function show(): RedirectResponse|\Illuminate\View\View
    {
        if (self::adminExists()) {
            return redirect()->route('admin.login');
        }

        return view('admin.setup');
    }

    public function store(Request $request): RedirectResponse
    {
        if (self::adminExists()) {
            return redirect()->route('admin.login');
        }

        $data = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', new StrongPassword()],
        ]);

        $admin = Admin::create([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'master_admin',
        ]);

        Auth::guard('admin')->login($admin);

        return redirect()->route('admin.dashboard');
    }
}
