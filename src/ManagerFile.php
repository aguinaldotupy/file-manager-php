<?phpnamespace Tupy\FileManager;use Illuminate\Support\Facades\Request;use Illuminate\Support\Facades\Storage;use Illuminate\Support\Str;use Intervention\Image\Facades\Image;use Tupy\FileManager\Models\FileManager;class ManagerFile{    /**     * @var array     */    private $expression;    /**     * @var string     */    private $disk;    /**     * @var string     */    private $visibility;    /**     * @var string     */    private $path;    /**     * @var \Illuminate\Database\Eloquent\Model     */    private $object_instance;    /**     * @var \Illuminate\Http\UploadedFile | mixed     */    private $file;    /**     * @var string     */    private $caption;    /**     * @var string|int     */    private $order;    /**     * @var string     */    private $type;    /**     * @var int|string|null     */    private $uploadBy;    /**     * @var string|null     */    private $filename;    /**     * @var \Illuminate\Database\Eloquent\Model|object|\Tupy\FileManager\Models\FileManager|null     */    private $modelFile;    /**     * @var string     */    private $origin;    public function __construct($object_instance, $file, array $options = [])    {        $this->object_instance = $object_instance;        $this->file = $file;        $optionsDefault = [            'disk' => 'public',            'visibility' => 'public',            'path' => 'temp',            'caption' => 'File in path temporary',            'order' => 0,            'type' => Str::random(10),            'uploaded_by' => null,            'filename' => null,            'origin' => null        ];        $expression = array_merge($optionsDefault, $options);        $this->disk = $expression['disk'];        $this->visibility = $expression['visibility'];        $this->path = $expression['path'];        $this->caption = $expression['caption'];        $this->order = $expression['order'];        $this->type = $expression['type'];        $this->uploadBy = $expression['uploaded_by'];        $this->filename = $expression['filename'];        $this->origin = $expression['origin'];        $this->expression = $expression;    }    public static function make($object_instance, $file, array $options = [])    {        return new ManagerFile($object_instance, $file, $options);    }    public function uploadFile()    {        //Pego a extensão/MimeType do ficheiro        $mimeTypeExtension = $this->file->getMimeType();        //Pego somente a extensão (.pdf) para manter no nome do ficheiro        $extension = $this->file->getClientOriginalExtension();        //Gero o nome do ficheiro        if(!$this->filename){            $this->filename = md5($this->file . time()) . '.' . $extension;        }        //Pego o tamanho do ficheiro para salvar no db        $size = $this->file->getSize();        //Salvo no storage conforme os dados enviados ou com o default        $upload = Storage::disk($this->disk)->putFileAs($this->path, $this->file, $this->filename, $this->visibility);        $model = null;        if ($upload) {            $model = (new FileManager)->where('type', '=', $this->type)->first();            if(!$model) {                $model = new FileManager();            }            $model->fileable_type = $this->object_instance::getModel();            $model->fileable_id = $this->object_instance->id;            $model->name = $this->filename;            $model->type = $this->type;            $model->uploaded_by = $this->uploadBy;            $model->full_name = "{$this->path}/$this->filename";            $model->mime_type = $mimeTypeExtension;            $model->extension = $extension;            $model->size = $size;            $model->path_storage = $this->path;            $model->disk = $this->disk;            $model->visibility = $this->visibility;            $model->order = $this->order;            $model->caption = $this->caption;            $model->origin = $this->origin;            $model->true_timestamp = null;            $model->save();        }        $this->modelFile = $model;        return $this;    }    public static function get(array $expression)    {        // dd($expression);        $value = self::getValueFile($expression);        $url = self::getStorage($value);        return $url;    }    //Verificar na base de dados de forma dinâmica o disco e a visibilidade ao executar uma função de get    protected static function getStorage(array $expression = null)    {        $defaultCloud = 's3';        $defaultLocal = 'public';        if (is_array($expression)) {            $filename = $expression['value'];            $disk = $expression['disk'];            $visibility = $expression['visibility'];            if ($visibility == 'public') {                $file = Storage::disk($disk)->url($filename);            } else {                $file = Storage::disk($disk)->temporaryUrl($filename, Carbon::now()->addMinutes(5));            }        } else {            $file = Storage::disk($defaultLocal)->url($expression);        }        return $file;    }    public static function imageUpload(array $expression = null)    {        if ($expression) {            $disk       = $expression['disk'] ? $expression['disk'] : 's3';            $visibility = $expression['visibility'] ? $expression['visibility'] : 'private';            $path       = $expression['path'] ? $expression['path'] : "notSpecific";            $value      = $expression['value'] ? $expression['value'] : \Request::file('image');            $caption    = $expression['caption'] ? $expression['caption'] : null;            $order      = $expression['order'] ? $expression['order'] : 0;        } else {            $disk       = 's3';            $visibility = 'private';            $path       = 'notSpecific';            $value      = $expression;            $caption    = null;            $order      = null;        }        if ($value != null) {            // if a base64 was sent, store it in the db            if (starts_with($value, 'data:image') || is_file($value)) {                //Make the image                $image = Image::make($value)->resize(600, null, function ($constraint) {                    $constraint->aspectRatio();                })->crop(500, 500)->encode('jpg');                //Generate a filename.                $filename = md5($value . time()) . '.jpg';                //Store the image on disk.                $upload = Storage::disk($disk)->put($path . '/' . $filename, $image->stream(), $visibility);                // dd($upload);                // Se o store der certo, envia os dados de retorno para salvar na relacão de tabela                if ($upload == true) {                    $size = Storage::disk($disk)->size($path . '/' . $filename);                    $results = [                        'file_name'     => $filename,                        'extension'     => $image->mime(),                        'size'          => $size,                        'path_storage'  => $path,                        'disk'          => $disk,                        'visibility'    => $visibility,                        'caption'       => $caption,                        'order'         => $order,                        'true_timestamp' => Carbon::now()                    ];                    if(isset($expression['object_instance'])){                        $object_instance = $expression['object_instance'] ;                        $type = $expression['type'];                        self::saveRelation($object_instance, $type, $results);                    }                } else {                    $results = false;                }            }        } else {            $results = null;        }        return $results;    }    /**     * @param object $instance_object     * @param String $type Qual o nome do tipo da imagem ou o name que você recebe no request     * @param array $expression Um array com as informações a serem salvo     * [     *      'file_name'         => 'name',     *      'extension'         => 'img/png' (mimetype),     *      'size'              => 1 (int),     *      'path_storage'      => 'documents' (string),     *      'disk'              => 'local' (string),     *      'visibility'        => 'public' (string),     * ]     *     * @return void[$instance_object, $type, $expression]     */    protected static function saveRelation(object $instance_object, string $type, array $expression)    {        $instance_object->files()->updateOrCreate(            [                'fileable_id'   => $instance_object->id,                'type'          => $type,            ],            [                'fileable_id'    => $instance_object->id,                'type'           => $type,                'full_name'      => $expression['full_name'],                'name'           => $expression['name'],                'mime_type'      => $expression['mime_type'],                'extension'      => $expression['extension'],                'size'           => $expression['size'],                'path_storage'   => $expression['path_storage'],                'disk'           => $expression['disk'],                'visibility'     => $expression['visibility'],                'order'          => $expression['order'],                'caption'        => $expression['caption'],                'origem'         => $expression['origem'],                'true_timestamp' => $expression['true_timestamp'],            ]        );    }    public static function exists(array $expression)    {        $request    = request()->instance();        // Se for um array, deve ser enviado quando o tipo do arquivo e qual o ID do user        if (is_array($expression)) {            $type = $expression['type'];            $object_instance = $expression['object_instance'];        } else {            //Se a solicitação da imagem vem de um form de create, no url deve conter o parametro de id            if ($request->id == true) {                $id     = $request->id;                $type   = $expression;            } else {                //Se estiver em uma rota onde não consegue passar variáveis, vamos tentar buscar no URL o id do user                $uri    = explode('/', $request->route()->uri);                $vai    = $uri[1];                $id     = $request->$vai;                $type   = $expression;            }        }        if (isset($object_instance)) {            $file = $object_instance->file()->where('type', $type)->first();            // dd($file);            // $file = FileUser::where([['user_id', '=', $id], ['field_application', $type]])->first();            if (isset($file->path_storage) && isset($file->file_name)) {                $path = $file->path_storage;                $file_name = $file->file_name;                $fullName = $path . "/" . $file_name;                $disk = $file->disk;                $value = Storage::disk($disk)->exists($fullName);            } else {                $value = false;            }        } else {            $value = false;        }        return $value;    }    public static function imageCheck()    {        return Storage::disk('public')->url('helper/check.png');    }    public static function imageUserNull()    {        $imageNull = config('file-manager.placeholder');        return $imageNull;    }    public static function fileNull()    {        return Storage::disk('public')->url(config('file-manager.no_image'));    }    //Vou dar retorno de 1 file ou de vários ou terei uma função que pode me retornar mais de 1?    protected static function getValueFile(array $expression)    {        // Se for um array, deve ser enviado quando o tipo do arquivo e qual o ID do user        if (is_array($expression)) {            $type = $expression['type'];            $object_instance = $expression['object_instance'];        }        if (isset($object_instance)) {            // $file = FileUser::where([['user_id', '=', $id], ['field_application', $type]])->first();            $file = $object_instance->file()->where('type', $type)->first();            if (isset($file->path_storage) && isset($file->file_name)) {                $path = $file->path_storage;                $file_name = $file->file_name;                $disk = $file->disk;                $visibility = $file->visibility;                $value = [                    'value'         => $path . "/" . $file_name,                    'disk'          => $disk,                    'visibility'    => $visibility,                ];            } else {                $value = config('file-manager.no_image');            }        } else {            $value = config('file-manager.no_image');        }        return $value;    }    /**     * @param string $disk     * @return ManagerFile     */    public function setDisk(string $disk): ManagerFile    {        $this->disk = $disk;        return $this;    }    /**     * @param string $visibility     * @return ManagerFile     */    public function setVisibility(string $visibility): ManagerFile    {        $this->visibility = $visibility;        return $this;    }    /**     * @param string $path     * @return ManagerFile     */    public function setPath(string $path): ManagerFile    {        $this->path = $path;        return $this;    }    /**     * @param \Illuminate\Database\Eloquent\Model $object_instance     * @return ManagerFile     */    public function setObjectInstance($object_instance): ManagerFile    {        $this->object_instance = $object_instance;        return $this;    }    /**     * @param \Illuminate\Http\UploadedFile|mixed $file     * @return ManagerFile     */    public function setFile($file)    {        $this->file = $file;        return $this;    }    /**     * @param string $caption     * @return ManagerFile     */    public function setCaption(string $caption): ManagerFile    {        $this->caption = $caption;        return $this;    }    /**     * @param int|string $order     * @return ManagerFile     */    public function setOrder($order)    {        $this->order = $order;        return $this;    }    /**     * @param string $type     * @return ManagerFile     */    public function setType(string $type): ManagerFile    {        $this->type = $type;        return $this;    }    /**     * @param int|string|null $uploadBy     * @return ManagerFile     */    public function setUploadBy($uploadBy)    {        $this->uploadBy = $uploadBy;        return $this;    }}