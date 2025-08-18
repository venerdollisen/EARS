<?php

require_once BASE_PATH . '/core/Model.php';

class CheckDisbursementModel extends Model {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Create a new check disbursement transaction
     */
    public function createCheckDisbursement($headerData, $distributions = []) {
        try {
            $this->db->beginTransaction();
            
            $sql = "INSERT INTO check_disbursements (
                reference_no, transaction_date, total_amount, 
                description, supplier_id, project_id, department_id,
                check_number, bank, check_date, payee_name, po_number, 
                cwo_number, ebr_number, return_reason,
                status, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $headerData['reference_no'],
                $headerData['transaction_date'],
                $headerData['total_amount'] ?? 0,
                $headerData['description'] ?? null,
                $headerData['supplier_id'] ?? null,
                $headerData['project_id'] ?? null,
                $headerData['department_id'] ?? null,
                $headerData['check_number'] ?? null,
                $headerData['bank'] ?? null,
                $headerData['check_date'] ?? null,
                $headerData['payee_name'] ?? null,
                $headerData['po_number'] ?? null,
                $headerData['cwo_number'] ?? null,
                $headerData['ebr_number'] ?? null,

                $headerData['return_reason'] ?? null,
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
     * Create distribution lines for check disbursement
     */
    private function createDistributions($headerId, $distributions) {
        $sql = "INSERT INTO check_disbursement_details (
            check_disbursement_id, account_id, transaction_type, amount, description, 
            project_id, department_id, supplier_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($distributions as $distribution) {
            // Determine transaction_type for check disbursement
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
     * Determine transaction type for check disbursement distribution
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
            
            // For check disbursements: debits to asset accounts, credits to others
            return in_array($accountType, ['Asset']) ? 'debit' : 'credit';
        } catch (Exception $e) {
            error_log('Error determining transaction type: ' . $e->getMessage());
            return 'debit'; // Default fallback
        }
    }
    
    /**
     * Get check disbursement by ID with distributions
     */
    public function getCheckDisbursementById($id) {
        try {
            // Get header
            $headerSql = "SELECT chd.*, u.full_name as created_by_name
                         FROM check_disbursements chd
                         LEFT JOIN users u ON chd.created_by = u.id
                         WHERE chd.id = ?";
            
            $headerStmt = $this->db->prepare($headerSql);
            $headerStmt->execute([$id]);
            $header = $headerStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$header) {
                return null;
            }
            
            // Get distributions
            $distSql = "SELECT chdd.*, coa.account_code, coa.account_name, 
                               cat.type_name as account_type,
                               p.project_name, d.department_name, s.supplier_name,
                               chdd.transaction_type as payment_type
                        FROM check_disbursement_details chdd
                        JOIN chart_of_accounts coa ON chdd.account_id = coa.id
                        JOIN coa_account_types cat ON coa.account_type_id = cat.id
                        LEFT JOIN projects p ON chdd.project_id = p.id
                        LEFT JOIN departments d ON chdd.department_id = d.id
                        LEFT JOIN suppliers s ON chdd.supplier_id = s.id
                        WHERE chdd.check_disbursement_id = ?";
            
            $distStmt = $this->db->prepare($distSql);
            $distStmt->execute([$id]);
            $distributions = $distStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $header['distributions'] = $distributions;
            return $header;
            
        } catch (Exception $e) {
            error_log('Error getting check disbursement: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get recent check disbursements within fiscal year
     */
    public function getRecentCheckDisbursements($limit = 10) {
        try {
            // Ensure limit is an integer
            $limit = (int)$limit;
            
            // Get fiscal year dates
            $fiscalYear = $this->getFiscalYearDates();
            
            $sql = "SELECT chd.id, chd.reference_no, chd.transaction_date, chd.total_amount,
                           chd.total_amount as amount, chd.description, chd.payee_name,
                           chd.status, chd.created_at, u.full_name as created_by_name
                    FROM check_disbursements chd
                    LEFT JOIN users u ON chd.created_by = u.id
                    WHERE chd.transaction_date >= ? AND chd.transaction_date <= ?
                    ORDER BY chd.created_at DESC
                    LIMIT " . $limit;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fiscalYear['start'], $fiscalYear['end']]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log('getRecentCheckDisbursements found ' . count($results) . ' records within fiscal year ' . $fiscalYear['start'] . ' to ' . $fiscalYear['end']);
            
            return $results;
            
        } catch (Exception $e) {
            error_log('Error getting recent check disbursements: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get DataTable data for check disbursements with server-side processing within fiscal year
     */
    public function getDataTableData($start, $length, $search, $orderBy, $orderDir) {
        try {
            error_log("getDataTableData called with: start=$start, length=$length, search='$search', orderBy='$orderBy', orderDir='$orderDir'");
            
            // Get fiscal year dates
            $fiscalYear = $this->getFiscalYearDates();
            
            // Base query with fiscal year filter
            $baseQuery = "FROM check_disbursements chd
                         LEFT JOIN users u ON chd.created_by = u.id
                         WHERE chd.transaction_date >= ? AND chd.transaction_date <= ?";
            
            // Search condition
            $searchCondition = "";
            $searchParams = [$fiscalYear['start'], $fiscalYear['end']];
            
            if (!empty($search)) {
                $searchCondition = " AND (chd.reference_no LIKE ? OR chd.payee_name LIKE ? OR chd.description LIKE ? OR chd.check_number LIKE ? OR chd.bank LIKE ?)";
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
            $dataSql = "SELECT chd.id, chd.reference_no, chd.transaction_date, chd.total_amount,
                               chd.description, chd.payee_name, chd.status,
                               chd.created_at, u.full_name as created_by_name
                        " . $baseQuery . $searchCondition . "
                        ORDER BY {$orderBy} {$orderDir}
                        LIMIT {$start}, {$length}";
            
            $dataStmt = $this->db->prepare($dataSql);
            $dataStmt->execute($searchParams);
            $data = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("DataTable query executed. Found " . count($data) . " records. Total: $totalRecords, Filtered: $filteredRecords");
            
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
                
                $formattedData[] = [
                    $formattedDate,
                    '<span class="badge bg-primary">' . htmlspecialchars($row['reference_no']) . '</span>',
                    '<span class="text-end">' . $formattedAmount . '</span>',
                    htmlspecialchars($row['description'] ?? '-'),
                    htmlspecialchars($row['payee_name'] ?? '-'),
                    '<span class="badge ' . $statusClass . '">' . htmlspecialchars($status) . '</span>',
                    '<button type="button" class="btn btn-sm btn-outline-primary chk-view-transaction-btn" data-transaction-id="' . $row['id'] . '"><i class="bi bi-eye"></i> View</button>'
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
            error_log('Error in getDataTableData: ' . $e->getMessage());
            return [
                'totalRecords' => 0,
                'filteredRecords' => 0,
                'data' => []
            ];
        }
    }
    
    /**
     * Update check disbursement
     */
    public function updateCheckDisbursement($id, $data) {
        try {
            $this->db->beginTransaction();
            
            // Update header
            $sql = "UPDATE check_disbursements SET 
                    reference_no = ?, transaction_date = ?, total_amount = ?, 
                    description = ?, supplier_id = ?, project_id = ?, department_id = ?,
                    check_number = ?, bank = ?, check_date = ?, payee_name = ?, 
                    po_number = ?, cwo_number = ?, ebr_number = ?, 
                    return_reason = ?, status = ?,
                    updated_at = CURRENT_TIMESTAMP
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
                $data['check_number'] ?? null,
                $data['bank'] ?? null,
                $data['check_date'] ?? null,
                $data['payee_name'] ?? null,
                $data['po_number'] ?? null,
                $data['cwo_number'] ?? null,
                $data['ebr_number'] ?? null,
                $data['return_reason'] ?? null,
                $data['status'] ?? 'pending',
                $id
            ]);
            
            // Delete existing distributions
            $deleteSql = "DELETE FROM check_disbursement_details WHERE check_disbursement_id = ?";
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
     * Delete check disbursement
     */
    public function deleteCheckDisbursement($id) {
        try {
            $this->db->beginTransaction();
            
            // Delete distributions first (cascade should handle this, but explicit for safety)
            $deleteDistSql = "DELETE FROM check_disbursement_details WHERE check_disbursement_id = ?";
            $deleteDistStmt = $this->db->prepare($deleteDistSql);
            $deleteDistStmt->execute([$id]);
            
            // Delete header
            $deleteHeaderSql = "DELETE FROM check_disbursements WHERE id = ?";
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
     * Generate reference number for check disbursement
     */
    public function generateReferenceNumber() {
        try {
            $prefix = 'CHK';
            $year = date('Y');
            $month = date('m');
            
            $sql = "SELECT COUNT(*) as count FROM check_disbursements 
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
            $sql = "SELECT COUNT(*) FROM check_disbursements WHERE reference_no = ?";
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
            $sql = "UPDATE check_disbursements SET status = ?, updated_at = CURRENT_TIMESTAMP";
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
            error_log('Error updating check disbursement status: ' . $e->getMessage());
            return false;
        }
    }
} 