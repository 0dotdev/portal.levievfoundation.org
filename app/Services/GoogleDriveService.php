<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\GoogleDriveTokenController;
use Exception;

class GoogleDriveService
{
    /**
     * Get a file from Google Drive, refreshing the token if needed.
     *
     * @param string $path
     * @return string|false The file contents or false on failure
     */
    public function getFile($path)
    {
        try {
            return Storage::disk('google')->get($path);
        } catch (Exception $e) {
            // Check for 401/invalid token
            if ($this->isTokenError($e)) {
                // Refresh token
                app(GoogleDriveTokenController::class)->refreshAccessToken();
                // Try again
                try {
                    // Clear config cache to reload the new token
                    app()['config']->set('filesystems.disks.google.accessToken', trim(file_get_contents(storage_path('app/google_drive_access_token.txt'))));
                    return Storage::disk('google')->get($path);
                } catch (Exception $e2) {
                    Log::error('Google Drive retry failed: ' . $e2->getMessage());
                    return false;
                }
            }
            Log::error('Google Drive getFile error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Write a stream to Google Drive, refreshing the token if needed.
     */
    public function writeStreamWithRetry($path, $stream, $options = [])
    {
        try {
            return \Storage::disk('google')->writeStream($path, $stream, $options);
        } catch (Exception $e) {
            if ($this->isTokenError($e)) {
                app(\App\Http\Controllers\GoogleDriveTokenController::class)->refreshAccessToken();
                app()['config']->set('filesystems.disks.google.accessToken', trim(file_get_contents(storage_path('app/google_drive_access_token.txt'))));
                return \Storage::disk('google')->writeStream($path, $stream, $options);
            }
            throw $e;
        }
    }

    protected function isTokenError(Exception $e)
    {
        $msg = $e->getMessage();
        return str_contains($msg, 'Invalid Credentials') || str_contains($msg, 'invalid_grant') || str_contains($msg, '401');
    }
} 