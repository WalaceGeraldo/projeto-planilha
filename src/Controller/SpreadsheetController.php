<?php

namespace App\Controller;

use App\Repository\SpreadsheetRepository;
use App\Service\SpreadsheetService;
use Exception;

class SpreadsheetController {
    private $repo;
    private $service;
    private $userId;
    private $userRole;

    public function __construct() {
        $this->repo = new SpreadsheetRepository();
        $this->service = new SpreadsheetService($this->repo);
        
        $this->userId = $_SESSION['user_id'] ?? null;
        $this->userRole = $_SESSION['role'] ?? 'viewer';
    }

    public function handleRequest($action) {
        try {
            switch ($action) {
                case 'create':
                    return $this->create();
                case 'read':
                    return $this->read();
                case 'save':
                    return $this->save();
                case 'rename':
                    return $this->rename();
                case 'delete':
                    return $this->delete();
                case 'history':
                    return $this->history();
                case 'preview_import':
                    return $this->previewImport();
                case 'confirm_import':
                    return $this->confirmImport();
                case 'export_bulk':
                    return $this->exportBulk();
                default:
                    throw new Exception("Action not found");
            }
        } catch (Exception $e) {
            http_response_code(500);
            return ['error' => $e->getMessage()];
        }
    }

    private function create() {
        if ($this->userRole === 'viewer') throw new Exception("Permissão negada.");
        $input = json_decode(file_get_contents('php://input'), true);
        $name = $input['name'] ?? 'Nova Planilha';
        
        $id = $this->repo->create($name, $this->userId);
        $this->repo->logHistory($id, $this->userId, "Criou a planilha");
        
        return ['success' => true, 'id' => $id];
    }

    private function read() {
        $id = $_GET['id'] ?? null;
        if (!$id) throw new Exception("ID não fornecido.");
        
        $meta = $this->repo->find($id);
        if (!$meta) throw new Exception("Planilha não encontrada.");
        
        $dados = $this->repo->getData($id);
        return ['dados' => $dados, 'meta' => $meta];
    }

    private function save() {
        if ($this->userRole === 'viewer') throw new Exception("Apenas leitura.");
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        $dados = $input['dados'] ?? [];
        $msg = $input['message'] ?? 'Atualização automática';

        if (!$id) throw new Exception("ID não fornecido.");
        
        $this->repo->saveData($id, $dados);
        $this->repo->logHistory($id, $this->userId, "Salvou Alterações", $msg);
        
        return ['success' => true];
    }

    private function rename() {
        if ($this->userRole === 'viewer') throw new Exception("Permissão negada.");
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        $newName = $input['name'] ?? null;
        
        if (!$id || !$newName) throw new Exception("Dados inválidos.");
        
        if ($this->repo->rename($id, $newName)) {
            $this->repo->logHistory($id, $this->userId, "Renomeou Planilha", "Novo nome: $newName");
            return ['success' => true];
        }
        throw new Exception("Erro ao renomear.");
    }

    private function delete() {
        if ($this->userRole !== 'admin') throw new Exception("Apenas admin.");
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        
        if (!$id) throw new Exception("ID inválido.");
        
        if ($this->repo->delete($id)) {
            return ['success' => true];
        }
        throw new Exception("Erro ao excluir.");
    }

    private function history() {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $hist = $this->repo->getHistory($id);
            return ['history' => $hist];
        } else {
            if ($this->userRole !== 'admin') throw new Exception("Acesso restrito.");
            $hist = $this->repo->getGlobalHistory();
            return ['history' => $hist];
        }
    }

    private function previewImport() {
        $result = $this->service->processImportPreview($_FILES['file'] ?? null);
        return array_merge(['success' => true], $result);
    }

    private function confirmImport() {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $this->service->processImportConfirmation(
            $data['temp_file'], 
            $data['original_name'] ?? 'Importado', 
            $data['sheets'] ?? [], 
            $this->userId
        );
        return ['success' => true, 'id' => $id];
    }

    private function exportBulk() {
        if ($this->userRole !== 'admin') throw new Exception("Apenas admin.");
        $ids = json_decode($_POST['ids'] ?? '[]', true);
        if (empty($ids)) throw new Exception("Nada selecionado.");
        
        $zipFile = $this->service->exportBulk($ids);
        
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="exportacao_planilhas.zip"');
        header('Content-Length: ' . filesize($zipFile));
        readfile($zipFile);
        @unlink($zipFile);
        exit; // Binary file output, exit directly
    }
}
