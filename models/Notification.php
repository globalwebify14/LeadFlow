<?php
require_once __DIR__ . '/../config/db.php';

class Notification {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function create($orgId, $userId, $type, $title, $message = '', $link = '') {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO notifications (organization_id, user_id, type, title, message, link) VALUES (:org, :user, :type, :title, :msg, :link)");
            return $stmt->execute([
                'org' => $orgId, 'user' => $userId, 'type' => $type,
                'title' => $title, 'msg' => $message, 'link' => $link,
            ]);
        } catch (Exception $e) {
            return false;
        }
    }

    public function getForUser($userId, $limit = 20, $unreadOnly = false) {
        try {
            $sql = "SELECT * FROM notifications WHERE user_id = :uid";
            if ($unreadOnly) $sql .= " AND is_read = 0";
            $sql .= " ORDER BY created_at DESC LIMIT :limit";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':uid',   $userId, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit,  PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    public function getUnreadCount($userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0");
            $stmt->execute(['uid' => $userId]);
            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }

    public function markAsRead($id) {
        try {
            $stmt = $this->pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = :id");
            return $stmt->execute(['id' => $id]);
        } catch (Exception $e) {
            return false;
        }
    }

    public function markAllRead($userId) {
        try {
            $stmt = $this->pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :uid AND is_read = 0");
            return $stmt->execute(['uid' => $userId]);
        } catch (Exception $e) {
            return false;
        }
    }
}
?>
