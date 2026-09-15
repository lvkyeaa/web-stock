@extends('layouts.customer')

@section('title', 'Peminjaman Fasilitas')

{{-- 💡 Mengamankan CDN FullCalendar di paling atas agar diload duluan oleh browser --}}
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>

@section('content')
<div class="space-y-6">

    {{-- ─── NOTIFIKASI ALERT SUKSES ─── --}}
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm font-medium shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- ─── NOTIFIKASI ALERT ERROR ─── --}}
    @if(session('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl text-sm font-medium shadow-sm">
            {{ session('error') }}
        </div>
    @endif

    {{-- ─── GRID KONTEN PENGAJUAN (FORM + PANEL TAB KARTU DENGAN HEADER TAB) ─── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {{-- COLUMN 1: FORM PENGAJUAN PEMINJAMAN FASILITAS --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 h-fit">
            <h2 class="text-lg font-bold text-bps-blue-dark mb-1">Form Pengajuan Peminjaman</h2>
            <p class="text-xs text-gray-400 mb-6">Silakan isi detail reservasi fasilitas dinas (mobil / ruang)</p>

            <form action="{{ route('customer.peminjaman.store') }}" method="POST" class="space-y-4">
                @csrf

                {{-- 1. PILIH JENIS FASILITAS --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Jenis Fasilitas</label>
                    <select name="jenis_fasilitas" id="jenis_fasilitas" required onchange="updateItemOptions()"
                        class="w-full px-3 py-2.5 bg-white border @error('jenis_fasilitas') border-red-500 @else border-slate-200 @enderror rounded-xl text-sm text-slate-800 focus:outline-none focus:border-bps-blue focus:ring-4 focus:ring-bps-blue/10 transition-all cursor-pointer">
                        <option value="mobil" {{ (old('jenis_fasilitas', $jenis) === 'ruang') ? '' : 'selected' }}>🚗 Mobil Dinas</option>
                        <option value="ruang" {{ (old('jenis_fasilitas', $jenis) === 'ruang') ? 'selected' : '' }}>🏢 Ruang Rapat</option>
                    </select>
                    @error('jenis_fasilitas')
                        <span class="text-xs text-red-500 font-semibold mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                {{-- 2. PILIH NAMA ITEM --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Item / Fasilitas</label>
                    
                    <div class="relative flex items-center">
                        <select name="nama_item" id="nama_item" required
                            class="w-full px-3 py-2.5 bg-white border @error('nama_item') border-red-500 @else border-slate-200 @enderror rounded-xl text-sm text-slate-800 appearance-none focus:outline-none focus:border-bps-blue focus:ring-4 focus:ring-bps-blue/10 transition-all cursor-pointer">
                            <option value="" disabled selected hidden>— Pilih Fasilitas —</option>
                        </select>

                        <div class="absolute right-4 pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>

                    @error('nama_item')
                        <span class="text-xs text-red-500 font-semibold mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                {{-- 3. WAKTU MULAI --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Waktu Mulai</label>
                    <input type="datetime-local" name="waktu_mulai" required value="{{ old('waktu_mulai') }}"
                        class="w-full px-3 py-2 border @error('waktu_mulai') border-red-500 @else border-gray-300 @enderror rounded-xl text-sm focus:outline-none focus:border-bps-blue focus:ring-4 focus:ring-bps-blue/10">
                    @error('waktu_mulai')
                        <span class="text-xs text-red-500 font-semibold mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                {{-- 4. WAKTU SELESAI --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Waktu Selesai</label>
                    <input type="datetime-local" name="waktu_selesai" required value="{{ old('waktu_selesai') }}"
                        class="w-full px-3 py-2 border @error('waktu_selesai') border-red-500 @else border-gray-300 @enderror rounded-xl text-sm focus:outline-none focus:border-bps-blue focus:ring-4 focus:ring-bps-blue/10">
                    @error('waktu_selesai')
                        <span class="text-xs text-red-500 font-semibold mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                {{-- 5. KEPERLUAN --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Keperluan / Agenda</label>
                    <textarea name="keperluan" rows="3" placeholder="Tuliskan alasan peminjaman..."
                        class="w-full px-3 py-2 border @error('keperluan') border-red-500 @else border-gray-300 @enderror rounded-xl text-sm focus:outline-none focus:border-bps-blue focus:ring-4 focus:ring-bps-blue/10">{{ old('keperluan') }}</textarea>
                    @error('keperluan')
                        <span class="text-xs text-red-500 font-semibold mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="w-full py-2.5 rounded-xl text-sm font-semibold bg-gradient-to-r from-bps-blue to-bps-blue-dark hover:from-bps-blue-dark hover:to-bps-blue text-white transition shadow-[0_8px_24px_-12px_rgba(0,61,130,0.8)] cursor-pointer">
                    Kirim Pengajuan
                </button>
            </form>
        </div>

        {{-- COLUMN 2 & 3: KARTU UTAMA PANEL KANAN DENGAN HEADER TAB OPTIONS AT TOP --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                
                {{-- CARD HEADER: TAB OPTIONS AT TOP, TITLE UNDERNEATH --}}
                <div class="space-y-3 mb-6 border-b border-gray-100 pb-5">
                    
                    {{-- 1. TAB OPTIONS DI BAGIAN ATAS KARTU --}}
                    <div class="flex bg-gray-100 p-1 rounded-xl w-fit border border-gray-200">
                        <button onclick="switchCustomerViewTab('calendar')" id="customer-tab-calendar"
                            class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all shadow-sm bg-white text-bps-blue-dark cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Kalender
                        </button>
                        <button onclick="switchCustomerViewTab('list')" id="customer-tab-list"
                            class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all text-gray-500 hover:text-gray-800 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                            </svg>
                            Riwayat / Daftar
                        </button>
                    </div>

                    {{-- 2. JUDUL DAN SUBJUDUL DI BAWAH TOMBOL TAB --}}
                    <div>
                        <h2 class="text-xl font-bold tracking-tight text-bps-blue-dark" id="customer-card-title">Kalender Kesibukan Jadwal Fasilitas</h2>
                        <p class="text-xs text-gray-400" id="customer-card-subtitle">Filter dan cek jadwal terisi sebelum mengajukan booking</p>
                    </div>
                </div>

                {{-- TAB CONTENT 1: KALENDER KESIBUKAN DENGAN FILTER FASILITAS --}}
                <div id="customer-view-calendar" class="block space-y-4">
                    <div class="flex items-center justify-end">
                        {{-- SUB TAB FILTER FASILITAS KALENDER --}}
                        <div class="flex bg-slate-50 p-1 rounded-xl w-fit border border-slate-200">
                            <button onclick="switchCalendarFilter('all')" id="cust-btn-all" class="px-3 py-1 text-xs font-semibold rounded-lg transition shadow-sm cursor-pointer bg-white text-bps-blue-dark">
                                Semua
                            </button>
                            <button onclick="switchCalendarFilter('mobil')" id="cust-btn-mobil" class="px-3 py-1 text-xs font-semibold rounded-lg transition cursor-pointer text-gray-500 hover:text-gray-700">
                                Mobil Dinas
                            </button>
                            <button onclick="switchCalendarFilter('ruang')" id="cust-btn-ruang" class="px-3 py-1 text-xs font-semibold rounded-lg transition cursor-pointer text-gray-500 hover:text-gray-700">
                                Ruang Rapat
                            </button>
                        </div>
                    </div>
                    
                    {{-- Wadah Utama Kalender --}}
                    <div id="calendar" style="min-height: 500px; background: white;"></div>
                </div>

                {{-- TAB CONTENT 2: TAMPILAN DAFTAR RIWAYAT PENGAJUAN --}}
                <div id="customer-view-list" class="hidden">
                    @if($peminjaman->isEmpty())
                        <div class="text-center py-12 text-gray-400 border-2 border-dashed border-gray-100 rounded-xl">
                            <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-sm">Belum ada riwayat peminjaman fasilitas.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-gray-200 text-xs font-bold text-gray-500 uppercase bg-gray-50">
                                        <th class="p-3">Fasilitas / Item</th>
                                        <th class="p-3">Jenis</th>
                                        <th class="p-3">Waktu Peminjaman</th>
                                        <th class="p-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 text-sm">
                                    @foreach($peminjaman as $item)
                                        <tr class="hover:bg-gray-50/50 transition">
                                            <td class="p-3 font-semibold text-gray-700">{{ $item->nama_item }}</td>
                                            <td class="p-3 text-xs capitalize font-medium">
                                                <span class="px-2 py-0.5 rounded-md text-[11px] font-semibold {{ $item->jenis_fasilitas === 'mobil' ? 'bg-sky-50 text-sky-700 border border-sky-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                                    {{ $item->jenis_fasilitas }}
                                                </span>
                                            </td>
                                            <td class="p-3 text-xs text-gray-600 space-y-0.5">
                                                <div class="text-green-600">Mulai: {{ $item->waktu_mulai->format('d M Y - H:i') }}</div>
                                                <div class="text-red-600">Selesai: {{ $item->waktu_selesai->format('d M Y - H:i') }}</div>
                                            </td>
                                            <td class="p-3">
                                                @if($item->status === 'pending')
                                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-600 border border-amber-200">Pending</span>
                                                @elseif($item->status === 'disetujui')
                                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-green-50 text-green-600 border border-green-200">Disetujui</span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-red-50 text-red-600 border border-red-200">Ditolak</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>

{{-- ─── LOGIKA SCRIPT INTERAKTIF FULLCALENDAR, TAB SWITCHER, & DYNAMIC DROPDOWN ─── --}}
<script>
    const dataMobil = {!! json_encode($dataMobil ?? []) !!};
    const dataRuang = {!! json_encode($dataRuang ?? []) !!};
    const dataSemua = [...dataMobil, ...dataRuang];

    const itemOptions = {
        mobil: [
            { value: "L 38", text: "L 38" },
            { value: "L 1760 HP", text: "L 1760 HP" },
            { value: "L 1758 HP", text: "L 1758 HP" },
            { value: "L 1759 HP", text: "L 1759 HP" },
            { value: "B 1877 PQS", text: "B 1877 PQS" },
            { value: "B 1875 PQS", text: "B 1875 PQS" },
            { value: "S 3351 NP", text: "S 3351 NP" },
            { value: "S 3346 NP", text: "S 3346 NP" }
        ],
        ruang: [
            { value: "Ruang Vicon", text: "Ruang Vicon" },
            { value: "Ruang Aula Majapahit", text: "Ruang Aula Majapahit" }
        ]
    };

    var calendar;

    function updateItemOptions() {
        const jenisSelect = document.getElementById('jenis_fasilitas');
        const itemSelect = document.getElementById('nama_item');
        if (!jenisSelect || !itemSelect) return;

        const jenisVal = jenisSelect.value;
        const oldVal = "{{ old('nama_item') }}";

        itemSelect.innerHTML = '<option value="" disabled selected hidden>— Pilih ' + (jenisVal === 'mobil' ? 'Mobil' : 'Ruangan') + ' —</option>';

        const options = itemOptions[jenisVal] || [];
        options.forEach(opt => {
            const el = document.createElement('option');
            el.value = opt.value;
            el.textContent = opt.text;
            if (oldVal === opt.value) {
                el.selected = true;
            }
            itemSelect.appendChild(el);
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateItemOptions();

        var calendarEl = document.getElementById('calendar');
        if(calendarEl) {
            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'id',
                timeZone: 'local',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                buttonText: {
                    today: 'Hari Ini',
                    month: 'Bulan',
                    week: 'Minggu',
                    day: 'Hari'
                },
                events: dataSemua,
                eventTimeFormat: { 
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false
                }
            });
            calendar.render();
        }
    });

    // Switch Tab Utama (Kalender vs Daftar) di dalam kartu
    function switchCustomerViewTab(view) {
        const calView = document.getElementById('customer-view-calendar');
        const listView = document.getElementById('customer-view-list');
        const btnCal = document.getElementById('customer-tab-calendar');
        const btnList = document.getElementById('customer-tab-list');
        const titleEl = document.getElementById('customer-card-title');
        const subTitleEl = document.getElementById('customer-card-subtitle');

        if (view === 'calendar') {
            calView.classList.remove('hidden');
            calView.classList.add('block');
            listView.classList.remove('block');
            listView.classList.add('hidden');

            if (titleEl) titleEl.textContent = 'Kalender Kesibukan Jadwal Fasilitas';
            if (subTitleEl) subTitleEl.textContent = 'Filter dan cek jadwal terisi sebelum mengajukan booking';

            btnCal.className = "flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all shadow-sm bg-white text-bps-blue-dark cursor-pointer";
            btnList.className = "flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all text-gray-500 hover:text-gray-800 cursor-pointer";

            if (calendar) {
                setTimeout(() => { calendar.updateSize(); }, 50);
            }
        } else {
            listView.classList.remove('hidden');
            listView.classList.add('block');
            calView.classList.remove('block');
            calView.classList.add('hidden');

            if (titleEl) titleEl.textContent = 'Riwayat Peminjaman Anda';
            if (subTitleEl) subTitleEl.textContent = 'Daftar status seluruh pengajuan peminjaman fasilitas Anda';

            btnList.className = "flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all shadow-sm bg-white text-bps-blue-dark cursor-pointer";
            btnCal.className = "flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all text-gray-500 hover:text-gray-800 cursor-pointer";
        }
    }

    // Filter Kalender Customer (Semua, Mobil, Ruang)
    function switchCalendarFilter(type) {
        if (!calendar) return;
        calendar.removeAllEvents();

        if (type === 'mobil') {
            calendar.addEventSource(dataMobil);
        } else if (type === 'ruang') {
            calendar.addEventSource(dataRuang);
        } else {
            calendar.addEventSource(dataSemua);
        }

        ['all', 'mobil', 'ruang'].forEach(tab => {
            const btn = document.getElementById(`cust-btn-${tab}`);
            if (btn) {
                if (tab === type) {
                    btn.className = "px-3 py-1 text-xs font-bold rounded-lg transition shadow-sm cursor-pointer bg-white text-bps-blue-dark";
                } else {
                    btn.className = "px-3 py-1 text-xs font-bold rounded-lg transition cursor-pointer text-gray-500 hover:text-gray-700";
                }
            }
        });
    }
</script>

<style>
    .fc { font-family: inherit; }
    .fc .fc-button-primary { background-color: #043264; border-color: #043264; font-size: 11px; font-weight: 600; text-transform: capitalize; border-radius: 8px; padding: 6px 12px; }
    .fc .fc-button-primary:hover { background-color: #05417c; border-color: #05417c; }
    .fc-event { border-radius: 6px; padding: 2px 4px; font-size: 0.75rem; border: none !important; }
    .fc .fc-toolbar-title { font-size: 16px; font-weight: 700; color: #043264; text-transform: capitalize; }
</style>
@endsection