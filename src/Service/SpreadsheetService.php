<?php

namespace App\Service;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet as ExcelDoc;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Repository\SpreadsheetRepository;
use ZipArchive;
use Exception;

class SpreadsheetService {
    private $repo;

    public function __construct(SpreadsheetRepository $repo) {
        $this->repo = $repo;
    }

    public function processImportPreview($file) {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Erro no upload.');
        }

        $tempDir = __DIR__ . '/../../temp_imports/';
        if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $tempFile = $tempDir . uniqid('import_') . '.' . $ext;
        
        if (!move_uploaded_file($file['tmp_name'], $tempFile)) {
            throw new Exception("Erro ao salvar arquivo temporário.");
        }

        $reader = IOFactory::createReaderForFile($tempFile);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($tempFile);
        
        $sheets = $spreadsheet->getSheetNames();
        
        return [
            'temp_file' => basename($tempFile),
            'original_name' => pathinfo($file['name'], PATHINFO_FILENAME),
            'sheets' => $sheets
        ];
    }

    public function processImportConfirmation($tempFileName, $originalName, $selectedSheets, $userId) {
        $tempFile = __DIR__ . '/../../temp_imports/' . basename($tempFileName);
        
        if (!file_exists($tempFile)) throw new Exception("Arquivo temporário expirou ou não existe.");
        
        $reader = IOFactory::createReaderForFile($tempFile);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($tempFile);
        
        $importedData = [];
        
        foreach ($selectedSheets as $sheetName) {
            $sheet = $spreadsheet->getSheetByName($sheetName);
            if ($sheet) {
                $importedData[$sheetName] = $sheet->toArray(null, true, true, true);
            }
        }
        
        $id = $this->repo->create($originalName, $userId);
        $this->repo->saveData($id, $importedData);
        $this->repo->logHistory($id, $userId, "Importou arquivo '$originalName'");
        
        @unlink($tempFile);
        return $id;
    }

    public function exportBulk($ids) {
        $zipFile = sys_get_temp_dir() . '/export_' . uniqid() . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE) !== TRUE) {
            throw new Exception("Erro ao criar ZIP.");
        }
        
        foreach ($ids as $id) {
            $meta = $this->repo->find($id);
            if (!$meta) continue;
            
            $data = $this->repo->getData($id);
            
            $spreadsheet = new ExcelDoc();
            $spreadsheet->removeSheetByIndex(0);
            
            if (empty($data)) {
                 $spreadsheet->createSheet()->setTitle('Vazia');
            } else {
                foreach ($data as $sheetName => $rows) {
                    $sheet = $spreadsheet->createSheet();
                    $validName = substr($sheetName, 0, 31);
                    $sheet->setTitle($validName);
                    
                    if (is_array($rows)) {
                        foreach ($rows as $rIdx => $cols) {
                            foreach ($cols as $cIdx => $val) {
                                $sheet->setCellValue($cIdx . $rIdx, $val);
                            }
                        }
                    }
                }
            }
            
            $writer = new Xlsx($spreadsheet);
            $tempXls = sys_get_temp_dir() . '/' . $id . '.xlsx';
            $writer->save($tempXls);
            
            $zip->addFile($tempXls, $meta['name'] . '.xlsx');
        }
        
        $zip->close();
        return $zipFile;
    }
}
