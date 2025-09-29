<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect('app'));

Route::get('/version', function () {
    return ['app_name' => "SONEL", 'version' => "1"];
});
