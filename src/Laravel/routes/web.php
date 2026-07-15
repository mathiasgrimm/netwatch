<?php

use Illuminate\Support\Facades\Route;
use Mathiasgrimm\Netwatch\Laravel\Http\Controllers\HealthController;
use Mathiasgrimm\Netwatch\Laravel\Http\Controllers\HealthProbeController;

Route::get('/health', HealthController::class);
Route::get('/health/probes/{probe}', HealthProbeController::class);
