@extends($user->hasRole('admin') ? 'layouts.admin' : 'layouts.customer')
@section('title', 'Profil')

@section('content')
<div class="space-y-6 max-w-xl">
    <div>
        <h2 class="text-xl font-semibold tracking-tight text-gray-900">Profil</h2>
        <p class="text-sm text-gray-500 mt-1">Nomor WhatsApp dipakai untuk mengirim kabar persetujuan pengajuan persediaan.</p>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm divide-y divide-gray-100">
        <dl class="p-4 sm:p-6 grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-xs font-bold text-gray-500 uppercase tracking-wider">Nama</dt>
                <dd class="mt-1 text-sm text-gray-900 break-words">{{ $user->name }}</dd>
            </div>
            <div>
                <dt class="text-xs font-bold text-gray-500 uppercase tracking-wider">Email</dt>
                <dd class="mt-1 text-sm text-gray-900 break-words">{{ $user->email }}</dd>
            </div>
            <div>
                <dt class="text-xs font-bold text-gray-500 uppercase tracking-wider">Role</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $user->labelPeran() }}</dd>
            </div>
            <div>
                <dt class="text-xs font-bold text-gray-500 uppercase tracking-wider">Tim</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $user->team?->name ?? '-' }}</dd>
            </div>
        </dl>

        {{-- Validasi di browser (pattern) sama dengan validasi server --}}
        <form method="POST" action="{{ route('profil.update') }}" class="p-4 sm:p-6 space-y-3">
            @csrf
            @method('PUT')
            <div>
                <label for="phone" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Nomor WhatsApp <span class="text-red-500">*</span></label>
                <input type="tel" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" required maxlength="15" inputmode="numeric"
                    pattern="62[0-9]{8,13}" title="Diawali 62, hanya angka, 10–15 digit. Contoh: 6281234567890" placeholder="6281234567890"
                    @error('phone') aria-invalid="true" aria-describedby="phone-error" @enderror
                    class="w-full px-3 py-2 rounded-xl border text-sm focus:outline-none focus:ring-2 @error('phone') border-red-300 focus:ring-red-200 focus:border-red-400 @else border-gray-200 focus:ring-bps-blue/30 focus:border-bps-blue @enderror">
                @error('phone')
                    <p id="phone-error" role="alert" class="text-xs font-medium text-red-700 mt-1">{{ $message }}</p>
                @enderror
                <p class="text-xs text-gray-500 mt-1">Diawali 62, tanpa +, spasi, atau tanda -.</p>
            </div>
            <div class="flex justify-end">
                <button type="submit"
                    class="px-5 py-2.5 rounded-xl bg-bps-blue text-white text-sm font-semibold hover:bg-bps-blue-dark transition cursor-pointer">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
