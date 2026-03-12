<?php
/**
 * MetaIntegration Model — Manages Facebook/Instagram Lead Ads integration
 */
class MetaIntegration {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get all integrations for an organization
     */
    public function getAll($orgId) {
        $stmt = $this->pdo->prepare("SELECT mi.*, u.name as agent_name FROM meta_integrations mi LEFT JOIN users u ON mi.auto_assign_to = u.id WHERE mi.organization_id = :org ORDER BY mi.created_at DESC");
        $stmt->execute(['org' => $orgId]);
        return $stmt->fetchAll();
    }

    /**
     * Get single integration
     */
    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM meta_integrations WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Create new integration
     */
    public function create($data) {
        $stmt = $this->pdo->prepare("INSERT INTO meta_integrations (organization_id, page_name, page_id, access_token, form_id, form_name, auto_assign_to, is_active) VALUES (:org, :page_name, :page_id, :token, :form_id, :form_name, :assign, :active)");
        return $stmt->execute([
            'org'       => $data['organization_id'],
            'page_name' => $data['page_name'],
            'page_id'   => $data['page_id'],
            'token'     => $data['access_token'],
            'form_id'   => $data['form_id'],
            'form_name' => $data['form_name'] ?: null,
            'assign'    => $data['auto_assign_to'] ?: null,
            'active'    => $data['is_active'] ?? 1,
        ]);
    }

    /**
     * Update integration
     */
    public function update($id, $data) {
        $stmt = $this->pdo->prepare("UPDATE meta_integrations SET page_name = :page_name, page_id = :page_id, access_token = :token, form_id = :form_id, form_name = :form_name, auto_assign_to = :assign, is_active = :active WHERE id = :id");
        return $stmt->execute([
            'id'        => $id,
            'page_name' => $data['page_name'],
            'page_id'   => $data['page_id'],
            'token'     => $data['access_token'],
            'form_id'   => $data['form_id'],
            'form_name' => $data['form_name'] ?: null,
            'assign'    => $data['auto_assign_to'] ?: null,
            'active'    => $data['is_active'] ?? 1,
        ]);
    }

    /**
     * Delete integration
     */
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM meta_integrations WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Toggle active state
     */
    public function toggleActive($id) {
        $stmt = $this->pdo->prepare("UPDATE meta_integrations SET is_active = NOT is_active WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Update last synced time
     */
    public function updateLastSynced($id) {
        $stmt = $this->pdo->prepare("UPDATE meta_integrations SET last_synced_at = NOW() WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Get active integrations (for webhook)
     */
    public function getActiveByFormId($formId) {
        $stmt = $this->pdo->prepare("SELECT * FROM meta_integrations WHERE form_id = :fid AND is_active = 1 LIMIT 1");
        $stmt->execute(['fid' => $formId]);
        return $stmt->fetch();
    }

    /**
     * Create lead from Meta data
     */
    public function createLeadFromMeta($orgId, $leadData, $integration) {
        $stmt = $this->pdo->prepare("INSERT INTO leads (organization_id, name, phone, email, source, status, priority, assigned_to, note, meta_campaign, meta_form_id, created_at) VALUES (:org, :name, :phone, :email, :source, 'New Lead', 'Warm', :assign, :note, :campaign, :form_id, NOW())");
        $stmt->execute([
            'org'      => $orgId,
            'name'     => $leadData['name'] ?? 'Meta Lead',
            'phone'    => $leadData['phone'] ?? '',
            'email'    => $leadData['email'] ?? null,
            'source'   => 'Facebook',
            'assign'   => $integration['auto_assign_to'],
            'note'     => 'Auto-imported from Meta Lead Ads',
            'campaign' => $leadData['campaign_name'] ?? $integration['page_name'],
            'form_id'  => $integration['form_id'],
        ]);
        return $this->pdo->lastInsertId();
    }

    /**
     * Get leads imported via Meta
     */
    public function getMetaLeads($orgId, $limit = 20) {
        $stmt = $this->pdo->prepare("SELECT l.*, u.name as agent_name FROM leads l LEFT JOIN users u ON l.assigned_to = u.id WHERE l.organization_id = :org AND l.meta_form_id IS NOT NULL ORDER BY l.created_at DESC LIMIT :lim");
        $stmt->bindValue('org', $orgId, PDO::PARAM_INT);
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Count meta leads
     */
    public function getMetaLeadCount($orgId) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM leads WHERE organization_id = :org AND meta_form_id IS NOT NULL");
        $stmt->execute(['org' => $orgId]);
        return $stmt->fetchColumn();
    }
}
?>
