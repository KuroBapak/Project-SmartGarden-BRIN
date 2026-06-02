<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class AccountController extends Controller
{
    public function index()
    {
        // Only Master Admin can access this, enforced by routes/middleware
        $users = User::orderBy('created_at', 'desc')->get();
        return view('accounts.index', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user', // newly created users are regular users
        ]);

        return redirect()->route('accounts.index')->with('status', 'Account created successfully.');
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,'.$user->id],
        ]);

        $user->name = $request->name;
        $user->email = $request->email;

        if ($request->filled('password')) {
            $request->validate([
                'password' => ['confirmed', Rules\Password::defaults()],
            ]);
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return redirect()->route('accounts.index')->with('status', 'Account updated successfully.');
    }

    public function destroy(User $user)
    {
        // Prevent deleting yourself
        if (auth()->id() === $user->id) {
            return back()->withErrors(['error' => 'You cannot delete your own account.']);
        }

        // Prevent deleting the only master admin
        if ($user->role === 'master_admin') {
            $masterAdminsCount = User::where('role', 'master_admin')->count();
            if ($masterAdminsCount <= 1) {
                return back()->withErrors(['error' => 'Cannot delete the last master admin account.']);
            }
        }

        $user->delete();

        return redirect()->route('accounts.index')->with('status', 'Account deleted successfully.');
    }
}
