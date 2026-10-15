@extends($isAdmin ? 'layouts.admin' : 'layouts.customer')

@section('title', 'Buat Peminjaman')

@section('content')
{{-- Validasi, cek bentrok & status (admin langsung disetujui, customer menunggu) sama dengan modal: lewat peminjaman.store --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 sm:p-6 max-w-2xl"
    x-data="bookingCreate(@js([
        'facilities' => $facilities,
        'jenis'      => $oldType ?? '',
        'oldItem'    => old('facility_request_id', ''),
        'jumlahPeserta' => old('jumlah_peserta', ''),
        'mulai'      => old('waktu_mulai', ''),
        'selesai'    => old('waktu_selesai', ''),
        'serverNow'  => now()->getTimestampMs(), // jam server saat halaman dibuka
        'timezone'   => config('app.timezone'),
    ]))">

    {{-- HEADER --}}
    <div class="mb-6 border-b border-gray-100 pb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-bps-blue-dark">Buat Peminjaman</h2>
            <p class="text-xs text-gray-400">Lengkapi detail reservasi fasilitas</p>
        </div>
        <a href="{{ route('peminjaman.index') }}" class="px-4 py-2 rounded-xl text-xs font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 transition">
            Kembali ke Jadwal
        </a>
    </div>

    @if(session('ai_filled'))
        <div class="mb-4 flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
            @include('partials.asisten-ai.ikon', ['class' => 'h-4 w-4 shrink-0'])
            Formulir diisi otomatis oleh Asisten AI. Periksa dan lengkapi sebelum mengirim.
        </div>
    @endif

    <form method="POST" action="{{ route('peminjaman.store') }}" class="space-y-4">
        @csrf

        {{-- 1. JENIS FASILITAS --}}
        <div>
            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Jenis Fasilitas</label>
            <div class="grid grid-cols-[repeat(auto-fit,minmax(120px,1fr))] gap-3">
                @foreach($types as $facilityType)
                    <label class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 transition cursor-pointer hover:shadow-md"
                        :class="jenis === @js($facilityType->code) ? 'shadow-md' : 'opacity-70'"
                        :style="jenis === @js($facilityType->code)
                            ? 'border-color:{{ $facilityType->color }};background:{{ $facilityType->color }}12'
                            : 'border-color:{{ $facilityType->color }}33;background:{{ $facilityType->color }}12'">
                        <input type="radio" class="sr-only" value="{{ $facilityType->code }}" x-model="jenis" @change="oldItem = ''">
                        <span class="text-3xl">{{ $facilityType->icon }}</span>
                        <span class="text-sm font-bold" style="color:{{ $facilityType->color }}">{{ $facilityType->name }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <template x-if="jenis">
            <div class="space-y-4">
                {{-- 2. PILIH MOBIL / RUANGAN --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1" x-text="jenis === 'car' ? 'Mobil (Plat Nomor)' : 'Ruangan'"></label>
                    <select name="facility_request_id" required @change="oldItem = $event.target.value"
                        class="w-full px-3 py-2.5 bg-white border rounded-xl text-sm text-slate-800 focus:outline-none focus:border-bps-blue focus:ring-4 focus:ring-bps-blue/10 transition-all cursor-pointer @error('facility_request_id') border-red-500 @else border-slate-200 @enderror">
                        <option value="" disabled :selected="!(items[jenis] || []).some(f => f.id === oldItem)" x-text="jenis === 'car' ? '— Pilih Mobil —' : '— Pilih Ruangan —'"></option>
                        <template x-for="facility in items[jenis] || []" :key="facility.id">
                            <option :value="facility.id" x-text="facility.name + (facility.capacity ? ` (kapasitas ${facility.capacity} orang)` : '')" :selected="facility.id === oldItem"></option>
                        </template>
                    </select>
                    @error('facility_request_id') <span class="text-xs text-red-500 font-semibold mt-1 block">{{ $message }}</span> @enderror
                </div>

                {{-- 3. WAKTU MULAI --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1" x-text="jenis === 'car' ? 'Waktu Berangkat' : 'Waktu Mulai Rapat'"></label>
                    <input type="datetime-local" name="waktu_mulai" required x-model="mulai" :min="minMulai"
                        class="w-full px-3 py-2 border rounded-xl text-sm focus:outline-none focus:border-bps-blue focus:ring-4 focus:ring-bps-blue/10 @error('waktu_mulai') border-red-500 @else border-gray-300 @enderror">
                    @error('waktu_mulai') <span class="text-xs text-red-500 font-semibold mt-1 block">{{ $message }}</span> @enderror
                </div>

                {{-- 4. WAKTU SELESAI (hari yang sama dengan waktu mulai) --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1" x-text="jenis === 'car' ? 'Waktu Kembali' : 'Waktu Selesai Rapat'"></label>
                    <input type="datetime-local" name="waktu_selesai" required x-model="selesai"
                        :min="mulai || null" :max="mulai ? mulai.slice(0, 10) + 'T23:59' : null"
                        class="w-full px-3 py-2 border rounded-xl text-sm focus:outline-none focus:border-bps-blue focus:ring-4 focus:ring-bps-blue/10 @error('waktu_selesai') border-red-500 @else border-gray-300 @enderror">
                    @error('waktu_selesai') <span class="text-xs text-red-500 font-semibold mt-1 block">{{ $message }}</span> @enderror
                </div>

                {{-- 5. KEPERLUAN --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1" x-text="jenis === 'car' ? 'Tujuan / Keperluan Perjalanan' : 'Agenda Rapat'"></label>
                    <textarea name="keperluan" rows="3" required :placeholder="jenis === 'car' ? 'Tuliskan tujuan dan keperluan perjalanan...' : 'Tuliskan agenda rapat...'"
                        class="w-full px-3 py-2 border rounded-xl text-sm focus:outline-none focus:border-bps-blue focus:ring-4 focus:ring-bps-blue/10 @error('keperluan') border-red-500 @else border-gray-300 @enderror">{{ old('keperluan') }}</textarea>
                    @error('keperluan') <span class="text-xs text-red-500 font-semibold mt-1 block">{{ $message }}</span> @enderror
                </div>

                {{-- 6. DETAIL RUANG RAPAT (hanya jenis ruang rapat) --}}
                <template x-if="jenis === 'room'">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Jumlah Peserta</label>
                            <input type="number" name="jumlah_peserta" min="1" :max="kapasitas || @js(\App\Models\Peminjaman::MAKS_PESERTA)" required x-model="jumlahPeserta" placeholder="Contoh: 20"
                                class="w-full px-3 py-2 border rounded-xl text-sm focus:outline-none focus:border-bps-blue focus:ring-4 focus:ring-bps-blue/10 @error('jumlah_peserta') border-red-500 @else border-gray-300 @enderror">
                            @error('jumlah_peserta') <span class="text-xs text-red-500 font-semibold mt-1 block">{{ $message }}</span> @enderror
                            <p x-show="kapasitas && Number(jumlahPeserta) > kapasitas" x-cloak class="text-xs text-amber-600 font-semibold mt-1"
                                x-text="`Melebihi kapasitas ruangan (${kapasitas} orang).`"></p>
                        </div>
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" name="butuh_sound_system" value="1" @checked(old('butuh_sound_system')) class="w-4 h-4 rounded accent-bps-blue">
                                Butuh pengaturan sound system
                            </label>
                            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" name="butuh_konsumsi" value="1" @checked(old('butuh_konsumsi')) class="w-4 h-4 rounded accent-bps-blue">
                                Butuh konsumsi
                            </label>
                        </div>
                    </div>
                </template>

                <button type="submit" class="w-full py-2.5 rounded-xl text-sm font-semibold bg-gradient-to-r from-bps-blue to-bps-blue-dark hover:from-bps-blue-dark hover:to-bps-blue text-white transition shadow-[0_8px_24px_-12px_rgba(0,61,130,0.8)] cursor-pointer">
                    {{ $isAdmin ? 'Simpan Peminjaman' : 'Kirim Pengajuan' }}
                </button>
            </div>
        </template>
    </form>
</div>

<script>
    function bookingCreate(config) {
        const selisihJam = config.serverNow - Date.now(); // koreksi jika jam perangkat berbeda dengan server

        // Tanggal hari ini menurut server, dalam zona waktu server (YYYY-MM-DD)
        const parts = Object.fromEntries(new Intl.DateTimeFormat('en-CA', {
            timeZone: config.timezone, year: 'numeric', month: '2-digit', day: '2-digit',
        }).formatToParts(new Date(Date.now() + selisihJam)).map(p => [p.type, p.value]));

        return {
            items: config.facilities,
            jenis: config.jenis,
            oldItem: config.oldItem, // fasilitas yang dipilih
            jumlahPeserta: config.jumlahPeserta,
            mulai: config.mulai,
            selesai: config.selesai,
            minMulai: `${parts.year}-${parts.month}-${parts.day}T00:00`, // jam tidak dibatasi, sama dengan modal

            // Fasilitas yang sedang dipilih & kapasitasnya (hanya ruang rapat yang punya kapasitas)
            get fasilitasTerpilih() {
                return (this.items[this.jenis] || []).find(f => f.id === this.oldItem) ?? null;
            },
            get kapasitas() {
                return this.fasilitasTerpilih?.capacity ?? null;
            },
        };
    }
</script>
@endsection
