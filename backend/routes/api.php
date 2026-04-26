<?php

use App\Http\Controllers\Api\ImportController;
use Illuminate\Support\Facades\Route;

Route::get('imports', [ImportController::class, 'index']);
Route::post('imports', [ImportController::class, 'store'])->middleware('throttle:30,1');
Route::get('imports/{import}', [ImportController::class, 'show']);
