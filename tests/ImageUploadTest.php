<?php

namespace Tupy\FileManager\Tests;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use Tupy\FileManager\ManagerFile;
use Tupy\FileManager\Models\FileManager;
use Tupy\FileManager\Tests\Fixtures\TestModel;

class ImageUploadTest extends TestCase
{
    private TestModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->model = TestModel::create(['name' => 'Test Subject']);
    }

    public function test_image_upload_with_data_uri_png()
    {
        // 10x10 PNG
        $pngDataUri = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAYAAACNMs+9AAAACXBIWXMAAAsTAAALEwEAmpwYAAAAF0lEQVQYlWP8//8/AzGAiShVowqpphAA1RIDET0/PewAAAAASUVORK5CYII=';

        $managerFile = ManagerFile::make($this->model, $pngDataUri, [
            'disk' => 'public',
            'path' => 'avatars',
            'type' => 'avatar',
        ]);

        $fileManager = $managerFile->imageUpload();

        $this->assertInstanceOf(FileManager::class, $fileManager);
        $this->assertEquals('avatar', $fileManager->type);
        $this->assertEquals('image/png', $fileManager->mime_type);
        $this->assertEquals('png', $fileManager->extension);
        $this->assertGreaterThan(0, $fileManager->size);
        $this->assertEquals('avatars', $fileManager->path_storage);
        $this->assertEquals($this->model->id, $fileManager->fileable_id);
        $this->assertEquals(TestModel::class, $fileManager->fileable_type);

        Storage::disk('public')->assertExists($fileManager->full_name);
        $this->assertEquals(Storage::disk('public')->size($fileManager->full_name), $fileManager->size);
    }

    public function test_image_upload_with_uploaded_file_jpeg()
    {
        $uploadedFile = UploadedFile::fake()->image('profile.jpg', 150, 150);

        $managerFile = ManagerFile::make($this->model, $uploadedFile, [
            'disk' => 'public',
            'path' => 'profiles',
            'type' => 'profile',
        ]);

        $fileManager = $managerFile->imageUpload();

        $this->assertInstanceOf(FileManager::class, $fileManager);
        $this->assertEquals('profile', $fileManager->type);
        $this->assertEquals('image/jpeg', $fileManager->mime_type);
        $this->assertEquals('jpeg', $fileManager->extension);
        $this->assertGreaterThan(0, $fileManager->size);
        $this->assertEquals('profiles', $fileManager->path_storage);
        $this->assertEquals($this->model->id, $fileManager->fileable_id);
        $this->assertEquals(TestModel::class, $fileManager->fileable_type);

        Storage::disk('public')->assertExists($fileManager->full_name);
        $this->assertEquals(Storage::disk('public')->size($fileManager->full_name), $fileManager->size);
    }

    public function test_image_upload_with_closure_scaling_image()
    {
        $uploadedFile = UploadedFile::fake()->image('banner.jpg', 400, 200);

        $managerFile = ManagerFile::make($this->model, $uploadedFile, [
            'disk' => 'public',
            'path' => 'banners',
            'type' => 'banner',
        ]);

        $fileManager = $managerFile->imageUpload(function (ImageInterface $image) {
            return $image->scale(width: 100);
        });

        $this->assertInstanceOf(FileManager::class, $fileManager);
        Storage::disk('public')->assertExists($fileManager->full_name);

        $savedContent = Storage::disk('public')->get($fileManager->full_name);
        $manager = new ImageManager(new Driver());
        $savedImage = $manager->decode($savedContent);

        $this->assertEquals(100, $savedImage->width());
        $this->assertEquals(50, $savedImage->height());
    }

    public function test_image_upload_with_closure_returning_encoded_image()
    {
        $uploadedFile = UploadedFile::fake()->image('custom.jpg', 200, 200);

        $managerFile = ManagerFile::make($this->model, $uploadedFile, [
            'disk' => 'public',
            'path' => 'custom',
            'type' => 'custom',
        ]);

        $fileManager = $managerFile->imageUpload(function (ImageInterface $image) {
            return $image->encode(new JpegEncoder(quality: 60));
        });

        $this->assertInstanceOf(FileManager::class, $fileManager);
        Storage::disk('public')->assertExists($fileManager->full_name);
        $this->assertGreaterThan(0, $fileManager->size);
    }

    public function test_image_upload_without_saving_relation()
    {
        $uploadedFile = UploadedFile::fake()->image('temp.jpg', 100, 100);

        $managerFile = ManagerFile::make($this->model, $uploadedFile, [
            'disk' => 'public',
            'path' => 'temp',
            'filename' => 'explicit_name.jpeg',
        ]);

        $result = $managerFile->imageUpload(save_relation: false);

        $this->assertSame($managerFile, $result);
        Storage::disk('public')->assertExists('temp/explicit_name.jpeg');
        $this->assertEquals(0, FileManager::count());
    }

    public function test_image_upload_with_configured_driver()
    {
        config(['file-manager.image_driver' => Driver::class]);

        $uploadedFile = UploadedFile::fake()->image('driver_test.png', 50, 50);

        $managerFile = ManagerFile::make($this->model, $uploadedFile, [
            'disk' => 'public',
            'path' => 'drivers',
            'type' => 'driver_test',
        ]);

        $fileManager = $managerFile->imageUpload();

        $this->assertInstanceOf(FileManager::class, $fileManager);
        Storage::disk('public')->assertExists($fileManager->full_name);
    }

    public function test_image_upload_with_imagick_driver()
    {
        if (!extension_loaded('imagick')) {
            $this->markTestSkipped('The imagick extension is not available.');
        }

        config(['file-manager.image_driver' => \Intervention\Image\Drivers\Imagick\Driver::class]);

        $uploadedFile = UploadedFile::fake()->image('imagick_test.png', 50, 50);

        $managerFile = ManagerFile::make($this->model, $uploadedFile, [
            'disk' => 'public',
            'path' => 'imagick',
            'type' => 'imagick_test',
        ]);

        $fileManager = $managerFile->imageUpload();

        $this->assertInstanceOf(FileManager::class, $fileManager);
        Storage::disk('public')->assertExists($fileManager->full_name);
    }
}

