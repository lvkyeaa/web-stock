<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Storage;

class Barang extends Model
{
    use HasUuids;

    protected $table = 'barang';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    // 💡 1. Tambahkan 'foto' ke dalam $fillable
    protected $fillable = ['stock_id', 'nama_barang', 'stock', 'satuan', 'foto', 'import_status_id'];

    // ─── Identitas nama ───
    // Spasi di awal/akhir dibuang dan spasi berurutan (termasuk non-breaking space dari salinan web/Word) dijadikan satu
    public static function rapikanNama(string $nama): string
    {
        return trim(preg_replace('/[\s\x{00A0}]+/u', ' ', $nama));
    }

    // Barang dengan nama yang sama, tanpa beda huruf besar/kecil & spasi berlebih (dipakai Tambah Manual & impor Excel)
    public static function cariNama(string $nama): ?self
    {
        $kunci = mb_strtolower(self::rapikanNama($nama));

        return self::all()->first(fn (self $barang) => mb_strtolower(self::rapikanNama($barang->nama_barang)) === $kunci);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // ─── Reservasi stok ───
    // stock    = stok fisik, baru dipotong saat admin menyetujui pengajuan
    // dipesan  = jumlah di pengajuan yang masih pending (sudah dijanjikan ke pemohon)
    // tersedia = stock - dipesan, yang boleh diminta customer
    // Dipesan dihitung dari pengajuan (bukan kolom tersendiri), jadi otomatis lepas saat pengajuan disetujui/ditolak.

    // Menambahkan atribut `dipesan` lewat subquery
    public function scopeWithDipesan(Builder $query): void
    {
        $query->withSum(['orderItems as dipesan' => fn ($q) => $q->whereRelation('order', 'status', 'pending')], 'jumlah');
    }

    // Hanya barang yang masih tersedia (stock > dipesan)
    public function scopeWhereTersedia(Builder $query): void
    {
        $query->where('stock', '>', fn (QueryBuilder $sub) => $sub->from('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereColumn('order_items.barang_id', 'barang.id')
            ->where('orders.status', 'pending')
            ->selectRaw('coalesce(sum(order_items.jumlah), 0)'));
    }

    // ─── Popularitas ───
    // Dihitung dari pengajuan yang DISETUJUI saja (jumlah pengajuan, bukan jumlah unit). Satu pengajuan berisi paling
    // banyak satu baris per barang (keranjang unik per barang), jadi jumlah baris order_items = jumlah pengajuan.
    public const POPULER_HARI = 90; // jendela waktu "populer saat ini"

    // Menambahkan atribut `diminta_90_hari` (jendela POPULER_HARI) dan `diminta_total` (sepanjang waktu)
    public function scopeWithPopularitas(Builder $query): void
    {
        $query->withCount([
            'orderItems as diminta_90_hari' => fn ($q) => $q->whereHas('order', fn ($o) => $o
                ->where('status', 'disetujui')
                ->where('created_at', '>=', now()->subDays(self::POPULER_HARI))),
            'orderItems as diminta_total' => fn ($q) => $q->whereRelation('order', 'status', 'disetujui'),
        ]);
    }

    // Butuh withDipesan() pada query yang memuat barang ini
    public function getTersediaAttribute(): int
    {
        return $this->stock - (int) $this->dipesan;
    }

    // 💡 2. Helper otomatis untuk memanggil URL Foto di Blade
    // Cara panggil di Blade nanti tinggal: {{ $barang->foto_url }}
    public function getFotoUrlAttribute()
    {
        if ($this->foto && Storage::disk('public')->exists($this->foto)) {
            return asset('storage/' . $this->foto);
        }

        // Jika barang belum diupload gambarnya, pakai SVG gambar default ini
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->nama_barang) . '&color=F97316&background=FFEDD5&size=256&font-size=0.3';
    }
}