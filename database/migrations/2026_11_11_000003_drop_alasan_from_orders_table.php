<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

// Alasan penolakan kini di order_approvals.catatan. Alasan pengajuan lama yang ditolak admin dipindahkan ke sana
// (admin diambil dari riwayat penolakannya) sebelum kolom orders.alasan dihapus.
return new class extends Migration
{
    public function up(): void
    {
        $ditolak = DB::table('orders')
            ->join('riwayat', fn ($join) => $join->on('riwayat.order_id', '=', 'orders.id')->where('riwayat.status_sesudah', 'ditolak'))
            ->where('orders.status', 'ditolak')
            ->whereNotNull('orders.alasan')
            ->whereNotExists(fn ($q) => $q->from('order_approvals')->whereColumn('order_approvals.order_id', 'orders.id'))
            ->get(['orders.id', 'orders.alasan', 'riwayat.actor_id', 'riwayat.created_at']);

        foreach ($ditolak->unique('id') as $order) {
            DB::table('order_approvals')->insert([
                'id' => (string) Str::uuid(), 'order_id' => $order->id, 'level' => 'admin', 'user_id' => $order->actor_id,
                'keputusan' => 'ditolak', 'catatan' => $order->alasan, 'created_at' => $order->created_at, 'updated_at' => $order->created_at,
            ]);
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('alasan');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('alasan')->nullable()->after('status');
        });

        foreach (DB::table('order_approvals')->where('keputusan', 'ditolak')->get(['order_id', 'catatan']) as $penolakan) {
            DB::table('orders')->where('id', $penolakan->order_id)->update(['alasan' => $penolakan->catatan]);
        }
    }
};
