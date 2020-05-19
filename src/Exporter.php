<?php

namespace Tupy\FileManager;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Class Exporter
 * @package App\Services
 */
class Exporter
{
    /**
     * @var array
     */
    private $data;

    /**
     * @var string
     */
    private $filename;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $pathToSave;

    /**
     * @var string
     */
    private $fullFileName;

    /**
     * @var string
     */
    private $relativePath;

    /**
     * @var bool
     */
    private $fileNameWithTimestamp;

    /**
     * @var \Illuminate\Contracts\Filesystem\Filesystem
     */
    private $storage;

    /**
     * @var string
     */
    private $diskToSave;

    /**
     * Create a new job instance.
     *
     * @param array $data
     * @param string $filename
     * @param string $type
     * @param string $pathToSave
     * @param string $diskToSave
     * @param boolean $fileNameWithTimestamp
     *
     * Supported Type
     * Xlsx = Default
     * Xls
     * Ods
     * Csv
     */
    public function __construct(array $data, string $filename = 'export', $type = 'Xlsx', $fileNameWithTimestamp = false, $pathToSave = 'temp', $diskToSave = 'public')
    {
        $this->data = $data;

        if ($fileNameWithTimestamp) {
            $timestamp = now()->format('d_m_Y_H_i_s');
            $this->filename = "{$filename}_$timestamp";
        } else {
            $this->filename = $filename;
        }

        $this->type = ucfirst($type);
        $this->pathToSave = $pathToSave;
        $this->fullFileName = $this->filename . '.' . strtolower($this->type);
        $this->diskToSave = $diskToSave;

        //Cria o diretório (caso não existir) para salvar o arquivo
        Storage::disk($diskToSave)->makeDirectory($pathToSave);

        //Onde será salvo
        $this->relativePath = storage_path("app/{$diskToSave}/{$pathToSave}/{$this->fullFileName}");

//        \Log::info("Relative path: {$this->relativePath}");
//        \Log::info("Filename: {$this->fullFileName}");
    }

    public static function make(array $data, string $filename = 'export', $type = 'Xlsx', $fileNameWithTimestamp = false, $pathToSave = 'temp', $diskToSave = 'public')
    {
        return new Exporter($data, $filename, $type, $fileNameWithTimestamp, $pathToSave, $diskToSave);
    }

    /**
     * @return array|string
     * @throws \Exception
     */
    protected function export()
    {
        $spreadsheet = new Spreadsheet();

        $numberSheet = 0;

        $spreadsheet->getProperties()
            ->setCreator("Tupy")
            ->setLastModifiedBy('user')
            ->setTitle(config('app.name'))
            ->setSubject("Export Data")
            ->setDescription("Transforme os seus sonhos em metas!")
            ->setKeywords("invencível")
            ->setCategory("imparável");

        foreach ($this->data as $keySheet => $arrayData) {
            try {
                $spreadsheet->setActiveSheetIndex($numberSheet);
            } catch (Exception $e) {
                return $e;
            }

            try {
                $sheet = $spreadsheet->getActiveSheet();
            } catch (Exception $e) {
                return $e;
            }

            $forHeader = array_values($arrayData);

            $newLetter = 'A';

            //Get index to name header excel
            foreach ($forHeader as $key => $value) {
                foreach ($value as $hammerKey => $hammer) {
                    $coordinate = "{$newLetter}1";

                    // set the names of header cells
                    $sheet->setCellValue($coordinate, $hammerKey);

                    //Autosize in column
                    $sheet->getColumnDimension($newLetter)->setAutoSize(true);

                    $newLetter++;
                }
                break;
            }

            // Add some data
            try {
                $sheet->fromArray($arrayData, null, 'A2');
            } catch (Exception $e) {
                return $e;
            }

            // Rename worksheet
            $sheet->setTitle($keySheet);

            // Auto filter in columns sheet
            try {
                $sheet->setAutoFilter($sheet->calculateWorksheetDimension());
            } catch (Exception $e) {
                return $e;
            }

            try {
                $spreadsheet->createSheet();
            } catch (Exception $e) {
                return $e;
            }

            $numberSheet++;
        }

        try {
            $spreadsheet->setActiveSheetIndex(0);
        } catch (Exception $e) {
            throw new \Error($e->getMessage(), $e->getCode());
//            return $e;
        }


        try {
            $writer = IOFactory::createWriter($spreadsheet, $this->type);
        } catch (\PhpOffice\PhpSpreadsheet\Writer\Exception $e) {
            throw new \Error($e->getMessage(), $e->getCode());
//            return $e;
        }

        try {
            $writer->save($this->relativePath);
        } catch (\PhpOffice\PhpSpreadsheet\Writer\Exception $e) {
            throw new \Error($e->getMessage(), $e->getCode());
//            return $e;
        }

        if (file_exists($this->relativePath)) {
            return [
                'relativePath' => $this->relativePath,
                'disk' => $this->diskToSave,
                'name' => $this->fullFileName,
                'path' => $this->pathToSave,
            ];

        } else {
            throw new \Exception('Ficheiro não disponível', 404);
        }

    }

    public function toDownload($delete_after_download = true)
    {
        try {
            $result = self::export();
            $path = $result['relativePath'];
//            Log::debug("result export: {$path}");

            if ($delete_after_download) {
                return response()->download($path)->deleteFileAfterSend();
            }

            return response()->download($path);
        } catch (\Exception $e) {
//            dd($e->getMessage());
            Log::debug($e->getMessage());
            throw new \Error($e->getMessage(), $e->getCode());
//            return false;
        }
    }

    public function toPath($returnArray = false)
    {
        try {
            if ($returnArray) {
                $result = self::export();
                return isset($result['relativePath']) ? $result['relativePath'] : '';
            }
            return self::export();
        } catch (\Exception $e) {
//            dd($e->getMessage());
            Log::debug('Error', [$e->getMessage()]);
            throw new \Error($e->getMessage(), $e->getCode());
//            return false;
        }
    }

    public function toArrayPath()
    {
        try {
            return self::export();
        } catch (\Exception $e) {
            Log::debug('Error', [$e->getMessage()]);
            throw new \Error($e->getMessage(), $e->getCode());
        }
    }
}
