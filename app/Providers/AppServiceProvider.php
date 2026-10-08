<?php

namespace App\Providers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Format tanggal tampilan di seluruh aplikasi (nama bulan mengikuti locale aplikasi, APP_LOCALE=id):
        // $tanggal->tanggal()    → 1 Januari 2026
        // $tanggal->tanggalJam() → 1 Januari 2026, 10:00
        Carbon::macro('tanggal', fn () => $this->translatedFormat('j F Y'));
        Carbon::macro('tanggalJam', fn () => $this->translatedFormat('j F Y, H:i'));

        // Driver Socialite 'keycloak' untuk login Majapahit (paket socialiteproviders/keycloak, konfigurasi di config/services.php)
        Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
            $event->extendSocialite('keycloak', \SocialiteProviders\Keycloak\Provider::class);
        });
    }
}