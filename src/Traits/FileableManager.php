<?php

namespace Tupy\FileManager;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Intervention\Image\Facades\Image;

trait FileableManager
{
    public function files()
    {
        return $this->morphMany(FileManager::class, 'fileable');
    }

    public function deleteFile($attribute_name, $deleteAllByType = false)
    {
        // if the image was erased
        $file = $this->files()->where('type', $attribute_name)->first();
        // dd($file);

        // $file = FileUser::where([['user_id', '=', $object_instance], ['type', $attribute_name]])->first();

        // delete the image from disk
        if ($file != null) {
            Storage::disk($file->disk)->delete($file->path_storage . '/' . $file->file_name);

            $file->delete();
        }
    }

    public static function S3Private($type)
    {
        // dd('estou aqui no s3', $type);
        $disk = config('filesystems.default');

        $file = Storage::disk($disk)->temporaryUrl($type, Carbon::now()->addMinutes(5));

        return $file;
    }






    public static function fileUpload($expression)
    {
        if (is_array($expression)) {
            $disk       = $expression['disk'] ? $expression['disk'] : 's3';
            $visibility = $expression['visibility'] ? $expression['visibility'] : 'private';
            $path       = $expression['path'] ? $expression['path'] : "notSpecific";
            $value      = $expression['value'] ? $expression['value'] : \Request::file('file');
            $caption    = $expression['caption'] ? $expression['caption'] : null;
            $order      = $expression['order'] ? $expression['order'] : 0;
        } else {
            $disk       = 's3';
            $visibility = 'private';
            $path       = 'notSpecific';
            $value      = $expression;
            $caption    = null;
            $order      = null;
        }

        //Se vier valor no request(file), avanço para a próxima etapa
        if ($value != null) {

            //Se for ficheiro válido, continuo a operação
            if (is_file($value)) {

                // Armazeno nesta variável para facilitar a leitura do código
                $file = $value;

                //Pego a extensão/MimeType do ficheiro
                $mimeTypeExtension = $file->getMimeType();

                //Pego somente a extensão (.pdf) para manter no nome do ficheiro
                $extension = $file->getClientOriginalExtension();

                //Gero o nome do ficheiro
                $filename = md5($file . time()) . '.' . $extension;

                //Pego o tamanho do ficheiro para salvar no db
                $size = $file->getSize();

                //Salvo no storage conforme os dados enviados ou com o default
                $upload = Storage::disk($disk)->putFileAs($path, $file, $filename, $visibility);

                // Se o store der certo, envia os dados de retorno para salvar na relacão de tabela
                if ($upload == true) {
                    $results = [
                        'file_name'         => $filename,
                        'extension'         => $mimeTypeExtension,
                        'size'              => $size,
                        'path_storage'      => $path,
                        'disk'              => $disk,
                        'visibility'        => $visibility,
                        'caption'           => $caption,
                        'order'             => $order,
                    ];
                } else {
                    $results = null;
                }
            } else {
                $results = null;
            }
        } else {
            $results = null;
        }

        return $results;
    }

    public static function fileOrImageUploadAndSaveRelationaAndReturnChanges($object_instance, $expression, $type)
    {
        $disk       = $expression['disk'] ?? 's3';
        $visibility = $expression['visibility'] ?? 'private';
        $path       = $expression['path'] ?? "notSpecific";
        $value      = $expression['value'] ?? \Request::file('image');
        $caption    = $expression['caption'] ?? null;
        $order      = $expression['order'] ?? 0;

        if ($value != null) {

            // if a base64 was sent, store it in the db
            if (starts_with($value, 'data:image')) {

                //Make the image
                $imageUpload = \Image::make($value)->resize(600, null, function ($constraint) {
                    $constraint->aspectRatio();
                })->crop(500, 500)->encode('jpg');

                //Generate a filename.
                $filename = md5($value . time()) . '.jpg';

                //Store the image on disk.
                $upload = Storage::disk($disk)->put($path . '/' . $filename, $imageUpload->stream(), $visibility);
                // dd($upload);

                // Se o store der certo, envia os dados de retorno para salvar na relacão de tabela
                if ($upload == true) {
                    $sizeImage = Storage::disk($disk)->size($path . '/' . $filename);

                    $image = $object_instance->file()->where('type', $type)->first();

                    if ($image == null) {
                        $image = $object_instance->file()->create([
                            'type'          => $type,
                            'fileable_id'   => $object_instance->id,
                            'type'          => $type,
                            'file_name'     => $filename,
                            'extension'     => $imageUpload->mime(),
                            'size'          => $sizeImage,
                            'path_storage'  => $path,
                            'disk'          => $disk,
                            'visibility'    => $visibility,
                            'origem'        => \Request::route()->uri,
                            'caption'       => $caption,
                            'order'         => $order,
                        ]);

                        $dirtyImage = $type;
                    } else {
                        $image->type          = $type;
                        $image->file_name     = $filename;
                        $image->extension     = $image->mime();
                        $image->size          = $sizeImage;
                        $image->path_storage  = $path;
                        $image->disk          = $disk;
                        $image->visibility    = $visibility;
                        $image->origem        = \Request::route()->uri;
                        $image->caption       = $caption;
                        $image->order         = $order;
                        $dirtyImage           = $image->getDirty();
                        $image->save();

                        // return $dirty;
                    }

                    $results = [
                        'file_name'     => $filename,
                        'path_storage'  => $path,
                        'changed'       => $dirtyImage
                    ];

                    return $results;
                }
            }

            if (is_file($value)) {
                // Armazeno nesta variável para facilitar a leitura do código
                $fileUpload = $value;

                //Pego a extensão/MimeType do ficheiro
                $mimeTypeExtension = $fileUpload->getMimeType();

                //Pego somente a extensão (.pdf) para manter no nome do ficheiro
                $extensionFile = $fileUpload->getClientOriginalExtension();

                //Gero o nome do ficheiro
                $filename = md5($fileUpload . time()) . '.' . $extensionFile;

                //Pego o tamanho do ficheiro para salvar no db
                $sizeFile = $fileUpload->getSize();

                //Salvo no storage conforme os dados enviados ou com o default
                $upload = Storage::disk($disk)->putFileAs($path, $fileUpload, $filename, $visibility);

                // Se o store der certo, envia os dados de retorno para salvar na relacão de tabela
                if ($upload == true) {

                    $sizeFile = Storage::disk($disk)->size($path . '/' . $filename);

                    $file = $object_instance->file()->where('type', $type)->first();

                    if ($file == null) {
                        $file = $object_instance->file()->create([
                            'type'          => $type,
                            'fileable_id'   => $object_instance->id,
                            'type'          => $type,
                            'file_name'     => $filename,
                            'extension'     => $mimeTypeExtension,
                            'size'          => $sizeFile,
                            'path_storage'  => $path,
                            'disk'          => $disk,
                            'visibility'    => $visibility,
                            'origem'        => \Request::route()->uri,
                            'caption'       => $caption,
                            'order'         => $order,
                        ]);

                        $dirtyFile = 'new';
                    } else {
                        $file->type          = $type;
                        $file->file_name     = $filename;
                        $file->extension     = $mimeTypeExtension;
                        $file->size          = $sizeFile;
                        $file->path_storage  = $path;
                        $file->disk          = $disk;
                        $file->visibility    = $visibility;
                        $file->origem        = \Request::route()->uri;
                        $file->caption       = $caption;
                        $file->order         = $order;
                        $dirtyFile           = $file->getDirty();
                        $file->save();

                        // return $dirtyFile;
                    }

                    $results = [
                        'file_name'     => $filename,
                        'path_storage'  => $path,
                        'changed'       => $dirtyFile
                    ];

                    return $results;
                }
            }
        }
    }

    public static function getProfileOrNull($type)
    {
        if ($type != null) {
            $url = FileManagerTrait::S3Private($type);
        } else {
            $url = FileManagerTrait::imageUserNull();
        }

        return $url;
    }


    public static function getDocumentCloud($type)
    {
        //Esta sendo utilizada no seed
        $file = $type;

        $url = FileManagerTrait::S3Private($file);

        return $url;
    }

    
}
