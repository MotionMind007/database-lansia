<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('app.dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'email.required'    => 'Email atau username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $credentials = $request->only('password');

        // Support login via email ATAU username
        $field = str_contains($request->email, '@') ? 'email' : 'username';
        $credentials[$field] = $request->email;

        // Cek apakah user exist dan aktif
        $user = User::where($field, $request->email)->first();

        if (! $user) {
            activity('auth')
                ->event('login_failed')
                ->withProperties($this->requestContext($request, [
                    'login' => $request->email,
                    'reason' => 'user_not_found',
                ]))
                ->log('Percobaan login gagal.');

            throw ValidationException::withMessages([
                'email' => 'Email atau password tidak valid.',
            ]);
        }

        if (! $user->is_active) {
            activity('auth')
                ->causedBy($user)
                ->performedOn($user)
                ->event('login_blocked')
                ->withProperties($this->requestContext($request, ['reason' => 'inactive_user']))
                ->log('Percobaan login ditolak karena akun tidak aktif.');

            throw ValidationException::withMessages([
                'email' => 'Akun Anda tidak aktif. Hubungi Administrator.',
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            activity('auth')
                ->causedBy($user)
                ->performedOn($user)
                ->event('login_failed')
                ->withProperties($this->requestContext($request, ['reason' => 'invalid_password']))
                ->log('Percobaan login gagal.');

            throw ValidationException::withMessages([
                'email' => 'Email atau password tidak valid.',
            ]);
        }

        // Update last login
        $user->update(['last_login_at' => now()]);

        $request->session()->regenerate();

        activity('auth')
            ->causedBy($user)
            ->performedOn($user)
            ->event('login')
            ->withProperties($this->requestContext($request))
            ->log('User berhasil login.');

        // Redirect berdasarkan role
        return redirect()->route('app.dashboard');
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            activity('auth')
                ->causedBy($user)
                ->performedOn($user)
                ->event('logout')
                ->withProperties($this->requestContext($request))
                ->log('User logout.');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }

    private function requestContext(Request $request, array $extra = []): array
    {
        return array_merge([
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ], $extra);
    }
}
