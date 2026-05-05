<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\GoogleDriveTokenController;
use App\Notifications\ApplicationStatusNotification;
use Illuminate\Support\Facades\Artisan;

Route::get('/', function () {
    // If user is authenticated, redirect based on role
    if (Auth::check()) {
        $user = Auth::user();
        if ($user->roles === 'admin') {
            return redirect('/admin');
        }
        return redirect('/dashboard');
    }

    // If not authenticated, redirect to register
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
Route::get('/test', function () {

    // Create user notification message
    $notificationMessage = "Your application has been submitted successfully.\n\n";
    $notificationMessage .= "We have received {5} grant " . (5 === 1 ? 'application.' : 'applications.');

    if (4 > 0) {
        $notificationMessage .= "\n{4} " . (4 === 1 ? 'child was' : 'children were') . ' not included in any grant application, so no application was created for them.';
    }

    $notificationMessage .= "\n\nPlease wait while we review your submission. We will notify you once there is an update.";

    // Notify the user
    Auth::user()->notify(new ApplicationStatusNotification(
        'Application Submitted',
        $notificationMessage,
        url('/dashboard/applications')
    ));

    return '✅ All caches cleared!';
});


Route::post('/refresh-google-drive-token', [GoogleDriveTokenController::class, 'refreshAccessToken'])->middleware('auth');
Route::get('/google-drive-preview/{path}', [App\Http\Controllers\Controller::class, 'googleDrivePreview'])->where('path', '.*');
