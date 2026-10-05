{{-- Dialog yang dibuka/ditutup lewat kelas "hidden" dan diberi atribut data-dialog:
     tutup saat klik area gelap di luar kotak dialog, atau tekan Esc --}}
<script>
    (() => {
        // Hanya tutup jika tekan & lepas tombol mouse sama-sama di area gelap, agar memilih teks di kolom isian
        // lalu menyeret kursor keluar kotak dialog tidak ikut menutupnya
        let ditekanDi = null;
        document.addEventListener('mousedown', (e) => { ditekanDi = e.target; });

        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-dialog]') && ditekanDi === e.target) {
                e.target.classList.add('hidden');
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key !== 'Escape') return;
            document.querySelectorAll('[data-dialog]:not(.hidden)').forEach((el) => el.classList.add('hidden'));
        });
    })();
</script>
