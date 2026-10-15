<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\Peminjaman;
use App\Models\User;
use App\Services\AsistenAi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// AI assistant: user sentence → booking form (prefilled, not submitted) or supplies cart.
// The AI result is not trusted as is: intent, ids, and stock are re-checked here.
class AIController extends Controller
{
    // Intents served by each AI assistant menu (context)
    private const INTENTS = [
        'dashboard'  => ['peminjaman', 'persediaan'],
        'peminjaman' => ['peminjaman'],
        'persediaan' => ['persediaan'],
    ];

    private const SESSION_PLAN = 'ai_assistant.supplies_plan'; // supplies recommendation waiting for the user's selection

    public function process(Request $request, AsistenAi $assistant)
    {
        $request->validate([
            'message' => 'required|string|max:500',
            'context' => 'required|in:' . implode(',', array_keys(self::INTENTS)),
        ]);

        $intents = self::INTENTS[$request->context];
        $result = $assistant->tafsirkan(trim($request->message), $intents);

        if (!$result) {
            return response()->json(['message' => 'Asisten AI sedang tidak dapat memproses permintaan. Silakan coba lagi atau gunakan formulir biasa.'], 503);
        }

        // Rejection messages come from the app, never from AI-generated text
        if ($result['intent'] === 'tolak') {
            return $this->reject('Maaf, Asisten AI hanya dapat membantu ' . match ($request->context) {
                'peminjaman' => 'peminjaman fasilitas.',
                'persediaan' => 'pengajuan persediaan.',
                default      => 'peminjaman fasilitas dan pengajuan persediaan.',
            });
        }
        if (!in_array($result['intent'], $intents, true)) {
            return $this->reject($result['intent'] === 'persediaan'
                ? 'Pengajuan persediaan tidak dapat dibuat di sini. Gunakan Asisten AI di halaman Katalog Persediaan atau Dashboard.'
                : 'Peminjaman fasilitas tidak dapat dibuat di sini. Gunakan Asisten AI di halaman Peminjaman atau Dashboard.');
        }

        return $result['intent'] === 'peminjaman'
            ? $this->fillBookingForm((array) ($result['peminjaman'] ?? []))
            : $this->prepareSupplies((array) ($result['persediaan'] ?? []), trim($request->message));
    }

    // Supplies recommendation page (from the AI assistant on the dashboard or catalog): related items per request,
    // the user ticks what they want. The plan is stored in the session by process(), so the AI is not called again
    public function supplies(Request $request)
    {
        $plan = $request->session()->get(self::SESSION_PLAN);

        if (!$plan) {
            return redirect()->route('barang.katalog')->with('error', 'Belum ada rekomendasi persediaan. Tulis permintaan Anda lewat Asisten AI.');
        }

        // Stock may have changed since process(): only show items that are still available
        $barangs = Barang::withDipesan()->findMany(array_merge(...array_column($plan['groups'], 'options')))->keyBy('id');

        $groups = collect($plan['groups'])->map(fn($group, $index) => [
            'index'    => $index,
            'request'  => $group['request'],
            'options'  => collect($group['options'])
                ->filter(fn($id) => $barangs->has($id) && $barangs[$id]->tersedia > 0)
                ->map(fn($id) => [
                    'id'        => $id,
                    'name'      => $barangs[$id]->nama_barang,
                    'unit'      => $barangs[$id]->satuan,
                    'available' => $barangs[$id]->tersedia,
                    'quantity'  => min($group['quantity'], $barangs[$id]->tersedia), // capped at available stock
                    'capped'    => $group['quantity'] > $barangs[$id]->tersedia,
                    'checked'   => $id === $group['exact'],
                ])->values(),
        ]);

        return view('asisten-ai.persediaan', [
            'message'   => $plan['message'],
            'groups'    => $groups->filter(fn($group) => $group['options']->isNotEmpty())->values(),
            'notFound'  => $groups->filter(fn($group) => $group['options']->isEmpty())->pluck('request')->concat($plan['not_found'])->values(),
            'cartCount' => $request->user()->jumlahKeranjang(),
        ]);
    }

    // Selected items replace the cart, then go to the cart page
    public function apply(Request $request)
    {
        $request->validate(['selected' => 'nullable|array', 'selected.*' => 'array', 'selected.*.*' => 'string']);

        $plan = $request->session()->get(self::SESSION_PLAN);

        if (!$plan) {
            return redirect()->route('barang.katalog')->with('error', 'Rekomendasi sudah kedaluwarsa. Silakan kirim ulang permintaan Anda lewat Asisten AI.');
        }

        // Only items offered for that request are accepted; each gets the request's quantity
        $items = [];
        foreach ($plan['groups'] as $index => $group) {
            foreach (array_intersect($group['options'], (array) $request->input("selected.$index", [])) as $id) {
                $items[$id] = ($items[$id] ?? 0) + $group['quantity'];
            }
        }

        if (!$items) {
            return redirect()->route('asisten-ai.supplies')->with('error', 'Pilih minimal satu persediaan.');
        }

        $this->fillCart($request->user(), $items);
        $request->session()->forget(self::SESSION_PLAN);

        return redirect()->route('keranjang.index');
    }

    // Booking: the form on the Create Booking page is filled via old input (old()); the user reviews and submits it,
    // so validation & conflict checks still go through BookingController::store
    private function fillBookingForm(array $data)
    {
        $facility = Facility::with('facilityType')->find(is_string($data['facility_id'] ?? null) ? $data['facility_id'] : null);
        $type = FacilityType::where('code', is_string($data['type'] ?? null) ? $data['type'] : null)->first();

        // The facility must match the requested type; with no type, take it from the facility
        if ($facility && $type && $facility->facility_type_id !== $type->id) {
            $facility = null;
        }
        $typeCode = $type?->code ?? $facility?->facilityType->code;

        // Jumlah peserta boleh berupa angka atau teks angka ("20"); di luar 1–batas kolom diabaikan
        $jumlahPeserta = filter_var($data['jumlah_peserta'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => Peminjaman::MAKS_PESERTA],
        ]);

        $dateTime = fn($value) => is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value) ? $value : null;

        session()->flashInput(array_filter([
            'jenis'               => $typeCode,
            'facility_request_id' => $facility?->id,
            'waktu_mulai'         => $dateTime($data['waktu_mulai'] ?? null),
            'waktu_selesai'       => $dateTime($data['waktu_selesai'] ?? null),
            'keperluan'           => is_string($data['keperluan'] ?? null) ? mb_substr(trim($data['keperluan']), 0, 1000) : null,
            // Detail ruang rapat; kapasitas dicek saat formulir dikirim (BookingController)
            'jumlah_peserta'      => $typeCode === 'room' && $jumlahPeserta !== false ? $jumlahPeserta : null,
            'butuh_sound_system'  => $typeCode === 'room' && ($data['butuh_sound_system'] ?? null) === true ? '1' : null,
            'butuh_konsumsi'      => $typeCode === 'room' && ($data['butuh_konsumsi'] ?? null) === true ? '1' : null,
        ]));
        session()->flash('ai_filled', true);

        return response()->json(['redirect' => route('peminjaman.create')]);
    }

    // Supplies: keep only available items the AI offered, store the plan, and open the recommendation page
    private function prepareSupplies(array $data, string $message)
    {
        $requests = collect(array_slice((array) ($data['permintaan'] ?? []), 0, 10))->filter(fn($item) => is_array($item));

        $offered = $requests->flatMap(fn($item) => array_filter((array) ($item['pilihan'] ?? []), 'is_string'))->unique()->values()->all();
        $available = Barang::whereTersedia()->whereIn('id', $offered)->pluck('id')->all();

        $groups = [];
        $notFound = [];
        foreach ($requests as $item) {
            $requestText = $this->text($item['teks'] ?? null);
            if (!$requestText) {
                continue;
            }

            // AI order (best match first), at most 5, available items only
            $options = collect((array) ($item['pilihan'] ?? []))
                ->filter(fn($id) => is_string($id) && in_array($id, $available, true))
                ->unique()->take(5)->values()->all();

            if (!$options) {
                $notFound[] = $requestText;
                continue;
            }

            $groups[] = [
                'request'  => $requestText,
                'quantity' => $this->quantity($item['jumlah'] ?? null) ?? 1, // no number given → 1
                'options'  => $options,
                'exact'    => in_array($item['tepat'] ?? null, $options, true) ? $item['tepat'] : null, // pre-ticked
            ];
        }

        if (!$groups) {
            return $this->reject(
                'Tidak ada persediaan tersedia yang cocok dengan permintaan Anda.',
                array_map(fn($name) => "{$name} (tidak ditemukan)", $notFound),
            );
        }

        session()->put(self::SESSION_PLAN, ['message' => $message, 'groups' => $groups, 'not_found' => $notFound]);

        return response()->json(['redirect' => route('asisten-ai.supplies')]);
    }

    // Empty the cart, then fill it with the selected items; stock is re-checked and quantities capped at what is available
    private function fillCart(User $user, array $items): void
    {
        $notes = DB::transaction(function () use ($user, $items) {
            // Lock the user row, same as CartController::store
            User::whereKey($user->id)->lockForUpdate()->first();

            $user->cartItems()->delete();

            $notes = [];
            foreach (Barang::withDipesan()->findMany(array_keys($items)) as $barang) {
                $available = max(0, $barang->tersedia);
                if ($available > 0) {
                    $user->cartItems()->create(['barang_id' => $barang->id, 'jumlah' => min($items[$barang->id], $available)]);
                }
                if ($items[$barang->id] > $available) {
                    $notes[] = $available > 0
                        ? "{$barang->nama_barang} (hanya tersedia {$available} {$barang->satuan})"
                        : "{$barang->nama_barang} (stok habis)";
                }
            }

            return $notes;
        });

        session()->flash('success', 'Keranjang diisi oleh Asisten AI. Periksa kembali sebelum mengirim pengajuan.');
        if ($notes) {
            session()->flash('error', 'Perlu diperhatikan: ' . implode('; ', $notes) . '.');
        }
    }

    // Integer >= 1 from the AI result, otherwise null
    private function quantity($value): ?int
    {
        return filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
    }

    // Short text from the AI result (request name), empty → null
    private function text($value): ?string
    {
        return is_string($value) && trim($value) !== '' ? mb_substr(trim($value), 0, 100) : null;
    }

    private function reject(string $message, array $notes = [])
    {
        return response()->json(['message' => $message, 'notes' => $notes], 422);
    }
}
