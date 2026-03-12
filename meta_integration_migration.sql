USE crm_leads;

-- 1. System Settings for Global Super Admin Configs
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pre-seed the required Facebook App settings
INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES 
('facebook_app_id', ''),
('facebook_app_secret', ''),
('webhook_verify_token', 'rand0m_v3r1fy_t0k3n_2024');

-- 2. Modify or recreate facebook_integrations 
-- Since `meta_integrations` already exists but the prompt asked for `facebook_integrations`, we'll rename or create a fresh one matching the new robust OAuth model.
CREATE TABLE IF NOT EXISTS facebook_integrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id INT NOT NULL,
    facebook_user_id VARCHAR(100) NOT NULL,
    access_token TEXT NOT NULL,
    token_expiry TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Connected Facebook Pages
CREATE TABLE IF NOT EXISTS facebook_pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id INT NOT NULL,
    page_id VARCHAR(100) NOT NULL,
    page_name VARCHAR(255) NOT NULL,
    page_access_token TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY org_page (organization_id, page_id),
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Connected Facebook Forms
CREATE TABLE IF NOT EXISTS facebook_forms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id INT NOT NULL,
    page_id VARCHAR(100) NOT NULL,
    form_id VARCHAR(100) NOT NULL,
    form_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY org_form (organization_id, form_id),
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Raw Facebook Leads
CREATE TABLE IF NOT EXISTS facebook_leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id INT NOT NULL,
    page_id VARCHAR(100) NOT NULL,
    form_id VARCHAR(100) NOT NULL,
    leadgen_id VARCHAR(100) NOT NULL UNIQUE,
    raw_data JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Webhook Debug Logs
CREATE TABLE IF NOT EXISTS webhook_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(100) NOT NULL,
    payload JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
