<?php

namespace Tupy\FileManager\Tests;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Tupy\FileManager\Exporter;

class ExporterTest extends TestCase
{
    public function test_exporter_generates_valid_xlsx_with_phpspreadsheet_5()
    {
        $data = [
            'Users' => [
                [
                    'Nome' => 'Aguinaldo Tupy',
                    'Email' => 'aguinaldo.tupy@gmail.com',
                    'Perfil' => 'Admin',
                ],
                [
                    'Nome' => 'John Doe',
                    'Email' => 'john@example.com',
                    'Perfil' => 'User',
                ],
            ],
        ];

        $exporter = Exporter::make($data, 'users_export', 'Xlsx', false, 'temp', 'public');
        $result = $exporter->toArrayPath();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('relativePath', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertEquals('users_export.xlsx', $result['name']);
        $this->assertFileExists($result['relativePath']);

        // Read the generated spreadsheet with PhpSpreadsheet 5 to verify it's valid
        $spreadsheet = IOFactory::load($result['relativePath']);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertEquals('Users', $sheet->getTitle());
        $this->assertEquals('Nome', $sheet->getCell('A1')->getValue());
        $this->assertEquals('Email', $sheet->getCell('B1')->getValue());
        $this->assertEquals('Perfil', $sheet->getCell('C1')->getValue());
        $this->assertEquals('Aguinaldo Tupy', $sheet->getCell('A2')->getValue());
        $this->assertEquals('aguinaldo.tupy@gmail.com', $sheet->getCell('B2')->getValue());
        $this->assertEquals('John Doe', $sheet->getCell('A3')->getValue());

        // Cleanup
        if (file_exists($result['relativePath'])) {
            unlink($result['relativePath']);
        }
    }

    public function test_exporter_to_path_string()
    {
        $data = [
            'Products' => [
                [
                    'SKU' => 'PROD-1',
                    'Price' => 99.90,
                ],
            ],
        ];

        $exporter = Exporter::make($data, 'products_export', 'Xlsx', false, 'temp', 'public');
        $filePath = $exporter->toPath(returnArray: true);

        $this->assertIsString($filePath);
        $this->assertFileExists($filePath);

        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}
