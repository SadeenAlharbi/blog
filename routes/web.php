<?php

use Illuminate\Support\Facades\Route;

Route::get('/platformscode-test', function () {
    return view('platformscode-test');
});
Route::get('/', function () {
    return view('welcome');
});
