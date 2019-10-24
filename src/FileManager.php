<?php

namespace Tupy\FileManager;

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
    // protected $guarded = ['authenticatable_id', 'authenticatable_type'];
 	protected $fillable = [
        'fileable_id', 'fileable_type', 'type', 'file_name', 'mime_type', 'size', 'path_storage', 'disk', 'visibility', 'thumbnail', 'tags', 'observation', 'caption', 'order', 'origem', 'expiration_date'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    // protected $casts = [
    //     'login_at' => 'datetime',
    //     'logout_at' => 'datetime',
    // ];

    /**
     * Get the fileable entity that the file belongs to.
     */
    public function fileable()
    {
        return $this->morphTo();
    }
}