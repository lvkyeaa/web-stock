<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    // Sambutan + pintasan ke Katalog Persediaan & Peminjaman Fasilitas (tanpa statistik)
    public function index()
    {
        return view('customer.dashboard');
    }
}
