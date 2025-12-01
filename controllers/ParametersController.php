<?php
require_once BASE_PATH . '/models/AccountingParametersModel.php';
require_once BASE_PATH . '/models/UserModel.php';

class ParametersController extends Controller {
    
    public function index() {
        $this->requireAuth();
        $this->requirePermission('parameters');
        
        $this->render('parameters/index', [
            'user' => $this->auth->getCurrentUser()
        ]);
    }
    
    public function accounting() {
        $this->requireAuth();
        $this->requirePermission('parameters');
        
        // Get accounting parameters using model
        $parametersModel = new AccountingParametersModel();
        $parameters = $parametersModel->getParameters();
        
        $this->render('parameters/accounting', [
            'parameters' => $parameters,
            'user' => $this->auth->getCurrentUser()
        ]);
    }
    
    public function save() {
        $this->requireAuth();
        $this->requirePermission('parameters');
        
        $data = $this->getRequestData();
        
        if (!isset($data['parameters'])) {
            $this->jsonResponse(['error' => 'Invalid parameters data'], 400);
        }
        
        try {
            $parametersModel = new AccountingParametersModel();
            
            // Map parameter IDs to their correct database IDs
            $parameterMapping = [
                1 => 'company_name',
                2 => 'fiscal_year_start', 
                3 => 'fiscal_year_end',
                4 => 'default_currency',
                5 => 'decimal_places',
                6 => 'auto_backup',
                7 => 'session_timeout'
            ];
            
            $parametersToUpdate = [];
            foreach ($data['parameters'] as $param) {
                if (isset($param['id']) && isset($param['value']) && isset($parameterMapping[$param['id']])) {
                    $parameterName = $parameterMapping[$param['id']];
                    $parametersToUpdate[] = [
                        'name' => $parameterName,
                        'value' => $param['value']
                    ];
                }
            }
            
            $result = $parametersModel->saveParametersByName($parametersToUpdate);
            
            if ($result) {
                // Ensure session active
                if (session_status() !== PHP_SESSION_ACTIVE) {
                    session_start();
                }

                // Read back saved fiscal dates (use defaults if missing)
                $fyStart = $parametersModel->getParameterValue('fiscal_year_start', date('Y-01-01'));
                $fyEnd = $parametersModel->getParameterValue('fiscal_year_end', date('Y-12-31'));

                // Save into PHP session for immediate use
                $_SESSION['fiscal_year_start'] = $fyStart;
                $_SESSION['fiscal_year_end'] = $fyEnd;

                // Persist fiscal dates into current user's DB record (year_start/year_end)
                try {
                    $userModel = new UserModel();
                    $currentUser = $this->auth->getCurrentUser();
                    if ($currentUser && isset($currentUser['id'])) {
                        // Map parameter names to users table column names
                        $userUpdate = [
                            'year_start' => $fyStart,
                            'year_end' => $fyEnd
                        ];
                        $userModel->updateUser($currentUser['id'], $userUpdate);

                        // Also reflect in session (so UI can read them)
                        $_SESSION['year_start'] = $fyStart;
                        $_SESSION['year_end'] = $fyEnd;
                    }
                } catch (Exception $e) {
                    // Log but do not fail the overall parameters update
                    error_log('Failed to persist fiscal dates to user record: ' . $e->getMessage());
                }

                $this->jsonResponse(['success' => true, 'message' => 'Parameters updated successfully']);
            } else {
                $this->jsonResponse(['error' => 'Failed to update parameters'], 500);
            }
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Failed to update parameters: ' . $e->getMessage()], 500);
        }
    }
}
?>