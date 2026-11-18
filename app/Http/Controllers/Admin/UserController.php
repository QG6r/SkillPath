<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Users/Index', [
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email', 'role']),
            'roles' => User::ROLES,
        ]);
    }

    /** Teachers and admins are not self-registered: an admin creates them here. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::defaults()],
            'role' => ['required', Rule::in(User::ROLES)],
        ]);

        User::create([
            ...$data,
            'password' => Hash::make($data['password']),
            'email_verified_at' => now(),
        ]);

        return back()->with('success', 'User created.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'You cannot change your own role.');
        }

        $data = $request->validate([
            'role' => ['required', Rule::in(User::ROLES)],
        ]);

        $user->update($data);

        return back()->with('success', 'Role changed.');
    }
}
