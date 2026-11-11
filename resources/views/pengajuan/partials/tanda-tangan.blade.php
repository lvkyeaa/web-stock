{{-- Kotak tanda tangan (kanvas, mouse/sentuh/pena). value = data URL PNG, '' jika kosong.
     $atribut: atribut tambahan, mis. 'x-model="tandaTangan"' (ambil nilainya dari Alpine induk) atau 'id="..."'
     (dari luar Alpine: Alpine.$data(el).value / .hapus()). --}}
<div x-data="padTandaTangan()" x-modelable="value" {!! $atribut ?? '' !!}>
    <div class="relative rounded-xl border border-dashed border-gray-300 bg-white">
        <canvas x-ref="kanvas" class="block w-full h-40 touch-none cursor-crosshair" aria-label="Kotak tanda tangan"
            @pointerdown="mulai($event)" @pointermove="gores($event)" @pointerup="selesai()" @pointerleave="selesai()"></canvas>
        <span x-show="!value" class="pointer-events-none absolute inset-0 flex items-center justify-center text-xs text-gray-400">Tanda tangan di sini</span>
    </div>
    <button type="button" @click="hapus()" x-show="value" x-cloak
        class="mt-1.5 text-xs font-semibold text-gray-500 hover:text-gray-700 underline cursor-pointer">Hapus tanda tangan</button>
</div>

@once
<script>
    function padTandaTangan() {
        let menggores = false, adaGoresan = false;

        return {
            value: '',

            // Ukuran kanvas mengikuti lebarnya di layar (bisa 0 saat dialog masih tersembunyi), jadi disiapkan saat mulai menggores
            siapkan() {
                const kanvas = this.$refs.kanvas;
                const rasio = window.devicePixelRatio || 1;
                const lebar = Math.round(kanvas.clientWidth * rasio), tinggi = Math.round(kanvas.clientHeight * rasio);
                if (kanvas.width === lebar && kanvas.height === tinggi) return;

                kanvas.width = lebar;
                kanvas.height = tinggi;
                const ctx = kanvas.getContext('2d');
                ctx.scale(rasio, rasio);
                ctx.lineWidth = 2;
                ctx.lineCap = ctx.lineJoin = 'round';
                ctx.strokeStyle = '#111827';
            },

            mulai(e) {
                if (!this.value) this.siapkan();
                menggores = true;
                adaGoresan = false;
                this.$refs.kanvas.setPointerCapture(e.pointerId);
                const ctx = this.$refs.kanvas.getContext('2d');
                ctx.beginPath();
                ctx.moveTo(e.offsetX, e.offsetY);
            },

            gores(e) {
                if (!menggores) return;
                const ctx = this.$refs.kanvas.getContext('2d');
                ctx.lineTo(e.offsetX, e.offsetY);
                ctx.stroke();
                adaGoresan = true;
            },

            selesai() {
                if (!menggores) return;
                menggores = false;
                if (adaGoresan) this.value = this.$refs.kanvas.toDataURL('image/png'); // ketukan tanpa goresan tidak dihitung
            },

            hapus() {
                const kanvas = this.$refs.kanvas;
                kanvas.getContext('2d').clearRect(0, 0, kanvas.width, kanvas.height);
                this.value = '';
            },
        };
    }
</script>
@endonce
