<?php

namespace App\Traits;

trait ResolvesMediaUrl
{
    protected function resolveMediaUrl(string $path): string
    {
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        $baseUrl = rtrim((string) config('app.url'), '/');
        if ($baseUrl === '') {
            $baseUrl = rtrim(url('/'), '/');
        }

        $publicPath = rtrim((string) env('APP_PUBLIC_PATH', '/backend/public'), '/');
        $basePath = rtrim((string) parse_url($baseUrl, PHP_URL_PATH), '/');
        $baseHasPublicPath = $basePath !== '' && str_ends_with($basePath, $publicPath);

        if (app()->isLocal() || $baseHasPublicPath) {
            $mediaBasePath = '/api/v1/public/media';
        } else {
            $mediaBasePath = $publicPath . '/api/v1/public/media';
        }

        $normalizedPath = ltrim($path, '/');
        $encodedPath = str_replace('%2F', '/', rawurlencode($normalizedPath));

        return $baseUrl . $mediaBasePath . '/' . $encodedPath;
    }
}
