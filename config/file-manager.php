<?php

return [

	/*
    |--------------------------------------------------------------------------
    | Bucket default 
    |--------------------------------------------------------------------------
    |
    */

    'no_image' => env('FILE_MANAGER_NO_IMAGE', '/helper/noImage.png'),

    'no_file' => env('FILE_MANAGER_NO_FILE', '/helper/noImage.png'),

    'placeholder' => env('FILE_MANAGER_PLACEHOLDER', 'https://placehold.it/160x160/00a65a/ffffff/&text==/'),

    'path_to_models' => env('FILE_MANAGER_PATH_TO_MODELS', 'App\\Models\\'),


	/*
    |--------------------------------------------------------------------------
    | Minutes expired temporary url 
    |--------------------------------------------------------------------------
    |
    */

    'interval_temporary' => env('FILE_MANAGER_TIME_TEMPORARY', 5);

];