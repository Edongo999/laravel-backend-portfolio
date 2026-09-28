<?php

use Laravel\Sanctum\Http\Middleware\AuthenticateSession;

return [

    /*
    |--------------------------------------------------------------------------
    | Stateful Domains
    |--------------------------------------------------------------------------
    |
    | Comme tu utilises désormais les tokens Bearer,
    | tu n’as plus besoin de configurer les domaines stateful.
    |
    */

    'stateful' => [],

    /*
    |--------------------------------------------------------------------------
    | Sanctum Guards
    |--------------------------------------------------------------------------
    |
    | Tu peux garder "web" ou ajouter "api" selon ton usage.
    |
    */

    'guard' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Expiration Minutes
    |--------------------------------------------------------------------------
    |
    | Durée de vie des tokens générés par Sanctum.
    | Exemple : 1440 minutes = 24 heures.
    |
    */

    'expiration' => 1440,

    /*
    |--------------------------------------------------------------------------
    | Token Prefix
    |--------------------------------------------------------------------------
    */

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Middleware
    |--------------------------------------------------------------------------
    |
    | Comme tu n’utilises plus les cookies CSRF,
    | tu peux garder uniquement AuthenticateSession.
    |
    */

    'middleware' => [
        'authenticate_session' => AuthenticateSession::class,
    ],

];