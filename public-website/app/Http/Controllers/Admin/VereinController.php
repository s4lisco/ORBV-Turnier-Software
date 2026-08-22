<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Verein;
use App\Rules\StrongPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VereinController extends Controller
{
    public function index(): View
    {
        return view('admin.vereine.index', ['vereine' => Verein::orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('admin.vereine.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:vereine,email'],
            'username' => ['required', 'string', 'max:255', 'unique:vereine,username'],
            'password' => ['required', new StrongPassword()],
        ]);

        Verein::create($data);

        return redirect()->route('admin.vereine.index')->with('status', 'Verein angelegt.');
    }

    public function edit(Verein $verein): View
    {
        return view('admin.vereine.edit', ['verein' => $verein]);
    }

    public function update(Request $request, Verein $verein): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:vereine,email,' . $verein->id],
            'username' => ['required', 'string', 'max:255', 'unique:vereine,username,' . $verein->id],
            'password' => ['nullable', new StrongPassword()],
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $verein->update($data);

        return redirect()->route('admin.vereine.index')->with('status', 'Verein aktualisiert.');
    }

    public function destroy(Verein $verein): RedirectResponse
    {
        $verein->delete();

        return redirect()->route('admin.vereine.index')->with('status', 'Verein gelöscht.');
    }
}
