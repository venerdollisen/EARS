<?php
require_once BASE_PATH . '/core/Controller.php';
require_once BASE_PATH . '/models/NotificationModel.php';
require_once BASE_PATH . '/models/TransactionModel.php';

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

    // View a notification: mark as read and return transaction details if link references a transaction id
    public function view($id) {
        $this->requireAuth();
        try {
            $user = $this->auth->getCurrentUser();
            $notifModel = new NotificationModel();
            $notifModel->markAsRead((int)$id, (int)$user['id']);
            // Also try to detect transaction id in the link query (?id=)
            $stmt = $this->db->prepare("SELECT link FROM notifications WHERE id = ? AND recipient_user_id = ?");
            $stmt->execute([(int)$id, (int)$user['id']]);
            $link = $stmt->fetchColumn();
            $transaction = null;
            if ($link) {
                $tm = new TransactionModel();
                // Try query param id first
                $qs = [];
                $query = parse_url($link, PHP_URL_QUERY);
                if ($query) { parse_str($query, $qs); }
                if (!empty($qs['id'])) {
                    $transaction = $tm->getTransactionWithDetails((int)$qs['id']);
                }
                // If not found via link, look for the most recent pending header matching title/message pattern for this recipient
                if (!$transaction) {
                    // Extract CV no from message if present: e.g., "(CV: CV-20250101-123456)"
                    $stmtMsg = $this->db->prepare("SELECT message FROM notifications WHERE id = ?");
                    $stmtMsg->execute([(int)$id]);
                    $msg = (string)$stmtMsg->fetchColumn();
                    if ($msg) {
                        if (preg_match('/CV:\s*([A-Za-z0-9\-]+)/', $msg, $m)) {
                            $cv = $m[1];
                            $q = $this->db->prepare("SELECT id FROM transactions WHERE reference_no = ? AND parent_transaction_id IS NULL ORDER BY id DESC LIMIT 1");
                            $q->execute([$cv]);
                            $tid = $q->fetchColumn();
                            if ($tid) {
                                $transaction = $tm->getTransactionWithDetails((int)$tid);
                            }
                        }
                    }
                }
            }
            return $this->jsonResponse(['success' => true, 'transaction' => $transaction]);
        } catch (Exception $e) {
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
                user_id INT NOT NULL,
                comment TEXT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_trn (transaction_id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE
            ) ENGINE=InnoDB");
            $stmt = $this->db->prepare("INSERT INTO transaction_comments (transaction_id, user_id, comment) VALUES (?, ?, ?)");
            $stmt->execute([$transactionId, (int)$user['id'], $comment]);
            return $this->jsonResponse(['success' => true, 'message' => 'Comment added']);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
?>

