<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Google_Client;
use Google_Service_Drive;

class GenerateGoogleDriveToken extends Command
{
    protected $signature = 'google:generate-token';
    protected $description = 'Generate Google Drive OAuth tokens (access + refresh)';

    public function handle()
    {
        $client = new Google_Client();
        $client->setClientId(config('filesystems.disks.google.clientId'));
        $client->setClientSecret(config('filesystems.disks.google.clientSecret'));
        $client->setRedirectUri('http://localhost');
        $client->addScope(Google_Service_Drive::DRIVE);
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        // Step 1: Get Authorization URL
        $authUrl = $client->createAuthUrl();
        $this->info("Open this link in your browser:\n$authUrl");

        // Step 2: Ask for the code from Google
        $code = $this->ask('Paste the authorization code here');

        // Step 3: Exchange code for tokens
        $token = $client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            $this->error('Error: ' . $token['error_description']);
            return;
        }

        $this->info("Access Token: " . $token['access_token']);
        $this->info("Refresh Token: " . ($token['refresh_token'] ?? 'No refresh token received!'));

        $this->comment("Add the refresh token to your .env file as GOOGLE_DRIVE_REFRESH_TOKEN");
    }
}
