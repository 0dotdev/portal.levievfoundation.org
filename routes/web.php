<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\GoogleDriveTokenController;

Route::get('/', function () {
    Log::info('Test log');
    return redirect(route('filament.dashboard.auth.register'));
});


Route::post('/refresh-google-drive-token', [GoogleDriveTokenController::class, 'refreshAccessToken'])->middleware('auth');
Route::get('/google-drive-preview/{path}', [App\Http\Controllers\Controller::class, 'googleDrivePreview'])->where('path', '.*');
