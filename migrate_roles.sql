-- Migration script to update user roles

-- 1. Add new roles temporarily
ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'admin', 'manager', 'org_owner', 'org_admin', 'team_lead', 'agent') DEFAULT 'agent';

-- 2. Migrate existing users safely
UPDATE users SET role = 'org_owner' WHERE role = 'admin';
UPDATE users SET role = 'team_lead' WHERE role = 'manager';

-- 3. Solidify correct roles structure
ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'org_owner', 'org_admin', 'team_lead', 'agent') DEFAULT 'agent';
