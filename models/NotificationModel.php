<?php
require_once BASE_PATH . '/core/Model.php';

class NotificationModel extends Model {
    protected $table = 'notifications';

    public function __construct() {
        parent::__construct();
    }

    public function createNotification(int $recipientUserId, string $title, string $message, ?string $link = null): int {
        // De-duplicate: if an identical notification already exists for this user, return it instead of creating new.
        // Also, if a new link is provided and existing has no link (or different), update it.
        $existing = $this->findExistingNotification($recipientUserId, $title, $message);
        if ($existing) {
            if ($link && (!isset($existing['link']) || $existing['link'] !== $link)) {
                $this->updateLink((int)$existing['id'], $link);
            }
            return (int)$existing['id'];
        }
        $data = [
            'recipient_user_id' => $recipientUserId,
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ];
        return (int)$this->create($data);
    }

    private function findExistingNotification(int $recipientUserId, string $title, string $message): ?array {
        $sql = "SELECT id, link FROM {$this->table} WHERE recipient_user_id = ? AND title = ? AND message = ? ORDER BY id DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$recipientUserId, $title, $message]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getUnreadCountForUser(int $userId): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE recipient_user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    public function getRecentForUser(int $userId, int $limit = 10): array {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE recipient_user_id = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateLink(int $notificationId, string $link): bool {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET link = ? WHERE id = ?");
        return $stmt->execute([$link, $notificationId]);
    }

    public function markAsRead(int $notificationId, int $userId): bool {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET is_read = 1, read_at = NOW() WHERE id = ? AND recipient_user_id = ?");
        return $stmt->execute([$notificationId, $userId]);
    }

    /**
     * Delete notifications linked to a specific transaction id and/or reference no.
     * Returns number of deleted rows.
     */
    public function deleteByTransaction(int $transactionId, ?string $referenceNo = null): int {
        $clauses = [];
        $params = [];
        // Match link pattern with id query param
        $clauses[] = "(link LIKE ?)";
        $params[] = '%id=' . $transactionId . '%';
        // Also match by reference number in message if provided
        if ($referenceNo) {
            $clauses[] = "(message LIKE ?)";
            $params[] = '%' . $referenceNo . '%';
        }
        $sql = "DELETE FROM {$this->table} WHERE " . implode(' OR ', $clauses);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }
}
?>

