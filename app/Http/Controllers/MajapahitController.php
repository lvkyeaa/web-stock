<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Socialite;

class MajapahitController extends Controller
{
    public function redirectToMajapahit()
    {
        return Socialite::driver('keycloak')->redirect();
    }

    public function handleMajapahitCallback(Request $request)
    {
        try {
            $socialiteUser = Socialite::driver('keycloak')->user();

            $user = User::where('email', $socialiteUser->email)->first();

            if (!$user) {
                $user = User::create([
                    'email' => $socialiteUser->email,
                    'username' => $socialiteUser->email,
                    'name' => $socialiteUser->name,
                    // Login lewat Majapahit; password acak agar akun tidak bisa dimasuki lewat form login biasa
                    'password' => Hash::make(Str::random(40)),
                ]);
                $user->assignRole('customer');
            } else {
                $user->update(['name' => $socialiteUser->name]);
            }

            Auth::login($user, true);
            $request->session()->regenerate();

            return redirect('/');
        } catch (Exception $e) {
            if (str_contains($e->getMessage(), 'Expired token') || str_contains($e->getMessage(), 'Signature verification failed')) {
                return $this->errorResponse('Token Majapahit tidak valid, silahkan buka ulang Majapahit');
            }

            report($e);

            return $this->errorResponse('Terjadi kesalahan pada aplikasi ini, silahkan coba lagi');
        }
    }

    // Kembali ke halaman login dengan pesan galat (ditampilkan di bawah form, seperti password salah)
    private function errorResponse(string $pesan)
    {
        return redirect()->route('login')->withErrors(['username' => $pesan]);
    }
}
