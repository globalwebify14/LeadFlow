<?php
require_once __DIR__ . '/../config/db.php';

class Automation {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getSequences($orgId) {
        $stmt = $this->pdo->prepare("SELECT * FROM automation_sequences WHERE organization_id = :org_id ORDER BY id DESC");
        $stmt->execute(['org_id' => $orgId]);
        return $stmt->fetchAll();
    }

    public function getSequenceWithSteps($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM automation_sequences WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $sequence = $stmt->fetch();
        
        if ($sequence) {
            $stmt = $this->pdo->prepare("SELECT * FROM automation_steps WHERE sequence_id = :id ORDER BY day_offset ASC, id ASC");
            $stmt->execute(['id' => $id]);
            $sequence['steps'] = $stmt->fetchAll();
        }
        return $sequence;
    }

    public function createSequence($orgId, $name) {
        $stmt = $this->pdo->prepare("INSERT INTO automation_sequences (organization_id, name) VALUES (:org_id, :name)");
        $stmt->execute(['org_id' => $orgId, 'name' => $name]);
        return $this->pdo->lastInsertId();
    }

    public function updateSequence($id, $data) {
        $stmt = $this->pdo->prepare("UPDATE automation_sequences SET name = :name, is_active = :is_active WHERE id = :id");
        return $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'is_active' => $data['is_active']
        ]);
    }

    public function addStep($sequenceId, $data) {
        $stmt = $this->pdo->prepare("INSERT INTO automation_steps (sequence_id, day_offset, action_type, action_data) VALUES (:sid, :offset, :type, :data)");
        return $stmt->execute([
            'sid' => $sequenceId,
            'offset' => $data['day_offset'],
            'type' => $data['action_type'],
            'data' => $data['action_data']
        ]);
    }

    public function deleteStep($id) {
        $stmt = $this->pdo->prepare("DELETE FROM automation_steps WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function deleteSequence($id) {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare("DELETE FROM automation_steps WHERE sequence_id = :id")->execute(['id' => $id]);
            $this->pdo->prepare("DELETE FROM automation_sequences WHERE id = :id")->execute(['id' => $id]);
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }
}
?>
