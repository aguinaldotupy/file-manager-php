# Laravel File Manager (`tupy/filemanager`)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/tupy/filemanager.svg?style=flat-square)](https://packagist.org/packages/tupy/filemanager)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![PHP Version](https://img.shields.io/badge/PHP-%5E8.3-blue.svg?style=flat-square)](https://www.php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-11%20%7C%2012%20%7C%2013-red.svg?style=flat-square)](https://laravel.com)

A lightweight Laravel package for managing file and image uploads linked to Eloquent models through a polymorphic database table, powered by **Intervention Image v4** and **PhpSpreadsheet v5**.

---

> [!TIP]
> ### 💡 Recommended Alternative: Spatie Media Library
> For **new projects** or complex media handling requirements, we strongly recommend considering [**Spatie Laravel Media Library**](https://github.com/spatie/laravel-medialibrary) (`spatie/laravel-medialibrary`).
>
> Spatie's library is the established community standard for Laravel media management, offering extensive features such as responsive images, multi-file conversions, custom collections, and active ecosystem support.
>
> **`tupy/filemanager`** continues to be maintained primarily for legacy and existing applications (such as Vendamee) and lightweight use cases that rely specifically on this morph table structure and built-in spreadsheet export capabilities.

---

## Features

- 📸 **Image Processing with Intervention Image 4.3**: Native GD and Imagick drivers with support for modern encoding, transformation, scaling, and Data URI decoding.
- 📁 **Polymorphic Storage**: Associate uploaded files and images with any Eloquent model using the `HasFiles` trait.
- 🔄 **Custom Image Transformation Closures**: Hook into the upload pipeline with full access to `Intervention\Image\Interfaces\ImageInterface`.
- 📊 **Spreadsheet Exporting**: Built-in `Exporter` utility powered by `phpoffice/phpspreadsheet` (v5) to quickly generate XLSX, CSV, and ODS files.
- 🗄️ **Storage Flexibility**: Fully integrates with Laravel Filesystem disks and visibility levels.

---

## Requirements

- **PHP**: `^8.3`
- **Laravel / Illuminate**: `^11.0`, `^12.0`, or `^13.0`
- **Intervention Image**: `^4.3`
- **PhpSpreadsheet**: `^5.0`
- **PHP Extensions**: `ext-zip`, `ext-mbstring`, and `ext-gd` or `ext-imagick`

---

## Installation

Install the package via Composer:

```bash
composer require tupy/filemanager
```

### Publish Configuration and Migrations

Publish the configuration file:

```bash
php artisan vendor:publish --tag=file-manager-config
```

Publish the database migration for the `file_manager` table:

```bash
php artisan vendor:publish --tag=file-manager-migrations
```

Run the database migrations:

```bash
php artisan migrate
```

---

## Configuration

The published configuration file is located at `config/file-manager.php`:

```php
return [
    'no_image' => env('FILE_MANAGER_NO_IMAGE', '/images/noImage.png'),
    'no_file' => env('FILE_MANAGER_NO_FILE', '/images/noImage.png'),
    'placeholder' => env('FILE_MANAGER_PLACEHOLDER', 'https://placehold.it/160x160/c98959/ffffff/&text=D'),
    'path_model' => env('FILE_MANAGER_PATH_TO_MODELS', 'App\\Models\\'),
    'disk_default' => env('FILESYSTEM_DRIVER'),

    // Intervention Image 4 Driver: GD (default) or Imagick
    'image_driver' => env('FILE_MANAGER_IMAGE_DRIVER', \Intervention\Image\Drivers\Gd\Driver::class),

    'interval_temporary' => env('FILE_MANAGER_TIME_TEMPORARY', 5),
    'middleware' => ['auth'],
];
```

To use Imagick instead of GD, add the following to your `.env`:

```env
FILE_MANAGER_IMAGE_DRIVER="Intervention\Image\Drivers\Imagick\Driver"
```

---

## Usage

### 1. Preparing your Model

Add the `HasFiles` trait to any Eloquent model you want to attach files to:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Tupy\FileManager\Traits\HasFiles;

class User extends Model
{
    use HasFiles;
}
```

### 2. Uploading Images

Use `ManagerFile::make()` followed by `imageUpload()`:

```php
use Tupy\FileManager\ManagerFile;

$user = User::find(1);
$file = $request->file('avatar'); // or a data:image/... URI or file path

$fileRecord = ManagerFile::make($user, $file, [
    'disk' => 'public',
    'path' => 'avatars',
    'type' => 'avatar',
    'caption' => 'User profile picture',
])->imageUpload();
```

#### Using Transformation Closures (Intervention Image v4)

You can pass a closure to `imageUpload()` to manipulate the image before it is stored. The closure receives an `Intervention\Image\Interfaces\ImageInterface` instance:

```php
use Intervention\Image\Interfaces\ImageInterface;

// Option A: Modify and return ImageInterface (auto-encoded by file extension)
$fileRecord = ManagerFile::make($user, $file, [
    'disk' => 'public',
    'path' => 'avatars',
    'type' => 'avatar',
])->imageUpload(function (ImageInterface $image) {
    return $image->scale(width: 300);
});

// Option B: Custom encode and return EncodedImageInterface
use Intervention\Image\Encoders\JpegEncoder;

$fileRecord = ManagerFile::make($user, $file, [
    'disk' => 'public',
    'path' => 'banners',
    'type' => 'banner',
])->imageUpload(function (ImageInterface $image) {
    return $image->scaleDown(width: 1200)
                 ->encode(new JpegEncoder(quality: 85));
});
```

### 3. Uploading Generic Files (PDF, ZIP, Documents)

For documents that do not require image processing:

```php
use Tupy\FileManager\ManagerFile;

$fileRecord = ManagerFile::make($user, $request->file('contract'), [
    'disk' => 'public',
    'path' => 'documents',
    'type' => 'contract',
    'caption' => 'Employment Contract',
])->uploadFile();
```

### 4. Accessing Attached Files

Models using `HasFiles` gain convenient relationships and helper methods:

```php
// Retrieve all files attached to the model
$files = $user->files;

// Retrieve the first file attached to the model
$firstFile = $user->file;

// Retrieve URL of a file filtered by type
$avatarUrl = $user->profile('avatar');
```

### 5. Exporting Spreadsheets

Use the built-in `Exporter` to generate spreadsheets from array data:

```php
use Tupy\FileManager\Exporter;

$data = [
    'Users' => [
        ['Name' => 'Alice', 'Email' => 'alice@example.com'],
        ['Name' => 'Bob', 'Email' => 'bob@example.com'],
    ],
];

// Generate XLSX and get file information
$result = Exporter::make($data, 'users_export', 'Xlsx')->toArrayPath();

// Download response with auto-delete after download
return Exporter::make($data, 'users_export')->toDownload(delete_after_download: true);
```

---

## Testing

Run the test suite using PHPUnit:

```bash
composer test
```

Or execute directly:

```bash
./vendor/bin/phpunit
```

---

## Changelog & Upgrades

Please see [CHANGELOG.md](CHANGELOG.md) for details on changes and [UPGRADE.md](UPGRADE.md) for detailed instructions on migrating from `3.x` to `4.0`.

---

## Contributing

Contributions are welcome! Please review [CONTRIBUTING.md](CONTRIBUTING.md) for details.

---

## Security

If you discover any security-related issues, please review our [Security Policy](SECURITY.md) before opening a public issue.

---

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
