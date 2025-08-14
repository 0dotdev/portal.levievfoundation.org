<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleDriveTokenController extends Controller
{
    public function refreshAccessToken()
    {
        $clientId = config('filesystems.disks.google.clientId');
        $clientSecret = config('filesystems.disks.google.clientSecret');
        $refreshToken = config('filesystems.disks.google.refreshToken');

        $response = \Illuminate\Support\Facades\Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if (!$response->ok() || !isset($response['access_token'])) {
            return response()->json(['error' => 'Failed to refresh token', 'details' => $response->json()], 500);
        }

        $newToken = $response['access_token'];
        $this->updateTokenFile($newToken);

        return response()->json(['access_token' => $newToken]);
    }

    protected function updateTokenFile($token)
    {
        file_put_contents(storage_path('app/google_drive_access_token.txt'), $token);
    }
}
