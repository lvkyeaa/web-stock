<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

// Profil pengguna yang login (admin & customer): lihat data akun, ubah nomor WhatsApp sendiri
class ProfilController extends Controller
{
    public function index(Request $request)
    {
        return view('profil.index', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'regex:/^62[0-9]{8,13}$/'],
        ], [
            'phone.required' => 'Isi nomor WhatsApp terlebih dahulu.',
            'phone.regex'    => 'Nomor WhatsApp harus diawali 62 dan hanya berisi angka (10–15 digit), contoh 6281234567890.',
        ]);

        $request->user()->update(['phone' => $request->phone]);

        return redirect()->route('profil.index')->with('success', 'Nomor WhatsApp berhasil diperbarui.');
    }
}
