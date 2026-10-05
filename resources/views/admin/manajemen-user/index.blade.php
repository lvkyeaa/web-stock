@extends('layouts.admin')
@section('title', 'Pengguna')

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold tracking-tight text-gray-900">Pengguna</h2>
            <button onclick="document.getElementById('modalTambahUser').classList.remove('hidden')"
                class="flex items-center gap-2 bg-gradient-to-r from-bps-blue to-bps-blue-dark hover:from-bps-blue-dark hover:to-bps-blue text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition shadow-[0_8px_24px_-12px_rgba(0,61,130,0.8)] cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Pengguna
            </button>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-bps-blue/5 border-b border-gray-100">
                            <th class="text-left px-6 py-4 text-xs font-bold text-bps-blue uppercase tracking-wider">No</th>
                            <th class="text-left px-6 py-4 text-xs font-bold text-bps-blue uppercase tracking-wider">Username</th>
                            <th class="text-left px-6 py-4 text-xs font-bold text-bps-blue uppercase tracking-wider">Role</th>
                            <th class="text-left px-6 py-4 text-xs font-bold text-bps-blue uppercase tracking-wider">Dibuat</th>
                            <th class="text-left px-6 py-4 text-xs font-bold text-bps-blue uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($users as $i => $user)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $users->firstItem() + $i }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-xl {{ $user->getRoleNames()->first() === 'admin' ? 'bg-bps-blue' : 'bg-bps-green' }} flex items-center justify-center">
                                            <span class="text-white text-xs font-bold uppercase">{{ substr($user->username, 0, 1) }}</span>
                                        </div>
                                        <div>
                                            <span class="text-sm font-semibold text-gray-900 block">{{ $user->username }}</span>
                                            <span class="text-xs text-gray-400 block">{{ $user->name ?? '-' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold {{ $user->getRoleNames()->first() === 'admin' ? 'bg-bps-blue/10 text-bps-blue' : 'bg-bps-green/10 text-bps-green' }}">
                                        {{ $user->labelPeran() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-500">{{ $user->created_at->tanggal() }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <button type="button" 
                                            onclick="openEditModal('{{ $user->id }}', '{{ $user->username }}', '{{ $user->name ?? '' }}', '{{ $user->getRoleNames()->first() }}')"
                                            class="p-1.5 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition cursor-pointer" 
                                            title="Edit user">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>

                                        @if ($user->id !== auth()->id())
                                            <form action="{{ route('admin.manajemen-user.destroy', $user) }}" method="POST" onsubmit="return confirm('Hapus user ini?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="p-1.5 rounded-lg bg-red-50 text-red-500 hover:bg-red-100 transition cursor-pointer" title="Hapus user">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs text-gray-400 italic px-1">Anda</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-400 text-sm">Belum ada user.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($users->hasPages())
                <div class="px-6 py-4 border-t border-gray-100">{{ $users->links() }}</div>
            @endif
        </div>
    </div>

    {{-- Modal Tambah User --}}
    <div id="modalTambahUser" data-dialog class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-bold text-gray-900">Tambah Pengguna</h3>
                <button onclick="document.getElementById('modalTambahUser').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form action="{{ route('admin.manajemen-user.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nama Lengkap</label>
                    <input type="text" name="name" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-bps-blue">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Username</label>
                    <input type="text" name="username" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-bps-blue">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Password</label>
                    <input type="password" name="password" required minlength="6" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-bps-blue">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Role</label>
                    <select name="role" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-bps-blue bg-white">
                        <option value="customer">Pengguna</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="document.getElementById('modalTambahUser').classList.add('hidden')" class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 cursor-pointer">Batal</button>
                    <button type="submit" class="flex-1 px-4 py-2.5 rounded-xl bg-bps-blue text-white text-sm font-semibold hover:bg-bps-blue-dark transition cursor-pointer">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- 💡 MODAL EDIT USER --}}
    <div id="modalEditUser" data-dialog class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-bold text-gray-900">Edit Data Pengguna</h3>
                <button onclick="document.getElementById('modalEditUser').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="formEditUser" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nama Lengkap</label>
                    <input type="text" name="name" id="edit_name" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-bps-blue">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Username</label>
                    <input type="text" name="username" id="edit_username" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-bps-blue">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Role</label>
                    <select name="role" id="edit_role" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-bps-blue bg-white">
                        <option value="customer">Pengguna</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                {{-- 🔒 PERUBAHAN DI SINI: INPUT PASSWORD BARU (OPSIONAL) --}}
                <div class="pt-2 border-t border-gray-100 space-y-3">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Ubah Password (Opsional)</p>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Password Baru</label>
                        <input type="password" name="password" id="edit_password" minlength="6" placeholder="Kosongkan jika tidak diubah" class="w-full px-4 py-2 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-bps-blue">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Konfirmasi Password Baru</label>
                        <input type="password" name="password_confirmation" id="edit_password_confirmation" placeholder="Ulangi password baru" class="w-full px-4 py-2 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-bps-blue">
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="document.getElementById('modalEditUser').classList.add('hidden')" class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 cursor-pointer">Batal</button>
                    <button type="submit" class="flex-1 px-4 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition cursor-pointer">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- 💡 JAVASCRIPT HANDLING EDIT MODAL --}}
    <script>
        function openEditModal(id, username, name, role) {
            // Pasang endpoint action update
            document.getElementById('formEditUser').action = `/admin/manajemen-user/${id}`;
            
            // Set value input lama di dalam modal
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_role').value = role;

            // Reset input password setiap kali modal dibuka
            document.getElementById('edit_password').value = '';
            document.getElementById('edit_password_confirmation').value = '';
            
            // Munculkan modal edit
            document.getElementById('modalEditUser').classList.remove('hidden');
        }
    </script>
@endsection