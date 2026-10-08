<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        // ?next=/path: setelah login kembali ke halaman itu (mis. katalog). Hanya path di situs ini, bukan URL situs lain
        $next = (string) $request->query('next');
        if (str_starts_with($next, '/') && ! str_starts_with($next, '//') && ! str_contains($next, '\\')) {
            $request->session()->put('url.intended', url($next));
        }

        if (Auth::check()) {
            return $this->redirectByRole(Auth::user());
        }

        return view('auth.login');
    }

    // Form login username/email & password (halaman utama login memakai Majapahit)
    public function showWebAdminLogin()
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user());
        }

        return view('auth.webadmin');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ], [
            'username.required' => 'Username atau Email wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        // Batasi percobaan login: 5 kali gagal per menit untuk kombinasi username + IP yang sama
        $kunciLimit = 'login|' . Str::lower($request->username) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($kunciLimit, 5)) {
            return back()->withErrors([
                'username' => 'Terlalu banyak percobaan login. Coba lagi dalam ' . RateLimiter::availableIn($kunciLimit) . ' detik.'
            ])->withInput();
        }

        // Login bisa memakai username atau email
        $field = filter_var($request->username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (!Auth::attempt([$field => $request->username, 'password' => $request->password])) {
            RateLimiter::hit($kunciLimit, 60);

            return back()->withErrors([
                'username' => 'Username/Email atau password salah.'
            ])->withInput();
        }

        RateLimiter::clear($kunciLimit);
        $request->session()->regenerate();

        return $this->redirectByRole(Auth::user());
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Berhasil logout.');
    }

    private function redirectByRole(User $user)
    {
        if ($user->hasRole('admin')) {
            return redirect()->intended(route('admin.dashboard')); // halaman tujuan sebelum login jika ada
        }

        if ($user->hasRole('customer')) {
            return redirect()->intended(route('customer.dashboard'));
        }

        // Akun tanpa role tidak boleh tetap login (mencegah redirect berulang ke halaman login)
        Auth::logout();

        return redirect()->route('login')->withErrors(['username' => 'Akun Anda belum memiliki role. Hubungi admin.']);
    }
}
