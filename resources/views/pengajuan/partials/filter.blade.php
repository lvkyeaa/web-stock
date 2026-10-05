{{-- Filter daftar pengajuan (state dari pengajuanList): cari (debounce) & status. $placeholder: teks petunjuk kolom cari --}}
<form action="{{ url()->current() }}" method="GET" @submit.prevent="search()"
    class="rounded-2xl border border-slate-200/70 bg-white p-3 shadow-sm flex flex-col md:flex-row md:items-center gap-3">
    <div class="flex items-center gap-2 md:flex-1 min-w-0">
        <div class="relative flex-1 min-w-0">
            <input type="text" name="q" x-model="filters.q" @input.debounce.400ms="search()" placeholder="{{ $placeholder }}"
                class="w-full pl-9 pr-9 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-bps-blue">

            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            <button type="button" x-show="filters.q" x-cloak @click="resetSearch()" title="Hapus pencarian" aria-label="Hapus pencarian"
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <button type="submit" class="shrink-0 bg-gradient-to-r from-bps-blue to-bps-blue-dark text-white px-4 py-2 rounded-xl text-xs font-bold transition shadow-sm hover:shadow cursor-pointer uppercase tracking-wider">
            Cari
        </button>
    </div>

    <label class="flex items-center gap-2 text-[11px] font-semibold text-slate-500 whitespace-nowrap">
        Status
        <select x-model="filters.status" @change="search()"
            class="py-2 pl-3 pr-8 rounded-xl border border-slate-200 bg-white text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-bps-blue cursor-pointer">
            <option value="">Semua</option>
            @foreach (\App\Http\Controllers\OrderController::STATUS as $nilai => $label)
                <option value="{{ $nilai }}">{{ $label }}</option>
            @endforeach
        </select>
    </label>
</form>
