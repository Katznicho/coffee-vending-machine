<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        $showQuickLogin = app()->environment('local') || config('app.demo_quick_login');

        return view('auth.login', compact('showQuickLogin'));
    }

    public function quickLogin(Request $request)
    {
        if (! app()->environment('local') && ! config('app.demo_quick_login')) {
            abort(404);
        }

        $user = User::where('email', config('app.demo_login_email', 'admin@vendormachine.test'))->first();

        if (! $user) {
            return redirect()->route('login')->withErrors([
                'email' => 'Admin user not found. Run: php artisan db:seed',
            ]);
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        throw ValidationException::withMessages([
            'email' => __('The provided credentials do not match our records.'),
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
