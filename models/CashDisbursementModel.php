<?php

require_once BASE_PATH . '/core/Model.php';

class CashDisbursementModel extends Model {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Create a new cash disbursement transaction
     */
    public function createCashDisbursement($headerData, $distributions = []) {
        try {
            $this->db->beginTransaction();
            
            $sql = "INSERT INTO cash_disbursements (
                reference_no, transaction_date, total_amount, 
                description, supplier_id, project_id, department_id,
                payment_form, payee_name, po_number, cwo_number, 
                status, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $headerData['reference_no'],
                $headerData['transaction_date'],
                $headerData['total_amount'] ?? 0,
                $headerData['description'] ?? null,
                $headerData['supplier_id'] ?? null,
                $headerData['project_id'] ?? null,
                $headerData['department_id'] ?? null,
                $headerData['payment_form'] ?? 'cash',
                $headerData['payee_name'] ?? null,
                $headerData['po_number'] ?? null,
                $headerData['cwo_number'] ?? null,
                $headerData['status'] ?? 'pending',
                $headerData['created_by']
            ]);
            
            $headerId = $this->db->lastInsertId();
            
            // Create distributions
            if (!empty($distributions)) {
                $this->createDistributions($headerId, $distributions);
            }
            
            $this->db->commit();
            return ['success' => true, 'transaction_id' => $headerId];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Create distribution lines for cash disbursement
     */
    private function createDistributions($headerId, $distributions) {
        $sql = "INSERT INTO cash_disbursement_details (
            cash_disbursement_id, account_id, transaction_type, amount, description, 
            project_id, department_id, supplier_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($distributions as $distribution) {
            // Determine transaction_type for cash disbursement
            $transactionType = $this->determineTransactionType($distribution);
            
            $stmt->execute([
                $headerId,
                $distribution['account_id'],
                $transactionType,
                $distribution['amount'],
                $distribution['description'] ?? null,
                $distribution['project_id'] ?? null,
                $distribution['department_id'] ?? null,
                $distribution['supplier_id'] ?? null
            ]);
        }
    }
    
    /**
     * Determine transaction type for cash disbursement distribution
     */
    private function determineTransactionType($distribution) {
        try {
            // Get account type from coa_account_types via JOIN
            $sql = "SELECT cat.type_name 
                   FROM chart_of_accounts coa 
                   JOIN coa_account_types cat ON coa.account_type_id = cat.id 
                   WHERE coa.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$distribution['account_id']]);
            $accountType = $stmt->fetchColumn();
            
            // For cash disbursements: debits to asset accounts, credits to others
            return in_array($accountType, ['Asset']) ? 'debit' : 'credit';
        } catch (Exception $e) {
            error_log('Error determining transaction type: ' . $e->getMessage());
            return 'debit'; // Default fallback
        }
    }
    
    /**
     * Get cash disbursement by ID with distributions
     */
    public function getCashDisbursementById($id) {
        try {
            // Get header
            $headerSql = "SELECT cd.*, u.full_name as created_by_name
                         FROM cash_disbursements cd
                         LEFT JOIN users u ON cd.created_by = u.id
                         WHERE cd.id = ?";
            
            $headerStmt = $this->db->prepare($headerSql);
            $headerStmt->execute([$id]);
            $header = $headerStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$header) {
                return null;
            }
            
            // Get distributions
            $distSql = "SELECT cdd.*, coa.account_code, coa.account_name, 
                               cat.type_name as account_type,
                               p.project_name, d.department_name, s.supplier_name,
                               cdd.transaction_type as payment_type
                        FROM cash_disbursement_details cdd
                        JOIN chart_of_accounts coa ON cdd.account_id = coa.id
                        JOIN coa_account_types cat ON coa.account_type_id = cat.id
                        LEFT JOIN projects p ON cdd.project_id = p.id
                        LEFT JOIN departments d ON cdd.department_id = d.id
                        LEFT JOIN suppliers s ON cdd.supplier_id = s.id
                        WHERE cdd.cash_disbursement_id = ?";
            
            $distStmt = $this->db->prepare($distSql);
            $distStmt->execute([$id]);
            $distributions = $distStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $header['distributions'] = $distributions;
            return $header;
            
        } catch (Exception $e) {
            error_log('Error getting cash disbursement: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get recent cash disbursements within fiscal year
     */
    public function getRecentCashDisbursements($limit = 10) {
        try {
            // Ensure limit is an integer
            $limit = (int)$limit;
            
            // Get fiscal year dates
            $fiscalYear = $this->getFiscalYearDates();
            
            $sql = "SELECT cd.id, cd.reference_no, cd.transaction_date, cd.total_amount,
                           cd.total_amount as amount, cd.description, cd.payment_form, cd.payee_name,
                           cd.status, cd.created_at, u.full_name as created_by_name
                    FROM cash_disbursements cd
                    LEFT JOIN users u ON cd.created_by = u.id
                    WHERE cd.transaction_date >= ? AND cd.transaction_date <= ?
                    ORDER BY cd.created_at DESC
                    LIMIT " . $limit;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fiscalYear['start'], $fiscalYear['end']]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log('getRecentCashDisbursements found ' . count($results) . ' records within fiscal year ' . $fiscalYear['start'] . ' to ' . $fiscalYear['end']);
            
            return $results;
            
        } catch (Exception $e) {
            error_log('Error getting recent cash disbursements: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get data for server-side DataTable processing
     */
    public function getDataTableData($start, $length, $search, $orderBy, $orderDir, $userRole = 'user') {
        try {
            error_log("getDataTableData called with: start=$start, length=$length, search='$search', orderBy='$orderBy', orderDir='$orderDir', userRole='$userRole'");
            
            // Get fiscal year dates
            $fiscalYear = $this->getFiscalYearDates();
            error_log("Fiscal year dates: " . json_encode($fiscalYear));
            
            // Base query with fiscal year filter
            $baseQuery = "FROM cash_disbursements cd
                         LEFT JOIN users u ON cd.created_by = u.id
                         WHERE cd.transaction_date >= ? AND cd.transaction_date <= ?";
            
            // Search condition
            $searchCondition = "";
            $searchParams = [$fiscalYear['start'], $fiscalYear['end']];
            
            if (!empty($search)) {
                $searchCondition = " AND (cd.reference_no LIKE ? OR cd.description LIKE ? OR cd.payment_form LIKE ? OR cd.status LIKE ? OR cd.payee_name LIKE ?)";
                $searchTerm = "%{$search}%";
                $searchParams = array_merge($searchParams, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            }
            
            // Count total records within fiscal year
            $countSql = "SELECT COUNT(*) as total " . $baseQuery;
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute([$fiscalYear['start'], $fiscalYear['end']]);
            $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Count filtered records
            $filteredSql = "SELECT COUNT(*) as total " . $baseQuery . $searchCondition;
            $filteredStmt = $this->db->prepare($filteredSql);
            $filteredStmt->execute($searchParams);
            $filteredRecords = $filteredStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get data
            $dataSql = "SELECT cd.id, cd.reference_no, cd.transaction_date, cd.total_amount,
                               cd.description, cd.payment_form, cd.status,
                               cd.payee_name, cd.created_at, u.full_name as created_by_name
                        " . $baseQuery . $searchCondition . "
                        ORDER BY {$orderBy} {$orderDir}
                        LIMIT {$start}, {$length}";
            
            $dataStmt = $this->db->prepare($dataSql);
            $dataStmt->execute($searchParams);
            $data = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("DataTable query executed. Found " . count($data) . " records. Total: $totalRecords, Filtered: $filteredRecords");
            error_log("DataTable query: " . $dataSql);
            error_log("DataTable params: " . json_encode($searchParams));
            error_log("DataTable raw data: " . json_encode($data));
            
            // Format data for DataTable
            $formattedData = [];
            foreach ($data as $row) {
                // Format date
                $formattedDate = date('M d, Y', strtotime($row['transaction_date']));
                
                // Format amount
                $formattedAmount = '₱' . number_format($row['total_amount'], 2);
                
                // Format status badge
                $statusClass = 'bg-secondary';
                $status = $row['status'] ?? 'pending';
                
                switch ($status) {
                                     case 'approved':
                     $statusClass = 'bg-success';
                     break;
                 case 'rejected':
                     $statusClass = 'bg-danger';
                     break;
                 case 'pending':
                     $statusClass = 'bg-warning';
                     break;
                    default:
                        $statusClass = 'bg-secondary';
                        break;
                }
                
                // Create action buttons
                $actionButtons = '<div class="d-flex gap-1">';
                $actionButtons .= '<button type="button" class="btn btn-sm btn-outline-primary cd-view-transaction-btn d-flex align-items-center" data-transaction-id="' . $row['id'] . '"><i class="bi bi-eye me-1"></i> View</button>';
                
                // Add delete button for pending or rejected transactions (only for accountant, manager, admin)
                if (in_array($userRole, ['accountant', 'manager', 'admin']) && ($status === 'pending' || $status === 'rejected')) {
                    error_log("Adding delete button for user role: $userRole, status: $status, transaction ID: " . $row['id']);
                    $actionButtons .= '<button type="button" class="btn btn-sm btn-outline-danger cd-delete-transaction-btn d-flex align-items-center" data-transaction-id="' . $row['id'] . '" data-reference="' . htmlspecialchars($row['reference_no']) . '"><i class="bi bi-trash me-1"></i> Delete</button>';
                } else {
                    error_log("NOT adding delete button - user role: $userRole, status: $status, transaction ID: " . $row['id']);
                }
                $actionButtons .= '</div>';
                
                $formattedData[] = [
                    $formattedDate,
                    '<span class="badge bg-primary">' . htmlspecialchars($row['reference_no']) . '</span>',
                    '<span class="text-end">' . $formattedAmount . '</span>',
                    htmlspecialchars($row['description'] ?? '-'),
                    htmlspecialchars($row['payee_name'] ?? '-'),
                    '<span class="badge ' . $statusClass . '">' . htmlspecialchars(ucfirst($status)) . '</span>',
                    $actionButtons
                ];
            }
            
            $result = [
                'totalRecords' => (int)$totalRecords,
                'filteredRecords' => (int)$filteredRecords,
                'data' => $formattedData
            ];
            
            error_log("DataTable result: " . json_encode($result));
            return $result;
            
        } catch (Exception $e) {
            error_log('Error getting DataTable data: ' . $e->getMessage());
            return [
                'totalRecords' => 0,
                'filteredRecords' => 0,
                'data' => []
            ];
        }
    }
    
    /**
     * Update cash disbursement
     */
    public function updateCashDisbursement($id, $data) {
        try {
            $this->db->beginTransaction();
            
            // Update header
            $sql = "UPDATE cash_disbursements SET 
                    reference_no = ?, transaction_date = ?, total_amount = ?, 
                    description = ?, supplier_id = ?, project_id = ?, department_id = ?,
                    payment_form = ?, payee_name = ?, po_number = ?, cwo_number = ?, 
                    status = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['reference_no'],
                $data['transaction_date'],
                $data['total_amount'],
                $data['description'] ?? null,
                $data['supplier_id'] ?? null,
                $data['project_id'] ?? null,
                $data['department_id'] ?? null,
                $data['payment_form'] ?? 'cash',
                $data['payee_name'] ?? null,
                $data['po_number'] ?? null,
                $data['cwo_number'] ?? null,
                $data['status'] ?? 'posted',
                $id
            ]);
            
            // Delete existing distributions
            $deleteSql = "DELETE FROM cash_disbursement_details WHERE cash_disbursement_id = ?";
            $deleteStmt = $this->db->prepare($deleteSql);
            $deleteStmt->execute([$id]);
            
            // Create new distributions
            if (!empty($data['distributions'])) {
                $this->createDistributions($id, $data['distributions']);
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Delete cash disbursement
     */
    public function deleteCashDisbursement($id) {
        try {
            $this->db->beginTransaction();
            
            // Delete distributions first (cascade should handle this, but explicit for safety)
            $deleteDistSql = "DELETE FROM cash_disbursement_details WHERE cash_disbursement_id = ?";
            $deleteDistStmt = $this->db->prepare($deleteDistSql);
            $deleteDistStmt->execute([$id]);
            
            // Delete header
            $deleteHeaderSql = "DELETE FROM cash_disbursements WHERE id = ?";
            $deleteHeaderStmt = $this->db->prepare($deleteHeaderSql);
            $deleteHeaderStmt->execute([$id]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Generate reference number for cash disbursement
     */
    public function generateReferenceNumber() {
        try {
            $prefix = 'CD';
            $year = date('Y');
            $month = date('m');
            
            $sql = "SELECT COUNT(*) as count FROM cash_disbursements 
                    WHERE YEAR(created_at) = ? AND MONTH(created_at) = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$year, $month]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $count = $result['count'] + 1;
            return $prefix . $year . $month . str_pad($count, 4, '0', STR_PAD_LEFT);
            
        } catch (Exception $e) {
            error_log('Error generating reference number: ' . $e->getMessage());
            return $prefix . date('YmdHis');
        }
    }
    
    /**
     * Check if reference number exists
     */
    public function referenceNumberExists($referenceNo, $excludeId = null) {
        try {
            $sql = "SELECT COUNT(*) FROM cash_disbursements WHERE reference_no = ?";
            $params = [$referenceNo];
            
            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return (bool)$stmt->fetchColumn();
            
        } catch (Exception $e) {
            error_log('Error checking reference number: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update transaction status
     */
    public function updateStatus($id, $status, $returnReason = null) {
        try {
            $sql = "UPDATE cash_disbursements SET status = ?, updated_at = CURRENT_TIMESTAMP";
            $params = [$status];
            
            if ($returnReason !== null) {
                $sql .= ", return_reason = ?";
                $params[] = $returnReason;
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $id;
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($params);
            
            return $result && $stmt->rowCount() > 0;
            
        } catch (Exception $e) {
            error_log('Error updating cash disbursement status: ' . $e->getMessage());
            return false;
        }
    }
} 