<?php
require_once BASE_PATH . '/core/Controller.php';
require_once BASE_PATH . '/models/JournalEntryModel.php';
require_once BASE_PATH . '/models/TransactionValidationModel.php';

class JournalEntriesController extends Controller {
    
    private $journalEntryModel;
    private $validationModel;
    
    public function __construct() {
        parent::__construct();
        $this->journalEntryModel = new JournalEntryModel();
        $this->validationModel = new TransactionValidationModel($this->db);
    }
    
    public function index() {
        $this->requireAuth();
        
        $journalEntries = $this->journalEntryModel->getAllJournalEntries();
        
        $this->render('journal-entries/index', [
            'journalEntries' => $journalEntries,
            'user' => $this->auth->getCurrentUser()
        ]);
    }
    
    public function create() {
        $this->requireAuth();
        
        // Load required data for the form
        require_once BASE_PATH . '/models/ChartOfAccountsModel.php';
        require_once BASE_PATH . '/models/ProjectModel.php';
        require_once BASE_PATH . '/models/DepartmentModel.php';
        require_once BASE_PATH . '/models/SupplierModel.php';
        
        $chartOfAccountsModel = new ChartOfAccountsModel();
        $projectModel = new ProjectModel();
        $departmentModel = new DepartmentModel();
        $supplierModel = new SupplierModel();
        
        $this->render('journal-entries/create', [
            'user' => $this->auth->getCurrentUser(),
            'accounts' => $chartOfAccountsModel->getAllAccounts(),
            'projects' => $projectModel->getAllProjects(),
            'departments' => $departmentModel->getAllDepartments(),
            'suppliers' => $supplierModel->getAllSuppliers()
        ]);
    }
    
    public function view($id) {
        $this->requireAuth();
        
        $journalEntry = $this->journalEntryModel->getJournalEntry($id);
        
        if (!$journalEntry) {
            $this->redirect('/journal-entries');
            return;
        }
        
        $this->render('journal-entries/view', [
            'journalEntry' => $journalEntry,
            'user' => $this->auth->getCurrentUser()
        ]);
    }
    
    public function save() {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
            return;
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Prepare distributions for validation
            $distributions = [];
            foreach ($input['entries'] as $entry) {
                $distributions[] = [
                    'account_id' => $entry['account_id'],
                    'payment_type' => $entry['transaction_type'], // debit or credit
                    'amount' => $entry['amount']
                ];
            }
            
            // Validate using accounting principles
            $validationResult = $this->validationModel->validateTransactionDistributions($distributions);
            
            if (!$validationResult['valid']) {
                $this->jsonResponse([
                    'error' => 'Accounting validation failed',
                    'details' => $validationResult['errors'],
                    'warnings' => $validationResult['warnings']
                ], 400);
                return;
            }
            
            // Show warnings if any (but allow transaction to proceed)
            if (!empty($validationResult['warnings'])) {
                $this->jsonResponse([
                    'warning' => 'Journal entry has warnings but is valid',
                    'warnings' => $validationResult['warnings'],
                    'message' => 'Please review the warnings before proceeding'
                ], 200);
                return;
            }
            
            // Prepare data for saving
            $data = [
                'reference_no' => $input['reference_no'],
                'transaction_date' => $input['transaction_date'],
                'description' => $input['description'],
                'total_amount' => array_sum(array_column($input['entries'], 'amount')),
                'jv_status' => $input['jv_status'],
                'for_posting' => $input['for_posting'],
                'reference_number1' => $input['reference_number'] ?? null,
                'reference_number2' => null,
                'cwo_number' => null,
                'bill_invoice_ref' => $input['bill_invoice_ref'] ?? null,
                'created_by' => $this->auth->getCurrentUser()['id'],
                'entries' => $input['entries']
            ];
            
            // Save journal entry
            $journalEntryId = $this->journalEntryModel->createJournalEntry($data);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Journal entry created successfully',
                'journal_entry_id' => $journalEntryId,
                'reference_no' => $data['reference_no']
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Failed to create journal entry: ' . $e->getMessage()], 500);
        }
    }
    
    public function approve($id) {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
            return;
        }
        
        try {
            $result = $this->journalEntryModel->approveJournalEntry($id, $this->auth->getCurrentUser()['id']);
            
            if ($result) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Journal entry approved successfully'
                ]);
            } else {
                $this->jsonResponse(['error' => 'Failed to approve journal entry'], 400);
            }
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Failed to approve journal entry: ' . $e->getMessage()], 500);
        }
    }
    
    public function reject($id) {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
            return;
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $reason = $input['reason'] ?? 'No reason provided';
            
            $result = $this->journalEntryModel->rejectJournalEntry($id, $reason, $this->auth->getCurrentUser()['id']);
            
            if ($result) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Journal entry rejected successfully'
                ]);
            } else {
                $this->jsonResponse(['error' => 'Failed to reject journal entry'], 400);
            }
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Failed to reject journal entry: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Get validation rules for frontend display
     */
    public function getValidationRules() {
        $this->requireAuth();
        
        $rules = $this->validationModel->getValidationRules();
        
        $this->jsonResponse([
            'success' => true,
            'rules' => $rules
        ]);
    }
    
    /**
     * DataTable endpoint for server-side processing
     */
    public function datatable() {
        // Temporarily comment out auth for debugging
        // $this->requireAuth();
        
        try {
            // Get DataTable parameters
            $draw = $_GET['draw'] ?? 1;
            $start = (int)($_GET['start'] ?? 0);
            $length = (int)($_GET['length'] ?? 10);
            $search = $_GET['search']['value'] ?? '';
            $orderColumn = $_GET['order'][0]['column'] ?? 0;
            $orderDir = $_GET['order'][0]['dir'] ?? 'desc';
            
            // Map column index to database field
            $columns = ['th.transaction_date', 'th.reference_no', 'th.total_amount', 'th.status', 'th.id'];
            $orderBy = $columns[$orderColumn] ?? 'th.transaction_date';
            
            error_log('Journal Entry DataTable request received: ' . json_encode($_GET));
            
            // Get data from model
            $result = $this->journalEntryModel->getDataTableData($start, $length, $search, $orderBy, $orderDir);
            
            $response = [
                'draw' => (int)$draw,
                'recordsTotal' => $result['totalRecords'],
                'recordsFiltered' => $result['filteredRecords'],
                'data' => $result['data']
            ];
            
            error_log('Journal Entry DataTable response: ' . json_encode($response));
            echo json_encode($response);
            
        } catch (Exception $e) {
            error_log('Journal Entry DataTable error: ' . $e->getMessage());
            echo json_encode([
                'draw' => (int)($draw ?? 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Failed to load data'
            ]);
        }
    }
}
?>
