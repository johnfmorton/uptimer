<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('kiro-welcome');
});

Route::get('/example', function () {
    return view('example');
});


Route::get('/original', function () {
    return view('welcome');
});
