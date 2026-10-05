{{-- Modal "Masukkan ke Keranjang" + notifikasi, dipakai halaman katalog (admin & customer).
     Buka dengan openAjukanModal(id, nama, tersedia, satuan, fotoUrl). --}}
{{-- MODAL MASUKKAN KERANJANG --}}
<div id="modalAjukan" data-dialog class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 backdrop-blur-xs">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg font-bold text-gray-900">Masukkan ke Keranjang</h3>
            <button onclick="document.getElementById('modalAjukan').classList.add('hidden')"
                class="text-gray-400 hover:text-gray-600 cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <form id="formAjukan" method="POST" class="space-y-4" onsubmit="submitAjukan(event)">
            @csrf
            {{-- Detail barang dalam modal --}}
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 flex items-center gap-4">
                <img id="ajukanFoto" src="" class="w-14 h-14 rounded-xl object-cover border border-slate-200 flex-shrink-0 shadow-sm">
                <div class="min-w-0 flex-1">
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Persediaan Dipilih</p>
                    <p id="ajukanNama" class="font-bold text-slate-900 text-sm truncate"></p>
                    <p id="ajukanStock" class="text-xs text-emerald-600 font-semibold mt-0.5"></p>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Jumlah yang Diminta</label>
                <input type="number" id="ajukanJumlah" name="jumlah" min="1" value="1" required
                    class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-bps-blue">
            </div>
            
            <p id="ajukanError" role="alert" class="hidden p-3 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700"></p>

            <div class="flex gap-3 pt-2">
                <button type="button" onclick="document.getElementById('modalAjukan').classList.add('hidden')"
                    class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-slate-50 cursor-pointer">Batal</button>
                <button type="submit" id="ajukanSubmit"
                    class="flex-1 px-4 py-2.5 rounded-xl bg-bps-blue text-white text-sm font-semibold hover:bg-bps-blue-dark transition cursor-pointer shadow-md disabled:opacity-60 disabled:cursor-wait">Masukkan</button>
            </div>
        </form>
    </div>
</div>

{{-- Notifikasi singkat setelah barang masuk keranjang --}}
<div id="toastKeranjang" role="status" aria-live="polite"
    class="hidden fixed bottom-4 right-4 left-4 sm:left-auto sm:max-w-sm z-50 p-4 rounded-xl bg-green-50 border border-green-200 text-green-800 text-sm shadow-lg"></div>

<script>
    let toastTimer;

    function showToast(message) {
        const toast = document.getElementById('toastKeranjang');
        toast.textContent = message;
        toast.classList.remove('hidden');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toast.classList.add('hidden'), 3000);
    }

    // Tambah ke keranjang tanpa reload: badge keranjang (tombol & ikon top bar) diperbarui lewat event
    async function submitAjukan(event) {
        event.preventDefault();
        const form = event.target;
        const submit = document.getElementById('ajukanSubmit');
        const error = document.getElementById('ajukanError');

        submit.disabled = true;
        error.classList.add('hidden');

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: new FormData(form),
            });
            const json = await response.json().catch(() => ({}));

            if (!response.ok) {
                error.textContent = json.errors?.jumlah?.[0] ?? json.message ?? 'Gagal menambahkan persediaan. Silakan coba lagi.';
                error.classList.remove('hidden');
                return;
            }

            document.getElementById('modalAjukan').classList.add('hidden');
            window.dispatchEvent(new CustomEvent('keranjang-diperbarui', { detail: { jumlah: json.jumlah_keranjang } }));
            showToast(json.message);
        } catch (e) {
            error.textContent = 'Gagal menambahkan persediaan. Periksa koneksi lalu coba lagi.';
            error.classList.remove('hidden');
        } finally {
            submit.disabled = false;
        }
    }

    function openAjukanModal(id, nama, stock, satuan, fotoUrl) {
        let url = "{{ route('keranjang.add', ':id') }}";
        url = url.replace(':id', id);
        document.getElementById('formAjukan').action = url;

        document.getElementById('ajukanNama').textContent = nama;
        document.getElementById('ajukanFoto').src = fotoUrl;
        document.getElementById('ajukanStock').textContent = `Stok tersedia: ${stock} ${satuan}`;
        document.getElementById('ajukanJumlah').max = stock;
        document.getElementById('ajukanJumlah').value = 1;
        document.getElementById('ajukanError').classList.add('hidden');
        document.getElementById('modalAjukan').classList.remove('hidden');
    }
</script>
