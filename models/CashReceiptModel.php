<?php

require_once BASE_PATH . '/core/Model.php';

class CashReceiptModel extends Model {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Create a new cash receipt transaction
     */
    public function createCashReceipt($headerData, $distributions = []) {
        try {
            $this->db->beginTransaction();
            
            $sql = "INSERT INTO cash_receipts (
                reference_no, transaction_date, total_amount, 
                description, payment_form, check_number, bank, 
                billing_number, payee_name, status, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $headerData['reference_no'],
                $headerData['transaction_date'],
                $headerData['total_amount'] ?? 0,
                $headerData['description'] ?? null,
                $headerData['payment_form'] ?? 'cash',
                $headerData['check_number'] ?? null,
                $headerData['bank'] ?? null,
                $headerData['billing_number'] ?? null,
                $headerData['payee_name'] ?? null,
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
     * Create distribution lines for cash receipt
     */
    private function createDistributions($headerId, $distributions) {
        $sql = "INSERT INTO cash_receipt_details (
            cash_receipt_id, account_id, transaction_type, amount, description, 
            project_id, department_id, supplier_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($distributions as $distribution) {
            // Determine transaction_type for cash receipt
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
     * Determine transaction type for cash receipt distribution
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
            
            // For cash receipts: credits to asset accounts, debits to others
            return in_array($accountType, ['Asset']) ? 'credit' : 'debit';
        } catch (Exception $e) {
            error_log('Error determining transaction type: ' . $e->getMessage());
            return 'debit'; // Default fallback
        }
    }
    
    /**
     * Get cash receipt by ID with distributions
     */
    public function getCashReceiptById($id) {
        try {
            // Get header
            $headerSql = "SELECT cr.*, u.full_name as created_by_name
                         FROM cash_receipts cr
                         LEFT JOIN users u ON cr.created_by = u.id
                         WHERE cr.id = ?";
            
            $headerStmt = $this->db->prepare($headerSql);
            $headerStmt->execute([$id]);
            $header = $headerStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$header) {
                return null;
            }
            
            // Get distributions
            $distSql = "SELECT crd.*, coa.account_code, coa.account_name, 
                               cat.type_name as account_type,
                               p.project_name, d.department_name, s.supplier_name,
                               crd.transaction_type as payment_type
                        FROM cash_receipt_details crd
                        JOIN chart_of_accounts coa ON crd.account_id = coa.id
                        JOIN coa_account_types cat ON coa.account_type_id = cat.id
                        LEFT JOIN projects p ON crd.project_id = p.id
                        LEFT JOIN departments d ON crd.department_id = d.id
                        LEFT JOIN suppliers s ON crd.supplier_id = s.id
                        WHERE crd.cash_receipt_id = ?";
            
            $distStmt = $this->db->prepare($distSql);
            $distStmt->execute([$id]);
            $distributions = $distStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $header['distributions'] = $distributions;
            return $header;
            
        } catch (Exception $e) {
            error_log('Error getting cash receipt: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get recent cash receipts within fiscal year
     */
    public function getRecentCashReceipts($limit = 10) {
        try {
            // Ensure limit is an integer
            $limit = (int)$limit;
            
            // Get fiscal year dates
            $fiscalYear = $this->getFiscalYearDates();
            
            $sql = "SELECT cr.id, cr.reference_no, cr.transaction_date, cr.total_amount,
                           cr.total_amount as amount, cr.description, cr.payment_form, cr.payee_name,
                           cr.status, cr.created_at, u.full_name as created_by_name
                    FROM cash_receipts cr
                    LEFT JOIN users u ON cr.created_by = u.id
                    WHERE cr.transaction_date >= ? AND cr.transaction_date <= ?
                    ORDER BY cr.created_at DESC
                    LIMIT " . $limit;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fiscalYear['start'], $fiscalYear['end']]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log('getRecentCashReceipts found ' . count($results) . ' records within fiscal year ' . $fiscalYear['start'] . ' to ' . $fiscalYear['end']);
            
            return $results;
            
        } catch (Exception $e) {
            error_log('Error getting recent cash receipts: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get data for server-side DataTable processing within fiscal year
     */
    public function getDataTableData($start, $length, $search, $orderBy, $orderDir) {
        try {
            error_log("getDataTableData called with: start=$start, length=$length, search='$search', orderBy='$orderBy', orderDir='$orderDir'");
            
            // Get fiscal year dates
            $fiscalYear = $this->getFiscalYearDates();
            
            // Base query with fiscal year filter
            $baseQuery = "FROM cash_receipts cr
                         LEFT JOIN users u ON cr.created_by = u.id
                         WHERE cr.transaction_date >= ? AND cr.transaction_date <= ?";
            
            // Search condition
            $searchCondition = "";
            $searchParams = [$fiscalYear['start'], $fiscalYear['end']];
            
            if (!empty($search)) {
                $searchCondition = " AND (cr.reference_no LIKE ? OR cr.description LIKE ? OR cr.payment_form LIKE ? OR cr.status LIKE ?)";
                $searchTerm = "%{$search}%";
                $searchParams = array_merge($searchParams, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
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
            $dataSql = "SELECT cr.id, cr.reference_no, cr.transaction_date, cr.total_amount,
                               cr.description, cr.payment_form, cr.status,
                               cr.created_at, u.full_name as created_by_name
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
                    '<span class="badge bg-success">' . htmlspecialchars($row['reference_no']) . '</span>',
                    htmlspecialchars($row['payment_form'] ?? 'Cash'),
                    '<span class="text-end">' . $formattedAmount . '</span>',
                    htmlspecialchars($row['description'] ?? '-'),
                    '<span class="badge ' . $statusClass . '">' . htmlspecialchars(ucfirst($status)) . '</span>',
                    '<button type="button" class="btn btn-sm btn-outline-primary view-transaction-btn" data-transaction-id="' . $row['id'] . '"><i class="bi bi-eye"></i> View</button>'
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
     * Update cash receipt
     */
    public function updateCashReceipt($id, $data) {
        try {
            $this->db->beginTransaction();
            
            // Update header
            $sql = "UPDATE cash_receipts SET 
                    reference_no = ?, transaction_date = ?, total_amount = ?, 
                    description = ?, payment_form = ?, check_number = ?, bank = ?, 
                    billing_number = ?, payee_name = ?, status = ?,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['reference_no'],
                $data['transaction_date'],
                $data['total_amount'],
                $data['description'] ?? null,
                $data['payment_form'] ?? 'cash',
                $data['check_number'] ?? null,
                $data['bank'] ?? null,
                $data['billing_number'] ?? null,
                $data['payee_name'] ?? null,
                $data['status'] ?? 'posted',
                $id
            ]);
            
            // Delete existing distributions
            $deleteSql = "DELETE FROM cash_receipt_details WHERE cash_receipt_id = ?";
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
     * Delete cash receipt
     */
    public function deleteCashReceipt($id) {
        try {
            $this->db->beginTransaction();
            
            // Delete distributions first (cascade should handle this, but explicit for safety)
            $deleteDistSql = "DELETE FROM cash_receipt_details WHERE cash_receipt_id = ?";
            $deleteDistStmt = $this->db->prepare($deleteDistSql);
            $deleteDistStmt->execute([$id]);
            
            // Delete header
            $deleteHeaderSql = "DELETE FROM cash_receipts WHERE id = ?";
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
     * Generate reference number for cash receipt
     */
    public function generateReferenceNumber() {
        try {
            $prefix = 'CR';
            $year = date('Y');
            $month = date('m');
            
            $sql = "SELECT COUNT(*) as count FROM cash_receipts 
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
            $sql = "SELECT COUNT(*) FROM cash_receipts WHERE reference_no = ?";
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
            $sql = "UPDATE cash_receipts SET status = ?, updated_at = CURRENT_TIMESTAMP";
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
            error_log('Error updating cash receipt status: ' . $e->getMessage());
            return false;
        }
    }
} 