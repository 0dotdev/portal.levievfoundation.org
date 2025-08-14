<?php

namespace App\Http\Controllers;

use App\Services\GoogleDriveService;
use Illuminate\Http\Request;

abstract class Controller
{
    //

    public function googleDrivePreview(Request $request, $path)
    {
        $service = new GoogleDriveService();
        $file = $service->getFile($path);
        if ($file === false) {
            abort(404, 'File not found or cannot be previewed.');
        }
        // Optionally set content-type based on extension
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $mime = $ext === 'pdf' ? 'application/pdf' : ($ext === 'jpg' || $ext === 'jpeg' ? 'image/jpeg' : ($ext === 'png' ? 'image/png' : 'application/octet-stream'));
        return response($file, 200)->header('Content-Type', $mime);
    }
}
