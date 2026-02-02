<?php

require_once __DIR__ . '/../includes/PostgresDB.php';

class User {
    
    // helper para instanciar DB
    private static function db() {
        return PostgresDB::getInstance();
    }

    public static function getAll() {
        $db = self::db();
        $stmt = $db->query("SELECT * FROM users");
        return $stmt->fetchAll();
    }

    public static function find($id) {
        $db = self::db();
        $stmt = $db->query("SELECT * FROM users WHERE id = ?", [$id]);
        return $stmt->fetch(); // Retorna false se não achar
    }

    public static function findByUsername($username) {
        $db = self::db();
        $stmt = $db->query("SELECT * FROM users WHERE username = ?", [$username]);
        return $stmt->fetch();
    }

    public static function authenticate($username, $password) {
        $user = self::findByUsername($username);
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }

    public static function create($username, $password, $role = 'editor') {
        // Verifica se já existe
        if (self::findByUsername($username)) {
            throw new Exception("Usuário já existe.");
        }

        $newId = uniqid(); // Mantendo compatibilidade com IDs existentes
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $db = self::db();
        $sql = "INSERT INTO users (id, username, password, role) VALUES (?, ?, ?, ?)";
        $db->query($sql, [$newId, $username, $hashedPassword, $role]);

        return $newId;
    }

    public static function update($id, $username, $password = null, $role = 'editor') {
        $db = self::db();
        
        // Verifica se usuário existe
        $existing = self::find($id);
        if (!$existing) return false;

        if ($password) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET username = ?, password = ?, role = ? WHERE id = ?";
            $db->query($sql, [$username, $hashedPassword, $role, $id]);
        } else {
            $sql = "UPDATE users SET username = ?, role = ? WHERE id = ?";
            $db->query($sql, [$username, $role, $id]);
        }
        
        return true;
    }

    public static function delete($id) {
        $db = self::db();
        $stmt = $db->query("DELETE FROM users WHERE id = ?", [$id]);
        return $stmt->rowCount() > 0;
    }
}
?>
