<?php

use App\Http\Controllers\Api\ImportController;
use Illuminate\Support\Facades\Route;

Route::get('imports', [ImportController::class, 'index']);
Route::post('imports', [ImportController::class, 'store']);
Route::get('imports/{import}', [ImportController::class, 'show']);
