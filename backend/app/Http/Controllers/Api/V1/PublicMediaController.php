<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class PublicMediaController extends Controller
{
    public function show(string $path)
    {
        $decodedPath = ltrim(rawurldecode($path), '/');

        if ($decodedPath === '' || str_contains($decodedPath, '..')) {
            abort(404);
        }

        if (!Storage::disk('public')->exists($decodedPath)) {
            abort(404);
        }

        $disk = Storage::disk('public');
        $content = $disk->get($decodedPath);
        $mimeType = Storage::mimeType('public/' . $decodedPath) ?: 'application/octet-stream';

        return response($content, 200, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=31536000'
        ]);
    }
}
