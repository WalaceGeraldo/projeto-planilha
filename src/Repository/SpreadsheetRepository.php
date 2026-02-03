<?php

namespace App\Repository;

use App\Config\Database;
use PDO;

class SpreadsheetRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll() {
        $sql = "
            SELECT s.id, s.name, s.owner_id, s.created_at, s.updated_at, u.username as owner_name
            FROM spreadsheets s
            LEFT JOIN users u ON s.owner_id = u.id
            ORDER BY s.updated_at DESC
        ";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT id, name, owner_id, created_at, updated_at FROM spreadsheets WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function create($name, $ownerId) {
        $newId = uniqid();
        $stmt = $this->db->prepare("INSERT INTO spreadsheets (id, name, owner_id, data, created_at, updated_at) VALUES (:id, :name, :owner_id, :data, NOW(), NOW())");
        $stmt->execute([
            'id' => $newId,
            'name' => $name,
            'owner_id' => $ownerId,
            'data' => json_encode([])
        ]);
        return $newId;
    }

    public function getData($id) {
        $stmt = $this->db->prepare("SELECT data FROM spreadsheets WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        
        if ($row && $row['data']) {
            return json_decode($row['data'], true);
        }
        return [];
    }

    public function saveData($id, $data) {
        $stmt = $this->db->prepare("UPDATE spreadsheets SET data = :data, updated_at = NOW() WHERE id = :id");
        $stmt->execute([
            'data' => json_encode($data),
            'id' => $id
        ]);
    }

    public function rename($id, $newName) {
        $stmt = $this->db->prepare("UPDATE spreadsheets SET name = :name, updated_at = NOW() WHERE id = :id");
        $stmt->execute([
            'name' => $newName,
            'id' => $id
        ]);
        return $stmt->rowCount() > 0;
    }

    public function delete($id) {
        $this->db->prepare("DELETE FROM spreadsheet_history WHERE spreadsheet_id = :id")->execute(['id' => $id]);
        $stmt = $this->db->prepare("DELETE FROM spreadsheets WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function logHistory($spreadsheetId, $userId, $action, $details = null) {
        $stmt = $this->db->prepare("INSERT INTO spreadsheet_history (spreadsheet_id, user_id, action, details, timestamp) VALUES (:sid, :uid, :action, :details, NOW())");
        $stmt->execute([
            'sid' => $spreadsheetId,
            'uid' => $userId,
            'action' => substr($action, 0, 50),
            'details' => $details
        ]);
    }

    public function getHistory($spreadsheetId) {
        $sql = "
            SELECT h.*, u.username as user 
            FROM spreadsheet_history h 
            LEFT JOIN users u ON h.user_id = u.id 
            WHERE h.spreadsheet_id = :sid 
            ORDER BY h.timestamp DESC 
            LIMIT 50
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['sid' => $spreadsheetId]);
        $history = $stmt->fetchAll();

        foreach ($history as &$entry) {
            if (!$entry['user']) $entry['user'] = 'Desconhecido';
        }
        return $history;
    }

    public function getGlobalHistory() {
        $sql = "
            SELECT h.*, u.username as user, s.name as spreadsheet 
            FROM spreadsheet_history h 
            LEFT JOIN users u ON h.user_id = u.id 
            LEFT JOIN spreadsheets s ON h.spreadsheet_id = s.id
            ORDER BY h.timestamp DESC 
            LIMIT 100
        ";
        $stmt = $this->db->query($sql);
        $history = $stmt->fetchAll();

        foreach ($history as &$entry) {
            if (!$entry['user']) $entry['user'] = 'Desconhecido';
            if (!$entry['spreadsheet']) $entry['spreadsheet'] = 'Planilha Excluída';
        }
        return $history;
    }
}
