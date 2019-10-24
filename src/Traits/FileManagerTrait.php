<?php

namespace Tupy\FileManager\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Intervention\Image\Facades\Image;

trait FileManagerTrait
{
	//Get file
    public static function get(array $expression)
    {
        // dd($expression);
        $value = self::getValueFile($expression);
        $url = self::getStorage($value);

        return $url;
    }


	//Verificar na base de dados de forma dinâmica o disco e a visibilidade ao executar uma função de get
    protected static function getStorage(array $expression = null)
    {
        $defaultCloud = 's3';
        $defaultLocal = 'public';

        if (is_array($expression)) {
            $filename = $expression['value'];
            $disk = $expression['disk'];
            $visibility = $expression['visibility'];

            if ($visibility == 'public') {
                $file = Storage::disk($disk)->url($filename);
            } else {
                $file = Storage::disk($disk)->temporaryUrl($filename, Carbon::now()->addMinutes(5));
            }
        } else {
            $file = Storage::disk($defaultLocal)->url($expression);
        }

        return $file;
    }

	public static function imageUpload(array $expression = null)
    {

        if ($expression) {
            $disk       = $expression['disk'] ? $expression['disk'] : 's3';
            $visibility = $expression['visibility'] ? $expression['visibility'] : 'private';
            $path       = $expression['path'] ? $expression['path'] : "notSpecific";
            $value      = $expression['value'] ? $expression['value'] : \Request::file('image');
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

        if ($value != null) {

            // if a base64 was sent, store it in the db
            if (starts_with($value, 'data:image') || is_file($value)) {

                //Make the image
                $image = \Image::make($value)->resize(600, null, function ($constraint) {
                    $constraint->aspectRatio();
                })->crop(500, 500)->encode('jpg');

                //Generate a filename.
                $filename = md5($value . time()) . '.jpg';

                //Store the image on disk.
                $upload = Storage::disk($disk)->put($path . '/' . $filename, $image->stream(), $visibility);
                // dd($upload);

                // Se o store der certo, envia os dados de retorno para salvar na relacão de tabela
                if ($upload == true) {
                    $size = Storage::disk($disk)->size($path . '/' . $filename);

                    $results = [
                        'file_name'     => $filename,
                        'extension'     => $image->mime(),
                        'size'          => $size,
                        'path_storage'  => $path,
                        'disk'          => $disk,
                        'visibility'    => $visibility,
                        'caption'       => $caption,
                        'order'         => $order,
                        'true_timestamp' => Carbon::now()
                    ];

                    if(isset($expression['object_instance'])){
                        $object_instance = $expression['object_instance'] ;
                        $type = $expression['type'];
                        self::saveRelation($object_instance, $type, $results);
                    }

                } else {
                    $results = false;
                }
            }
        } else {
            $results = null;
        }

        return $results;
    }

    /**
     * @param  Int $user_id O Id do user
     * @param  String $type Qual o nome do tipo da imagem ou o name que você recebe no request
     * @param  Array $expression Um array com as informações a serem salvo
     * [
     *      'file_name'         => 'name',
     *      'extension'         => 'img/png' (mimetype),
     *      'size'              => 1 (int),
     *      'path_storage'      => 'documents' (string),
     *      'disk'              => 'local' (string),
     *      'visibility'        => 'public' (string),
     * ]
     *
     * @return void[$instance_object, $type, $expression]
     */
    protected static function saveRelation(object $instance_object, string $type, array $expression)
    {
        if (is_array($expression)) {
            $instance_object->file()->updateOrCreate(
                [
                    'fileable_id'   => $instance_object->id,
                    'type'          => $type,
                ],
                [
                    'fileable_id'    => $instance_object->id,
                    'type'           => $type,
                    'file_name'      => $expression['file_name'],
                    'extension'      => $expression['extension'],
                    'size'           => $expression['size'],
                    'path_storage'   => $expression['path_storage'],
                    'disk'           => $expression['disk'],
                    'visibility'     => $expression['visibility'],
                    'origem'         => \Request::route()->uri,
                    'caption'        => $expression['caption'],
                    'order'          => $expression['order'],
                    'true_timestamp' => $expression['true_timestamp'],
                ]
            );
        } else {
            echo 'Por favor, me envie um array para trabalhar corretamente. Ainda não sou inteligente para interpretar outra função, em breve receberei um update e você poderá enviar informações em outro formato.';
            // return response()->json(['imparáveis' => 'Por favor, me envie um array para trabalhar corretamente. Ainda não sou inteligente para interpretar outra função, em breve receberei um update e você poderá enviar informações em outro formato.'], 400);
        }
    }

    public static function exists(array $expression)
    {
        $request    = \Request::instance();

        // Se for um array, deve ser enviado quando o tipo do arquivo e qual o ID do user
        if (is_array($expression)) {
            $type = $expression['type'];
            $object_instance = $expression['object_instance'];
        } else {
            //Se a solicitação da imagem vem de um form de create, no url deve conter o parametro de userId
            if ($request->userId == true) {
                $id     = $request->userId;
                $type   = $expression;
            } else {
                //Se estiver em uma rota onde não consegue passar variáveis, vamos tentar buscar no URL o id do user
                $uri    = explode('/', $request->route()->uri);
                $vai    = $uri[1];
                $id     = $request->$vai;
                $type   = $expression;
            }
        }

        if (isset($object_instance)) {

            $file = $object_instance->file()->where('type', $type)->first();
            // dd($file);
            // $file = FileUser::where([['user_id', '=', $id], ['field_application', $type]])->first();

            if (isset($file->path_storage) && isset($file->file_name)) {
                $path = $file->path_storage;
                $file_name = $file->file_name;
                $fullName = $path . "/" . $file_name;
                $disk = $file->disk;

                $value = Storage::disk($disk)->exists($fullName);
            } else {
                $value = false;
            }
        } else {
            $value = false;
        }

        return $value;
    }


	public static function imageCheck()
    {
        return Storage::disk('public')->url('helper/check.png');
    }

    public static function imageUserNull()
    {
        $imageNull = config('file-manager.placeholder');

        return $imageNull;
    }

    public static function fileNull()
    {
       	return Storage::disk('public')->url(config('file-manager.no_image'));
    }

    //Vou dar retorno de 1 file ou de vários ou terei uma função que pode me retornar mais de 1?
	protected static function getValueFile(array $expression)
    {
        // Se for um array, deve ser enviado quando o tipo do arquivo e qual o ID do user
        if (is_array($expression)) {
            $type = $expression['type'];
            $object_instance = $expression['object_instance'];
        }

        if (isset($object_instance)) {

            // $file = FileUser::where([['user_id', '=', $id], ['field_application', $type]])->first();

            $file = $object_instance->file()->where('type', $type)->first();

            if (isset($file->path_storage) && isset($file->file_name)) {
                $path = $file->path_storage;
                $file_name = $file->file_name;
                $disk = $file->disk;
                $visibility = $file->visibility;

                $value = [
                    'value'         => $path . "/" . $file_name,
                    'disk'          => $disk,
                    'visibility'    => $visibility,
                ];
            } else {
                $value = config('file-manager.no_image');
            }
        } else {
            $value = config('file-manager.no_image');
        }

        return $value;
    }
}