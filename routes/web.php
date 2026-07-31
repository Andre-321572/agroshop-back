<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/run-migrations-secret', function () {
    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    return "Migrations exécutées avec succès.";
});

Route::get('/run-seed-secret', function () {
    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
    return "Données de test (Seeders) injectées avec succès.";
});
