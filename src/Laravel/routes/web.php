<?php

use Illuminate\Support\Facades\Route;
use MathiasGrimm\Netwatch\Laravel\Http\Controllers\HealthController;
use MathiasGrimm\Netwatch\Laravel\Http\Controllers\HealthProbeController;

Route::get('/health', HealthController::class);
Route::get('/health/probes/{probe}', HealthProbeController::class);
