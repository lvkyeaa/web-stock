<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

// Template file impor persediaan: judul kolom sesuai yang dibaca job ImportPersediaan + satu baris contoh
class TemplateImportPersediaan implements FromArray, WithHeadings, ShouldAutoSize
{
    public function headings(): array
    {
        return ['nama_barang', 'satuan', 'jumlah'];
    }

    public function array(): array
    {
        return [
            ['AMPLOP COKLAT KECIL', 'BUAH', 100],
        ];
    }
}
