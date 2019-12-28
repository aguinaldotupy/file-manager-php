<?php

namespace Tupy\AuthenticationLog\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controller as BaseController;
use \ZipArchive;

class FileManagerController extends BaseController
{
    public function download(Request $request)
    {
        dd($request);
        //Brevemente disponível
        $path = $request->file;

        return response()->download($path);
    }

    public function downloadAlbum($object_instance, $album)
    {
        $album = request('album');

        $model = "\\App\\Models\\" . request('model');
        $report = $model::find(request('id'));
        $path = 'storage/temp/' . $album.'_'.time() . '.zip';

        $photos = $report->file()->get();
        $disk = 's3';

        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE);

        foreach ($photos as $photo) {
            $zip->addFromString($photo->file_name, Storage::disk($disk)->get($photo->path_storage . "/" . $photo->file_name));
        }

        $zip->close();

        header('Content-disposition: attachment; filename=' . $album . '.zip');
        header('Content-type: application/zip');
        readfile($path);
    }
}