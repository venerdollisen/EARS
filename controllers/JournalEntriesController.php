<?php
require_once BASE_PATH . '/core/Controller.php';
require_once BASE_PATH . '/models/JournalEntryModel.php';

class JournalEntriesController extends Controller {
    
    private $journalEntryModel;
    
    public function __construct() {
        parent::__construct();
        $this->journalEntryModel = new JournalEntryModel();
    }
    
    public function index() {
        $this->requireAuth();
        
        try {
            $journalEntries = $this->journalEntryModel->getAllJournalEntriesWithBalance();
            
            $this->render('journal-entries/index', [
                'journalEntries' => $journalEntries
            ]);
            
        } catch (Exception $e) {
            $this->render('errors/500', ['error' => $e->getMessage()]);
        }
    }
    
    public function create() {
        $this->requireAuth();
        
        try {
            $accounts = $this->journalEntryModel->getActiveAccounts();
            $projects = $this->journalEntryModel->getActiveProjects();
            $departments = $this->journalEntryModel->getActiveDepartments();
            $suppliers = $this->journalEntryModel->getActiveSuppliers();
            
            $this->render('journal-entries/create', [
                'accounts' => $accounts,
                'projects' => $projects,
                'departments' => $departments,
                'suppliers' => $suppliers
            ]);
        } catch (Exception $e) {
            $this->render('errors/500', ['error' => $e->getMessage()]);
        }
    }
    
    public function save() {
        $this->requireAuth();
        $data = $this->getRequestData();
        
        if (!isset($data['entries']) || empty($data['entries'])) {
            $this->jsonResponse(['error' => 'No journal entries provided'], 400);
        }
        
        try {
            $user = $this->auth->getCurrentUser();
            
            // Prepare data for model
            $journalEntryData = [
                'reference_no' => $this->journalEntryModel->generateReferenceNo(),
                'transaction_date' => $data['transaction_date'] ?? date('Y-m-d'),
                'description' => $data['description'] ?? '',
                'jv_status' => $data['jv_status'] ?? 'active',
                'for_posting' => $data['for_posting'] ?? 'for_checking',
                'reference_number1' => $data['reference_number1'] ?? '',
                'reference_number2' => $data['reference_number2'] ?? '',
                'cwo_number' => $data['cwo_number'] ?? '',
                'bill_invoice_ref' => $data['bill_invoice_ref'] ?? '',
                'created_by' => $user['id'],
                'entries' => []
            ];
            
            // Calculate total debits and credits
            $totalDebits = 0;
            $totalCredits = 0;
            
            foreach ($data['entries'] as $entry) {
                if ($entry['transaction_type'] === 'debit') {
                    $totalDebits += floatval($entry['amount']);
                } else {
                    $totalCredits += floatval($entry['amount']);
                }
                
                $journalEntryData['entries'][] = [
                    'account_id' => $entry['account_id'],
                    'project_id' => $entry['project_id'] ?? null,
                    'department_id' => $entry['department_id'] ?? null,
                    'supplier_id' => $entry['supplier_id'] ?? null,
                    'transaction_type' => $entry['transaction_type'],
                    'amount' => $entry['amount'],
                    'description' => $entry['description'] ?? ''
                ];
            }
            
            // Check if debits equal credits
            if (abs($totalDebits - $totalCredits) > 0.01) {
                $this->jsonResponse(['error' => 'Total debits must equal total credits'], 400);
            }
            
            $journalEntryData['total_amount'] = $totalDebits;
            
            // Create journal entry using model
            $journalEntryId = $this->journalEntryModel->createJournalEntry($journalEntryData);
            
            $this->jsonResponse([
                'success' => true, 
                'message' => 'Journal entry created successfully',
                'journal_entry_id' => $journalEntryId
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Failed to create journal entry: ' . $e->getMessage()], 500);
        }
    }
    
    public function view($id) {
        $this->requireAuth();
        
        try {
            // Get journal entry using model
            $journalEntry = $this->journalEntryModel->getJournalEntryById($id);
            
            if (!$journalEntry) {
                $this->render('errors/404');
                return;
            }
            
            // Get journal entry details using model
            $details = $this->journalEntryModel->getJournalEntryDetails($id);
            
            $this->render('journal-entries/view', [
                'journalEntry' => $journalEntry,
                'details' => $details
            ]);
            
        } catch (Exception $e) {
            $this->render('errors/500', ['error' => $e->getMessage()]);
        }
    }
    
    public function approve($id) {
        $this->requireAuth();
        
        try {
            $approvedBy = $this->auth->getCurrentUser()['id'];
            
            // Approve journal entry using model
            $this->journalEntryModel->approveJournalEntry($id, $approvedBy);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Journal entry approved successfully'
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Failed to approve journal entry: ' . $e->getMessage()], 500);
        }
    }
    
    public function reject($id) {
        $this->requireAuth();
        $data = $this->getRequestData();
        
        try {
            $reason = $data['reason'] ?? 'No reason provided';
            $rejectedBy = $this->auth->getCurrentUser()['id'];
            
            // Reject journal entry using model
            $this->journalEntryModel->rejectJournalEntry($id, $rejectedBy, $reason);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Journal entry rejected successfully'
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Failed to reject journal entry: ' . $e->getMessage()], 500);
        }
    }
}
?> 