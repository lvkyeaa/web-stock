@extends('layouts.admin')
@section('title', 'Persetujuan Pengajuan Persediaan')

@section('content')
    <div class="space-y-6">
        <div class="rounded-2xl border border-slate-200/70 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Kelola</p>
                    <h2 class="mt-1 text-xl font-semibold tracking-tight text-slate-900">Persetujuan Pengajuan Persediaan</h2>
                </div>
                <div class="flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-2">
                    <span class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-400">Total</span>
                    <span class="text-sm font-semibold text-slate-700">{{ $transaksi->total() }}</span>
                </div>
            </div>
        </div>


        <div class="overflow-hidden rounded-2xl border border-slate-200/70 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/60">
                            <th class="w-14 px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-[0.22em] text-bps-blue-dark">No</th>
                            <th class="w-44 px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-[0.22em] text-bps-blue-dark">Pemohon</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-[0.22em] text-bps-blue-dark">Daftar Persediaan Diminta</th>
                            <th class="w-28 px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-[0.22em] text-bps-blue-dark">Status</th>
                            <th class="w-36 px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-[0.22em] text-bps-blue-dark">Tanggal</th>
                            <th class="w-44 px-4 py-3 text-center text-[10px] font-semibold uppercase tracking-[0.22em] text-bps-blue-dark">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($transaksi as $i => $item)
                            <tr class="align-top transition hover:bg-slate-50/70">
                                <td class="px-4 py-4 text-sm text-slate-500">{{ $transaksi->firstItem() + $i }}</td>
                                <td class="px-4 py-4 text-sm font-semibold text-slate-900">
                                    {{ $item->user->username ?? $item->user->name ?? '-' }}
                                    <span class="block text-[10px] font-normal text-slate-400">{{ $item->code }}</span>
                                </td>

                                {{-- Daftar Rincian Barang dalam 1 Paket --}}
                                <td class="px-4 py-4 text-sm text-slate-700">
                                    <div class="space-y-1.5">
                                        @foreach($item->items as $detail)
                                            <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-100 bg-slate-50/60 px-3 py-2">
                                                <div class="min-w-0 flex-1 flex items-center gap-2">
                                                    <span class="font-bold text-bps-blue-dark text-xs bg-slate-200/60 px-2 py-0.5 rounded-md">
                                                        x{{ $detail->jumlah }}
                                                    </span>
                                                    <span class="truncate text-xs font-semibold text-slate-800" title="{{ $detail->nama_barang }}">
                                                        {{ $detail->nama_barang }}
                                                    </span>
                                                </div>
                                                <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                                                    {{ $detail->satuan }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>

                                {{-- Status Transaksi Induk --}}
                                <td class="px-4 py-4">
                                    @php
                                        $colors = [
                                            'pending' => 'bg-amber-50 text-amber-700 border border-amber-200',
                                            'disetujui' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                                            'ditolak' => 'bg-rose-50 text-rose-700 border border-rose-200',
                                        ];
                                    @endphp
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold capitalize {{ $colors[$item->status] ?? 'bg-slate-100 text-slate-600' }}">
                                        {{ $item->status }}
                                    </span>
                                </td>

                                <td class="px-4 py-4 text-xs whitespace-nowrap text-slate-500">
                                    {{ $item->created_at->tanggalJam() }}
                                </td>

                                {{-- Tombol Aksi Paket (ACC / TOLAK / CETAK PDF) --}}
                                <td class="px-4 py-4 text-center">
                                    <div class="flex flex-col gap-2">
                                        @if ($item->status === 'pending')
                                            {{-- Tombol Setujui --}}
                                            <form action="{{ route('pengajuan.update-status', $item->id) }}" method="POST" onsubmit="return confirm('Setujui seluruh permintaan persediaan ini?')">
                                                @csrf 
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="disetujui">
                                                <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-bps-blue to-bps-blue-dark hover:from-bps-blue-dark hover:to-bps-blue px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition cursor-pointer">
                                                    Setujui
                                                </button>
                                            </form>

                                            {{-- Tombol Tolak --}}
                                            <button onclick="openTolakModal('{{ $item->id }}')" class="w-full rounded-xl bg-gradient-to-r from-rose-500 to-rose-600 hover:from-rose-600 hover:to-rose-500 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition cursor-pointer">
                                                Tolak
                                            </button>
                                        @else
                                            <div class="text-left mb-1">
                                                <span class="block text-[11px] font-semibold text-slate-500">Selesai</span>
                                                @if($item->alasan)
                                                    <span class="block text-[10px] italic text-rose-500 max-w-[130px] truncate" title="{{ $item->alasan }}">
                                                        Alasan: {{ $item->alasan }}
                                                    </span>
                                                @endif
                                            </div>
                                        @endif

                                        {{-- 🖨️ TOMBOL CETAK PDF (HANYA MUNCUL JIKA STATUS DISETUJUI) --}}
                                        @if ($item->status === 'disetujui')
                                            <a href="{{ route('pengajuan.cetak-pdf', $item->id) }}" 
                                               target="_blank" 
                                               class="w-full flex items-center justify-center gap-1 rounded-xl border border-red-200 bg-red-50/70 hover:bg-red-100 px-2.5 py-1.5 text-xs font-bold text-red-700 transition shadow-xs">
                                                <svg class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                                </svg>
                                                Cetak PDF
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-400 text-sm">Belum ada data pengajuan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($transaksi->hasPages())
                <div class="px-6 py-4 border-t border-gray-100">{{ $transaksi->links() }}</div>
            @endif
        </div>
    </div>

    {{-- Modal Tolak Paket Transaksi --}}
    <div id="modalTolak" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
            <h3 class="text-lg font-bold text-slate-900 mb-1">Tolak Pengajuan Persediaan</h3>
            <p class="text-xs text-slate-400 mb-4">Silakan masukkan alasan penolakan permintaan persediaan ini.</p>
            
            <form id="formTolak" method="POST" class="space-y-4">
                @csrf 
                @method('PATCH')
                <input type="hidden" name="status" value="ditolak">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Alasan Penolakan</label>
                    <textarea name="alasan" rows="3" placeholder="Tuliskan alasan penolakan..."
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-rose-400 resize-none" required></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="document.getElementById('modalTolak').classList.add('hidden')"
                        class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 text-xs font-semibold text-gray-600 hover:bg-gray-50 cursor-pointer">Batal</button>
                    <button type="submit" class="flex-1 px-4 py-2.5 rounded-xl bg-rose-500 text-white text-xs font-semibold hover:bg-rose-600 transition cursor-pointer">Tolak Pengajuan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openTolakModal(id) {
            // Menghasilkan URL dinamis sesuai nama route Laravel
            let routeUrl = "{{ route('pengajuan.update-status', ':id') }}";
            document.getElementById('formTolak').action = routeUrl.replace(':id', id);
            document.getElementById('modalTolak').classList.remove('hidden');
        }
    </script>
@endsection