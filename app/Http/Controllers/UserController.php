<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index()
    {
        $users = User::orderBy('name')->paginate(10);

        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        return view('users.create', [
            'roles' => UserRole::options(),
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', Password::default()],
            'role' => ['required', Rule::enum(UserRole::class)],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        User::create($validated);

        return redirect()->route('users.index')->with('success', 'Gebruiker aangemaakt.');
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        return view('users.edit', [
            'user' => $user,
            'roles' => UserRole::options(),
        ]);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', Password::default()],
            'role' => ['required', Rule::enum(UserRole::class)],
        ]);

        if (blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        if ($user->is($request->user())) {
            // Prevent an administrator from removing their own rights.
            if ($validated['role'] !== UserRole::Beheerder->value) {
                return back()
                    ->withErrors(['role' => 'Je kunt je eigen beheerdersrol niet verwijderen.'])
                    ->withInput();
            }

            $validated['is_active'] = true;
        } else {
            $validated['is_active'] = $request->boolean('is_active');
        }

        $user->update($validated);

        return redirect()->route('users.index')->with('success', 'Gebruiker bijgewerkt.');
    }

    /**
     * Activate or deactivate the specified user.
     */
    public function toggleActive(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Je kunt je eigen account niet deactiveren.');
        }

        $user->update(['is_active' => ! $user->isActive()]);

        return back()->with(
            'success',
            $user->isActive() ? 'Gebruiker geactiveerd.' : 'Gebruiker gedeactiveerd.',
        );
    }
}
