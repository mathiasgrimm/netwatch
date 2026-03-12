<?php

use Illuminate\Support\Facades\Route;
use Mathiasgrimm\Netwatch\Laravel\Http\Controllers\HealthController;

Route::get('/health', HealthController::class);
