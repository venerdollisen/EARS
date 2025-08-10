<?php
require_once BASE_PATH . '/core/Controller.php';
require_once BASE_PATH . '/models/UserModel.php';

class UserManagementController extends Controller {
    public function index() {
        $this->requireAuth();
        // Only admins/managers can access
        $current = $this->auth->getCurrentUser();
        if (!$current || ($current['role'] ?? 'user') === 'user') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }
        $userModel = new UserModel();
        $users = $userModel->getAllUsers();
        $this->render('users/index', ['users' => $users, 'user' => $this->auth->getCurrentUser()]);
    }

    public function assistants() {
        $this->requireAuth();
        $current = $this->auth->getCurrentUser();
        if (!$current || ($current['role'] ?? 'user') === 'user') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }
        $userModel = new UserModel();
        $assistants = $userModel->getUsersByRole('user');
        $this->render('users/assistants', ['users' => $assistants, 'user' => $this->auth->getCurrentUser()]);
    }

    public function create() {
        $this->requireAuth();
        $current = $this->auth->getCurrentUser();
        if (!$current || ($current['role'] ?? 'user') === 'user') { $this->jsonResponse(['error' => 'Forbidden'], 403); }
        $this->render('users/form', ['mode' => 'create', 'user' => $this->auth->getCurrentUser()]);
    }

    public function edit($id) {
        $this->requireAuth();
        $current = $this->auth->getCurrentUser();
        if (!$current || ($current['role'] ?? 'user') === 'user') { $this->jsonResponse(['error' => 'Forbidden'], 403); }
        $userModel = new UserModel();
        $record = $userModel->getUserById((int)$id);
        $this->render('users/form', ['mode' => 'edit', 'record' => $record, 'user' => $this->auth->getCurrentUser()]);
    }

    public function store() {
        $this->requireAuth();
        $current = $this->auth->getCurrentUser();
        if (!$current || ($current['role'] ?? 'user') === 'user') { $this->jsonResponse(['error' => 'Forbidden'], 403); }
        $data = $this->getRequestData();
        try {
            $userModel = new UserModel();
            $payload = [
                'username' => trim($data['username'] ?? ''),
                'password' => trim($data['password'] ?? ''),
                'full_name' => trim($data['full_name'] ?? ''),
                'email' => trim($data['email'] ?? ''),
                'role' => $data['role'] ?? 'user',
                'status' => $data['status'] ?? 'active'
            ];
            if (!$payload['username'] || !$payload['password']) {
                $this->jsonResponse(['error' => 'Username and password are required'], 400);
            }
            $id = $userModel->createUser($payload);
            $this->jsonResponse(['success' => true, 'id' => $id]);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    public function update($id) {
        $this->requireAuth();
        $current = $this->auth->getCurrentUser();
        if (!$current || ($current['role'] ?? 'user') === 'user') { $this->jsonResponse(['error' => 'Forbidden'], 403); }
        $data = $this->getRequestData();
        try {
            $userModel = new UserModel();
            $payload = [
                'username' => trim($data['username'] ?? ''),
                'password' => trim($data['password'] ?? ''),
                'full_name' => trim($data['full_name'] ?? ''),
                'email' => trim($data['email'] ?? ''),
                'role' => $data['role'] ?? 'user',
                'status' => $data['status'] ?? 'active'
            ];
            if (!$payload['username']) {
                $this->jsonResponse(['error' => 'Username is required'], 400);
            }
            $ok = $userModel->updateUser((int)$id, $payload);
            $this->jsonResponse(['success' => (bool)$ok]);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id) {
        $this->requireAuth();
        $current = $this->auth->getCurrentUser();
        if (!$current || ($current['role'] ?? 'user') === 'user') { $this->jsonResponse(['error' => 'Forbidden'], 403); }
        try {
            $userModel = new UserModel();
            $ok = $userModel->deleteUser((int)$id);
            $this->jsonResponse(['success' => (bool)$ok]);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
?>

