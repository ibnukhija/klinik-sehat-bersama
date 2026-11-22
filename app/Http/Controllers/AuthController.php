<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $demoUsers = User::orderBy('id')->get()->sortBy(fn (User $u) => match ($u->role) {
            User::ROLE_ADMIN => 1,
            User::ROLE_DOKTER => 2,
            User::ROLE_KASIR => 3,
            default => 4,
        })->values();

        return view('auth.login', compact('demoUsers'));
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'identity' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $field = filter_var($credentials['identity'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (Auth::attempt([$field => $credentials['identity'], 'password' => $credentials['password']], $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        return back()
            ->withErrors(['identity' => 'Username/email atau password salah.'])
            ->onlyInput('identity');
    }

    public function quickLogin(Request $request): RedirectResponse
    {
        abort_unless(app()->environment('local', 'testing'), 403);

        $data = $request->validate([
            'username' => ['required', 'string', 'exists:users,username'],
        ]);

        $user = User::where('username', $data['username'])->firstOrFail();
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', "Masuk sebagai {$user->name} ({$user->roleLabel()}).");
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
