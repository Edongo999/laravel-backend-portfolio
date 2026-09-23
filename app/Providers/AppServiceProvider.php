<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema; // ajoute cette ligne

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
            // Fix pour éviter l'erreur "clé trop longue"
            Schema::defaultStringLength(191);

            //  Forcer l'encodage UTF-8 pour les réponses JSON
            header('Content-Type: application/json; charset=UTF-8');

            ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
                return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
            });
        }

}
