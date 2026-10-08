@extends(auth()->user()->hasRole('admin') ? 'layouts.admin' : 'layouts.customer')
@section('title', 'Keranjang Persediaan')

@section('content')
{{-- Ubah jumlah, hapus, dan kirim pengajuan lewat fetch (JSON): halaman tidak di-reload --}}
<div class="space-y-6" x-data="keranjangPage(@js([
    'items'     => $items,
    'teams'     => $teams,
    'csrf'      => csrf_token(),
    'updateUrl' => route('keranjang.update', ':id'),
    'deleteUrl' => route('keranjang.delete', ':id'),
    'storeUrl'  => route('pengajuan.store'),
]))">
    <div>
        <h2 class="text-xl font-semibold tracking-tight text-gray-900">Keranjang Persediaan</h2>
        <p class="text-sm text-gray-500 mt-1">Periksa persediaan dan jumlahnya sebelum mengirim pengajuan.</p>
        @role('admin')
            <p class="text-xs text-bps-blue mt-1 font-medium">Pengajuan admin langsung disetujui dan stok langsung dipotong.</p>
        @endrole
    </div>

    {{-- Pesan gagal kirim pengajuan (jika berhasil, halaman pindah ke Pengajuan Saya) --}}
    <div x-show="notice" x-cloak role="alert"
        class="p-4 rounded-xl border text-sm bg-red-50 border-red-200 text-red-800">
        <span x-text="notice?.text"></span>
    </div>

    <div class="space-y-4">
        <div x-show="items.length" class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Nama Persediaan</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Satuan</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider w-40">Jumlah Diminta</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="item in items" :key="item.id">
                            {{-- Jumlah melebihi stok tersedia (stok - yang sudah diajukan): tetap di keranjang, tapi menahan pengiriman --}}
                            <tr class="transition" :class="(bermasalah(item) || item.error) ? 'bg-amber-50/60' : 'hover:bg-gray-50/50'">
                                <td class="px-6 py-4 align-top">
                                    <div class="font-bold text-gray-900" x-text="item.nama_barang"></div>
                                    <div class="text-xs text-gray-400" x-text="`Tersedia: ${item.tersedia} ${item.satuan}`"></div>

                                    {{-- Jumlah yang baru diketik ditolak server --}}
                                    <p x-show="item.error" x-cloak role="alert" class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-red-700 max-w-md">
                                        <svg class="w-4 h-4 flex-shrink-0 text-red-500" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                        <span x-text="item.error"></span>
                                    </p>

                                    {{-- Jumlah tersimpan melebihi stok tersedia --}}
                                    <p x-show="bermasalah(item)" x-cloak role="alert" class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-amber-700 max-w-md">
                                        <svg class="w-4 h-4 flex-shrink-0 text-amber-500" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                        <span>
                                            <template x-if="item.tersedia === 0">
                                                <span>
                                                    <span x-show="item.dipesan > 0">Stok habis, semua sudah diajukan oleh tim lain.</span>
                                                    <span x-show="item.dipesan <= 0">Stok habis.</span>
                                                    Hapus persediaan ini, atau tunggu sampai stok tersedia lagi.
                                                </span>
                                            </template>
                                            <template x-if="item.tersedia > 0">
                                                <span x-text="`Hanya ${item.tersedia} ${item.satuan} tersedia${item.dipesan > 0 ? ' (sisanya sudah diajukan oleh tim lain)' : ''}. Kurangi jumlahnya menjadi ${item.tersedia} atau kurang.`"></span>
                                            </template>
                                        </span>
                                    </p>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap align-top">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800" x-text="item.satuan"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap align-top">
                                    <input type="number" min="1" :max="Math.max(1, item.tersedia)" :value="item.jumlah"
                                        :aria-label="`Jumlah ${item.nama_barang}`" :disabled="item.saving"
                                        @change="ubahJumlah(item, $event.target)"
                                        @keydown.enter.prevent="$event.target.blur()"
                                        class="w-20 px-3 py-1.5 rounded-xl border text-center text-sm focus:outline-none focus:ring-2 disabled:opacity-60"
                                        :class="item.error ? 'border-red-300 focus:ring-red-200 focus:border-red-400' : 'border-gray-200 focus:ring-bps-blue/30 focus:border-bps-blue'">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium align-top">
                                    <button type="button" @click="hapus(item)" :disabled="item.saving"
                                        class="text-red-600 hover:text-red-900 font-semibold cursor-pointer flex items-center gap-1 ml-auto disabled:opacity-50 disabled:cursor-wait">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        Hapus
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            {{-- Tim wajib dipilih; jika tim punya lebih dari satu ketua, pemohon memilih salah satunya --}}
            <div class="px-6 py-4 border-t border-gray-100 grid gap-4 sm:grid-cols-3">
                <div>
                    <span class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Diminta oleh</span>
                    <p class="px-3 py-2 rounded-xl border border-gray-200 bg-gray-50 text-sm text-gray-700">{{ auth()->user()->name }}</p>
                </div>
                <div>
                    <label for="team_id" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Tim <span class="text-red-500">*</span></label>
                    <select id="team_id" x-model="teamId" @change="pilihTim()" required
                        class="w-full px-3 py-2 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-bps-blue/30 focus:border-bps-blue">
                        <option value="">-- Pilih Tim --</option>
                        <template x-for="team in teams" :key="team.id">
                            <option :value="team.id" x-text="team.name"></option>
                        </template>
                    </select>
                </div>
                <div x-show="timTerpilih?.chiefs.length" x-cloak>
                    <label for="person_responsible_user_id" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Ketua Tim/Penanggung Jawab <span class="text-red-500">*</span></label>
                    <select id="person_responsible_user_id" x-model="chiefId" required :disabled="timTerpilih?.chiefs.length === 1"
                        class="w-full px-3 py-2 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-bps-blue/30 focus:border-bps-blue disabled:bg-gray-50 disabled:text-gray-700">
                        <option value="" x-show="timTerpilih?.chiefs.length > 1">-- Pilih Ketua Tim/Penanggung Jawab --</option>
                        <template x-for="chief in timTerpilih?.chiefs ?? []" :key="chief.id">
                            <option :value="chief.id" x-text="chief.name"></option>
                        </template>
                    </select>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 flex flex-col sm:flex-row sm:items-center sm:justify-end gap-3 border-t border-gray-100">
                <p x-show="jumlahBermasalah" x-cloak class="text-xs font-medium text-amber-700 sm:mr-auto"
                    x-text="`Perbaiki ${jumlahBermasalah} item yang ditandai di atas untuk mengirim pengajuan.`"></p>
                <p x-show="!jumlahBermasalah && !siapDikirim" x-cloak class="text-xs font-medium text-gray-500 sm:mr-auto">Pilih tim dan ketua tim/penanggung jawab untuk mengirim pengajuan.</p>
                <button type="button" @click="kirim()" :disabled="jumlahBermasalah > 0 || !siapDikirim || sending || items.some(i => i.saving)"
                    class="inline-flex items-center gap-2 bg-gradient-to-r from-bps-blue to-bps-blue-dark hover:from-bps-blue-dark hover:to-bps-blue text-white px-6 py-3 rounded-xl text-sm font-semibold transition shadow-[0_8px_24px_-12px_rgba(0,61,130,0.8)] cursor-pointer disabled:from-slate-300 disabled:to-slate-300 disabled:shadow-none disabled:cursor-not-allowed">
                    <span x-text="sending ? 'Mengirim...' : '🚀 Kirim Pengajuan Sekarang'"></span>
                </button>
            </div>
        </div>

        <div x-show="!items.length" x-cloak class="text-center py-16 bg-white border border-gray-100 rounded-2xl shadow-sm space-y-4">
            <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto text-gray-400">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
            </div>
            <h4 class="text-base font-bold text-gray-500">Keranjang belanjamu kosong</h4>
            <a href="{{ route('barang.katalog') }}" class="inline-flex items-center justify-center bg-gradient-to-r from-bps-blue to-bps-blue-dark hover:from-bps-blue-dark hover:to-bps-blue text-white px-4 py-2 rounded-xl text-sm font-semibold transition cursor-pointer shadow-[0_8px_24px_-12px_rgba(0,61,130,0.8)]">
                Lihat Katalog Persediaan
            </a>
        </div>
    </div>
</div>

<script>
    function keranjangPage(config) {
        const kirimJson = (url, method, body) => fetch(url, {
            method,
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.csrf },
            body: body ? JSON.stringify(body) : undefined,
        });

        // Badge keranjang di top bar & tombol "Lihat Keranjang" ikut diperbarui
        const perbaruiBadge = (jumlah) => window.dispatchEvent(new CustomEvent('keranjang-diperbarui', { detail: { jumlah } }));

        return {
            items: config.items.map(item => ({ ...item, error: '', saving: false })),
            notice: null,
            sending: false,
            teams: config.teams,
            teamId: '',
            chiefId: '',

            get timTerpilih() {
                return this.teams.find(team => team.id === this.teamId) ?? null;
            },

            // Tim wajib dipilih; ketua wajib dipilih hanya jika tim punya ketua
            get siapDikirim() {
                return !!this.timTerpilih && (!this.timTerpilih.chiefs.length || !!this.chiefId);
            },

            // Satu ketua: langsung terpilih; lebih dari satu: pemohon wajib memilih; tanpa ketua: dikosongkan
            pilihTim() {
                const chiefs = this.timTerpilih?.chiefs ?? [];
                this.chiefId = chiefs.length === 1 ? chiefs[0].id : '';
            },

            bermasalah(item) {
                return item.jumlah > item.tersedia;
            },

            get jumlahBermasalah() {
                return this.items.filter(item => this.bermasalah(item)).length;
            },

            // Ganti data baris dengan data terbaru dari server (stok tersedia bisa berubah karena pengajuan tim lain)
            perbarui(item, data) {
                Object.assign(item, data);
            },

            async ubahJumlah(item, input) {
                const jumlah = parseInt(input.value, 10);
                item.error = '';

                if (!Number.isInteger(jumlah) || jumlah < 1) {
                    item.error = 'Jumlah minimal 1.';
                    input.value = item.jumlah;
                    return;
                }
                if (jumlah === item.jumlah) return;

                item.saving = true;
                try {
                    const response = await kirimJson(config.updateUrl.replace(':id', item.id), 'PATCH', { jumlah });
                    const json = await response.json().catch(() => ({}));

                    if (json.item) this.perbarui(item, json.item);

                    if (!response.ok) {
                        // Jumlah baru tidak tersedia: tampilkan galat di baris ini, jumlah kembali ke yang tersimpan
                        item.error = json.errors?.jumlah?.[0] ?? json.message ?? 'Gagal memperbarui jumlah.';
                        input.value = item.jumlah;
                    }
                } catch (e) {
                    item.error = 'Gagal memperbarui jumlah. Periksa koneksi lalu coba lagi.';
                    input.value = item.jumlah;
                } finally {
                    item.saving = false;
                }
            },

            async hapus(item) {
                item.saving = true;
                item.error = '';
                try {
                    const response = await kirimJson(config.deleteUrl.replace(':id', item.id), 'DELETE');
                    const json = await response.json().catch(() => ({}));
                    if (!response.ok) throw new Error(json.message);

                    this.items = this.items.filter(i => i.id !== item.id);
                    perbaruiBadge(json.jumlah_keranjang);
                } catch (e) {
                    item.error = 'Gagal menghapus. Silakan coba lagi.';
                } finally {
                    item.saving = false;
                }
            },

            async kirim() {
                this.sending = true;
                this.notice = null;
                let pindahHalaman = false;
                try {
                    const response = await kirimJson(config.storeUrl, 'POST', { team_id: this.teamId, person_responsible_user_id: this.chiefId || null });
                    const json = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        // Stok berubah sejak halaman dibuka: perbarui semua baris agar peringatannya tampil
                        if (json.items) {
                            this.items = json.items.map(data => ({ ...data, error: '', saving: false }));
                        }
                        this.notice = { text: json.message ?? 'Gagal mengirim pengajuan. Silakan coba lagi.' };
                        return;
                    }

                    // Berhasil: pindah ke halaman Pengajuan Saya (pesan sukses tampil di sana lewat flash session).
                    // Tombol tetap nonaktif selama pindah halaman agar tidak terkirim dua kali.
                    pindahHalaman = true;
                    window.location.href = json.url;
                } catch (e) {
                    this.notice = { text: 'Gagal mengirim pengajuan. Periksa koneksi lalu coba lagi.' };
                } finally {
                    if (!pindahHalaman) this.sending = false;
                }
            },
        };
    }
</script>
@endsection
