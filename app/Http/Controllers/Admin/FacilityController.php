<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\FacilityType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// Kelola fasilitas yang bisa dipinjam (mobil, ruang rapat, Zoom, ...). Daftar, tambah, ubah, hapus lewat JSON tanpa reload halaman.
// Kapasitas hanya untuk ruang rapat; jenis lain selalu disimpan kosong.
class FacilityController extends Controller
{
    // Hanya kerangka halaman; daftar diambil lewat data() (JSON). Kondisi awal dari query string (tetap saat reload)
    public function index(Request $request)
    {
        return view('admin.fasilitas.index', ['initial' => [
            'type_id'    => (string) $request->type_id,
            'page'       => max(1, (int) $request->page),
            'types'      => FacilityType::orderBy('name')->get(['id', 'code', 'name', 'icon', 'color']),
            'dataUrl'    => route('admin.fasilitas.data'),
            'storeUrl'   => route('admin.fasilitas.store'),
            'updateUrl'  => route('admin.fasilitas.update', ':id'),
            'destroyUrl' => route('admin.fasilitas.destroy', ':id'),
            'csrf'       => csrf_token(),
        ]]);
    }

    // API JSON: daftar fasilitas per halaman (urut per jenis, lalu nama), dengan filter jenis
    public function data(Request $request)
    {
        $request->validate([
            'type_id' => 'nullable|string',
            'page'    => 'nullable|integer|min:1',
        ]);

        $facilities = Facility::query()
            ->join('facility_types', 'facility_types.id', '=', 'facilities.facility_type_id')
            ->when($request->filled('type_id'), fn ($query) => $query->where('facilities.facility_type_id', $request->type_id))
            ->select('facilities.*') // sebelum withCount, agar kolom jumlah tidak tertimpa
            ->with('facilityType')
            ->withCount([
                'permintaanPeminjaman as jumlah_diminta' => fn ($query) => $query->withTrashed(),
                'peminjaman as jumlah_diberikan' => fn ($query) => $query->withTrashed(),
            ])
            ->orderBy('facility_types.name')
            ->orderBy('facilities.name')
            ->paginate(10);

        return response()->json([
            'data' => $facilities->getCollection()->map(fn (Facility $facility) => $this->item($facility, $facility->jumlah_diminta + $facility->jumlah_diberikan > 0)),
            'meta' => [
                'current_page' => $facilities->currentPage(),
                'last_page'    => $facilities->lastPage(),
                'from'         => $facilities->firstItem(),
                'to'           => $facilities->lastItem(),
                'total'        => $facilities->total(),
                // Jumlah per jenis untuk tombol filter ('' = semua)
                'counts'       => Facility::selectRaw('facility_type_id, count(*) as jumlah')->groupBy('facility_type_id')->pluck('jumlah', 'facility_type_id')
                    ->pipe(fn ($counts) => $counts->put('', $counts->sum())),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $facility = Facility::create($this->validasi($request));

        return response()->json([
            'message' => "Fasilitas {$facility->name} berhasil ditambahkan.",
            'data'    => $this->item($facility, false),
        ], 201);
    }

    public function update(Request $request, Facility $facility)
    {
        $data = $this->validasi($request, $facility);

        // Fasilitas yang diminta & diberikan harus sejenis, jadi jenis tidak boleh diganti setelah dipakai peminjaman
        if ($data['facility_type_id'] !== $facility->facility_type_id && $facility->pernahDipinjam()) {
            return response()->json([
                'message' => 'Jenis fasilitas tidak dapat diubah karena fasilitas ini sudah dipakai peminjaman.',
                'errors'  => ['facility_type_id' => ['Jenis fasilitas tidak dapat diubah karena fasilitas ini sudah dipakai peminjaman.']],
            ], 422);
        }

        $facility->update($data);

        return response()->json([
            'message' => "Fasilitas {$facility->name} berhasil diperbarui.",
            'data'    => $this->item($facility->load('facilityType'), $facility->pernahDipinjam()), // jenis bisa saja berubah
        ]);
    }

    // Hapus fasilitas di database ikut menghapus peminjamannya (cascade), jadi hanya fasilitas yang belum pernah dipinjam
    public function destroy(Facility $facility)
    {
        if ($facility->pernahDipinjam()) {
            return response()->json(['message' => "{$facility->name} tidak dapat dihapus karena sudah dipakai peminjaman."], 422);
        }

        $facility->delete();

        return response()->json([
            'message' => "Fasilitas {$facility->name} berhasil dihapus.",
            'data'    => ['id' => $facility->id],
        ]);
    }

    // Satu baris fasilitas untuk daftar (format sama untuk data, tambah, dan ubah)
    private function item(Facility $facility, bool $dipakai): array
    {
        return [
            'id'               => $facility->id,
            'name'             => $facility->name,
            'capacity'         => $facility->capacity,
            'facility_type_id' => $facility->facility_type_id,
            'type'             => $facility->facilityType->only(['code', 'name', 'icon', 'color']),
            'dipakai'          => $dipakai, // sudah dipakai peminjaman: tidak bisa dihapus / diganti jenisnya
        ];
    }

    private function validasi(Request $request, ?Facility $facility = null): array
    {
        $isRuang = FacilityType::whereKey($request->facility_type_id)->value('code') === 'room';

        $data = $request->validate([
            'facility_type_id' => 'required|exists:facility_types,id',
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('facilities')->where('facility_type_id', $request->facility_type_id)->ignore($facility),
            ],
            'capacity' => $isRuang ? 'required|integer|min:1|max:65535' : 'nullable',
        ], [
            'facility_type_id.required' => 'Pilih jenis fasilitas.',
            'name.required'   => 'Nama fasilitas wajib diisi.',
            'name.unique'     => 'Fasilitas dengan nama ini sudah ada.',
            'capacity.required' => 'Kapasitas ruang rapat wajib diisi.',
            'capacity.min'    => 'Kapasitas minimal 1 orang.',
        ]);

        return [
            'facility_type_id' => $data['facility_type_id'],
            'name' => trim($data['name']),
            'capacity' => $isRuang ? (int) $data['capacity'] : null, // hanya ruang rapat yang punya kapasitas
        ];
    }
}
