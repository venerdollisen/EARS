<?php
require_once BASE_PATH . '/models/AccountTitleGroupModel.php';
require_once BASE_PATH . '/models/COAAccountTypeModel.php';
require_once BASE_PATH . '/models/ChartOfAccountsModel.php';
require_once BASE_PATH . '/models/SupplierModel.php';
require_once BASE_PATH . '/models/ProjectModel.php';
require_once BASE_PATH . '/models/DepartmentModel.php';

class FileMaintenanceController extends Controller {
    
    public function index() {
        $this->requireAuth();
        $this->requirePermission('file_maintenance');
        
        $this->render('file-maintenance/index', [
            'user' => $this->auth->getCurrentUser()
        ]);
    }
    
    public function accountTitleGroup() {
        $this->requireAuth();
        $this->requirePermission('file_maintenance');
        
        $accountTitleGroupModel = new AccountTitleGroupModel();
        $groups = $accountTitleGroupModel->getAllGroups();
        
        $this->render('file-maintenance/account-title-group', [
            'groups' => $groups,
            'user' => $this->auth->getCurrentUser()
        ]);
    }
    
    public function coaAccountType() {
        $this->requireAuth();
        $this->requirePermission('file_maintenance');
        
        $coaAccountTypeModel = new COAAccountTypeModel();
        $types = $coaAccountTypeModel->getAllTypes();
        
        $this->render('file-maintenance/coa-account-type', [
            'types' => $types,
            'user' => $this->auth->getCurrentUser()
        ]);
    }
    
    public function chartOfAccounts() {
        $this->requireAuth();
        $this->requirePermission('file_maintenance');
        
        $chartOfAccountsModel = new ChartOfAccountsModel();
        $coaAccountTypeModel = new COAAccountTypeModel();
        $accountTitleGroupModel = new AccountTitleGroupModel();
        
        $accounts = $chartOfAccountsModel->getAllAccounts();
        $accountTypes = $coaAccountTypeModel->getAllTypes();
        $accountGroups = $accountTitleGroupModel->getAllGroups();
        
        $this->render('file-maintenance/chart-of-accounts', [
            'accounts' => $accounts,
            'accountTypes' => $accountTypes,
            'accountGroups' => $accountGroups,
            'user' => $this->auth->getCurrentUser()
        ]);
    }
    
    public function subsidiaryAccount() {
        $this->requireAuth();
        $this->requirePermission('file_maintenance');
        
        $supplierModel = new SupplierModel();
        $chartOfAccountsModel = new ChartOfAccountsModel();
        
        $suppliers = $supplierModel->getAllSuppliers();
        $accounts = $chartOfAccountsModel->getAllAccounts();
        
        $this->render('file-maintenance/subsidiary-account', [
            'suppliers' => $suppliers,
            'accounts' => $accounts,
            'user' => $this->auth->getCurrentUser()
        ]);
    }
    
    public function projects() {
        $this->requireAuth();
        $this->requirePermission('file_maintenance');
        
        $projectModel = new ProjectModel();
        $projects = $projectModel->getAllProjects();
        
        $this->render('file-maintenance/projects', [
            'projects' => $projects,
            'user' => $this->auth->getCurrentUser()
        ]);
    }
    
    public function departments() {
        $this->requireAuth();
        $this->requirePermission('file_maintenance');
        
        $departmentModel = new DepartmentModel();
        $departments = $departmentModel->getAllDepartments();
        
        $this->render('file-maintenance/departments', [
            'departments' => $departments,
            'user' => $this->auth->getCurrentUser()
        ]);
    }
    
    public function save() {
        $this->requireAuth();
        $this->requirePermission('file_maintenance');
        
        $data = $this->getRequestData();
        
        if (!isset($data['action']) || !isset($data['table'])) {
            $this->jsonResponse(['error' => 'Invalid request data'], 400);
        }
        
        try {
            switch ($data['table']) {
                case 'account_title_groups':
                    $this->saveAccountTitleGroup($data);
                    break;
                case 'coa_account_types':
                    $this->saveCoaAccountType($data);
                    break;
                case 'chart_of_accounts':
                    $this->saveChartOfAccounts($data);
                    break;
                case 'suppliers':
                    $this->saveSupplier($data);
                    break;
                case 'projects':
                    $this->saveProject($data);
                    break;
                case 'departments':
                    $this->saveDepartment($data);
                    break;
                default:
                    throw new Exception('Invalid table');
            }
            
            $this->jsonResponse(['success' => true, 'message' => 'Data saved successfully']);
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Failed to save data: ' . $e->getMessage()], 500);
        }
    }
    
    private function saveAccountTitleGroup($data) {
        $accountTitleGroupModel = new AccountTitleGroupModel();
        
        if ($data['action'] === 'create') {
            $accountTitleGroupModel->createGroup([
                'group_name' => $data['group_name'],
                'description' => $data['description']
            ]);
        } else {
            $accountTitleGroupModel->updateGroup($data['id'], [
                'group_name' => $data['group_name'],
                'description' => $data['description']
            ]);
        }
    }
    
    private function saveCoaAccountType($data) {
        $coaAccountTypeModel = new COAAccountTypeModel();
        
        if ($data['action'] === 'create') {
            $coaAccountTypeModel->createType([
                'type_name' => $data['type_name'],
                'description' => $data['description']
            ]);
        } else {
            $coaAccountTypeModel->updateType($data['id'], [
                'type_name' => $data['type_name'],
                'description' => $data['description']
            ]);
        }
    }
    
    private function saveChartOfAccounts($data) {
        $chartOfAccountsModel = new ChartOfAccountsModel();
        
        if ($data['action'] === 'create') {
            $chartOfAccountsModel->createAccount([
                'account_code' => $data['account_code'],
                'account_name' => $data['account_name'],
                'account_type_id' => $data['account_type_id'],
                'group_id' => $data['group_id'],
                'description' => $data['description']
            ]);
        } elseif ($data['action'] === 'update') {
            $chartOfAccountsModel->updateAccount($data['id'], [
                'account_code' => $data['account_code'],
                'account_name' => $data['account_name'],
                'account_type_id' => $data['account_type_id'],
                'group_id' => $data['group_id'],
                'description' => $data['description']
            ]);
        } elseif ($data['action'] === 'delete') {
            // Redirect to delete method for proper handling
            $this->deleteChartOfAccounts($data['id']);
        } else {
            throw new Exception('Invalid action');
        }
    }
    
    private function saveSupplier($data) {
        $supplierModel = new SupplierModel();
        
        if ($data['action'] === 'create') {
            $supplierModel->createSupplier([
                'supplier_name' => $data['supplier_name'],
                'contact_person' => $data['contact_person'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'address' => $data['address'],
                'vat_subject' => $data['vat_subject'] ?? 'VAT',
                'tin' => $data['tin'] ?? null,
                'vat_rate' => $data['vat_rate'] ?? 12.00,
                'vat_account_id' => $data['vat_account_id'] ?? null,
                'account_id' => $data['account_id']
            ]);
        } else {
            $supplierModel->updateSupplier($data['id'], [
                'supplier_name' => $data['supplier_name'],
                'contact_person' => $data['contact_person'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'address' => $data['address'],
                'vat_subject' => $data['vat_subject'] ?? 'VAT',
                'tin' => $data['tin'] ?? null,
                'vat_rate' => $data['vat_rate'] ?? 12.00,
                'vat_account_id' => $data['vat_account_id'] ?? null,
                'account_id' => $data['account_id']
            ]);
        }
    }
    
    public function delete() {
        $this->requireAuth();
        $this->requirePermission('file_maintenance');
        
        $data = $this->getRequestData();
        
        if (!isset($data['table']) || !isset($data['id'])) {
            $this->jsonResponse(['error' => 'Invalid request data'], 400);
        }
        
        try {
            switch ($data['table']) {
                case 'account_title_groups':
                    $this->deleteAccountTitleGroup($data['id']);
                    break;
                case 'coa_account_types':
                    $this->deleteCoaAccountType($data['id']);
                    break;
                case 'chart_of_accounts':
                    $this->deleteChartOfAccounts($data['id']);
                    break;
                case 'suppliers':
                    $this->deleteSupplier($data['id']);
                    break;
                case 'projects':
                    $this->deleteProject($data['id']);
                    break;
                case 'departments':
                    $this->deleteDepartment($data['id']);
                    break;
                default:
                    throw new Exception('Invalid table');
            }
            
            $this->jsonResponse(['success' => true, 'message' => 'Data deleted successfully']);
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Failed to delete data: ' . $e->getMessage()], 500);
        }
    }
    
    private function deleteAccountTitleGroup($id) {
        $accountTitleGroupModel = new AccountTitleGroupModel();
        $accountTitleGroupModel->deleteGroup($id);
    }
    
    private function deleteCoaAccountType($id) {
        $coaAccountTypeModel = new COAAccountTypeModel();
        $coaAccountTypeModel->deleteType($id);
    }
    
    private function deleteChartOfAccounts($id) {
        $chartOfAccountsModel = new ChartOfAccountsModel();
        $chartOfAccountsModel->deleteAccount($id);
    }
    
    private function deleteSupplier($id) {
        $supplierModel = new SupplierModel();
        $supplierModel->deleteSupplier($id);
    }
    
    private function saveProject($data) {
        $projectModel = new ProjectModel();
        
        if ($data['action'] === 'create') {
            $projectModel->createProject([
                'project_code' => $data['project_code'],
                'project_name' => $data['project_name'],
                'description' => $data['description'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'budget' => $data['budget'],
                'manager' => $data['manager'],
                'status' => $data['status']
            ]);
        } elseif ($data['action'] === 'update') {
            $projectModel->updateProject($data['id'], [
                'project_code' => $data['project_code'],
                'project_name' => $data['project_name'],
                'description' => $data['description'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'budget' => $data['budget'],
                'manager' => $data['manager'],
                'status' => $data['status']
            ]);
        } elseif ($data['action'] === 'delete') {
            $this->deleteProject($data['id']);
        } else {
            throw new Exception('Invalid action');
        }
    }
    
    private function saveDepartment($data) {
        $departmentModel = new DepartmentModel();
        
        if ($data['action'] === 'create') {
            $departmentModel->createDepartment([
                'department_code' => $data['department_code'],
                'department_name' => $data['department_name'],
                'description' => $data['description'],
                'manager' => $data['manager'],
                'location' => $data['location'],
                'status' => $data['status']
            ]);
        } elseif ($data['action'] === 'update') {
            $departmentModel->updateDepartment($data['id'], [
                'department_code' => $data['department_code'],
                'department_name' => $data['department_name'],
                'description' => $data['description'],
                'manager' => $data['manager'],
                'location' => $data['location'],
                'status' => $data['status']
            ]);
        } elseif ($data['action'] === 'delete') {
            $this->deleteDepartment($data['id']);
        } else {
            throw new Exception('Invalid action');
        }
    }
    
    private function deleteProject($id) {
        $projectModel = new ProjectModel();
        $projectModel->deleteProject($id);
    }
    
    private function deleteDepartment($id) {
        $departmentModel = new DepartmentModel();
        $departmentModel->deleteDepartment($id);
    }
    
    public function getProject($id) {
        $this->requireAuth();
        $this->requirePermission('file_maintenance');
        
        try {
            $projectModel = new ProjectModel();
            $project = $projectModel->getProjectById($id);
            
            if ($project) {
                $this->jsonResponse(['success' => true, 'data' => $project]);
            } else {
                $this->jsonResponse(['error' => 'Project not found'], 404);
            }
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Failed to get project: ' . $e->getMessage()], 500);
        }
    }
    
    public function getDepartment($id) {
        $this->requireAuth();
        $this->requirePermission('file_maintenance');
        
        try {
            $departmentModel = new DepartmentModel();
            $department = $departmentModel->getDepartmentById($id);
            
            if ($department) {
                $this->jsonResponse(['success' => true, 'data' => $department]);
            } else {
                $this->jsonResponse(['error' => 'Department not found'], 404);
            }
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Failed to get department: ' . $e->getMessage()], 500);
        }
    }
}
?> 