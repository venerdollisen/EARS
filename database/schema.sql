DROP DATABASE ears_db;
-- EARS Database Schema
-- Enterprise Analytics and Result Systems

-- Create database
CREATE DATABASE IF NOT EXISTS ears_db;
USE ears_db;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    role ENUM('admin', 'user', 'manager') DEFAULT 'user',
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Accounting parameters table
CREATE TABLE accounting_parameters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parameter_name VARCHAR(100) NOT NULL,
    parameter_value TEXT,
    description TEXT,
    category VARCHAR(50) DEFAULT 'general',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Account title groups table
CREATE TABLE account_title_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_name VARCHAR(100) NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- COA account types table
CREATE TABLE coa_account_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(100) NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Chart of accounts table
CREATE TABLE chart_of_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_code VARCHAR(20) UNIQUE NOT NULL,
    account_name VARCHAR(200) NOT NULL,
    account_type_id INT,
    group_id INT,
    description TEXT,
    balance DECIMAL(15,2) DEFAULT 0.00,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (account_type_id) REFERENCES coa_account_types(id),
    FOREIGN KEY (group_id) REFERENCES account_title_groups(id)
);

-- Suppliers table (Subsidiary accounts)
CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(200) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    vat_subject ENUM('VAT', 'Non-VAT', 'Zero-Rated') DEFAULT 'VAT',
    tin VARCHAR(20),
    vat_rate DECIMAL(5,2) DEFAULT 12.00,
    vat_account_id INT,
    account_id INT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES chart_of_accounts(id),
    FOREIGN KEY (vat_account_id) REFERENCES chart_of_accounts(id)
);

-- Projects table
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_code VARCHAR(20) UNIQUE NOT NULL,
    project_name VARCHAR(200) NOT NULL,
    description TEXT,
    start_date DATE,
    end_date DATE,
    budget DECIMAL(15,2) DEFAULT 0.00,
    manager VARCHAR(100),
    status ENUM('active', 'inactive', 'completed', 'on_hold') DEFAULT 'active',
    created_by INT,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (updated_by) REFERENCES users(id)
);

-- Departments table
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_code VARCHAR(20) UNIQUE NOT NULL,
    department_name VARCHAR(200) NOT NULL,
    description TEXT,
    manager VARCHAR(100),
    location VARCHAR(200),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_by INT,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (updated_by) REFERENCES users(id)
);

-- Insert default data

-- Default admin user (password: admin123)
INSERT INTO users (username, password, full_name, email, role) VALUES 
('admin', '$2y$10$6E1/PBkUVUHre1ZzSeBx6OB6LNRXwGZwmKK0FDY9s5sjFsaeKGt9a', 'System Administrator', 'admin@ears.com', 'admin');

-- Default accounting parameters
INSERT INTO accounting_parameters (parameter_name, parameter_value, description, category) VALUES
('company_name', 'Enterprise Analytics and Result Systems', 'Company Name', 'company'),
('company_address', '', 'Company Address', 'company'),
('company_phone', '', 'Company Phone', 'company'),
('company_email', '', 'Company Email', 'company'),
('fiscal_year_start', '2024-01-01', 'Fiscal Year Start Date', 'fiscal'),
('fiscal_year_end', '2025-12-31', 'Fiscal Year End Date', 'fiscal'),
('default_currency', 'PHP', 'Default Currency', 'currency'),
('decimal_places', '2', 'Number of Decimal Places', 'display'),
('date_format', 'Y-m-d', 'Date Format', 'display'),
('timezone', 'Asia/Manila', 'Timezone', 'display'),
('theme', 'light', 'Theme', 'display'),
('language', 'en', 'Language', 'display'),
('records_per_page', '25', 'Records per Page', 'display'),
('auto_backup', '1', 'Enable Auto Backup', 'system'),
('session_timeout', '3600', 'Session Timeout in Seconds', 'security'),
('max_login_attempts', '5', 'Maximum Login Attempts', 'security'),
('lockout_duration', '30', 'Lockout Duration in Minutes', 'security'),
('password_expiry_days', '90', 'Password Expiry in Days', 'security'),
('min_password_length', '8', 'Minimum Password Length', 'security'),
('require_uppercase', '1', 'Require Uppercase Letters', 'security'),
('require_numbers', '1', 'Require Numbers', 'security'),
('require_special_chars', '1', 'Require Special Characters', 'security'),
('enable_audit_log', '1', 'Enable Audit Logging', 'security'),
('ip_whitelist', '', 'IP Whitelist', 'security'),
('maintenance_mode', '0', 'Maintenance Mode', 'security'),
('enable_2fa', '0', 'Enable Two-Factor Authentication', 'security'),
('2fa_method', 'email', '2FA Method', 'security'),
('backup_codes_count', '10', 'Backup Codes Count', 'security'),
('backup_frequency', 'daily', 'Backup Frequency', 'backup'),
('backup_time', '02:00', 'Backup Time', 'backup'),
('backup_retention_days', '30', 'Backup Retention Days', 'backup'),
('backup_database', '1', 'Include Database in Backup', 'backup'),
('backup_files', '1', 'Include Files in Backup', 'backup'),
('backup_compression', 'medium', 'Backup Compression Level', 'backup'),
('backup_encryption', '0', 'Encrypt Backups', 'backup'),
('backup_location', 'local', 'Backup Location', 'backup'),
('backup_path', '/backups', 'Backup Directory', 'backup'),
('max_backup_size', '1000', 'Maximum Backup Size in MB', 'backup'),
('backup_notification_email', '', 'Backup Notification Email', 'backup'),
('backup_notification_success', '1', 'Notify on Backup Success', 'backup'),
('backup_notification_failure', '1', 'Notify on Backup Failure', 'backup'),
('backup_log_level', 'info', 'Backup Log Level', 'backup'),
('smtp_host', '', 'SMTP Host', 'email'),
('smtp_port', '587', 'SMTP Port', 'email'),
('smtp_username', '', 'SMTP Username', 'email'),
('smtp_password', '', 'SMTP Password', 'email'),
('smtp_encryption', 'tls', 'SMTP Encryption', 'email'),
('from_email', '', 'From Email', 'email'),
('from_name', '', 'From Name', 'email'),
('reply_to_email', '', 'Reply-To Email', 'email'),
('email_signature', '', 'Email Signature', 'email'),
('notify_system_errors', '1', 'Notify System Errors', 'notifications'),
('notify_login_attempts', '1', 'Notify Failed Login Attempts', 'notifications'),
('notify_backup_status', '1', 'Notify Backup Status', 'notifications'),
('notify_disk_space', '1', 'Notify Low Disk Space', 'notifications'),
('notify_new_transactions', '1', 'Notify New Transactions', 'notifications'),
('notify_large_transactions', '1', 'Notify Large Transactions', 'notifications'),
('large_transaction_threshold', '10000', 'Large Transaction Threshold', 'notifications'),
('notify_monthly_reports', '1', 'Notify Monthly Reports', 'notifications'),
('enable_in_app_notifications', '1', 'Enable In-App Notifications', 'notifications'),
('notification_sound', '1', 'Notification Sound', 'notifications'),
('notification_position', 'top-right', 'Notification Position', 'notifications'),
('notification_duration', '5', 'Notification Duration in Seconds', 'notifications'),
('enable_sms_notifications', '0', 'Enable SMS Notifications', 'notifications'),
('sms_provider', 'twilio', 'SMS Provider', 'notifications'),
('sms_api_key', '', 'SMS API Key', 'notifications'),
('sms_api_secret', '', 'SMS API Secret', 'notifications');

-- Default COA account types
INSERT INTO coa_account_types (type_name, description) VALUES
('Asset', 'Accounts that represent economic resources'),
('Liability', 'Accounts that represent economic obligations'),
('Equity', 'Accounts that represent ownership interest'),
('Revenue', 'Accounts that represent income'),
('Expense', 'Accounts that represent costs');

-- Default account title groups
-- Seed data for account_title_groups
INSERT INTO account_title_groups (group_name, description, status)
VALUES
  ('Current Assets', 'Accounts under short-term asset category', 'active'),
  ('Fixed Assets', 'Accounts for long-term tangible assets', 'active'),
  ('Intangible Assets', 'Non-physical assets like goodwill or patents', 'active'),
  ('Current Liabilities', 'Short-term financial obligations', 'active'),
  ('Long-term Liabilities', 'Debts and obligations due beyond one year', 'active'),
  ('Operating Expenses', 'Day-to-day business operating costs', 'active'),
  ('Administrative Expenses', 'General overhead expenses not directly tied to production', 'active'),
  ('Revenue', 'Income earned from normal business operations', 'active'),
  ('Other Income', 'Non-operating income such as interest or investment returns', 'active'),
  ('VAT Accounts', 'Group for VAT-related accounts (e.g., Input VAT, Output VAT)', 'active');


-- Default chart of accounts
INSERT INTO chart_of_accounts (account_code, account_name, account_type_id, group_id, description)
VALUES
  ('1000', 'Cash on Hand', 1, 1, 'Physical cash available'),
  ('1001', 'Accounts Receivable', 1, 1, 'Amounts owed by customers'),
  ('1002', 'Input VAT', 1, 10, 'VAT paid on purchases, claimable from government'),
  ('1100', 'Office Equipment', 1, 2, 'Office furniture and equipment'),
  ('1200', 'Goodwill', 1, 3, 'Intangible asset from acquisition'),
  ('2000', 'Accounts Payable', 2, 4, 'Amounts owed to suppliers'),
  ('2001', 'VAT Payable', 2, 10, 'Tax collected from customers to be remitted to government'),
  ('2100', 'Bank Loan', 2, 5, 'Long-term loan from bank'),
--   ('3000', 'Common Stock', 3, NULL, 'Owner investment in company'),
--   ('3100', 'Retained Earnings', 3, NULL, 'Accumulated profits reinvested'),
  ('4000', 'Sales Revenue', 4, 8, 'Income from sales of products/services'),
  ('4100', 'Service Revenue', 4, 8, 'Income from service fees'),
  ('5000', 'Salaries Expense', 5, 6, 'Costs of employee wages'),
  ('5100', 'Rent Expense', 5, 6, 'Costs of office rent'),
  ('5200', 'Office Supplies Expense', 5, 7, 'Costs of office materials and supplies');


-- Default suppliers
INSERT INTO suppliers (supplier_name, contact_person, phone, email, address, vat_subject, tin, vat_rate, vat_account_id, account_id) VALUES
('ABC Supplies Co.', 'John Smith', '+63 912 345 6789', 'john@abcsupplies.com', '123 Main St., Manila', 'VAT', '123-456-789-000', 12.00, 9, 4),
('XYZ Corporation', 'Jane Doe', '+63 987 654 3210', 'jane@xyzcorp.com', '456 Business Ave., Quezon City', 'Non-VAT', '987-654-321-000', 0.00, NULL, 4);

-- Default projects
INSERT INTO projects (project_code, project_name, description, start_date, end_date, budget, manager, status) VALUES
('PRJ-001', 'Office Renovation', 'Complete renovation of main office building', '2024-01-01', '2024-06-30', 500000.00, 'Maria Santos', 'active'),
('PRJ-002', 'IT Infrastructure Upgrade', 'Upgrade of computer systems and network', '2024-03-01', '2024-08-31', 300000.00, 'Juan Dela Cruz', 'active'),
('PRJ-003', 'Marketing Campaign 2024', 'Annual marketing and advertising campaign', '2024-01-01', '2024-12-31', 200000.00, 'Ana Reyes', 'active');

-- Default departments
INSERT INTO departments (department_code, department_name, description, manager, location, status) VALUES
('DEPT-001', 'Finance Department', 'Handles all financial operations and accounting', 'Pedro Martinez', '2nd Floor, Main Building', 'active'),
('DEPT-002', 'Human Resources', 'Manages personnel and recruitment', 'Carmen Lopez', '1st Floor, Main Building', 'active'),
('DEPT-003', 'Information Technology', 'IT support and system administration', 'Roberto Garcia', '3rd Floor, Annex Building', 'active'),
('DEPT-004', 'Marketing Department', 'Marketing and sales operations', 'Isabel Torres', '1st Floor, Main Building', 'active'),
('DEPT-005', 'Operations Department', 'General operations and logistics', 'Miguel Rodriguez', 'Ground Floor, Warehouse', 'active');

-- Add audit trail table
CREATE TABLE audit_trail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(50) NOT NULL,
    table_name VARCHAR(50) NOT NULL,
    record_id INT NOT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_table_name (table_name),
    INDEX idx_record_id (record_id),
    INDEX idx_created_at (created_at)
);

-- Add foreign key for user_id
ALTER TABLE audit_trail
ADD CONSTRAINT fk_audit_trail_user
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL; 

-- In-app notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255) NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_at DATETIME NULL,
    INDEX idx_user_read (recipient_user_id, is_read),
    CONSTRAINT fk_notifications_user FOREIGN KEY (recipient_user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Separate Books Migration Script
-- Convert unified transaction tables to book-specific tables
-- Step 1: Create new book-specific tables

-- Cash Receipts
CREATE TABLE IF NOT EXISTS cash_receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference_no VARCHAR(50) UNIQUE NOT NULL,
    transaction_date DATE NOT NULL,
    total_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    description TEXT,
    payment_form ENUM('cash', 'check', 'bank_transfer') DEFAULT 'cash',
    check_number VARCHAR(50),
    bank VARCHAR(100),
    payee_name VARCHAR(255),
    billing_number VARCHAR(50),
    collection_receipt VARCHAR(50),
    delivery_receipt VARCHAR(50),
    return_reason TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS cash_receipt_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cash_receipt_id INT NOT NULL,
    account_id INT NOT NULL,
    project_id INT,
    department_id INT,
    supplier_id INT,
    transaction_type ENUM('debit', 'credit') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cash_receipt_id) REFERENCES cash_receipts(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES chart_of_accounts(id),
    FOREIGN KEY (project_id) REFERENCES projects(id),
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
);

-- Cash Disbursements
CREATE TABLE IF NOT EXISTS cash_disbursements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference_no VARCHAR(50) UNIQUE NOT NULL,
    transaction_date DATE NOT NULL,
    total_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    description TEXT,
    supplier_id INT,
    project_id INT,
    department_id INT,
    payment_form ENUM('cash', 'check') DEFAULT 'cash',
    payee_name VARCHAR(255),
    po_number VARCHAR(50),
    cwo_number VARCHAR(50),
    return_reason TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (project_id) REFERENCES projects(id),
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS cash_disbursement_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cash_disbursement_id INT NOT NULL,
    account_id INT NOT NULL,
    project_id INT,
    department_id INT,
    supplier_id INT,
    transaction_type ENUM('debit', 'credit') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cash_disbursement_id) REFERENCES cash_disbursements(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES chart_of_accounts(id),
    FOREIGN KEY (project_id) REFERENCES projects(id),
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
);

-- Check Disbursements
CREATE TABLE IF NOT EXISTS check_disbursements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference_no VARCHAR(50) UNIQUE NOT NULL,
    transaction_date DATE NOT NULL,
    total_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    description TEXT,
    supplier_id INT,
    project_id INT,
    department_id INT,
    check_number VARCHAR(50),
    bank VARCHAR(100),
    check_date DATE,
    payee_name VARCHAR(255),
    po_number VARCHAR(50),
    cwo_number VARCHAR(50),
    ebr_number VARCHAR(50),
    return_reason TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (project_id) REFERENCES projects(id),
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS check_disbursement_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    check_disbursement_id INT NOT NULL,
    account_id INT NOT NULL,
    project_id INT,
    department_id INT,
    supplier_id INT,
    transaction_type ENUM('debit', 'credit') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (check_disbursement_id) REFERENCES check_disbursements(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES chart_of_accounts(id),
    FOREIGN KEY (project_id) REFERENCES projects(id),
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
);

-- Step 2: Create indexes for better performance
CREATE INDEX idx_cash_receipts_date ON cash_receipts(transaction_date);
CREATE INDEX idx_cash_receipts_status ON cash_receipts(status);
CREATE INDEX idx_cash_receipts_reference ON cash_receipts(reference_no);
CREATE INDEX idx_cash_receipt_details_receipt ON cash_receipt_details(cash_receipt_id);
CREATE INDEX idx_cash_receipt_details_account ON cash_receipt_details(account_id);
CREATE INDEX idx_cash_receipt_details_type ON cash_receipt_details(transaction_type);

CREATE INDEX idx_cash_disbursements_date ON cash_disbursements(transaction_date);
CREATE INDEX idx_cash_disbursements_status ON cash_disbursements(status);
CREATE INDEX idx_cash_disbursements_reference ON cash_disbursements(reference_no);
CREATE INDEX idx_cash_disbursements_supplier ON cash_disbursements(supplier_id);
CREATE INDEX idx_cash_disbursement_details_disbursement ON cash_disbursement_details(cash_disbursement_id);
CREATE INDEX idx_cash_disbursement_details_account ON cash_disbursement_details(account_id);
CREATE INDEX idx_cash_disbursement_details_type ON cash_disbursement_details(transaction_type);

CREATE INDEX idx_check_disbursements_date ON check_disbursements(transaction_date);
CREATE INDEX idx_check_disbursements_status ON check_disbursements(status);
CREATE INDEX idx_check_disbursements_reference ON check_disbursements(reference_no);
CREATE INDEX idx_check_disbursements_supplier ON check_disbursements(supplier_id);
CREATE INDEX idx_check_disbursement_details_disbursement ON check_disbursement_details(check_disbursement_id);
CREATE INDEX idx_check_disbursement_details_account ON check_disbursement_details(account_id);
CREATE INDEX idx_check_disbursement_details_type ON check_disbursement_details(transaction_type);

-- Journal Entries Header
CREATE TABLE IF NOT EXISTS journal_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference_no VARCHAR(50) UNIQUE NOT NULL,
    transaction_date DATE NOT NULL,
    description TEXT,
    total_amount DECIMAL(15,2) DEFAULT 0.00,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    jv_status ENUM('active', 'inactive') DEFAULT 'active',
    for_posting ENUM('for_checking', 'for_posting') DEFAULT 'for_checking',
    reference_number1 VARCHAR(50),
    reference_number2 VARCHAR(50),
    cwo_number VARCHAR(50),
    bill_invoice_ref VARCHAR(50),
    rejection_reason TEXT,
    created_by INT,
    approved_by INT,
    rejected_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_at TIMESTAMP NULL,
    rejected_at TIMESTAMP NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id),
    FOREIGN KEY (rejected_by) REFERENCES users(id)
);

-- Journal Entry Details
CREATE TABLE IF NOT EXISTS journal_entry_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    journal_entry_id INT NOT NULL,
    account_id INT NOT NULL,
    project_id INT,
    department_id INT,
    supplier_id INT,
    transaction_type ENUM('debit', 'credit') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES chart_of_accounts(id),
    FOREIGN KEY (project_id) REFERENCES projects(id),
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
);

-- Create indexes for journal entries (if they don't exist)
CREATE INDEX IF NOT EXISTS idx_journal_entries_date ON journal_entries(transaction_date);
CREATE INDEX IF NOT EXISTS idx_journal_entries_status ON journal_entries(status);
CREATE INDEX IF NOT EXISTS idx_journal_entries_jv_status ON journal_entries(jv_status);
CREATE INDEX IF NOT EXISTS idx_journal_entries_for_posting ON journal_entries(for_posting);
CREATE INDEX IF NOT EXISTS idx_journal_entries_reference ON journal_entries(reference_no);
CREATE INDEX IF NOT EXISTS idx_journal_entry_details_entry ON journal_entry_details(journal_entry_id);
CREATE INDEX IF NOT EXISTS idx_journal_entry_details_account ON journal_entry_details(account_id);
CREATE INDEX IF NOT EXISTS idx_journal_entry_details_type ON journal_entry_details(transaction_type);

-- Create view for journal entry summaries (drop if exists first)
DROP VIEW IF EXISTS v_journal_entry_summaries;
CREATE VIEW v_journal_entry_summaries AS
SELECT 
    je.id,
    je.reference_no,
    je.transaction_date,
    je.description,
    je.total_amount,
    je.status,
    je.jv_status,
    je.for_posting,
    je.reference_number1,
    je.bill_invoice_ref,
    je.created_at,
    je.approved_at,
    je.rejected_at,
    u_created.username as created_by_username,
    u_created.full_name as created_by_full_name,
    u_approved.full_name as approved_by_full_name,
    u_rejected.full_name as rejected_by_full_name
FROM journal_entries je
LEFT JOIN users u_created ON je.created_by = u_created.id
LEFT JOIN users u_approved ON je.approved_by = u_approved.id
LEFT JOIN users u_rejected ON je.rejected_by = u_rejected.id
ORDER BY je.transaction_date DESC, je.id DESC;

-- Create view for journal entry details with account information (drop if exists first)
DROP VIEW IF EXISTS v_journal_entry_details;
CREATE VIEW v_journal_entry_details AS
SELECT 
    jed.id,
    jed.journal_entry_id,
    je.reference_no,
    je.transaction_date,
    jed.account_id,
    coa.account_code,
    coa.account_name,
    cat.type_name as account_type,
    jed.amount,
    jed.transaction_type,
    jed.description,
    jed.project_id,
    p.project_name,
    jed.department_id,
    d.department_name,
    jed.supplier_id,
    s.supplier_name,
    je.created_by,
    u.full_name as created_by_name,
    je.created_at
FROM journal_entry_details jed
JOIN journal_entries je ON jed.journal_entry_id = je.id
JOIN chart_of_accounts coa ON jed.account_id = coa.id
JOIN coa_account_types cat ON coa.account_type_id = cat.id
LEFT JOIN projects p ON jed.project_id = p.id
LEFT JOIN departments d ON jed.department_id = d.id
LEFT JOIN suppliers s ON jed.supplier_id = s.id
LEFT JOIN users u ON je.created_by = u.id;


-- Step 4: Create views for unified reporting (optional)
DROP VIEW IF EXISTS v_all_transactions;
CREATE VIEW v_all_transactions AS
SELECT 
    'cash_receipt' as transaction_type,
    id,
    reference_no,
    transaction_date,
    total_amount,
    description,
    status,
    created_by,
    created_at
FROM cash_receipts
UNION ALL
SELECT 
    'cash_disbursement' as transaction_type,
    id,
    reference_no,
    transaction_date,
    total_amount,
    description,
    status,
    created_by,
    created_at
FROM cash_disbursements
UNION ALL
SELECT 
    'check_disbursement' as transaction_type,
    id,
    reference_no,
    transaction_date,
    total_amount,
    description,
    status,
    created_by,
    created_at
FROM check_disbursements
UNION ALL
SELECT 
    'journal_entry' as transaction_type,
    id,
    reference_no,
    transaction_date,
    total_amount,
    description,
    status,
    created_by,
    created_at
FROM journal_entries
ORDER BY transaction_date DESC, id DESC;

-- Step 5: Create unified account balance view
DROP VIEW IF EXISTS v_unified_account_balances;
CREATE VIEW v_unified_account_balances AS
SELECT 
    coa.id as account_id,
    coa.account_code,
    coa.account_name,
    cat.type_name as account_type,
    COALESCE(SUM(CASE WHEN crd.transaction_type = 'debit' THEN crd.amount ELSE 0 END), 0) +
    COALESCE(SUM(CASE WHEN cdd.transaction_type = 'debit' THEN cdd.amount ELSE 0 END), 0) +
    COALESCE(SUM(CASE WHEN chdd.transaction_type = 'debit' THEN chdd.amount ELSE 0 END), 0) +
    COALESCE(SUM(CASE WHEN jed.transaction_type = 'debit' THEN jed.amount ELSE 0 END), 0) as total_debits,
    COALESCE(SUM(CASE WHEN crd.transaction_type = 'credit' THEN crd.amount ELSE 0 END), 0) +
    COALESCE(SUM(CASE WHEN cdd.transaction_type = 'credit' THEN cdd.amount ELSE 0 END), 0) +
    COALESCE(SUM(CASE WHEN chdd.transaction_type = 'credit' THEN chdd.amount ELSE 0 END), 0) +
    COALESCE(SUM(CASE WHEN jed.transaction_type = 'credit' THEN jed.amount ELSE 0 END), 0) as total_credits,
    CASE 
        WHEN cat.type_name IN ('Asset', 'Expense') THEN 
            (COALESCE(SUM(CASE WHEN crd.transaction_type = 'debit' THEN crd.amount ELSE 0 END), 0) +
             COALESCE(SUM(CASE WHEN cdd.transaction_type = 'debit' THEN cdd.amount ELSE 0 END), 0) +
             COALESCE(SUM(CASE WHEN chdd.transaction_type = 'debit' THEN chdd.amount ELSE 0 END), 0) +
             COALESCE(SUM(CASE WHEN jed.transaction_type = 'debit' THEN jed.amount ELSE 0 END), 0)) -
            (COALESCE(SUM(CASE WHEN crd.transaction_type = 'credit' THEN crd.amount ELSE 0 END), 0) +
             COALESCE(SUM(CASE WHEN cdd.transaction_type = 'credit' THEN cdd.amount ELSE 0 END), 0) +
             COALESCE(SUM(CASE WHEN chdd.transaction_type = 'credit' THEN chdd.amount ELSE 0 END), 0) +
             COALESCE(SUM(CASE WHEN jed.transaction_type = 'credit' THEN jed.amount ELSE 0 END), 0))
        ELSE 
            (COALESCE(SUM(CASE WHEN crd.transaction_type = 'credit' THEN crd.amount ELSE 0 END), 0) +
             COALESCE(SUM(CASE WHEN cdd.transaction_type = 'credit' THEN cdd.amount ELSE 0 END), 0) +
             COALESCE(SUM(CASE WHEN chdd.transaction_type = 'credit' THEN chdd.amount ELSE 0 END), 0) +
             COALESCE(SUM(CASE WHEN jed.transaction_type = 'credit' THEN jed.amount ELSE 0 END), 0)) -
            (COALESCE(SUM(CASE WHEN crd.transaction_type = 'debit' THEN crd.amount ELSE 0 END), 0) +
             COALESCE(SUM(CASE WHEN cdd.transaction_type = 'debit' THEN cdd.amount ELSE 0 END), 0) +
             COALESCE(SUM(CASE WHEN chdd.transaction_type = 'debit' THEN chdd.amount ELSE 0 END), 0) +
             COALESCE(SUM(CASE WHEN jed.transaction_type = 'debit' THEN jed.amount ELSE 0 END), 0))
    END as balance
FROM chart_of_accounts coa
LEFT JOIN coa_account_types cat ON coa.account_type_id = cat.id
LEFT JOIN cash_receipt_details crd ON coa.id = crd.account_id
LEFT JOIN cash_disbursement_details cdd ON coa.id = cdd.account_id
LEFT JOIN check_disbursement_details chdd ON coa.id = chdd.account_id
LEFT JOIN journal_entry_details jed ON coa.id = jed.account_id
WHERE coa.status = 'active'
GROUP BY coa.id, coa.account_code, coa.account_name, cat.type_name;

ALTER TABLE users
ADD year_start DATE NOT NULL DEFAULT '2000-01-01',
ADD year_end DATE NOT NULL DEFAULT '2000-12-31';

