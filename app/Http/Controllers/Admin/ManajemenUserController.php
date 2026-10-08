<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Peminjaman;
use App\Models\Riwayat;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

// Kelola pengguna: daftar, tambah, ubah, dan hapus lewat JSON tanpa reload halaman
class ManajemenUserController extends Controller
{
    // Hanya kerangka halaman; daftar diambil lewat data() (JSON). Kondisi awal dari query string (tetap saat reload)
    public function index(Request $request)
    {
        return view('admin.manajemen-user.index', ['initial' => [
            'q'          => mb_substr(trim((string) $request->q), 0, 100),
            'page'       => max(1, (int) $request->page),
            'dataUrl'    => route('admin.manajemen-user.data'),
            'storeUrl'   => route('admin.manajemen-user.store'),
            'updateUrl'  => route('admin.manajemen-user.update', ':id'),
            'destroyUrl' => route('admin.manajemen-user.destroy', ':id'),
            'csrf'       => csrf_token(),
        ]]);
    }

    // API JSON: daftar pengguna per halaman, dengan pencarian nama / username
    public function data(Request $request)
    {
        $request->validate([
            'q'    => 'nullable|string|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $users = User::with('roles')
            ->when($request->filled('q'), function ($query) use ($request) {
                $kata = '%' . trim($request->q) . '%';
                $query->where(fn ($w) => $w->where('name', 'like', $kata)->orWhere('username', 'like', $kata));
            })
            ->latest()
            ->paginate(10);

        return response()->json([
            'data' => $users->getCollection()->map(fn (User $user) => [
                'id'          => $user->id,
                'name'        => $user->name,
                'username'    => $user->username,
                'email'       => $user->email,
                'role'        => $user->getRoleNames()->first(),
                'role_label'  => $user->labelPeran(),
                'dibuat'      => $user->created_at->tanggal(),
                'is_self'     => $user->id === $request->user()->id,
            ]),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'from'         => $users->firstItem(),
                'to'           => $users->lastItem(),
                'total'        => $users->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users,email|unique:users,username', // username = email
            'password' => 'required|string|min:6',
            'role'     => 'required|in:admin,customer',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'username' => $request->email, // username disamakan dengan email
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);
        $user->assignRole($request->role);

        return response()->json(['message' => 'User berhasil ditambahkan.']);
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Tidak dapat menghapus akun sendiri.'], 422);
        }

        // Pengguna yang punya riwayat tidak boleh dihapus (database juga menolak: foreign key RESTRICT)
        $riwayat = array_filter([
            'pengajuan persediaan'       => Order::where('user_id', $user->id)->count(),
            'penanggung jawab pengajuan' => Order::where('person_responsible_user_id', $user->id)->count(),
            'peminjaman fasilitas'       => Peminjaman::where('user_id', $user->id)->count(), // yang belum dihapus
            'riwayat persetujuan'        => Riwayat::where('actor_id', $user->id)->count(),
        ]);
        if ($riwayat) {
            $rincian = collect($riwayat)->map(fn ($jumlah, $jenis) => "{$jumlah} {$jenis}")->implode(', ');

            return response()->json(['message' => "User tidak bisa dihapus karena sudah memiliki {$rincian}."], 422);
        }

        // Semua peminjamannya sudah dihapus (soft delete): hapus permanen dulu, lalu user-nya
        DB::transaction(function () use ($user) {
            Peminjaman::onlyTrashed()->where('user_id', $user->id)->forceDelete();
            $user->delete();
        });

        return response()->json(['message' => 'User berhasil dihapus.']);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
                Rule::unique('users', 'username')->ignore($user->id), // username = email
            ],
            'role'     => 'required|in:admin,customer',
            'password' => 'nullable|string|min:6|confirmed', // Opsional, wajib konfirmasi jika diisi
        ]);

        $data = [
            'name'     => $request->name,
            'username' => $request->email, // username disamakan dengan email
            'email'    => $request->email,
        ];

        // Jalankan update password HANYA jika field password diisi oleh admin
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        // Minimal harus tetap ada satu admin, agar tidak ada yang terkunci dari menu admin
        if ($user->hasRole('admin') && $request->role !== 'admin' && User::role('admin')->count() <= 1) {
            return response()->json(['message' => 'Role tidak bisa diubah: minimal harus ada satu admin.'], 422);
        }

        $user->update($data);
        $user->syncRoles([$request->role]);

        return response()->json(['message' => 'Data user berhasil diperbarui!']);
    }
}
