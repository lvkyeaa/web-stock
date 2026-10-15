{{-- Kotak ketik Asisten AI (pinjam fasilitas / ajukan persediaan dengan bahasa sehari-hari).
     $konteks: dashboard | peminjaman | persediaan (jenis permintaan yang dilayani, lihat AIController).
     $contoh: daftar contoh kalimat (chip yang bisa diklik). $placeholder: petunjuk di kolom ketik.
     Berhasil: pindah ke formulir peminjaman / halaman rekomendasi persediaan. Ditolak: pesan tampil di sini. --}}
<form x-data="asistenAi(@js([
        'konteks' => $konteks,
        'url'     => route('asisten-ai.process'),
        'csrf'    => csrf_token(),
    ]))" @submit.prevent="kirim()" class="space-y-3">
    <div class="relative rounded-2xl border border-slate-200 bg-white shadow-sm transition focus-within:border-bps-blue focus-within:ring-4 focus-within:ring-bps-blue/10">
        <textarea x-model="teks" x-ref="teks" rows="2" maxlength="500" :disabled="memuat"
            @keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); $el.form.requestSubmit() }"
            placeholder="{{ $placeholder }}"
            aria-label="Ketik permintaan untuk Asisten AI"
            class="block w-full resize-none rounded-2xl bg-transparent px-4 pt-3.5 pb-12 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none"></textarea>

        <div class="absolute inset-x-3 bottom-2.5 flex items-center justify-between gap-2">
            <span class="text-[11px] text-slate-400 hidden sm:inline">Enter untuk kirim · Shift+Enter baris baru</span>
            <button type="submit" :disabled="!teks.trim() || memuat"
                class="ml-auto inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-bps-orange to-bps-blue px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:brightness-110 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer">
                <span x-text="memuat ? 'Memproses…' : 'Kirim'"></span>
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M12 5l7 7-7 7" />
                </svg>
            </button>
        </div>
    </div>

    {{-- Contoh kalimat: klik untuk mengisi kolom --}}
    <div class="flex flex-wrap items-center gap-2">
        <span class="text-[11px] font-semibold text-slate-400">Coba:</span>
        @foreach ($contoh as $c)
            <button type="button" @click="teks = @js($c); reset(); $refs.teks.focus()"
                class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs text-slate-600 transition hover:border-bps-orange hover:bg-bps-orange/5 hover:text-bps-orange cursor-pointer">
                {{ $c }}
            </button>
        @endforeach
    </div>

    {{-- Ditolak / gagal: pesan + barang yang tidak dapat dimasukkan --}}
    <div x-show="pesan" x-cloak role="alert" class="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div>
            <p x-text="pesan"></p>
            <ul x-show="catatan.length" class="mt-1 list-disc pl-4">
                <template x-for="c in catatan"><li x-text="c"></li></template>
            </ul>
        </div>
    </div>
</form>

@once
<script>
    function asistenAi(config) {
        return {
            teks: '',
            memuat: false,
            pesan: '',      // penolakan / galat
            catatan: [],    // barang yang tidak dapat dimasukkan

            reset() {
                this.pesan = '';
                this.catatan = [];
            },

            kirim() {
                if (this.teks.trim()) this.minta(config.url, { message: this.teks, context: config.konteks });
            },

            async minta(url, body) {
                this.memuat = true;
                this.reset();
                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.csrf },
                        body: JSON.stringify(body),
                    });
                    const json = await response.json().catch(() => ({}));

                    if (json.redirect) {
                        window.location.href = json.redirect;
                        return; // tetap memuat sampai halaman berpindah
                    }

                    if (response.status === 429) {
                        this.pesan = 'Terlalu banyak permintaan. Tunggu sebentar lalu coba lagi.';
                    } else {
                        this.pesan = json.message || 'Asisten AI sedang tidak dapat memproses permintaan. Silakan coba lagi.';
                        this.catatan = json.notes || [];
                    }
                } catch (e) {
                    this.pesan = 'Gagal terhubung ke server. Periksa koneksi lalu coba lagi.';
                }
                this.memuat = false;
            },
        };
    }
</script>
@endonce
