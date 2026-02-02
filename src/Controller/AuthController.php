<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Exception;

class AuthController {
    private $userRepo;

    public function __construct() {
        $this->userRepo = new UserRepository();
    }

    public function handleRequest($action, $input) {
        try {
            switch ($action) {
                case 'login':
                    return $this->login($input);
                case 'register':
                    return $this->register($input);
                case 'logout':
                    return $this->logout();
                case 'check':
                    return $this->check();
                // Admin actions
                case 'list_users':
                case 'create_user':
                case 'update_user':
                case 'delete_user':
                    return $this->handleAdminActions($action, $input);
                default:
                    throw new Exception("Action not found");
            }
        } catch (Exception $e) {
            http_response_code(400);
            return ['error' => $e->getMessage()];
        }
    }

    private function login($input) {
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';
        
        $user = $this->userRepo->authenticate($username, $password);
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            return ['success' => true];
        } else {
            http_response_code(401);
            return ['error' => 'Usuário ou senha incorretos'];
        }
    }

    private function register($input) {
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';
        
        if (empty($username) || empty($password)) throw new Exception("Preencha todos os campos.");
        
        $this->userRepo->create($username, $password);
        return ['success' => true];
    }

    private function logout() {
        session_destroy();
        return ['success' => true, 'redirect' => 'login.php'];
    }

    private function check() {
        if (isset($_SESSION['user_id'])) {
            return ['logged_in' => true, 'username' => $_SESSION['username']];
        }
        return ['logged_in' => false];
    }

    private function handleAdminActions($action, $input) {
        if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? 'viewer') !== 'admin') {
            http_response_code(403);
            return ['error' => 'Acesso negado.'];
        }

        if ($action === 'list_users') {
            $users = $this->userRepo->getAll();
            // remove passwords
            return ['users' => array_map(function($u) { unset($u['password']); return $u; }, $users)];
        }

        if ($action === 'create_user') {
            $this->userRepo->create($input['username'] ?? '', $input['password'] ?? '', $input['role'] ?? 'editor');
            return ['success' => true];
        }

        if ($action === 'update_user') {
            $success = $this->userRepo->update($input['id'], $input['username'], $input['password'] ?? null, $input['role']);
            return $success ? ['success' => true] : ['error' => 'Usuário não encontrado.'];
        }

        if ($action === 'delete_user') {
            if ($input['id'] === $_SESSION['user_id']) throw new Exception("Você não pode se excluir.");
            $success = $this->userRepo->delete($input['id']);
            return $success ? ['success' => true] : ['error' => 'Erro ao excluir.'];
        }
    }
}
