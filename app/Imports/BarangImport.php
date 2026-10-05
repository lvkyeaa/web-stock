<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithHeadingRow;

// Baris pertama file = judul kolom (nama_barang, stock/stok/jumlah, satuan); baris dibaca & diproses di job ImportPersediaan
class BarangImport implements WithHeadingRow
{
}
