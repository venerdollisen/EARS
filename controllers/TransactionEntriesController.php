<?php

require_once 'core/Controller.php';
require_once 'models/TransactionModel.php';
require_once 'models/ChartOfAccountsModel.php';
require_once 'models/SupplierModel.php';
require_once 'models/ProjectModel.php';
require_once 'models/DepartmentModel.php';

class TransactionEntriesController extends Controller {
    
    public function __construct() {
        parent::__construct();
    }
    
    public function index() {
        $this->requireAuth();
        $this->render('transaction-entries/index');
    }
    
    public function cashReceipt() {
        $this->requireAuth();
        
        $chartOfAccountsModel = new ChartOfAccountsModel();
        $supplierModel = new SupplierModel();
        $transactionModel = new TransactionModel();
        $projectModel = new ProjectModel();
        $departmentModel = new DepartmentModel();
        
        // Get accounts for dropdown
        $accounts = $chartOfAccountsModel->getAllAccounts();
        
        // Get suppliers for subsidiary accounts
        $suppliers = $supplierModel->getAllSuppliers();
        
        // Get projects and departments for dropdowns
        $projects = $projectModel->getActiveProjects();
        $departments = $departmentModel->getActiveDepartments();
        
        // Get recent cash receipts
        $transactions = $transactionModel->getTransactionsByType('cash_receipt');
        
        $this->render('transaction-entries/cash-receipt', [
            'accounts' => $accounts,
            'suppliers' => $suppliers,
            'projects' => $projects,
            'departments' => $departments,
            'transactions' => $transactions,
            'user' => $this->auth->getCurrentUser()
        ]);
    }
    
    public function cashDisbursement() {
        $this->requireAuth();
        $this->render('transaction-entries/cash-disbursement');
    }
    
    public function disbursement() {
        $this->requireAuth();
        $this->render('transaction-entries/cash-disbursement');
    }
    
    public function checkDisbursement() {
        $this->requireAuth();
        $this->render('transaction-entries/cash-disbursement');
    }
} 