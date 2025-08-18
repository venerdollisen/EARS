<?php
require_once BASE_PATH . '/core/Controller.php';
require_once BASE_PATH . '/models/NotificationModel.php';
require_once BASE_PATH . '/models/CashReceiptModel.php';
require_once BASE_PATH . '/models/CashDisbursementModel.php';
require_once BASE_PATH . '/models/CheckDisbursementModel.php';

class NotificationController extends Controller {
    public function recent() {
        $this->requireAuth();
        $notifModel = new NotificationModel();
        $user = $this->auth->getCurrentUser();
        $items = $notifModel->getRecentForUser((int)$user['id'], 10);
        $unread = $notifModel->getUnreadCountForUser((int)$user['id']);
        $this->jsonResponse(['success' => true, 'data' => $items, 'unread' => $unread]);
    }

    public function count() {
        $this->requireAuth();
        $notifModel = new NotificationModel();
        $user = $this->auth->getCurrentUser();
        $unread = $notifModel->getUnreadCountForUser((int)$user['id']);
        $this->jsonResponse(['success' => true, 'unread' => $unread]);
    }

    // View a notification: return transaction details if link references a transaction id (DO NOT mark as read)
    public function view($id) {
        $this->requireAuth();
        try {
            $user = $this->auth->getCurrentUser();
            $notifModel = new NotificationModel();
            // DO NOT mark as read when viewing - only mark as read when transaction is approved/rejected
            // $notifModel->markAsRead((int)$id, (int)$user['id']);
            // Also try to detect transaction id in the link query (?id=)
            $stmt = $this->db->prepare("SELECT link FROM notifications WHERE id = ? AND recipient_user_id = ?");
            $stmt->execute([(int)$id, (int)$user['id']]);
            $link = $stmt->fetchColumn();
            error_log("Notification view - Link: " . $link);
            
            // Also get the notification message to understand what type it should be
            $stmtMsg = $this->db->prepare("SELECT message, title FROM notifications WHERE id = ?");
            $stmtMsg->execute([(int)$id]);
            $notificationData = $stmtMsg->fetch(PDO::FETCH_ASSOC);
            error_log("Notification view - Title: " . ($notificationData['title'] ?? 'N/A'));
            error_log("Notification view - Message: " . ($notificationData['message'] ?? 'N/A'));
            
            $transaction = null;
            if ($link) {
                // Try query param id first
                $qs = [];
                $query = parse_url($link, PHP_URL_QUERY);
                if ($query) { parse_str($query, $qs); }
                error_log("Notification view - Query params: " . json_encode($qs));
                if (!empty($qs['id'])) {
                    $transactionId = (int)$qs['id'];
                    error_log("Notification view - Transaction ID: " . $transactionId);
                    
                    // Determine which table to search based on notification title/message
                    $searchTable = null;
                    if (strpos($notificationData['title'] ?? '', 'Cash Receipt') !== false || 
                        strpos($notificationData['message'] ?? '', 'cash receipt') !== false) {
                        $searchTable = 'cash_receipts';
                    } elseif (strpos($notificationData['title'] ?? '', 'Cash Disbursement') !== false || 
                             strpos($notificationData['message'] ?? '', 'cash disbursement') !== false) {
                        $searchTable = 'cash_disbursements';
                    } elseif (strpos($notificationData['title'] ?? '', 'Check Disbursement') !== false || 
                             strpos($notificationData['message'] ?? '', 'check disbursement') !== false) {
                        $searchTable = 'check_disbursements';
                    }
                    
                    error_log("Notification view - Determined search table: " . ($searchTable ?? 'unknown'));
                    
                    // Try to get transaction from all three models
                    $cashReceiptModel = new CashReceiptModel();
                    $cashDisbursementModel = new CashDisbursementModel();
                    $checkDisbursementModel = new CheckDisbursementModel();
                    
                    // First, let's check what exists in each table with this ID
                    $checkSql = "SELECT 'cash_receipts' as table_name, reference_no FROM cash_receipts WHERE id = ? 
                                UNION ALL 
                                SELECT 'cash_disbursements' as table_name, reference_no FROM cash_disbursements WHERE id = ? 
                                UNION ALL 
                                SELECT 'check_disbursements' as table_name, reference_no FROM check_disbursements WHERE id = ?";
                    $checkStmt = $this->db->prepare($checkSql);
                    $checkStmt->execute([$transactionId, $transactionId, $transactionId]);
                    $existingRecords = $checkStmt->fetchAll(PDO::FETCH_ASSOC);
                    error_log("Notification view - Records found with ID $transactionId: " . json_encode($existingRecords));
                    
                    // Search in the determined table first, then fallback to others
                    if ($searchTable === 'cash_receipts') {
                        $transaction = $cashReceiptModel->getCashReceiptById($transactionId);
                        if ($transaction) {
                            error_log("Notification view - Found cash receipt transaction (primary search)");
                            $transaction['transaction_type'] = 'cash_receipt';
                        }
                    } elseif ($searchTable === 'cash_disbursements') {
                        $transaction = $cashDisbursementModel->getCashDisbursementById($transactionId);
                        if ($transaction) {
                            error_log("Notification view - Found cash disbursement transaction (primary search)");
                            $transaction['transaction_type'] = 'cash_disbursement';
                        }
                    } elseif ($searchTable === 'check_disbursements') {
                        $transaction = $checkDisbursementModel->getCheckDisbursementById($transactionId);
                        if ($transaction) {
                            error_log("Notification view - Found check disbursement transaction (primary search)");
                            $transaction['transaction_type'] = 'check_disbursement';
                        }
                    }
                    
                    // If not found in the determined table, try others as fallback
                    if (!$transaction) {
                        error_log("Notification view - Not found in primary table, trying fallback search");
                        $transaction = $cashReceiptModel->getCashReceiptById($transactionId);
                        if ($transaction) {
                            error_log("Notification view - Found cash receipt transaction (fallback)");
                            $transaction['transaction_type'] = 'cash_receipt';
                        } else {
                            $transaction = $cashDisbursementModel->getCashDisbursementById($transactionId);
                            if ($transaction) {
                                error_log("Notification view - Found cash disbursement transaction (fallback)");
                                $transaction['transaction_type'] = 'cash_disbursement';
                            } else {
                                $transaction = $checkDisbursementModel->getCheckDisbursementById($transactionId);
                                if ($transaction) {
                                    error_log("Notification view - Found check disbursement transaction (fallback)");
                                    $transaction['transaction_type'] = 'check_disbursement';
                                }
                            }
                        }
                    }
                }
                // If not found via link, look for the most recent pending header matching title/message pattern for this recipient
                if (!$transaction) {
                    error_log("Notification view - Transaction not found via link, trying message parsing");
                    // Extract CV no from message if present: e.g., "(CV: CV-20250101-123456)"
                    $stmtMsg = $this->db->prepare("SELECT message FROM notifications WHERE id = ?");
                    $stmtMsg->execute([(int)$id]);
                    $msg = (string)$stmtMsg->fetchColumn();
                    error_log("Notification view - Message: " . $msg);
                    if ($msg) {
                        if (preg_match('/CV:\s*([A-Za-z0-9\-]+)/', $msg, $m)) {
                            $cv = $m[1];
                            error_log("Notification view - Extracted CV: " . $cv);
                            // Check all three transaction tables for the reference number
                            $tables = ['cash_receipts', 'cash_disbursements', 'check_disbursements'];
                            $tid = null;
                            $transactionType = null;
                            
                            foreach ($tables as $table) {
                                $q = $this->db->prepare("SELECT id FROM {$table} WHERE reference_no = ? ORDER BY id DESC LIMIT 1");
                                $q->execute([$cv]);
                                $result = $q->fetchColumn();
                                if ($result) {
                                    $tid = $result;
                                    $transactionType = $table;
                                    error_log("Notification view - Found transaction in table: " . $table . " with ID: " . $tid);
                                    break;
                                }
                            }
                            
                            if ($tid) {
                                // Get transaction from the appropriate model based on type
                                switch ($transactionType) {
                                    case 'cash_receipts':
                                        $cashReceiptModel = new CashReceiptModel();
                                        $transaction = $cashReceiptModel->getCashReceiptById($tid);
                                        if ($transaction) {
                                            $transaction['transaction_type'] = 'cash_receipt';
                                        }
                                        break;
                                    case 'cash_disbursements':
                                        $cashDisbursementModel = new CashDisbursementModel();
                                        $transaction = $cashDisbursementModel->getCashDisbursementById($tid);
                                        if ($transaction) {
                                            $transaction['transaction_type'] = 'cash_disbursement';
                                        }
                                        break;
                                    case 'check_disbursements':
                                        $checkDisbursementModel = new CheckDisbursementModel();
                                        $transaction = $checkDisbursementModel->getCheckDisbursementById($tid);
                                        if ($transaction) {
                                            $transaction['transaction_type'] = 'check_disbursement';
                                        }
                                        break;
                                }
                            }
                        }
                    }
                }
            }
            error_log("Notification view - Final transaction: " . json_encode($transaction));
            return $this->jsonResponse(['success' => true, 'transaction' => $transaction]);
        } catch (Exception $e) {
            error_log("Notification view - Error: " . $e->getMessage());
            return $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Add a review comment to a transaction (from notification flow)
    public function comment() {
        $this->requireAuth();
        try {
            $user = $this->auth->getCurrentUser();
            if (!in_array(($user['role'] ?? ''), ['admin','manager','accountant','assistant','user'], true)) {
                return $this->jsonResponse(['success' => false, 'message' => 'Forbidden'], 403);
            }
            $payload = $this->getRequestData();
            $transactionId = (int)($payload['transaction_id'] ?? 0);
            $comment = trim($payload['comment'] ?? '');
            if (!$transactionId || $comment === '') {
                return $this->jsonResponse(['success' => false, 'message' => 'Missing fields'], 400);
            }
            // Persist comment in a simple table; create if not exists in schema (assumes comments table exists)
            $this->db->exec("CREATE TABLE IF NOT EXISTS transaction_comments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                transaction_id INT NOT NULL,
                transaction_type VARCHAR(50) NOT NULL,
                user_id INT NOT NULL,
                comment TEXT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_trn (transaction_id, transaction_type),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB");
            $stmt = $this->db->prepare("INSERT INTO transaction_comments (transaction_id, transaction_type, user_id, comment) VALUES (?, ?, ?, ?)");
            $stmt->execute([$transactionId, $payload['transaction_type'] ?? 'unknown', (int)$user['id'], $comment]);
            return $this->jsonResponse(['success' => true, 'message' => 'Comment added']);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Mark a specific notification as read
     */
    public function markRead($id) {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
            return;
        }
        
        try {
            $user = $this->auth->getCurrentUser();
            $notifModel = new NotificationModel();
            
            // Mark the specific notification as read
            $success = $notifModel->markAsRead((int)$id, (int)$user['id']);
            
            if ($success) {
                $this->jsonResponse(['success' => true, 'message' => 'Notification marked as read']);
            } else {
                $this->jsonResponse(['error' => 'Failed to mark notification as read'], 500);
            }
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Error marking notification as read: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Mark notifications as read by reference number
     */
    public function markReadByReference() {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
            return;
        }
        
        try {
            $user = $this->auth->getCurrentUser();
            $input = json_decode(file_get_contents('php://input'), true);
            $referenceNo = trim($input['reference_no'] ?? '');
            
            if (empty($referenceNo)) {
                $this->jsonResponse(['error' => 'Reference number is required'], 400);
                return;
            }
            
            $notifModel = new NotificationModel();
            
            // Find notifications that contain this reference number in the message
            $sql = "SELECT id FROM notifications 
                    WHERE recipient_user_id = ? 
                    AND message LIKE ?
                    AND is_read = 0";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$user['id'], '%' . $referenceNo . '%']);
            
            $notificationIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Mark each notification as read
            $markedCount = 0;
            foreach ($notificationIds as $notificationId) {
                if ($notifModel->markAsRead($notificationId, $user['id'])) {
                    $markedCount++;
                }
            }
            
            $this->jsonResponse([
                'success' => true, 
                'message' => "Marked {$markedCount} notification(s) as read",
                'marked_count' => $markedCount
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Error marking notifications as read: ' . $e->getMessage()], 500);
        }
    }
}
?>

