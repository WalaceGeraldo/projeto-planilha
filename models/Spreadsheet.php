<?php

require_once __DIR__ . '/../includes/PostgresDB.php';
require_once __DIR__ . '/User.php'; // Para usar User::find se necessário

class Spreadsheet {

    private static function db() {
        return PostgresDB::getInstance();
    }
    
    public static function getAll() {
        $db = self::db();
        $stmt = $db->query("SELECT id, name, owner_id, created_at, updated_at FROM spreadsheets ORDER BY updated_at DESC");
        return $stmt->fetchAll();
    }

    public static function find($id) {
        $db = self::db();
        $stmt = $db->query("SELECT id, name, owner_id, created_at, updated_at FROM spreadsheets WHERE id = ?", [$id]);
        return $stmt->fetch();
    }

    public static function create($name, $ownerId) {
        $newId = uniqid();
        $db = self::db();
        
        $sql = "INSERT INTO spreadsheets (id, name, owner_id, data, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())";
        // Inicializa com array vazio em JSON
        $db->query($sql, [$newId, $name, $ownerId, json_encode([])]);
        
        return $newId;
    }

    public static function getData($id) {
        $db = self::db();
        $stmt = $db->query("SELECT data FROM spreadsheets WHERE id = ?", [$id]);
        $row = $stmt->fetch();
        
        if ($row && $row['data']) {
            return json_decode($row['data'], true);
        }
        return [];
    }

    public static function saveData($id, $data) {
        $db = self::db();
        $jsonData = json_encode($data);
        
        $sql = "UPDATE spreadsheets SET data = ?, updated_at = NOW() WHERE id = ?";
        $db->query($sql, [$jsonData, $id]);
    }

    public static function rename($id, $newName) {
        $db = self::db();
        $sql = "UPDATE spreadsheets SET name = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $db->query($sql, [$newName, $id]);
        return $stmt->rowCount() > 0;
    }

    public static function delete($id) {
        $db = self::db();
        
        // Deletar histórico primeiro (opcional, se tiver constraint de FK cascade não precisa mas aqui não temos certeza)
        $db->query("DELETE FROM spreadsheet_history WHERE spreadsheet_id = ?", [$id]);
        
        $stmt = $db->query("DELETE FROM spreadsheets WHERE id = ?", [$id]);
        return $stmt->rowCount() > 0;
    }

    public static function logHistory($spreadsheetId, $userId, $actionDescription) {
        $db = self::db();
        $sql = "INSERT INTO spreadsheet_history (spreadsheet_id, user_id, action, timestamp) VALUES (?, ?, ?, NOW())";
        $db->query($sql, [$spreadsheetId, $userId, $actionDescription]);
    }

    public static function getHistory($spreadsheetId) {
        $db = self::db();
        // JOIN para pegar o nome do usuário
        $sql = "
            SELECT h.*, u.username as user 
            FROM spreadsheet_history h 
            LEFT JOIN users u ON h.user_id = u.id 
            WHERE h.spreadsheet_id = ? 
            ORDER BY h.timestamp DESC 
            LIMIT 50
        ";
        $stmt = $db->query($sql, [$spreadsheetId]);
        $history = $stmt->fetchAll();

        // Se usuário foi deletado ou não encontrado, 'user' será null. Podemos tratar isso.
        foreach ($history as &$entry) {
            if (!$entry['user']) {
                $entry['user'] = 'Desconhecido';
            }
        }
        return $history;
    }

    public static function getGlobalHistory() {
        $db = self::db();
        $sql = "
            SELECT h.*, u.username as user, s.name as spreadsheet 
            FROM spreadsheet_history h 
            LEFT JOIN users u ON h.user_id = u.id 
            LEFT JOIN spreadsheets s ON h.spreadsheet_id = s.id
            ORDER BY h.timestamp DESC 
            LIMIT 100
        ";
        $stmt = $db->query($sql);
        $history = $stmt->fetchAll();

        foreach ($history as &$entry) {
            if (!$entry['user']) $entry['user'] = 'Desconhecido';
            if (!$entry['spreadsheet']) $entry['spreadsheet'] = 'Planilha Excluída';
        }
        return $history;
    }
}
?>
