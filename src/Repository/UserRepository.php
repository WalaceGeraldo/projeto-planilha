<?php

namespace App\Repository;

use App\Config\Database;
use Exception;
use PDO;

class UserRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM users");
        return $stmt->fetchAll();
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function findByUsername($username) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        return $stmt->fetch();
    }

    public function create($username, $password, $role = 'editor') {
        if ($this->findByUsername($username)) {
            throw new Exception("Usuário já existe.");
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->db->prepare("INSERT INTO users (username, password, role) VALUES (:username, :password, :role)");
        $stmt->execute([
            'username' => $username,
            'password' => $hashedPassword,
            'role' => $role
        ]);

        return $this->db->lastInsertId();
    }

    public function update($id, $username, $password = null, $role = 'editor') {
        $user = $this->find($id);
        if (!$user) return false;

        if ($password) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE users SET username = :username, password = :password, role = :role WHERE id = :id");
            $stmt->execute([
                'username' => $username,
                'password' => $hashedPassword,
                'role' => $role,
                'id' => $id
            ]);
        } else {
            $stmt = $this->db->prepare("UPDATE users SET username = :username, role = :role WHERE id = :id");
            $stmt->execute([
                'username' => $username,
                'role' => $role,
                'id' => $id
            ]);
        }
        return true;
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function authenticate($username, $password) {
        $user = $this->findByUsername($username);
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }
}
