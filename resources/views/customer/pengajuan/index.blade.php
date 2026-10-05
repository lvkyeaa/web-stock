@extends(auth()->user()->hasRole('admin') ? 'layouts.admin' : 'layouts.customer')
@section('title', 'Pengajuan Saya')

@section('content')
<div class="space-y-6">
    <div>
        <h2 class="text-xl font-semibold tracking-tight text-gray-900">Pengajuan Saya</h2>
        <p class="text-sm text-gray-500 mt-1">Pantau status pengajuan persediaan yang sudah dikirim.</p>
    </div>

    <div class="space-y-4">
        @if(count($riwayat) > 0)
            @php
                $warnaStatus = [
                    'pending'   => 'bg-yellow-100 text-yellow-800',
                    'disetujui' => 'bg-green-100 text-green-800',
                    'ditolak'   => 'bg-red-100 text-red-800',
                ];
            @endphp

            {{-- Satu kartu per pengajuan (tanpa tabel, agar nyaman di layar ponsel) --}}
            @foreach($riwayat as $item)
                <article class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <header class="flex flex-wrap items-start justify-between gap-x-4 gap-y-2 px-4 sm:px-6 py-4 border-b border-gray-100">
                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Kode Pengajuan</p>
                            <div class="mt-0.5 flex items-center gap-2" x-data="{ tersalin: false }">
                                <p class="font-mono text-lg sm:text-2xl font-bold tracking-tight text-gray-900 break-all">{{ $item->code }}</p>
                                <button type="button" title="Salin kode" aria-label="Salin kode {{ $item->code }}"
                                    @click="salinKode(@js($item->code)).then(ok => { tersalin = ok; setTimeout(() => tersalin = false, 1500) })"
                                    class="shrink-0 inline-flex items-center gap-1 rounded-lg p-1.5 text-gray-400 hover:text-bps-blue hover:bg-bps-blue/10 transition cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-bps-blue/40">
                                    <svg x-show="!tersalin" class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    <svg x-show="tersalin" x-cloak class="w-4 h-4 sm:w-5 sm:h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span x-show="tersalin" x-cloak role="status" class="text-xs font-semibold text-emerald-600">Tersalin</span>
                                </button>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">{{ $item->created_at->tanggalJam() }} · {{ $item->items->count() }} item</p>
                        </div>
                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold {{ $warnaStatus[$item->status] ?? '' }}">
                            {{ ucfirst($item->status) }}
                        </span>
                    </header>

                    <ul class="divide-y divide-gray-50 px-4 sm:px-6">
                        @foreach($item->items as $detail)
                            <li class="flex items-center justify-between gap-3 py-2.5 text-sm">
                                <span class="min-w-0">
                                    <span class="font-bold text-gray-900">x{{ $detail->jumlah }}</span>
                                    <span class="text-gray-700">{{ $detail->nama_barang }}</span>
                                </span>
                                <span class="shrink-0 text-[11px] text-gray-400">{{ $detail->satuan }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <footer class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 sm:px-6 py-3 bg-gray-50/70 border-t border-gray-100">
                        <p class="text-xs text-gray-500 min-w-0">
                            <span class="font-semibold text-gray-600">Catatan admin:</span>
                            <span class="italic">{{ $item->alasan ?? '-' }}</span>
                        </p>

                        {{-- Cetak PDF hanya untuk pengajuan yang sudah disetujui --}}
                        @if($item->status === 'disetujui')
                            <a href="{{ route('pengajuan.cetak-pdf', $item->id) }}" target="_blank"
                               class="inline-flex shrink-0 items-center justify-center gap-1.5 px-3 py-2 rounded-xl border border-red-200 bg-red-50 text-red-700 hover:bg-red-100 text-xs font-bold transition">
                                <svg class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                Cetak PDF
                            </a>
                        @else
                            <span class="text-xs text-gray-400 italic">Belum Di-ACC</span>
                        @endif
                    </footer>
                </article>
            @endforeach
        @else
            <div class="text-center py-16 bg-white border border-gray-100 rounded-2xl shadow-sm space-y-4">
                <h4 class="text-base font-bold text-gray-500">Belum ada riwayat pengajuan</h4>
            </div>
        @endif
    </div>
</div>

<script>
    // Salin kode pengajuan. Clipboard API hanya ada di HTTPS/localhost; di HTTP (mis. jaringan kantor) pakai cara lama.
    async function salinKode(teks) {
        try {
            await navigator.clipboard.writeText(teks);
            return true;
        } catch (e) {
            const el = document.createElement('textarea');
            el.value = teks;
            el.setAttribute('readonly', '');
            el.style.position = 'fixed';
            el.style.opacity = '0';
            document.body.appendChild(el);
            el.select();
            const ok = document.execCommand('copy');
            el.remove();
            return ok;
        }
    }
</script>
@endsection
