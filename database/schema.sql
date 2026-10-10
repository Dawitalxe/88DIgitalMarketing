CREATE DATABASE IF NOT EXISTS digi_mark_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE digi_mark_erp;

CREATE TABLE companies (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  code VARCHAR(30) NOT NULL UNIQUE,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_company_status(status)
) ENGINE=InnoDB;

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NULL,
  full_name VARCHAR(160) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  status ENUM('active','inactive','locked') NOT NULL DEFAULT 'active',
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  CONSTRAINT fk_users_company FOREIGN KEY(company_id) REFERENCES companies(id) ON DELETE SET NULL,
  INDEX idx_users_company(company_id),
  INDEX idx_users_status(status)
) ENGINE=InnoDB;

CREATE TABLE roles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE,
  label VARCHAR(120) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE permissions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  key_name VARCHAR(120) NOT NULL UNIQUE,
  label VARCHAR(180) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE user_roles (
  user_id BIGINT UNSIGNED NOT NULL,
  role_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY(user_id,role_id),
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY(role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE role_permissions (
  role_id BIGINT UNSIGNED NOT NULL,
  permission_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY(role_id,permission_id),
  FOREIGN KEY(role_id) REFERENCES roles(id) ON DELETE CASCADE,
  FOREIGN KEY(permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE teams (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NULL,
  name VARCHAR(120) NOT NULL,
  leader_user_id BIGINT UNSIGNED NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  FOREIGN KEY(company_id) REFERENCES companies(id) ON DELETE SET NULL,
  FOREIGN KEY(leader_user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_teams_company(company_id)
) ENGINE=InnoDB;

CREATE TABLE team_members (
  team_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY(team_id,user_id),
  FOREIGN KEY(team_id) REFERENCES teams(id) ON DELETE CASCADE,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE tasks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NULL,
  assigned_to BIGINT UNSIGNED NULL,
  assigned_by BIGINT UNSIGNED NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT NULL,
  status ENUM('Pending','In Progress','Done') NOT NULL DEFAULT 'Pending',
  due_date DATE NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  FOREIGN KEY(company_id) REFERENCES companies(id) ON DELETE SET NULL,
  FOREIGN KEY(assigned_to) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY(assigned_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_tasks_assignee_status(assigned_to,status),
  INDEX idx_tasks_company(company_id)
) ENGINE=InnoDB;

CREATE TABLE requests (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NULL,
  requested_by BIGINT UNSIGNED NOT NULL,
  reviewed_by BIGINT UNSIGNED NULL,
  request_type ENUM('Design','Content','Gate Pass','Permission','Model Approval','Leave','Priority Message','Other') NOT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT NULL,
  status ENUM('Pending','Approved','Rejected','Cancelled') NOT NULL DEFAULT 'Pending',
  requested_at DATETIME NOT NULL,
  reviewed_at DATETIME NULL,
  FOREIGN KEY(company_id) REFERENCES companies(id) ON DELETE SET NULL,
  FOREIGN KEY(requested_by) REFERENCES users(id) ON DELETE RESTRICT,
  FOREIGN KEY(reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_requests_status_date(status,requested_at),
  INDEX idx_requests_company(company_id)
) ENGINE=InnoDB;

CREATE TABLE attendance (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  work_date DATE NOT NULL,
  check_in DATETIME NULL,
  check_out DATETIME NULL,
  notes VARCHAR(500) NULL,
  UNIQUE KEY uq_attendance_user_date(user_id,work_date),
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_attendance_date(work_date)
) ENGINE=InnoDB;

CREATE TABLE salaries (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  pay_month CHAR(7) NOT NULL,
  base_salary DECIMAL(14,2) NOT NULL DEFAULT 0,
  bonus DECIMAL(14,2) NOT NULL DEFAULT 0,
  deductions DECIMAL(14,2) NOT NULL DEFAULT 0,
  notes VARCHAR(500) NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_salary_user_month(user_id,pay_month),
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE targets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  team_id BIGINT UNSIGNED NULL,
  company_id BIGINT UNSIGNED NULL,
  target_month CHAR(7) NOT NULL,
  content_target INT UNSIGNED NOT NULL DEFAULT 0,
  campaign_event_target INT UNSIGNED NOT NULL DEFAULT 0,
  content_actual INT UNSIGNED NOT NULL DEFAULT 0,
  campaign_event_actual INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  FOREIGN KEY(team_id) REFERENCES teams(id) ON DELETE SET NULL,
  FOREIGN KEY(company_id) REFERENCES companies(id) ON DELETE SET NULL,
  INDEX idx_targets_month(target_month)
) ENGINE=InnoDB;

CREATE TABLE company_performance (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  metric_date DATE NOT NULL,
  score DECIMAL(7,2) NOT NULL DEFAULT 0,
  notes VARCHAR(500) NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY(company_id) REFERENCES companies(id) ON DELETE CASCADE,
  FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL,
  UNIQUE KEY uq_company_performance(company_id,metric_date)
) ENGINE=InnoDB;

CREATE TABLE social_metrics (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  metric_date DATE NOT NULL,
  platform VARCHAR(40) NOT NULL,
  engagement BIGINT UNSIGNED NOT NULL DEFAULT 0,
  reach BIGINT UNSIGNED NOT NULL DEFAULT 0,
  content_count INT UNSIGNED NOT NULL DEFAULT 0,
  FOREIGN KEY(company_id) REFERENCES companies(id) ON DELETE CASCADE,
  UNIQUE KEY uq_social(company_id,metric_date,platform),
  INDEX idx_social_company_date(company_id,metric_date)
) ENGINE=InnoDB;

CREATE TABLE content_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NULL,
  title VARCHAR(200) NOT NULL,
  content_type VARCHAR(60) NULL,
  status ENUM('Draft','Review','Approved','Published','Archived') NOT NULL DEFAULT 'Draft',
  package_due_date DATE NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  FOREIGN KEY(company_id) REFERENCES companies(id) ON DELETE SET NULL,
  INDEX idx_content_company_status(company_id,status)
) ENGINE=InnoDB;

CREATE TABLE content_feedback (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  content_id BIGINT UNSIGNED NOT NULL,
  company_id BIGINT UNSIGNED NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  message TEXT NOT NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY(content_id) REFERENCES content_items(id) ON DELETE CASCADE,
  FOREIGN KEY(company_id) REFERENCES companies(id) ON DELETE SET NULL,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE RESTRICT,
  INDEX idx_feedback_content(content_id)
) ENGINE=InnoDB;

CREATE TABLE inventory_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(180) NOT NULL,
  category VARCHAR(100) NULL,
  quantity INT UNSIGNED NOT NULL DEFAULT 0,
  unit VARCHAR(30) NOT NULL DEFAULT 'pcs',
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_inventory_status(status)
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  company_id BIGINT UNSIGNED NULL,
  action VARCHAR(120) NOT NULL,
  entity_type VARCHAR(80) NULL,
  entity_id BIGINT UNSIGNED NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(500) NULL,
  metadata_json JSON NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY(company_id) REFERENCES companies(id) ON DELETE SET NULL,
  INDEX idx_audit_created(created_at),
  INDEX idx_audit_action(action),
  INDEX idx_audit_user(user_id)
) ENGINE=InnoDB;

CREATE TABLE security_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  ip_address VARCHAR(45) NULL,
  event_type VARCHAR(80) NOT NULL,
  reason VARCHAR(255) NOT NULL,
  metadata_json JSON NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_security_created(created_at),
  INDEX idx_security_type(event_type)
) ENGINE=InnoDB;

CREATE TABLE system_settings (
  setting_key VARCHAR(100) PRIMARY KEY,
  setting_value TEXT NOT NULL,
  updated_by BIGINT UNSIGNED NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY(updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO system_settings(setting_key,setting_value,updated_at) VALUES
('maintenance_mode','0',NOW())
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);

INSERT INTO roles(name,label) VALUES
('super_admin','IT Dispatch / Super Admin'),
('operations_admin','Admin Dispatch / Operations'),
('hr','Human Resources'),
('team_leader','Team Leader Dispatch'),
('employee','Employee Dispatch'),
('reception','Reception Dispatch'),
('client','Client Dispatch')
ON DUPLICATE KEY UPDATE label=VALUES(label);

INSERT INTO permissions(key_name,label) VALUES
('*','Super administrator wildcard'),
('system.maintenance.read','Read maintenance state'),
('system.maintenance.toggle','Toggle maintenance mode'),
('audit.read','Read audit trail'),
('tasks.read','Read assigned tasks'),
('tasks.update','Update task status'),
('tasks.assign','Assign tasks'),
('approvals.read','Read approval queue'),
('approvals.decide','Approve/reject requests'),
('companies.read','Read companies'),
('companies.write','Create/update companies'),
('companies.delete','Archive companies'),
('payroll.read','Read payroll'),
('payroll.write','Write payroll'),
('attendance.read','Read attendance'),
('attendance.write','Write attendance'),
('inventory.read','Read inventory'),
('inventory.write','Write inventory'),
('gate_pass.create','Create gate pass'),
('client.analytics.read','Read client analytics'),
('client.feedback.create','Create content feedback'),
('users.read','Read users'),
('users.write','Create/update users'),
('users.delete','Deactivate users'),
('roles.read','Read roles'),
('roles.write','Manage roles'),
('teams.read','Read teams'),
('teams.write','Manage teams'),
('targets.read','Read targets'),
('targets.write','Manage targets'),
('performance.read','Read company performance'),
('performance.write','Write company performance')
ON DUPLICATE KEY UPDATE label=VALUES(label);

INSERT INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p
WHERE r.name='super_admin'
ON DUPLICATE KEY UPDATE role_id=VALUES(role_id);

INSERT INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p
WHERE r.name='operations_admin' AND p.key_name IN (
'tasks.read','tasks.update','tasks.assign','approvals.read','approvals.decide',
'companies.read','companies.write','companies.delete','attendance.read','attendance.write',
'payroll.read','inventory.read','inventory.write','teams.read','teams.write','targets.read',
'targets.write','performance.read','performance.write','users.read'
)
ON DUPLICATE KEY UPDATE role_id=VALUES(role_id);

INSERT INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p
WHERE r.name='hr' AND p.key_name IN ('attendance.read','attendance.write','payroll.read','payroll.write','users.read','companies.read')
ON DUPLICATE KEY UPDATE role_id=VALUES(role_id);

INSERT INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p
WHERE r.name='team_leader' AND p.key_name IN ('tasks.read','tasks.update','tasks.assign','targets.read','targets.write','performance.read','performance.write','approvals.read','approvals.decide')
ON DUPLICATE KEY UPDATE role_id=VALUES(role_id);

INSERT INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p
WHERE r.name='employee' AND p.key_name IN ('tasks.read','tasks.update','gate_pass.create')
ON DUPLICATE KEY UPDATE role_id=VALUES(role_id);

INSERT INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p
WHERE r.name='reception' AND p.key_name IN ('inventory.read','inventory.write','gate_pass.create','attendance.read')
ON DUPLICATE KEY UPDATE role_id=VALUES(role_id);

INSERT INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p
WHERE r.name='client' AND p.key_name IN ('client.analytics.read','client.feedback.create')
ON DUPLICATE KEY UPDATE role_id=VALUES(role_id);
