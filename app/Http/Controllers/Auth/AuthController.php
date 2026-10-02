<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Peminjaman; // <--- Memastikan model ini yang dipakai secara konsisten

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user());
        }

        return view('auth.login');
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

        // Login bisa memakai username atau email
        $field = filter_var($request->username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (!Auth::attempt([$field => $request->username, 'password' => $request->password])) {
            return back()->withErrors([
                'username' => 'Username/Email atau password salah.'
            ])->withInput();
        }

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
            return redirect()->route('admin.dashboard');
        }

        if ($user->hasRole('customer')) {
            return redirect()->route('customer.dashboard');
        }

        // Akun tanpa role tidak boleh tetap login (mencegah redirect berulang ke halaman login)
        Auth::logout();

        return redirect()->route('login')->withErrors(['username' => 'Akun Anda belum memiliki role. Hubungi admin.']);
    }

    // Method untuk menampilkan halaman depan publik berisi Kalender
    public function showLanding()
    {
        // 1. Ambil jadwal MOBIL yang sudah disetujui admin menggunakan model Peminjaman
        $jadwalMobil = Peminjaman::with(['user', 'facility'])->whereRelation('facility.facilityType', 'code', 'car')
            ->where('status', 'disetujui')
            ->get()
            ->map(function ($item) {
                return [
                    'title' => '🚗 ' . $item->facility->name . ' (' . ($item->user->username ?? 'User') . ')',
                    'start' => $item->waktu_mulai->toIso8601String(),
                    'end' => $item->waktu_selesai->toIso8601String(),
                    'backgroundColor' => '#0284c7', // Warna sky blue
                    'extendedProps' => [
                        'keperluan' => $item->keperluan,
                        'user' => $item->user->username ?? 'Tidak Diketahui'
                    ]
                ];
            });

        // 2. Ambil jadwal RUANG yang sudah disetujui admin menggunakan model Peminjaman
        $jadwalRuang = Peminjaman::with(['user', 'facility'])->whereRelation('facility.facilityType', 'code', 'room')
            ->where('status', 'disetujui')
            ->get()
            ->map(function ($item) {
                return [
                    'title' => '🏢 ' . $item->facility->name . ' (' . ($item->user->username ?? 'User') . ')',
                    'start' => $item->waktu_mulai->toIso8601String(),
                    'end' => $item->waktu_selesai->toIso8601String(),
                    'backgroundColor' => '#d97706', // Warna amber
                    'extendedProps' => [
                        'keperluan' => $item->keperluan,
                        'user' => $item->user->username ?? 'Tidak Diketahui'
                    ]
                ];
            });

        // Return ke file blade halaman depan (welcome)
        return view('welcome', compact('jadwalMobil', 'jadwalRuang'));
    }
}