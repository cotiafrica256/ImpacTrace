<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['app' => 'MECPA MEAL System API', 'status' => 'ok']);
});
