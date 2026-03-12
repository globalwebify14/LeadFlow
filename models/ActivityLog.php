<?php
require_once __DIR__ . '/../config/db.php';

class ActivityLog {
    private $pdo;

    public function __construct($pdo) { $this->pdo = $pdo; }

    public static function write($pdo, $action, $description = '', $userId = null, $orgId = null) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $uid = $userId ?? ($_SESSION['user_id'] ?? null);
        $oid = $orgId ?? ($_SESSION['organization_id'] ?? null);
        $stmt = $pdo->prepare(
            "INSERT INTO activity_logs (user_id, organization_id, action, description, ip_address)
             VALUES (:uid, :oid, :action, :desc, :ip)"
        );
        $stmt->execute(['uid' => $uid, 'oid' => $oid, 'action' => $action, 'desc' => $description, 'ip' => $ip]);
    }

    public function getAll($limit = 50, $offset = 0, $search = '') {
        $sql = "SELECT al.*, u.name as user_name, o.name as org_name
                FROM activity_logs al
                LEFT JOIN users u ON al.user_id = u.id
                LEFT JOIN organizations o ON al.organization_id = o.id
                WHERE 1=1";
        $params = [];
        if ($search) {
            $sql .= " AND (al.action LIKE :s OR al.description LIKE :s2 OR u.name LIKE :s3)";
            $params['s'] = "%$search%";
            $params['s2'] = "%$search%";
            $params['s3'] = "%$search%";
        }
        $sql .= " ORDER BY al.created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        foreach ($params as $k => $v) $stmt->bindValue(":$k", $v);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function count($search = '') {
        $sql = "SELECT COUNT(*) FROM activity_logs al LEFT JOIN users u ON al.user_id = u.id WHERE 1=1";
        $params = [];
        if ($search) {
            $sql .= " AND (al.action LIKE :s OR al.description LIKE :s2 OR u.name LIKE :s3)";
            $params['s'] = "%$search%";
            $params['s2'] = "%$search%";
            $params['s3'] = "%$search%";
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
}
?>
