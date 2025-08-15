<?php
require_once BASE_PATH . '/models/AccountingParametersModel.php';

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