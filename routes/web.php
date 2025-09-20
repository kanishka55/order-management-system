<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-log', function () {
    Log::debug('Test debug message');
    return 'Log message created';
});
