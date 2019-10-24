<?php

namespace Tupy\AuthenticationLog\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class FileManagerController extends Controller
{
    public function download(Request $request)
    {
        dd($request);
        //Brevemente disponível
        $path = $request->file;

        $fs = Storage::getDriver();
        $stream = $fs->readStream($path);
        return \Response::stream(function () use ($stream) {
            fpassthru($stream);
        }, 200, [
            "Content-Type" => $fs->getMimetype($path),
            "Content-Length" => $fs->getSize($path),
            "Content-disposition" => "attachment; filename=\"" . basename($path) . "\"",
        ]);
    }

    public function downloadAlbum($object_instance, $album)
    {
        $album = $request->album;

        $model = "\\App\\Models\\" . $request->model;
        $report = $model::find($request->id);
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