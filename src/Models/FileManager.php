<?php

namespace Tupy\FileManager\Models;

use Illuminate\Database\Eloquent\Model;

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
        'fileable_id', 'fileable_type', 'type', 'full_name', 'name', 'mime_type', 'extension', 'size', 'path_storage', 'disk', 'visibility', 'thumbnail', 'tags', 'observation', 'caption', 'order', 'origem', 'expiration_date', 'true_timestamp'
    ];

    /**
     * Get the fileable entity that the file belongs to.
     */
    public function fileable()
    {
        return $this->morphTo();
    }
}
