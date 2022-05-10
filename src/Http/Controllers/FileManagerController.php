<?php

namespace Tupy\FileManager\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controller as BaseController;
use Tupy\FileManager\Models\FileManager;
use Tupy\FileManager\Traits\AuthorizesRequests;
use \ZipArchive;

class FileManagerController extends BaseController
{
    use AuthorizesRequests;

    public function __construct()
    {
        $this->middleware(config('file-manager.middleware'));
    }

    public function destroy(FileManager $file)
    {
        try {
            $file->delete();
        } catch (\Exception $e) {
            throw new \Error('FileManager: ' . $e->getMessage(), $e->getCode());
        }

        return new JsonResponse(['message' => 'Deleted successfully'], 200);
    }

    public function download(Request $request)
    {
        dd($request);
        //Brevemente disponível
        $path = $request->file;

        return response()->download($path);
    }

    public function downloadAlbum($object_instance, $album)
    {
        //In progress
    }
}
