<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\TenantFileService;
use App\Traits\ResolvesMediaUrl;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    use ResolvesMediaUrl;

    protected $fileService;

    public function __construct(TenantFileService $fileService)
    {
        $this->fileService = $fileService;
    }

    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|file|image|max:20480', // Max 20MB
        ]);

        try {
            $path = $this->fileService->storeFile($request->file('image'), 'editor_uploads');
            $url = $this->resolveMediaUrl($path);

            return response()->json([
                'success' => 1,
                'file' => [
                    'url' => $url,
                ]
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erro upload editor: ' . $e->getMessage());
            return response()->json([
                'success' => 0,
                'message' => 'Erro ao fazer upload da imagem.'
            ], 500);
        }
    }

    public function storeMedia(Request $request)
    {
        $request->validate([
            'media' => 'required|file|mimetypes:image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/ogg|max:102400',
        ]);

        try {
            $file = $request->file('media');
            $mime = (string) $file->getMimeType();
            $isVideo = str_starts_with($mime, 'video/');
            $folder = $isVideo ? 'editor_uploads/videos' : 'editor_uploads/images';

            $path = $this->fileService->storeFile($file, $folder);
            $url = $this->resolveMediaUrl($path);

            return response()->json([
                'success' => 1,
                'file' => [
                    'url' => $url,
                    'type' => $isVideo ? 'video' : 'image',
                    'mime' => $mime,
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                ]
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erro upload de mídia do editor: ' . $e->getMessage());
            return response()->json([
                'success' => 0,
                'message' => 'Erro ao fazer upload da mídia.'
            ], 500);
        }
    }
}
