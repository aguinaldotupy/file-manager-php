<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'namespace' => 'Tupy\FileManager\Http\Controllers',
    'prefix' => 'file-manager'
], function () {
    Route::GET('/download-file', 'FileManagerController@download')->name('fileManager.download.file');
    Route::GET('/download-album-zip', 'FileManagerController@downloadAlbum')->name('fileManager.download.album');
    Route::DELETE('/destroy', 'FileManagerController@destroy')->name('fileManager.destroy');
});

