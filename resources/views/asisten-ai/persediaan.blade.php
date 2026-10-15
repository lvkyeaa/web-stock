@extends(auth()->user()->hasRole('admin') ? 'layouts.admin' : 'layouts.customer')
@section('title', 'Rekomendasi Persediaan')

@section('content')
{{-- Hasil Asisten AI (dashboard / katalog): persediaan tersedia yang berkaitan dengan tiap permintaan.
     Yang dicentang menggantikan isi keranjang, lalu pindah ke halaman keranjang. --}}
<form method="POST" action="{{ route('asisten-ai.apply') }}" class="space-y-6 max-w-3xl"
    x-data="{ jumlahDipilih: {{ $groups->sum(fn($group) => $group['options']->where('checked', true)->count()) }} }"
    @change="jumlahDipilih = $el.querySelectorAll('input[type=checkbox]:checked').length">
    @csrf

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="flex items-start gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-bps-orange to-bps-blue text-white shadow-md">
                @include('partials.asisten-ai.ikon', ['class' => 'h-5 w-5'])
            </div>
            <div>
                <h2 class="text-xl font-semibold tracking-tight text-gray-900">Rekomendasi Persediaan</h2>
                <p class="text-sm text-gray-500 mt-1">Permintaan Anda: <span class="italic">“{{ $message }}”</span></p>
            </div>
        </div>
        <a href="{{ route('barang.katalog') }}" class="px-4 py-2 rounded-xl text-xs font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 transition">
            Kembali ke Katalog
        </a>
    </div>

    <p class="text-sm text-gray-600">Centang persediaan yang Anda maksud. Jumlah dapat diubah lagi di halaman keranjang.</p>

    {{-- SATU KARTU PER PERMINTAAN --}}
    @foreach ($groups as $group)
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-3 bg-gray-50 border-b border-gray-100 text-sm font-bold text-gray-700">“{{ $group['request'] }}”</div>
            <div class="divide-y divide-gray-100">
                @foreach ($group['options'] as $option)
                    <label class="flex items-center gap-3 px-5 py-3 cursor-pointer hover:bg-slate-50 transition">
                        <input type="checkbox" name="selected[{{ $group['index'] }}][]" value="{{ $option['id'] }}" @checked($option['checked'])
                            class="h-4 w-4 rounded border-gray-300 text-bps-blue focus:ring-bps-blue/30">
                        <span class="flex-1 min-w-0">
                            <span class="block text-sm font-semibold text-gray-900">{{ $option['name'] }}</span>
                            <span class="block text-xs text-gray-500">Tersedia {{ $option['available'] }} {{ $option['unit'] }}</span>
                        </span>
                        <span class="text-sm font-semibold text-gray-700 whitespace-nowrap">
                            × {{ $option['quantity'] }} {{ $option['unit'] }}
                            @if ($option['capped'])
                                <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700">maks</span>
                            @endif
                        </span>
                    </label>
                @endforeach
            </div>
        </div>
    @endforeach

    @if ($notFound->isNotEmpty())
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            <p class="font-semibold mb-1">Tidak ditemukan persediaan yang tersedia untuk:</p>
            <ul class="list-disc pl-5">
                @foreach ($notFound as $name)
                    <li>{{ $name }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($cartCount > 0)
        <p class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
            Keranjang Anda berisi {{ $cartCount }} persediaan dan akan dikosongkan, lalu diganti dengan persediaan yang dipilih di sini.
        </p>
    @endif

    <button type="submit" :disabled="jumlahDipilih === 0"
        class="w-full sm:w-auto px-6 py-2.5 rounded-xl text-sm font-semibold text-white transition shadow-[0_8px_24px_-12px_rgba(0,61,130,0.8)] cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed {{ $cartCount > 0 ? 'bg-rose-600 hover:bg-rose-700' : 'bg-gradient-to-r from-bps-blue to-bps-blue-dark hover:from-bps-blue-dark hover:to-bps-blue' }}">
        {{ $cartCount > 0 ? 'Ganti Isi Keranjang' : 'Masukkan ke Keranjang' }}
        (<span x-text="jumlahDipilih"></span>)
    </button>
</form>
@endsection
