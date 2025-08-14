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
                Log::info('Refreshing Google Drive token for getFile...');

                // Refresh the token
                app(GoogleDriveTokenController::class)->refreshAccessToken();

                // Get the new token
                $newToken = trim(file_get_contents(storage_path('app/google_drive_access_token.txt')));
                Log::info('New token received for getFile: ' . substr($newToken, 0, 10) . '...');

                // Update the config and reset disk
                config(['filesystems.disks.google.accessToken' => $newToken]);
                Storage::forgetDisk('google');

                // Try again with new token
                try {
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
        // Ensure binary mode for PDF files
        if (pathinfo($path, PATHINFO_EXTENSION) === 'pdf') {
            if (is_resource($stream)) {
                stream_set_read_buffer($stream, 0);
                stream_set_write_buffer($stream, 0);
            }
            $options['mimetype'] = 'application/pdf';
        }

        try {
            // Ensure we have a valid stream
            if (!is_resource($stream)) {
                Log::error('Invalid stream provided for: ' . $path);
                throw new Exception('Invalid stream provided');
            }

            return Storage::disk('google')->writeStream($path, $stream, $options);
        } catch (Exception $e) {
            if ($this->isTokenError($e)) {
                Log::info('Refreshing Google Drive token for writeStream...');

                // Refresh the token
                app(GoogleDriveTokenController::class)->refreshAccessToken();

                // Get the new token
                $newToken = trim(file_get_contents(storage_path('app/google_drive_access_token.txt')));
                Log::info('New token received for writeStream: ' . substr($newToken, 0, 10) . '...');

                // Update the config and reset disk
                config(['filesystems.disks.google.accessToken' => $newToken]);
                Storage::forgetDisk('google');

                // Try again with new token
                try {
                    return Storage::disk('google')->writeStream($path, $stream, $options);
                } catch (Exception $e2) {
                    Log::error('Google Drive writeStream retry failed: ' . $e2->getMessage());
                    throw $e2;
                }
            }

            Log::error('Google Drive write error: ' . $e->getMessage());
            throw $e;
        } finally {
            // Always make sure we clean up the stream
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    protected function isTokenError(Exception $e)
    {
        $msg = $e->getMessage();
        Log::error('Google Drive Error: ' . $msg);

        // Log additional error details if available
        if (method_exists($e, 'getResponse')) {
            $response = $e->getResponse();
            if ($response) {
                Log::error('Response Status: ' . $response->getStatusCode());
                Log::error('Response Headers: ' . json_encode($response->getHeaders()));
                Log::error('Response Body: ' . $response->getBody());
            }
        }

        // Check if the error message contains JSON
        if (str_starts_with($msg, '{')) {
            $error = json_decode($msg, true);
            if (isset($error['error'])) {
                Log::error('Parsed Error Details: ' . json_encode($error['error']));
                if (isset($error['error']['code']) && $error['error']['code'] === 401) {
                    Log::info('Detected 401 error in JSON response');
                    return true;
                }
            }
        }

        // Check for various authentication error indicators
        $isAuthError = str_contains($msg, 'Invalid Credentials') ||
            str_contains($msg, 'invalid_grant') ||
            str_contains($msg, '401') ||
            str_contains($msg, 'UNAUTHENTICATED');

        if ($isAuthError) {
            Log::info('Detected authentication error in message: ' . $msg);
        }

        return $isAuthError;
    }
}
