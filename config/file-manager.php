<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bucket default
    |--------------------------------------------------------------------------
    |
    */
    'no_image' => env('FILE_MANAGER_NO_IMAGE', '/images/noImage.png'),

    'no_file' => env('FILE_MANAGER_NO_FILE', '/images/noImage.png'),

    'placeholder' => env('FILE_MANAGER_PLACEHOLDER', 'https://placehold.it/160x160/c98959/ffffff/&text=D'),

    'path_model' => env('FILE_MANAGER_PATH_TO_MODELS', 'App\\Models\\'),

    'disk_default' => env('FILESYSTEM_DRIVER'),

    /*
    |--------------------------------------------------------------------------
    | Minutes expired temporary url
    |--------------------------------------------------------------------------
    |
    */
    'interval_temporary' => env('FILE_MANAGER_TIME_TEMPORARY', 5),

    'middleware' => ['auth']
];
