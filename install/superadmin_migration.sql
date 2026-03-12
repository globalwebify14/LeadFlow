-- =============================================
-- Super Admin Module Migration
-- Run this in phpMyAdmin on crm_leads database
-- =============================================

-- 0. Create billing_history table (if not exists)
CREATE TABLE IF NOT EXISTS billing_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id INT DEFAULT NULL,
    plan_id INT DEFAULT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('paid','pending','failed') DEFAULT 'pending',
    payment_method VARCHAR(100) DEFAULT NULL,
    transaction_id VARCHAR(255) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    paid_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE SET NULL,
    FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE SET NULL,
    INDEX idx_billing_org (organization_id),
    INDEX idx_billing_status (status),
    INDEX idx_billing_paid (paid_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 1. Add missing columns to organizations
ALTER TABLE organizations
    ADD COLUMN IF NOT EXISTS owner_name VARCHAR(255) DEFAULT NULL AFTER name,
    ADD COLUMN IF NOT EXISTS owner_id INT DEFAULT NULL AFTER owner_name,
    ADD COLUMN IF NOT EXISTS subscription_plan_id INT DEFAULT NULL AFTER owner_id;

-- 2. Add missing columns to plans
ALTER TABLE plans
    ADD COLUMN IF NOT EXISTS yearly_price DECIMAL(10,2) DEFAULT 0.00 AFTER price,
    ADD COLUMN IF NOT EXISTS storage_limit INT DEFAULT 5120 AFTER max_deals,
    ADD COLUMN IF NOT EXISTS description TEXT DEFAULT NULL AFTER storage_limit;

-- 3. Create activity_logs table
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    organization_id INT DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_logs_user (user_id),
    INDEX idx_logs_org (organization_id),
    INDEX idx_logs_action (action),
    INDEX idx_logs_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Seed default subscription plans
INSERT IGNORE INTO plans (name, slug, price, yearly_price, max_users, max_leads, max_deals, storage_limit, description, is_active) VALUES
('Basic',        'basic',        999.00,  9990.00,  5,   500,   200,  1024,  'Great for small teams', 1),
('Professional', 'professional', 2499.00, 24990.00, 20,  5000,  2000, 5120,  'For growing businesses', 1),
('Enterprise',   'enterprise',   4999.00, 49990.00, 100, 50000, 20000,20480, 'Unlimited power', 1);

-- 5. Log the migration
INSERT INTO activity_logs (action, description) VALUES
('system_migration', 'Super Admin module migration applied');
