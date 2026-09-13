<?php

namespace Tupy\FileManager\Tests;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tupy\FileManager\ManagerFile;
use Tupy\FileManager\Models\FileManager;
use Tupy\FileManager\Tests\Fixtures\TestModel;

class FileUploadTest extends TestCase
{
    private TestModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->model = TestModel::create(['name' => 'Document Owner']);
    }

    public function test_upload_file_with_fake_pdf()
    {
        $fakePdf = UploadedFile::fake()->create('terms.pdf', 120, 'application/pdf');

        $managerFile = ManagerFile::make($this->model, $fakePdf, [
            'disk' => 'public',
            'path' => 'documents',
            'type' => 'terms',
            'caption' => 'Terms of service',
        ]);

        $fileManager = $managerFile->uploadFile();

        $this->assertInstanceOf(FileManager::class, $fileManager);
        $this->assertEquals('terms', $fileManager->type);
        $this->assertEquals('application/pdf', $fileManager->mime_type);
        $this->assertEquals('pdf', $fileManager->extension);
        $this->assertGreaterThan(0, $fileManager->size);
        $this->assertEquals('documents', $fileManager->path_storage);
        $this->assertEquals('Terms of service', $fileManager->caption);
        $this->assertEquals($this->model->id, $fileManager->fileable_id);
        $this->assertEquals(TestModel::class, $fileManager->fileable_type);

        Storage::disk('public')->assertExists($fileManager->full_name);
    }

    public function test_upload_file_without_saving_relation()
    {
        $fakePdf = UploadedFile::fake()->create('contract.pdf', 80, 'application/pdf');

        $managerFile = ManagerFile::make($this->model, $fakePdf, [
            'disk' => 'public',
            'path' => 'contracts',
            'filename' => 'custom_contract.pdf',
        ]);

        $result = $managerFile->uploadFile(save_relation: false);

        $this->assertSame($managerFile, $result);
        Storage::disk('public')->assertExists('contracts/custom_contract.pdf');
        $this->assertEquals(0, FileManager::count());
    }
}
