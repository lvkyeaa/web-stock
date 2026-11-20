@extends('layouts.public')
@section('title', 'Persetujuan Pengajuan')
@section('subtitle', 'Persetujuan Pengajuan Persediaan')
@section('tanpa-tombol-masuk', true)

@section('content')
{{-- Halaman persetujuan cepat dari tautan WhatsApp (tanpa login), dirancang untuk layar ponsel.
     Alur sama dengan halaman persetujuan di aplikasi: Setujui → dialog konfirmasi (ketua tim: tanda tangan), Tolak → dialog alasan.
     Jika server menolak isian (mis. tanda tangan kosong), dialog yang sama terbuka lagi. --}}
<div class="max-w-lg mx-auto space-y-4" x-data="{ dialog: @js($errors->any() ? old('status') : null), mengirim: false, tandaTangan: '' }"
    @keydown.escape.window="if (!mengirim) dialog = null">
    @if (session('success'))
        <div role="status" class="p-4 rounded-2xl border text-sm font-medium bg-green-50 border-green-200 text-green-800">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div role="alert" class="p-4 rounded-2xl border text-sm font-medium bg-red-50 border-red-200 text-red-800">{{ session('error') }}</div>
    @endif

    {{-- Ringkasan pengajuan --}}
    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-100 flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Pengajuan</p>
                <h1 class="text-base font-bold text-gray-900 font-mono break-all">{{ $order->code }}</h1>
            </div>
            <span class="shrink-0 inline-flex px-2.5 py-1 rounded-full text-xs font-bold
                {{ match ($order->status) { 'disetujui' => 'bg-green-100 text-green-700', 'ditolak', 'dibatalkan' => 'bg-red-100 text-red-700', default => 'bg-amber-100 text-amber-800' } }}">
                {{ $statusLabel }}
            </span>
        </div>
        <dl class="p-4 grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
            <div class="col-span-2">
                <dt class="text-xs text-gray-500">Pemohon</dt>
                <dd class="font-semibold text-gray-900 break-words">{{ $order->user->name }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500">Tim</dt>
                <dd class="font-semibold text-gray-900 break-words">{{ $order->team?->name ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500">Diajukan</dt>
                <dd class="font-semibold text-gray-900">{{ $order->created_at->tanggalJam() }}</dd>
            </div>
            <div class="col-span-2">
                <dt class="text-xs text-gray-500">Ketua Tim/Penanggung Jawab</dt>
                <dd class="font-semibold text-gray-900 break-words">{{ $order->personResponsible?->name ?? '-' }}</dd>
            </div>
        </dl>
    </section>

    {{-- Daftar persediaan yang diminta --}}
    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <h2 class="px-4 py-3 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">Persediaan Diminta ({{ $order->items->count() }})</h2>
        <ul class="divide-y divide-gray-100">
            @foreach ($order->items as $item)
                <li class="px-4 py-3 flex items-center justify-between gap-3 text-sm">
                    <span class="min-w-0 break-words text-gray-900">{{ $item->nama_barang }}</span>
                    <span class="shrink-0 font-bold text-gray-900">{{ $item->jumlah }} <span class="font-normal text-gray-500">{{ $item->satuan }}</span></span>
                </li>
            @endforeach
        </ul>
    </section>

    @if ($tingkat)
        <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 space-y-4">
            <div>
                <p class="text-sm text-gray-700">Halo <span class="font-semibold">{{ $user->name }}</span>,</p>
                <p class="text-sm text-gray-700">pengajuan ini menunggu keputusan Anda sebagai <span class="font-semibold">{{ $tingkat === 'ketua' ? 'ketua tim/penanggung jawab' : 'admin' }}</span>.</p>
            </div>
            {{-- Tombol besar mudah disentuh; Setujui di atas pada ponsel --}}
            <div class="flex flex-col-reverse sm:flex-row gap-3">
                <button type="button" @click="dialog = 'ditolak'"
                    class="w-full sm:flex-1 py-3.5 rounded-xl border border-red-200 bg-white text-red-600 text-base font-semibold hover:bg-red-50 transition cursor-pointer">
                    Tolak
                </button>
                <button type="button" @click="dialog = 'disetujui'"
                    class="w-full sm:flex-1 py-3.5 rounded-xl bg-bps-blue text-white text-base font-semibold hover:bg-bps-blue-dark transition cursor-pointer">
                    Setujui
                </button>
            </div>
        </section>

        {{-- Dialog Setujui: konfirmasi; ketua tim wajib tanda tangan. Di ponsel muncul dari bawah layar. --}}
        <div x-show="dialog === 'disetujui'" x-cloak class="fixed inset-0 z-50 bg-black/50 flex items-end sm:items-center justify-center sm:p-4"
            role="dialog" aria-modal="true" aria-labelledby="judulSetujui" @click.self="if (!mengirim) dialog = null">
            <form method="POST" action="{{ request()->fullUrl() }}" @submit="mengirim = true"
                class="bg-white w-full sm:max-w-md rounded-t-2xl sm:rounded-2xl shadow-2xl p-5 space-y-4 max-h-[90dvh] overflow-y-auto">
                @csrf
                <input type="hidden" name="status" value="disetujui">
                <div>
                    <h3 id="judulSetujui" class="text-lg font-bold text-gray-900">Setujui Pengajuan Persediaan?</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Pengajuan <span class="font-mono font-semibold text-gray-700">{{ $order->code }}</span> dari <span class="font-semibold text-gray-700">{{ $order->user->name }}</span>
                        ({{ $order->items->count() }} item) akan disetujui{{ $tingkat === 'ketua' ? ' dan diteruskan ke admin' : ' dan stok langsung dipotong' }}.
                    </p>
                </div>
                @if ($tingkat === 'ketua')
                    <div>
                        <span class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Tanda Tangan Ketua Tim/Penanggung Jawab <span class="text-red-500">*</span></span>
                        @include('pengajuan.partials.tanda-tangan', ['atribut' => 'x-model="tandaTangan"'])
                        <input type="hidden" name="tanda_tangan" :value="tandaTangan">
                    </div>
                @endif
                @if ($errors->any() && old('status') === 'disetujui')
                    <p role="alert" class="p-3 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700">{{ $errors->first() }}</p>
                @endif
                <div class="flex gap-3">
                    <button type="button" @click="dialog = null" :disabled="mengirim"
                        class="flex-1 py-3 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 cursor-pointer disabled:opacity-60">Batal</button>
                    <button type="submit" :disabled="mengirim{{ $tingkat === 'ketua' ? ' || !tandaTangan' : '' }}"
                        class="flex-1 py-3 rounded-xl bg-bps-blue text-white text-sm font-semibold hover:bg-bps-blue-dark transition cursor-pointer disabled:bg-slate-300 disabled:cursor-not-allowed"
                        x-text="mengirim ? 'Menyimpan...' : 'Ya, Setujui'">Ya, Setujui</button>
                </div>
            </form>
        </div>

        {{-- Dialog Tolak: alasan wajib --}}
        <div x-show="dialog === 'ditolak'" x-cloak class="fixed inset-0 z-50 bg-black/50 flex items-end sm:items-center justify-center sm:p-4"
            role="dialog" aria-modal="true" aria-labelledby="judulTolak" @click.self="if (!mengirim) dialog = null">
            <form method="POST" action="{{ request()->fullUrl() }}" @submit="mengirim = true"
                class="bg-white w-full sm:max-w-md rounded-t-2xl sm:rounded-2xl shadow-2xl p-5 space-y-4 max-h-[90dvh] overflow-y-auto">
                @csrf
                <input type="hidden" name="status" value="ditolak">
                <div>
                    <h3 id="judulTolak" class="text-lg font-bold text-gray-900">Tolak Pengajuan Persediaan</h3>
                    <p class="text-sm text-gray-500 mt-1">Pengajuan <span class="font-mono font-semibold text-gray-700">{{ $order->code }}</span>. Silakan masukkan alasan penolakan permintaan persediaan ini.</p>
                </div>
                <div>
                    <label for="alasan" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Alasan Penolakan <span class="text-red-500">*</span></label>
                    <textarea id="alasan" name="alasan" rows="3" maxlength="500" required placeholder="Tuliskan alasan penolakan..."
                        class="w-full px-3 py-2 rounded-xl border border-gray-200 text-base sm:text-sm focus:outline-none focus:ring-2 focus:ring-rose-400 resize-none">{{ old('alasan') }}</textarea>
                </div>
                @if ($errors->any() && old('status') === 'ditolak')
                    <p role="alert" class="p-3 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700">{{ $errors->first() }}</p>
                @endif
                <div class="flex gap-3">
                    <button type="button" @click="dialog = null" :disabled="mengirim"
                        class="flex-1 py-3 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 cursor-pointer disabled:opacity-60">Batal</button>
                    <button type="submit" :disabled="mengirim"
                        class="flex-1 py-3 rounded-xl bg-rose-500 text-white text-sm font-semibold hover:bg-rose-600 transition cursor-pointer disabled:opacity-60 disabled:cursor-wait"
                        x-text="mengirim ? 'Menyimpan...' : 'Tolak Pengajuan'">Tolak Pengajuan</button>
                </div>
            </form>
        </div>
    @else
        <div class="p-4 rounded-2xl border border-gray-200 bg-white text-sm text-gray-600 text-center space-y-1">
            @if ($keterangan && ! session('error'))
                <p class="font-semibold text-gray-800">{{ $keterangan }}</p>
            @endif
            <p>Tidak ada keputusan yang perlu Anda ambil untuk pengajuan ini.</p>
        </div>
    @endif
</div>
@endsection
