<?php
require_once __DIR__ . '/../config/db.php';

class Task {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAllTasks($orgId, $filters = [], $limit = 20, $offset = 0) {
        $sql = "SELECT t.*, l.name as lead_name, u.name as agent_name 
                FROM tasks t 
                LEFT JOIN leads l ON t.lead_id = l.id 
                LEFT JOIN users u ON t.assigned_to = u.id 
                WHERE t.organization_id = :org_id";
        $params = [':org_id' => $orgId];

        if (!empty($filters['status'])) {
            $sql .= " AND t.status = :status";
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['assigned_to'])) {
            $sql .= " AND t.assigned_to = :assigned_to";
            $params[':assigned_to'] = $filters['assigned_to'];
        }
        if (!empty($filters['lead_id'])) {
            $sql .= " AND t.lead_id = :lead_id";
            $params[':lead_id'] = $filters['lead_id'];
        }

        $sql .= " ORDER BY t.due_date ASC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function createTask($data) {
        $stmt = $this->pdo->prepare("INSERT INTO tasks (organization_id, lead_id, assigned_to, task_title, description, due_date, status) 
                                     VALUES (:org_id, :lead_id, :assigned_to, :title, :desc, :due_date, :status)");
        return $stmt->execute([
            'org_id'      => $data['organization_id'],
            'lead_id'     => $data['lead_id'] ?? null,
            'assigned_to' => $data['assigned_to'] ?? null,
            'title'       => $data['task_title'],
            'desc'        => $data['description'] ?? null,
            'due_date'    => $data['due_date'] ?? null,
            'status'      => $data['status'] ?? 'pending'
        ]);
    }

    public function updateTask($id, $data) {
        $stmt = $this->pdo->prepare("UPDATE tasks SET task_title=:title, description=:desc, due_date=:due_date, status=:status, assigned_to=:assigned_to WHERE id=:id");
        return $stmt->execute([
            'id'       => $id,
            'title'    => $data['task_title'],
            'desc'     => $data['description'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'status'   => $data['status'] ?? 'pending',
            'assigned_to' => $data['assigned_to'] ?? null
        ]);
    }

    public function deleteTask($id) {
        $stmt = $this->pdo->prepare("DELETE FROM tasks WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function getTaskById($id, $orgId = null) {
        $sql = "SELECT * FROM tasks WHERE id = :id";
        $params = ['id' => $id];
        if ($orgId) {
            $sql .= " AND organization_id = :org_id";
            $params['org_id'] = $orgId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
}
?>
