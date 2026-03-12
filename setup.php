<?php
/**
 * CRM Setup Script — Creates all tables, seeds default data
 * Visit: http://localhost/lead/setup.php
 */
require_once 'config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { 
            background: #f0f2f5; font-family: 'Segoe UI', sans-serif; 
            padding: 40px 20px; 
        }
        .setup-card { 
            max-width: 700px; margin: 0 auto; background: #fff; 
            border-radius: 16px; padding: 40px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.08); 
        }
        .step { 
            padding: 10px 0; border-bottom: 1px solid #f0f0f0; 
            display: flex; align-items: center; gap: 12px; 
        }
        .step:last-child { border: none; }
        .step .icon { font-size: 18px; }
        .step.success .icon { color: #10b981; }
        .step.error .icon { color: #ef4444; }
        .step.info .icon { color: #3b82f6; }
    </style>
</head>
<body>
<div class="setup-card">
    <h2 class="mb-1"><i class="bi bi-rocket-takeoff me-2 text-primary"></i>Lead CRM — Setup</h2>
    <p class="text-muted mb-4">Creating database tables and seeding default data...</p>
    <hr class="mb-4">
<?php
$steps = [];

function logStep($msg, $type = 'success') {
    global $steps;
    $icon = $type === 'success' ? 'bi-check-circle-fill' : ($type === 'error' ? 'bi-x-circle-fill' : 'bi-info-circle-fill');
    $steps[] = ['msg' => $msg, 'type' => $type, 'icon' => $icon];
}

try {
    // ============================================
    // DROP ALL OLD TABLES (correct dependency order)
    // ============================================
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $dropTables = [
        'billing_history', 'assignment_rules', 'meta_integrations',
        'api_keys', 'notifications', 'attachments', 'communication_logs',
        'followups', 'deal_activities', 'deals', 'pipeline_stages',
        'lead_tag_map', 'lead_tags', 'lead_notes', 'lead_activities',
        'leads', 'users', 'subscriptions', 'plans', 'organizations'
    ];
    foreach ($dropTables as $t) {
        $pdo->exec("DROP TABLE IF EXISTS `$t`");
    }
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    logStep('All old tables dropped (clean slate)');

    // ---- Organizations ----
    $pdo->exec("CREATE TABLE IF NOT EXISTS organizations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(100) NOT NULL UNIQUE,
        email VARCHAR(255) DEFAULT NULL,
        phone VARCHAR(50) DEFAULT NULL,
        address TEXT DEFAULT NULL,
        logo VARCHAR(255) DEFAULT NULL,
        website VARCHAR(255) DEFAULT NULL,
        status ENUM('active','inactive','suspended') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    logStep('Organizations table created');

    // ---- Plans ----
    $pdo->exec("CREATE TABLE IF NOT EXISTS plans (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(50) NOT NULL UNIQUE,
        price DECIMAL(10,2) DEFAULT 0.00,
        billing_cycle ENUM('monthly','yearly','lifetime') DEFAULT 'monthly',
        max_users INT DEFAULT 5,
        max_leads INT DEFAULT 1000,
        max_deals INT DEFAULT 500,
        features JSON DEFAULT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    logStep('Plans table created');

    // ---- Subscriptions ----
    $pdo->exec("CREATE TABLE IF NOT EXISTS subscriptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        organization_id INT NOT NULL,
        plan_id INT NOT NULL,
        status ENUM('active','expired','cancelled','trial') DEFAULT 'trial',
        starts_at DATE DEFAULT NULL,
        expires_at DATE DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
        FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    logStep('Subscriptions table created');

    // ---- Users ----
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        organization_id INT DEFAULT NULL,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        phone VARCHAR(50) DEFAULT NULL,
        role ENUM('super_admin','admin','manager','agent') DEFAULT 'agent',
        avatar VARCHAR(255) DEFAULT NULL,
        is_active TINYINT(1) DEFAULT 1,
        last_login TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE SET NULL,
        INDEX idx_users_org (organization_id),
        INDEX idx_users_role (role)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    logStep('Users table created (enhanced with roles)');

    // ---- Leads ----
    $pdo->exec("CREATE TABLE IF NOT EXISTS leads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        organization_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        phone VARCHAR(50) NOT NULL,
        email VARCHAR(255) DEFAULT NULL,
        company VARCHAR(255) DEFAULT NULL,
        source VARCHAR(100) DEFAULT NULL,
        status VARCHAR(50) DEFAULT 'New Lead',
        priority ENUM('Hot','Warm','Cold') DEFAULT 'Warm',
        assigned_to INT DEFAULT NULL,
        note TEXT DEFAULT NULL,
        pipeline_stage_id INT DEFAULT NULL,
        meta_campaign VARCHAR(255) DEFAULT NULL,
        meta_form_id VARCHAR(100) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
        FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_leads_org (organization_id),
        INDEX idx_leads_status (status),
        INDEX idx_leads_priority (priority),
        INDEX idx_leads_assigned (assigned_to),
        INDEX idx_leads_source (source),
        INDEX idx_leads_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    logStep('Leads table created (enhanced with priority, assignment)');

    // ---- Lead Tags ----
    $pdo->exec("CREATE TABLE IF NOT EXISTS lead_tags (
        id INT AUTO_INCREMENT PRIMARY KEY,
        organization_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        color VARCHAR(7) DEFAULT '#6366f1',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
        UNIQUE KEY unique_tag_org (name, organization_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    logStep('Lead Tags table created');

    // ---- Lead Tag Map ----
    $pdo->exec("CREATE TABLE IF NOT EXISTS lead_tag_map (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lead_id INT NOT NULL,
        tag_id INT NOT NULL,
        FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
        FOREIGN KEY (tag_id) REFERENCES lead_tags(id) ON DELETE CASCADE,
        UNIQUE KEY unique_lead_tag (lead_id, tag_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    logStep('Lead Tag Map table created');

    // ---- Lead Notes ----
    $pdo->exec("CREATE TABLE IF NOT EXISTS lead_notes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lead_id INT NOT NULL,
        user_id INT DEFAULT NULL,
        note TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    logStep('Lead Notes table created');

    // ---- Lead Activities ----
    $pdo->exec("CREATE TABLE IF NOT EXISTS lead_activities (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lead_id INT NOT NULL,
        user_id INT DEFAULT NULL,
        activity_type ENUM('status_change','note','call','email','meeting','followup','assignment','deal_created') DEFAULT 'status_change',
        description TEXT DEFAULT NULL,
        old_value VARCHAR(255) DEFAULT NULL,
        new_value VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_activities_lead (lead_id),
        INDEX idx_activities_type (activity_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    logStep('Lead Activities table created (enhanced with types)');

    // ---- Pipeline Stages ----
    $pdo->exec("CREATE TABLE IF NOT EXISTS pipeline_stages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        organization_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        color VARCHAR(7) DEFAULT '#6366f1',
        position INT DEFAULT 0,
        is_won TINYINT(1) DEFAULT 0,
        is_lost TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
        INDEX idx_pipeline_org (organization_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    logStep('Pipeline Stages table created');

    // ---- Deals ----
    $pdo->exec("CREATE TABLE IF NOT EXISTS deals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        organization_id INT NOT NULL,
        lead_id INT DEFAULT NULL,
        name VARCHAR(255) NOT NULL,
        value DECIMAL(15,2) DEFAULT 0.00,
        stage_id INT DEFAULT NULL,
        assigned_to INT DEFAULT NULL,
        expected_close_date DATE DEFAULT NULL,
        status ENUM('open','won','lost') DEFAULT 'open',
        description TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
        FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL,
        FOREIGN KEY (stage_id) REFERENCES pipeline_stages(id) ON DELETE SET NULL,
        FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_deals_org (organization_id),
        INDEX idx_deals_status (status),
        INDEX idx_deals_stage (stage_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    logStep('Deals table created');

    // ---- Deal Activities ----
    $pdo->exec("CREATE TABLE IF NOT EXISTS deal_activities (
        id INT AUTO_INCREMENT PRIMARY KEY,
        deal_id INT NOT NULL,
        user_id INT DEFAULT NULL,
        activity_type ENUM('stage_change','note','call','email','meeting','value_change') DEFAULT 'note',
        description TEXT DEFAULT NULL,
        old_value VARCHAR(255) DEFAULT NULL,
        new_value VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (deal_id) REFERENCES deals(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    logStep('Deal Activities table created');

    // ---- Follow-ups ----
    $pdo->exec("CREATE TABLE IF NOT EXISTS followups (
        id INT AUTO_INCREMENT PRIMARY KEY,
        organization_id INT NOT NULL,
        lead_id INT DEFAULT NULL,
        deal_id INT DEFAULT NULL,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT DEFAULT NULL,
        followup_date DATE NOT NULL,
        followup_time TIME DEFAULT NULL,
        priority ENUM('high','medium','low') DEFAULT 'medium',
        status ENUM('pending','completed','missed') DEFAULT 'pending',
        completed_at TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
        FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
        FOREIGN KEY (deal_id) REFERENCES deals(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_followups_date (followup_date),
        INDEX idx_followups_user (user_id),
        INDEX idx_followups_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    logStep('Follow-ups table created');

    // ---- Communication Logs ----
    $pdo->exec("CREATE TABLE IF NOT EXISTS communication_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        organization_id INT NOT NULL,
        lead_id INT DEFAULT NULL,
        deal_id INT DEFAULT NULL,
        user_id INT DEFAULT NULL,
        type ENUM('call','email','sms','whatsapp','meeting','other') DEFAULT 'call',
        direction ENUM('inbound','outbound') DEFAULT 'outbound',
        subject VARCHAR(255) DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        duration INT DEFAULT NULL,
        outcome VARCHAR(100) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
        FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL,
        FOREIGN KEY (deal_id) REFERENCES deals(id) ON DELETE SET NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    logStep('Communication Logs table created');

    // ---- Attachments ----
    $pdo->exec("CREATE TABLE IF NOT EXISTS attachments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        organization_id INT NOT NULL,
        lead_id INT DEFAULT NULL,
        deal_id INT DEFAULT NULL,
        user_id INT DEFAULT NULL,
        file_name VARCHAR(255) NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        file_type VARCHAR(100) DEFAULT NULL,
        file_size INT DEFAULT 0,
        file_path VARCHAR(500) NOT NULL,
        category ENUM('quotation','proposal','contract','document','image','other') DEFAULT 'document',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
        FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL,
        FOREIGN KEY (deal_id) REFERENCES deals(id) ON DELETE SET NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    logStep('Attachments table created');

    // ---- Notifications ----
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        organization_id INT NOT NULL,
        user_id INT NOT NULL,
        type VARCHAR(50) NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT DEFAULT NULL,
        link VARCHAR(500) DEFAULT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_notifications_user (user_id),
        INDEX idx_notifications_read (is_read)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    logStep('Notifications table created');

    // ---- API Keys ----
    $pdo->exec("CREATE TABLE IF NOT EXISTS api_keys (
        id INT AUTO_INCREMENT PRIMARY KEY,
        organization_id INT NOT NULL,
        user_id INT DEFAULT NULL,
        api_key VARCHAR(64) NOT NULL UNIQUE,
        name VARCHAR(100) DEFAULT 'Default',
        permissions JSON DEFAULT NULL,
        is_active TINYINT(1) DEFAULT 1,
        last_used_at TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_apikey (api_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    logStep('API Keys table created');

    // ---- Meta Integrations ----
    $pdo->exec("CREATE TABLE IF NOT EXISTS meta_integrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        organization_id INT NOT NULL,
        page_name VARCHAR(255) DEFAULT NULL,
        page_id VARCHAR(100) DEFAULT NULL,
        access_token TEXT DEFAULT NULL,
        form_id VARCHAR(100) DEFAULT NULL,
        form_name VARCHAR(255) DEFAULT NULL,
        auto_assign_to INT DEFAULT NULL,
        is_active TINYINT(1) DEFAULT 1,
        last_synced_at TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
        FOREIGN KEY (auto_assign_to) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    logStep('Meta Integrations table created');

    // ---- Assignment Rules ----
    $pdo->exec("CREATE TABLE IF NOT EXISTS assignment_rules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        organization_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        type ENUM('round_robin','manual','source_based') DEFAULT 'round_robin',
        source_filter VARCHAR(100) DEFAULT NULL,
        agent_ids JSON DEFAULT NULL,
        last_assigned_index INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    logStep('Assignment Rules table created');

    // ---- Billing History ----
    $pdo->exec("CREATE TABLE IF NOT EXISTS billing_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        organization_id INT NOT NULL,
        plan_id INT DEFAULT NULL,
        amount DECIMAL(10,2) NOT NULL,
        payment_method VARCHAR(50) DEFAULT NULL,
        transaction_id VARCHAR(255) DEFAULT NULL,
        status ENUM('paid','pending','failed','refunded') DEFAULT 'paid',
        billing_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
        FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    logStep('Billing History table created');

    // ============================================
    // SEED DEFAULT DATA
    // ============================================

    // Default Organization
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM organizations WHERE slug = 'default'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO organizations (name, slug, email) VALUES ('My Company', 'default', 'admin@crm.com')");
        logStep('Default organization created');
    } else {
        logStep('Default organization already exists', 'info');
    }
    $orgId = $pdo->query("SELECT id FROM organizations WHERE slug = 'default'")->fetchColumn();

    // Default Plans
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM plans");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO plans (name, slug, price, billing_cycle, max_users, max_leads, max_deals, features) VALUES 
            ('Basic', 'basic', 0.00, 'monthly', 3, 500, 100, '{\"pipeline\": true, \"reports\": false, \"api\": false, \"meta_integration\": false}'),
            ('Pro', 'pro', 999.00, 'monthly', 10, 5000, 1000, '{\"pipeline\": true, \"reports\": true, \"api\": true, \"meta_integration\": false}'),
            ('Enterprise', 'enterprise', 2499.00, 'monthly', 50, 50000, 10000, '{\"pipeline\": true, \"reports\": true, \"api\": true, \"meta_integration\": true}')
        ");
        logStep('Subscription plans seeded (Basic / Pro / Enterprise)');
    } else {
        logStep('Plans already exist', 'info');
    }
    $planId = $pdo->query("SELECT id FROM plans WHERE slug = 'pro'")->fetchColumn();

    // Default Subscription
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM subscriptions WHERE organization_id = ?");
    $stmt->execute([$orgId]);
    if ($stmt->fetchColumn() == 0) {
        $pdo->prepare("INSERT INTO subscriptions (organization_id, plan_id, status, starts_at, expires_at) VALUES (?, ?, 'active', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR))")
            ->execute([$orgId, $planId]);
        logStep('Default subscription created (Pro plan)');
    } else {
        logStep('Subscription already exists', 'info');
    }

    // Default Admin User
    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = 'admin@crm.com'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $pdo->prepare("INSERT INTO users (organization_id, name, email, password, role) VALUES (?, 'Admin', 'admin@crm.com', ?, 'admin')")
            ->execute([$orgId, $hashedPassword]);
        logStep('Default admin user created');
    } else {
        $pdo->prepare("UPDATE users SET password = ?, role = 'admin', organization_id = ? WHERE email = 'admin@crm.com'")
            ->execute([$hashedPassword, $orgId]);
        logStep('Admin user password reset', 'info');
    }

    // Demo Sales Agents
    $demoAgents = [
        ['Rahul Sharma', 'rahul@crm.com', 'agent'],
        ['Priya Patel', 'priya@crm.com', 'agent'],
        ['Amit Manager', 'amit@crm.com', 'manager'],
    ];
    foreach ($demoAgents as $agent) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$agent[1]]);
        if ($stmt->fetchColumn() == 0) {
            $pdo->prepare("INSERT INTO users (organization_id, name, email, password, role) VALUES (?, ?, ?, ?, ?)")
                ->execute([$orgId, $agent[0], $agent[1], $hashedPassword, $agent[2]]);
        }
    }
    logStep('Demo sales agents created (Rahul, Priya, Amit)');

    // Default Pipeline Stages
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pipeline_stages WHERE organization_id = ?");
    $stmt->execute([$orgId]);
    if ($stmt->fetchColumn() == 0) {
        $stages = [
            ['New Lead', '#6366f1', 0, 0, 0],
            ['Contacted', '#3b82f6', 1, 0, 0],
            ['Qualified', '#8b5cf6', 2, 0, 0],
            ['Proposal Sent', '#f59e0b', 3, 0, 0],
            ['Negotiation', '#f97316', 4, 0, 0],
            ['Closed Won', '#10b981', 5, 1, 0],
            ['Closed Lost', '#ef4444', 6, 0, 1],
        ];
        $stageStmt = $pdo->prepare("INSERT INTO pipeline_stages (organization_id, name, color, position, is_won, is_lost) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($stages as $s) {
            $stageStmt->execute([$orgId, $s[0], $s[1], $s[2], $s[3], $s[4]]);
        }
        logStep('Pipeline stages seeded (7 stages)');
    } else {
        logStep('Pipeline stages already exist', 'info');
    }

    // Default Tags
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM lead_tags WHERE organization_id = ?");
    $stmt->execute([$orgId]);
    if ($stmt->fetchColumn() == 0) {
        $tags = [
            ['VIP', '#ef4444'],
            ['Interested', '#10b981'],
            ['Callback', '#f59e0b'],
            ['Website', '#3b82f6'],
            ['Referral', '#8b5cf6'],
        ];
        $tagStmt = $pdo->prepare("INSERT INTO lead_tags (organization_id, name, color) VALUES (?, ?, ?)");
        foreach ($tags as $t) {
            $tagStmt->execute([$orgId, $t[0], $t[1]]);
        }
        logStep('Default lead tags seeded (5 tags)');
    } else {
        logStep('Lead tags already exist', 'info');
    }

    // Default API Key
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM api_keys WHERE organization_id = ?");
    $stmt->execute([$orgId]);
    if ($stmt->fetchColumn() == 0) {
        $apiKey = bin2hex(random_bytes(32));
        $pdo->prepare("INSERT INTO api_keys (organization_id, api_key, name) VALUES (?, ?, 'Default Key')")
            ->execute([$orgId, $apiKey]);
        logStep("API key generated: <code>$apiKey</code>");
    } else {
        logStep('API key already exists', 'info');
    }

    // Create uploads directory
    if (!is_dir('uploads')) {
        mkdir('uploads', 0755, true);
        logStep('Uploads directory created');
    } else {
        logStep('Uploads directory exists', 'info');
    }

} catch (PDOException $e) {
    logStep('Error: ' . $e->getMessage(), 'error');
}

// Render steps
foreach ($steps as $step): ?>
    <div class="step <?= $step['type'] ?>">
        <i class="bi <?= $step['icon'] ?> icon"></i>
        <span><?= $step['msg'] ?></span>
    </div>
<?php endforeach; ?>

    <hr class="mt-4">
    <div class="mt-3">
        <div class="bg-light rounded p-3 mb-3">
            <strong>Login Credentials:</strong><br>
            <span class="text-muted">Email:</span> <strong>admin@crm.com</strong><br>
            <span class="text-muted">Password:</span> <strong>admin123</strong>
        </div>
        <a href="login.php" class="btn btn-primary me-2"><i class="bi bi-box-arrow-in-right me-1"></i>Go to Login</a>
        <a href="modules/dashboard/" class="btn btn-outline-secondary"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
    </div>
</div>
</body>
</html>
