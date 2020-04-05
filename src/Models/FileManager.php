<?php

namespace Tupy\FileManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
/**
 * Class FileManager
 * @package Webteam\FileManager\Models
 * @mixin \Illuminate\Database\Eloquent\Builder|Model
 * @property int $id
 * @property string $fileable_type
 * @property int $fileable_id
 * @property int $uploaded_by
 * @property string $type
 * @property string $full_name
 * @property string $name
 * @property string $mime_type
 * @property string $extension
 * @property int $size
 * @property string $path_storage
 * @property string $disk
 * @property string $visibility
 * @property string|null $thumbnail
 * @property string|null $tags
 * @property string|null $observation
 * @property int $order
 * @property string|null $caption
 * @property string|null $origin
 * @property string|null $true_timestamp
 * @property string|null $expiration_date
 * @property-read string $url
 */
class FileManager extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'file_manager';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
 	protected $fillable = [
        'fileable_id', 'fileable_type', 'uploaded_by', 'type', 'full_name', 'name', 'mime_type', 'extension', 'size', 'path_storage', 'disk', 'visibility', 'thumbnail', 'tags', 'observation', 'caption', 'order', 'origem', 'expiration_date', 'true_timestamp'
    ];

    /**
     * Get the fileable entity that the file belongs to.
     */
    public function fileable()
    {
        return $this->morphTo();
    }

    protected static function booted()
    {
        static::deleted(function ($file) {
            /** @var \Tupy\FileManager\Models\FileManager $file */
            $file->deleteFile();
        });
    }

    /**
     * @throws \Exception
     * @return \Tupy\FileManager\Models\FileManager
     */
    public function deleteFile()
    {
        $storage = Storage::disk($this->disk);

        if(! $storage->exists($this->full_name)){
            throw new \Exception("File {$this->name} not exist in disk {$this->disk}");
        }

        Storage::disk($this->disk)->delete($this->full_name);

        return $this;
    }

    public function getPrivateUrlAttribute()
    {
        if ($this->full_name && Storage::disk($this->disk)->exists($this->full_name)) {
            return Storage::disk($this->disk)->temporaryUrl($this->full_name, now()->addMinutes(5));
        }

        return false;
    }

    public function getUrlAttribute()
    {
        $storage = Storage::disk($this->disk);

        if ($this->full_name && $storage->exists($this->full_name)) {

            if ($this->visibility === 'private') {
                return $storage->temporaryUrl($this->full_name, now()->addMinutes(5));
            }

            return $storage->url($this->full_name);
        }

        return null;
    }
}
