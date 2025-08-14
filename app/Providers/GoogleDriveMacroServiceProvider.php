<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\GoogleDriveTokenController;
use Exception;

class GoogleDriveMacroServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Macro for put (upload)
        Storage::disk('google')->macro('putWithRetry', function ($path, $contents, $options = []) {
            try {
                return Storage::disk('google')->put($path, $contents, $options);
            } catch (Exception $e) {
                if (str_contains($e->getMessage(), 'Invalid Credentials') || str_contains($e->getMessage(), '401')) {
                    app(GoogleDriveTokenController::class)->refreshAccessToken();
                    app()['config']->set('filesystems.disks.google.accessToken', trim(file_get_contents(storage_path('app/google_drive_access_token.txt'))));
                    return Storage::disk('google')->put($path, $contents, $options);
                }
                throw $e;
            }
        });

        // Macro for writeStream (upload)
        Storage::disk('google')->macro('writeStreamWithRetry', function ($path, $resource, $options = []) {
            try {
                return Storage::disk('google')->writeStream($path, $resource, $options);
            } catch (Exception $e) {
                if (str_contains($e->getMessage(), 'Invalid Credentials') || str_contains($e->getMessage(), '401')) {
                    app(GoogleDriveTokenController::class)->refreshAccessToken();
                    app()['config']->set('filesystems.disks.google.accessToken', trim(file_get_contents(storage_path('app/google_drive_access_token.txt'))));
                    return Storage::disk('google')->writeStream($path, $resource, $options);
                }
                throw $e;
            }
        });
    }
} 