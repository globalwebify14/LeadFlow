<?php
require_once __DIR__ . '/../config/db.php';

class ActivityLog {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Write a log entry — safe, never throws.
     */
    public static function write(
        $pdo,
        string $action,
        string $description = '',
        $userId  = null,
        $orgId   = null,
        string $severity = 'info'
    ): void {
        try {
            $ip  = self::getClientIp();
            $uid = $userId ?? ($_SESSION['user_id']        ?? null);
            $oid = $orgId  ?? ($_SESSION['organization_id'] ?? null);
            $ua  = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

            $stmt = $pdo->prepare(
                "INSERT INTO activity_logs
                    (user_id, organization_id, action, description, ip_address, user_agent, severity)
                 VALUES
                    (:uid, :oid, :action, :desc, :ip, :ua, :severity)"
            );
            $stmt->execute([
                'uid'      => $uid,
                'oid'      => $oid,
                'action'   => $action,
                'desc'     => $description,
                'ip'       => $ip,
                'ua'       => $ua,
                'severity' => $severity,
            ]);
        } catch (Exception $e) {
            // Silently fail — never let logging crash the app
        }
    }

    /**
     * Write a security-level log (login_failed, schema_change, etc.)
     */
    public static function security($pdo, string $action, string $description = '', $userId = null, $orgId = null): void {
        self::write($pdo, $action, $description, $userId, $orgId, 'critical');
    }

    /**
     * Get all logs with optional search, org filter, type filter, date filter
     */
    public function getAll(int $limit = 50, int $offset = 0, string $search = '', string $orgFilter = '', string $typeFilter = '', string $dateFrom = '', string $dateTo = ''): array {
        try {
            $sql = "SELECT al.*, u.name as user_name, o.name as org_name
                    FROM activity_logs al
                    LEFT JOIN users u ON al.user_id = u.id
                    LEFT JOIN organizations o ON al.organization_id = o.id
                    WHERE 1=1";
            $params = [];

            if ($search) {
                $sql .= " AND (al.action LIKE :s OR al.description LIKE :s2 OR u.name LIKE :s3)";
                $params['s']  = "%$search%";
                $params['s2'] = "%$search%";
                $params['s3'] = "%$search%";
            }
            if ($orgFilter) {
                $sql .= " AND al.organization_id = :org";
                $params['org'] = (int)$orgFilter;
            }
            if ($typeFilter) {
                $sql .= " AND al.action = :type";
                $params['type'] = $typeFilter;
            }
            if ($dateFrom) {
                $sql .= " AND DATE(al.created_at) >= :dfrom";
                $params['dfrom'] = $dateFrom;
            }
            if ($dateTo) {
                $sql .= " AND DATE(al.created_at) <= :dto";
                $params['dto'] = $dateTo;
            }

            $sql .= " ORDER BY al.created_at DESC LIMIT :limit OFFSET :offset";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':limit',  (int)$limit,  PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            foreach ($params as $k => $v) $stmt->bindValue(":$k", $v);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    public function count(string $search = '', string $orgFilter = '', string $typeFilter = '', string $dateFrom = '', string $dateTo = ''): int {
        try {
            $sql = "SELECT COUNT(*) FROM activity_logs al LEFT JOIN users u ON al.user_id = u.id WHERE 1=1";
            $params = [];
            if ($search) {
                $sql .= " AND (al.action LIKE :s OR al.description LIKE :s2 OR u.name LIKE :s3)";
                $params['s']  = "%$search%";
                $params['s2'] = "%$search%";
                $params['s3'] = "%$search%";
            }
            if ($orgFilter) {
                $sql .= " AND al.organization_id = :org";
                $params['org'] = (int)$orgFilter;
            }
            if ($typeFilter) {
                $sql .= " AND al.action = :type";
                $params['type'] = $typeFilter;
            }
            if ($dateFrom) {
                $sql .= " AND DATE(al.created_at) >= :dfrom";
                $params['dfrom'] = $dateFrom;
            }
            if ($dateTo) {
                $sql .= " AND DATE(al.created_at) <= :dto";
                $params['dto'] = $dateTo;
            }
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get recent security/critical alerts
     */
    public function getSecurityAlerts(int $limit = 10): array {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT al.*, u.name as user_name, o.name as org_name
                 FROM activity_logs al
                 LEFT JOIN users u ON al.user_id = u.id
                 LEFT JOIN organizations o ON al.organization_id = o.id
                 WHERE al.severity = 'critical' OR al.action IN ('login_failed','schema_change','table_dropped','suspicious_access')
                 ORDER BY al.created_at DESC LIMIT :limit"
            );
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get distinct action types for the filter dropdown
     */
    public function getActionTypes(): array {
        try {
            $stmt = $this->pdo->query("SELECT DISTINCT action FROM activity_logs ORDER BY action ASC");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get stats for the monitoring dashboard
     */
    public function getDashboardStats(): array {
        try {
            $today = date('Y-m-d');
            $stmt  = $this->pdo->prepare(
                "SELECT
                    COUNT(*) as total_today,
                    SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical_today,
                    SUM(CASE WHEN action = 'login_failed' THEN 1 ELSE 0 END) as failed_logins,
                    SUM(CASE WHEN action = 'login_success' THEN 1 ELSE 0 END) as successful_logins,
                    SUM(CASE WHEN action = 'lead_created' THEN 1 ELSE 0 END) as leads_created,
                    SUM(CASE WHEN action = 'lead_imported' THEN 1 ELSE 0 END) as leads_imported,
                    SUM(CASE WHEN action = 'user_created' THEN 1 ELSE 0 END) as users_created
                 FROM activity_logs
                 WHERE DATE(created_at) = :today"
            );
            $stmt->execute(['today' => $today]);
            return $stmt->fetch() ?: [];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Export logs as CSV string
     */
    public function exportCsv(array $logs): string {
        $rows = [['Time', 'Action', 'Description', 'User', 'Organization', 'IP', 'Severity']];
        foreach ($logs as $l) {
            $rows[] = [
                $l['created_at'],
                $l['action'],
                $l['description'] ?? '',
                $l['user_name']   ?? 'System',
                $l['org_name']    ?? '—',
                $l['ip_address']  ?? '—',
                $l['severity']    ?? 'info',
            ];
        }
        $out = '';
        foreach ($rows as $r) {
            $out .= implode(',', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $r)) . "\n";
        }
        return $out;
    }

    private static function getClientIp(): string {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'] as $k) {
            if (!empty($_SERVER[$k])) {
                return explode(',', $_SERVER[$k])[0];
            }
        }
        return '0.0.0.0';
    }
}
?>
