<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

// Lonceng notifikasi (tabel notifications): buka satu notifikasi (ditandai dibaca, lalu ke tautannya) & tandai semua dibaca
class NotifikasiController extends Controller
{
    public function buka(Request $request, string $id)
    {
        // Hanya notifikasi milik sendiri; milik orang lain → 404
        $notifikasi = $request->user()->notifications()->findOrFail($id);
        $notifikasi->markAsRead();

        return redirect()->to($notifikasi->data['url'] ?? url()->previous());
    }

    public function bacaSemua(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }
}
