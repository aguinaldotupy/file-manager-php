<?php

namespace Tupy\FileManager\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Tupy\FileManager\Traits\HasFiles;

class TestModel extends Model
{
    use HasFiles;

    protected $table = 'test_models';
    protected $guarded = [];
}
