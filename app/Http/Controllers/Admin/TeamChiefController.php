<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\TeamChief;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// Kelola ketua tim/penanggung jawab (tabel team_chief). Daftar, tambah, dan hapus lewat JSON tanpa reload halaman
class TeamChiefController extends Controller
{
    // Hanya kerangka halaman; daftar diambil lewat data() (JSON). Kondisi awal dari query string (tetap saat reload)
    public function index(Request $request)
    {
        return view('admin.ketua-tim.index', ['initial' => [
            'team_id'    => (string) $request->team_id,
            'q'          => mb_substr(trim((string) $request->q), 0, 100),
            'page'       => max(1, (int) $request->page),
            'teams'      => Team::orderBy('name')->get(['id', 'name']),
            'users'      => User::orderBy('name')->get(['id', 'name', 'username']),
            'dataUrl'    => route('admin.manajemen-user.ketua-tim.data'),
            'storeUrl'   => route('admin.manajemen-user.ketua-tim.store'),
            'destroyUrl' => route('admin.manajemen-user.ketua-tim.destroy', ':id'),
            'csrf'       => csrf_token(),
        ]]);
    }

    // API JSON: daftar ketua tim per halaman, dengan filter tim & pencarian nama tim / nama / username ketua
    public function data(Request $request)
    {
        $request->validate([
            'team_id' => 'nullable|string',
            'q'       => 'nullable|string|max:100',
            'page'    => 'nullable|integer|min:1',
        ]);

        $chiefs = TeamChief::query()
            ->join('teams', 'teams.id', '=', 'team_chief.team_id')
            ->join('users', 'users.id', '=', 'team_chief.user_id')
            ->when($request->filled('team_id'), fn ($query) => $query->where('team_chief.team_id', $request->team_id))
            ->when($request->filled('q'), function ($query) use ($request) {
                $kata = '%' . trim($request->q) . '%';
                $query->where(fn ($w) => $w->where('teams.name', 'like', $kata)
                    ->orWhere('teams.short_name', 'like', $kata)
                    ->orWhere('users.name', 'like', $kata)
                    ->orWhere('users.username', 'like', $kata));
            })
            ->orderBy('teams.name')
            ->orderBy('users.name')
            ->select('team_chief.id', 'teams.name as team_name', 'teams.short_name as team_short_name', 'users.name as user_name', 'users.username')
            ->paginate(10);

        return response()->json([
            'data' => $chiefs->getCollection()->map(fn (TeamChief $chief) => [
                'id'              => $chief->id,
                'team_name'       => $chief->team_name,
                'team_short_name' => $chief->team_short_name,
                'user_name'       => $chief->user_name,
                'username'        => $chief->username,
            ]),
            'meta' => [
                'current_page' => $chiefs->currentPage(),
                'last_page'    => $chiefs->lastPage(),
                'from'         => $chiefs->firstItem(),
                'to'           => $chiefs->lastItem(),
                'total'        => $chiefs->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'team_id' => 'required|exists:teams,id',
            'user_id' => [
                'required',
                'exists:users,id',
                Rule::unique('team_chief')->where('team_id', $request->team_id),
            ],
        ], [
            'team_id.required' => 'Pilih tim terlebih dahulu.',
            'user_id.required' => 'Pilih pegawai terlebih dahulu.',
            'user_id.unique'   => 'Pegawai ini sudah menjadi ketua tim tersebut.',
        ]);

        $chief = TeamChief::create($request->only(['team_id', 'user_id']));

        return response()->json([
            'message' => "{$chief->user->name} ditambahkan sebagai ketua tim {$chief->team->name}.",
        ]);
    }

    // Pengajuan lama tetap menyimpan penanggung jawabnya (orders.person_responsible_user_id merujuk ke users, bukan ke tabel ini)
    public function destroy(TeamChief $teamChief)
    {
        $teamChief->delete();

        return response()->json([
            'message' => "{$teamChief->user->name} tidak lagi menjadi ketua tim {$teamChief->team->name}.",
        ]);
    }
}
