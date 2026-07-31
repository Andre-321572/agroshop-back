<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/run-migrations-secret', function () {
    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    return "Migrations exécutées avec succès.";
});
