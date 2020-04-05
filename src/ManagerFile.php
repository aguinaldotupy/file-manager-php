<?phpnamespace Tupy\FileManager;use Illuminate\Support\Facades\Request;use Illuminate\Support\Facades\Storage;use Illuminate\Support\Str;use Intervention\Image\Constraint;use Intervention\Image\Facades\Image;use Tupy\FileManager\Models\FileManager;class ManagerFile{    /**     * @var array     */    private $expression;    /**     * @var string     */    private $disk;    /**     * @var string     */    private $visibility;    /**     * @var string     */    private $path;    /**     * @var \Illuminate\Database\Eloquent\Model     */    private $object_instance;    /**     * @var \Illuminate\Http\UploadedFile | mixed     */    private $file;    /**     * @var string     */    private $caption;    /**     * @var string|int     */    private $order;    /**     * @var string     */    private $type;    /**     * @var int|string|null     */    private $uploadBy;    /**     * @var string|null     */    private $filename;    /**     * @var \Illuminate\Database\Eloquent\Model|object|\Tupy\FileManager\Models\FileManager|null     */    private $modelFile;    /**     * @var string     */    private $origin;    /**     * @var string|null     */    private $mimeTypeExtension = null;    /**     * @var string     */    private $extension;    /**     * @var int     */    private $size;    public function __construct($object_instance, $file, array $options = [])    {        $this->object_instance = $object_instance;        $this->file = $file;        $optionsDefault = [            'disk' => 'public',            'visibility' => 'public',            'path' => 'temp',            'caption' => 'File in path temporary',            'order' => 0,            'type' => Str::random(10),            'uploaded_by' => null,            'filename' => null,            'origin' => null        ];        $expression = array_merge($optionsDefault, $options);        $this->disk = $expression['disk'];        $this->visibility = $expression['visibility'];        $this->path = $expression['path'];        $this->caption = $expression['caption'];        $this->order = $expression['order'];        $this->type = $expression['type'];        $this->uploadBy = $expression['uploaded_by'];        $this->filename = $expression['filename'];        $this->origin = $expression['origin'];        $this->expression = $expression;    }    public static function make($object_instance, $file, array $options = [])    {        return new ManagerFile($object_instance, $file, $options);    }    /**     * @param \Closure|null $closure     * @throws \Exception     * @return $this     */    public function imageUpload(\Closure $closure = null)    {        if ($this->file) {            // if a base64 was sent, store it in the db            if (Str::startsWith($this->file, 'data:image') || is_file($this->file)) {                $storage = Storage::disk($this->disk);                //Make the image                $image = Image::make($this->file);                $this->mimeTypeExtension = $image->mime();                $this->extension = Utils::getExtByMimeType($this->mimeTypeExtension);                if($closure){                    $image = call_user_func($closure, $image);                } else {                    $image->encode($this->extension);                }                //Generate a filename.                if(!$this->filename){                    $this->filename = md5($this->file . time()) . '.' . $this->extension;                }                $this->size = $image->filesize();                //Store the image on disk.                $upload = $storage->put($this->path . '/' . $this->filename, $image->stream(), $this->visibility);                // Se o store der certo, envia os dados de retorno para salvar na relacão de tabela                if ($upload) {                    $this->size = $storage->size($this->path . '/' . $this->filename);                    $this->modelFile = self::saveRelation();                }            }        }        return $this;    }    /**     * @throws \Exception     * @return $this     */    public function uploadFile()    {        //Pego a extensão/MimeType do ficheiro        $this->mimeTypeExtension = $this->file->getMimeType();        //Pego somente a extensão (.pdf) para manter no nome do ficheiro        $this->extension = $this->file->getClientOriginalExtension();        //Gero o nome do ficheiro        if(!$this->filename){            $this->filename = md5($this->file . time()) . '.' . $this->extension;        }        //Pego o tamanho do ficheiro para salvar no db        $this->size = $this->file->getSize();        //Salvo no storage conforme os dados enviados ou com o default        $upload = Storage::disk($this->disk)->putFileAs($this->path, $this->file, $this->filename, $this->visibility);        if ($upload) {            $this->modelFile = self::saveRelation();        }        return $this;    }    /**     * @throws \Exception     * @return \Illuminate\Database\Eloquent\Model|object|\Tupy\FileManager\Models\FileManager|null     */    protected function saveRelation()    {        try {            $class = new \ReflectionClass($this->object_instance);        } catch (\ReflectionException $e) {            throw new \Exception('Error in get class');        }        $model = (new FileManager)            ->where('type', '=', $this->type)            ->where('fileable_type', '=', $class->getName())            ->where('fileable_id', '=', $this->object_instance->id)            ->first();        if(!$model) {            $model = new FileManager();        } else {            $model->deleteFile();        }        $model->fileable_type = $class->getName();        $model->fileable_id = $this->object_instance->id;        $model->name = $this->filename;        $model->type = $this->type;        $model->uploaded_by = $this->uploadBy;        $model->full_name = "{$this->path}/$this->filename";        $model->mime_type = $this->mimeTypeExtension;        $model->extension = $this->extension;        $model->size = $this->size;        $model->path_storage = $this->path;        $model->disk = $this->disk;        $model->visibility = $this->visibility;        $model->order = $this->order;        $model->caption = $this->caption;        $model->origin = $this->origin;        $model->true_timestamp = null;        $model->save();        return $model;    }    public static function imageCheck()    {        return Storage::disk('public')->url('helper/check.png');    }    public static function imageUserNull()    {        return config('file-manager.placeholder');    }    public static function fileNull()    {        return Storage::disk('public')->url(config('file-manager.no_image'));    }    /**     * @param string $disk     * @return ManagerFile     */    public function setDisk(string $disk): ManagerFile    {        $this->disk = $disk;        return $this;    }    /**     * @param string $visibility     * @return ManagerFile     */    public function setVisibility(string $visibility): ManagerFile    {        $this->visibility = $visibility;        return $this;    }    /**     * @param string $path     * @return ManagerFile     */    public function setPath(string $path): ManagerFile    {        $this->path = $path;        return $this;    }    /**     * @param \Illuminate\Database\Eloquent\Model $object_instance     * @return ManagerFile     */    public function setObjectInstance($object_instance): ManagerFile    {        $this->object_instance = $object_instance;        return $this;    }    /**     * @param \Illuminate\Http\UploadedFile|mixed $file     * @return ManagerFile     */    public function setFile($file)    {        $this->file = $file;        return $this;    }    /**     * @param string $caption     * @return ManagerFile     */    public function setCaption(string $caption): ManagerFile    {        $this->caption = $caption;        return $this;    }    /**     * @param int|string $order     * @return ManagerFile     */    public function setOrder($order)    {        $this->order = $order;        return $this;    }    /**     * @param string $type     * @return ManagerFile     */    public function setType(string $type): ManagerFile    {        $this->type = $type;        return $this;    }    /**     * @param int|string|null $uploadBy     * @return ManagerFile     */    public function setUploadBy($uploadBy)    {        $this->uploadBy = $uploadBy;        return $this;    }}