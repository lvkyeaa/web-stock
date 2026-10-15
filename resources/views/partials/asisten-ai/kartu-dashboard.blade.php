{{-- Kartu Asisten AI di dashboard (admin & pengguna) --}}
<div class="relative overflow-hidden rounded-3xl border border-bps-orange/20 bg-gradient-to-br from-bps-orange/5 via-white to-bps-blue/5 p-5 sm:p-6 shadow-sm">
    <div class="mb-4 flex items-center gap-3">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-bps-orange to-bps-blue text-white shadow-md">
            @include('partials.asisten-ai.ikon', ['class' => 'h-5 w-5'])
        </div>
        <div>
            <h3 class="flex items-center gap-2 text-base font-bold tracking-tight text-slate-900">
                Asisten AI
                <span class="rounded-full bg-bps-orange px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-white">Baru</span>
            </h3>
            <p class="text-xs text-slate-500">Pinjam fasilitas atau ajukan persediaan cukup dengan mengetik, seperti mengobrol.</p>
        </div>
    </div>

    @include('partials.asisten-ai.kotak', [
        'konteks' => 'dashboard',
        'placeholder' => 'Ceritakan kebutuhan Anda, misalnya: "Pinjam ruang rapat besok jam 9 sampai 11 untuk rapat tim"',
        'contoh' => [
            'Pinjam mobil dinas Senin depan ke Malang',
            'Pesan Zoom kantor Jumat jam 13.00',
            'Minta 2 rim kertas A4 dan 1 kotak pulpen',
        ],
    ])
</div>
