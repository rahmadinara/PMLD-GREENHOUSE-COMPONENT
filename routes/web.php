
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

Route::get('/dashboard/data', [DashboardController::class, 'index'])
    ->name('dashboard.data');
