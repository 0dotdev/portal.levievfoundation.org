<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\GoogleDriveTokenController;
use Illuminate\Support\Facades\Artisan;

Route::get('/', function () {
    return redirect(route('filament.dashboard.auth.register'));
});

Route::get('/hZrOURmRas', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    Artisan::call('event:clear');
    Artisan::call('optimize:clear');

    return '✅ All caches cleared!';
});


Route::post('/refresh-google-drive-token', [GoogleDriveTokenController::class, 'refreshAccessToken'])->middleware('auth');
Route::get('/google-drive-preview/{path}', [App\Http\Controllers\Controller::class, 'googleDrivePreview'])->where('path', '.*');
