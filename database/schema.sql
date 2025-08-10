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

-- Transactions table
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_type ENUM('cash_receipt', 'disbursement', 'journal_adjustment') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    account_id INT NOT NULL,
    supplier_id INT,
    description TEXT,
    reference_no VARCHAR(50) UNIQUE NOT NULL,
    transaction_date DATE NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES chart_of_accounts(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
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
('fiscal_year_end', '2024-12-31', 'Fiscal Year End Date', 'fiscal'),
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

-- Default account title groups
INSERT INTO account_title_groups (group_name, description) VALUES
('Assets', 'Current and non-current assets'),
('Liabilities', 'Current and long-term liabilities'),
('Equity', "Owner's equity and retained earnings"),
('Revenue', 'Income and revenue accounts'),
('Expenses', 'Operating and non-operating expenses');

-- Default COA account types
INSERT INTO coa_account_types (type_name, description) VALUES
('Asset', 'Accounts that represent economic resources'),
('Liability', 'Accounts that represent economic obligations'),
('Equity', 'Accounts that represent ownership interest'),
('Revenue', 'Accounts that represent income'),
('Expense', 'Accounts that represent costs');

-- Default chart of accounts
INSERT INTO chart_of_accounts (account_code, account_name, account_type_id, group_id, description) VALUES
('1000', 'Cash and Cash Equivalents', 1, 1, 'Cash on hand and in bank'),
('1100', 'Accounts Receivable', 1, 1, 'Amounts owed by customers'),
('1200', 'Inventory', 1, 1, 'Goods held for sale'),
('2000', 'Accounts Payable', 2, 2, 'Amounts owed to suppliers'),
('3000', "Owner's Equity", 3, 3, "Owner's investment and retained earnings"),
('4000', 'Sales Revenue', 4, 4, 'Revenue from sales of goods/services'),
('5000', 'Cost of Goods Sold', 5, 5, 'Direct costs of producing goods'),
('6000', 'Operating Expenses', 5, 5, 'General and administrative expenses'),
('2100', 'Input VAT', 2, 2, 'VAT paid on purchases'),
('2200', 'Output VAT', 2, 2, 'VAT collected on sales'),
('2300', 'VAT Payable', 2, 2, 'VAT payable to BIR');

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

-- Add new columns to transactions table
ALTER TABLE transactions 
ADD COLUMN payment_form ENUM('cash', 'check', 'bank_transfer', 'credit_card') DEFAULT 'cash' AFTER transaction_type,
ADD COLUMN check_number VARCHAR(50) NULL AFTER payment_form,
ADD COLUMN bank VARCHAR(100) NULL AFTER check_number,
ADD COLUMN billing_number VARCHAR(100) NULL AFTER bank,
ADD COLUMN parent_transaction_id INT NULL AFTER billing_number,
ADD COLUMN STATUS ENUM('draft', 'posted', 'cancelled') DEFAULT 'posted' AFTER parent_transaction_id,
ADD COLUMN payee_name VARCHAR(255) NULL AFTER STATUS,
ADD COLUMN po_number VARCHAR(100) NULL AFTER payee_name,
ADD COLUMN cwo_number VARCHAR(100) NULL AFTER po_number,
ADD COLUMN ebr_number VARCHAR(100) NULL AFTER cwo_number,
ADD COLUMN check_date DATE NULL AFTER ebr_number,
ADD COLUMN cv_status ENUM('Active', 'Inactive', 'Pending', 'Approved', 'Rejected') DEFAULT 'Active' AFTER check_date,
ADD COLUMN cv_checked ENUM('Checked', 'Unchecked', 'Pending') DEFAULT 'Checked' AFTER cv_status,
ADD COLUMN check_payment_status ENUM('Approved', 'Pending', 'Rejected', 'On Hold') DEFAULT 'Approved' AFTER cv_checked,
ADD COLUMN return_reason VARCHAR(120) NULL AFTER check_payment_status,
ADD COLUMN project_id INT NULL AFTER return_reason,
ADD COLUMN department_id INT NULL AFTER project_id;

-- Add index for better performance
CREATE INDEX idx_transactions_parent_id ON transactions(parent_transaction_id);
CREATE INDEX idx_transactions_reference ON transactions(reference_no);
CREATE INDEX idx_transactions_date ON transactions(transaction_date);

-- Update existing transactions to have proper status
UPDATE transactions SET STATUS = 'posted' WHERE STATUS IS NULL;

-- Add foreign key for parent_transaction_id (self-referencing)
ALTER TABLE transactions 
ADD CONSTRAINT fk_transactions_parent 
FOREIGN KEY (parent_transaction_id) REFERENCES transactions(id) ON DELETE SET NULL;

-- Add foreign keys for project_id and department_id
ALTER TABLE transactions 
ADD CONSTRAINT fk_transactions_project 
FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL;

ALTER TABLE transactions 
ADD CONSTRAINT fk_transactions_department 
FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL;

-- Add VAT subject and TIN columns to suppliers table
ALTER TABLE suppliers 
ADD COLUMN vat_subject ENUM('VAT', 'Non-VAT', 'Zero-Rated') DEFAULT 'VAT' AFTER address,
ADD COLUMN tin VARCHAR(20) NULL AFTER vat_subject; 

-- Seed sample cash receipt data (1000 headers with 2-5 distribution lines each)
DELIMITER $$
DROP PROCEDURE IF EXISTS seed_cash_receipts $$
CREATE PROCEDURE seed_cash_receipts(IN headerCount INT)
BEGIN
    DECLARE i INT DEFAULT 1;
    DECLARE distCount INT;
    DECLARE headerId INT;
    DECLARE trnDate DATE;
    DECLARE refNo VARCHAR(50);
    DECLARE debitAmt DECIMAL(15,2);
    DECLARE creditAmt DECIMAL(15,2);
    DECLARE acc1 INT;
    DECLARE acc2 INT;
    DECLARE proj INT;
    DECLARE dept INT;
    DECLARE supp INT;
    DECLARE dSeq INT;
    DECLARE cSeq INT;

    SET trnDate = CURDATE();

    WHILE i <= headerCount DO
        -- generate reference and ensure uniqueness within the table
        gen_ref: LOOP
            SET refNo = CONCAT('CR-', DATE_FORMAT(trnDate, '%Y%m%d'), '-', LPAD(FLOOR(RAND()*999999)+1, 6, '0'));
            IF NOT EXISTS (SELECT 1 FROM transactions WHERE reference_no = refNo) THEN
                LEAVE gen_ref;
            END IF;
        END LOOP gen_ref;
        -- choose two valid COA ids from existing rows (fallback to 1 and 2 if missing)
        SELECT COALESCE(MIN(id),1) INTO acc1 FROM chart_of_accounts;
        SELECT COALESCE(MAX(id),2) INTO acc2 FROM chart_of_accounts;
        IF acc1 = acc2 THEN SET acc2 = acc1 + 1; END IF;

        -- random project/department/supplier (nullable)
        SELECT id INTO proj FROM projects ORDER BY RAND() LIMIT 1;
        SELECT id INTO dept FROM departments ORDER BY RAND() LIMIT 1;
        SELECT id INTO supp FROM suppliers ORDER BY RAND() LIMIT 1;

        -- pick 2-5 distribution lines, ensure balanced
        SET distCount = 2 + FLOOR(RAND()*4); -- 2..5
        SET debitAmt = ROUND((100 + RAND()*4900), 2); -- 100..5000
        SET creditAmt = debitAmt; -- start balanced; additional lines will split amounts

        -- create header
        INSERT INTO transactions (
            transaction_type, payment_form, reference_no, transaction_date,
            amount, account_id, created_by, created_at, project_id, department_id
        ) VALUES (
            'cash_receipt', 'cash', refNo, trnDate, debitAmt, acc1, 1, NOW(), proj, dept
        );
        SET headerId = LAST_INSERT_ID();
        SET dSeq = 1;
        SET cSeq = 1;

        -- first pair (debit on acc1, credit on acc2)
        INSERT INTO transactions (parent_transaction_id, transaction_type, reference_no, transaction_date,
                                  amount, account_id, supplier_id, project_id, department_id, description, created_by, created_at)
        VALUES (headerId, 'debit', CONCAT(refNo,'-D', LPAD(dSeq,3,'0')), trnDate,
                debitAmt, acc1, supp, proj, dept, 'Seed debit', 1, NOW());
        SET dSeq = dSeq + 1;

        INSERT INTO transactions (parent_transaction_id, transaction_type, reference_no, transaction_date,
                                  amount, account_id, supplier_id, project_id, department_id, description, created_by, created_at)
        VALUES (headerId, 'credit', CONCAT(refNo,'-C', LPAD(cSeq,3,'0')), trnDate,
                creditAmt, acc2, supp, proj, dept, 'Seed credit', 1, NOW());
        SET cSeq = cSeq + 1;

        -- optional extra balanced lines (in pairs)
        WHILE distCount > 2 DO
            SET debitAmt = ROUND((50 + RAND()*2000), 2);
            SET creditAmt = debitAmt;
            INSERT INTO transactions (parent_transaction_id, transaction_type, reference_no, transaction_date,
                                      amount, account_id, supplier_id, project_id, department_id, description, created_by, created_at)
            VALUES (headerId, 'debit', CONCAT(refNo,'-D', LPAD(dSeq,3,'0')), trnDate,
                    debitAmt, acc1, supp, proj, dept, 'Seed debit', 1, NOW());
            SET dSeq = dSeq + 1;
            INSERT INTO transactions (parent_transaction_id, transaction_type, reference_no, transaction_date,
                                      amount, account_id, supplier_id, project_id, department_id, description, created_by, created_at)
            VALUES (headerId, 'credit', CONCAT(refNo,'-C', LPAD(cSeq,3,'0')), trnDate,
                    creditAmt, acc2, supp, proj, dept, 'Seed credit', 1, NOW());
            SET cSeq = cSeq + 1;
            SET distCount = distCount - 1;
        END WHILE;

        SET i = i + 1;
    END WHILE;
END $$
DELIMITER ;

-- To seed 1000 cash receipts, run:
-- CALL seed_cash_receipts(1000);

-- Seed sample cash disbursement data (headers with balanced distribution)
DELIMITER $$
DROP PROCEDURE IF EXISTS seed_cash_disbursements $$
CREATE PROCEDURE seed_cash_disbursements(IN headerCount INT)
BEGIN
    DECLARE i INT DEFAULT 1;
    DECLARE headerId INT;
    DECLARE trnDate DATE;
    DECLARE refNo VARCHAR(50);
    DECLARE debitAmt DECIMAL(15,2);
    DECLARE creditAmt DECIMAL(15,2);
    DECLARE accCash INT;     -- cash / bank account
    DECLARE accExpense INT;  -- expense/payable account
    DECLARE proj INT;
    DECLARE dept INT;
    DECLARE supp INT;
    DECLARE dSeq INT;
    DECLARE cSeq INT;

    SET trnDate = CURDATE();

    WHILE i <= headerCount DO
        -- generate unique voucher/reference (CV-YYYYMMDD-######)
        gen_ref: LOOP
            SET refNo = CONCAT('CV-', DATE_FORMAT(trnDate, '%Y%m%d'), '-', LPAD(FLOOR(RAND()*999999)+1, 6, '0'));
            IF NOT EXISTS (SELECT 1 FROM transactions WHERE reference_no = refNo) THEN
                LEAVE gen_ref;
            END IF;
        END LOOP gen_ref;

        -- choose two valid COA ids (fallbacks)
        SELECT COALESCE(MIN(id),1) INTO accCash FROM chart_of_accounts;
        SELECT COALESCE(MAX(id),2) INTO accExpense FROM chart_of_accounts;
        IF accCash = accExpense THEN SET accExpense = accCash + 1; END IF;

        -- random project/department/supplier
        SELECT id INTO proj FROM projects ORDER BY RAND() LIMIT 1;
        SELECT id INTO dept FROM departments ORDER BY RAND() LIMIT 1;
        SELECT id INTO supp FROM suppliers ORDER BY RAND() LIMIT 1;

        -- disbursement total
        SET debitAmt = ROUND((100 + RAND()*4900), 2);
        SET creditAmt = debitAmt; -- total must balance

        -- header (cash_disbursement)
        INSERT INTO transactions (
            transaction_type, payment_form, reference_no, transaction_date,
            amount, account_id, payee_name, created_by, created_at, project_id, department_id
        ) VALUES (
            'cash_disbursement', 'cash', refNo, trnDate, debitAmt, accCash,
            'Seed Payee', 1, NOW(), proj, dept
        );
        SET headerId = LAST_INSERT_ID();
        SET dSeq = 1; SET cSeq = 1;

        -- debit line (expense)
        INSERT INTO transactions (
            parent_transaction_id, transaction_type, reference_no, transaction_date,
            amount, account_id, supplier_id, project_id, department_id, description, created_by, created_at
        ) VALUES (
            headerId, 'debit', CONCAT(refNo,'-D', LPAD(dSeq,3,'0')), trnDate,
            debitAmt, accExpense, supp, proj, dept, 'Seed debit (expense)', 1, NOW()
        );
        SET dSeq = dSeq + 1;

        -- credit line (cash)
        INSERT INTO transactions (
            parent_transaction_id, transaction_type, reference_no, transaction_date,
            amount, account_id, supplier_id, project_id, department_id, description, created_by, created_at
        ) VALUES (
            headerId, 'credit', CONCAT(refNo,'-C', LPAD(cSeq,3,'0')), trnDate,
            creditAmt, accCash, supp, proj, dept, 'Seed credit (cash)', 1, NOW()
        );
        SET cSeq = cSeq + 1;

        SET i = i + 1;
    END WHILE;
END $$
DELIMITER ;

-- To seed 100 check disbursements, run:
-- CALL seed_cash_disbursements(100);

-- Seed sample check disbursement data (headers with balanced distribution)
DELIMITER $$
DROP PROCEDURE IF EXISTS seed_check_disbursements $$
CREATE PROCEDURE seed_check_disbursements(IN headerCount INT)
BEGIN
    DECLARE i INT DEFAULT 1;
    DECLARE headerId INT;
    DECLARE trnDate DATE;
    DECLARE refNo VARCHAR(50);
    DECLARE debitAmt DECIMAL(15,2);
    DECLARE creditAmt DECIMAL(15,2);
    DECLARE accBank INT;
    DECLARE accExpense INT;
    DECLARE proj INT;
    DECLARE dept INT;
    DECLARE supp INT;
    DECLARE dSeq INT;
    DECLARE cSeq INT;

    SET trnDate = CURDATE();

    WHILE i <= headerCount DO
        -- unique reference CV-YYYYMMDD-######
        gen_ref2: LOOP
            SET refNo = CONCAT('CV-', DATE_FORMAT(trnDate, '%Y%m%d'), '-', LPAD(FLOOR(RAND()*999999)+1, 6, '0'));
            IF NOT EXISTS (SELECT 1 FROM transactions WHERE reference_no = refNo) THEN
                LEAVE gen_ref2;
            END IF;
        END LOOP gen_ref2;

        -- pick two chart of account ids
        SELECT COALESCE(MIN(id),1) INTO accBank FROM chart_of_accounts;
        SELECT COALESCE(MAX(id),2) INTO accExpense FROM chart_of_accounts;
        IF accBank = accExpense THEN SET accExpense = accBank + 1; END IF;

        -- random dimensions
        SELECT id INTO proj FROM projects ORDER BY RAND() LIMIT 1;
        SELECT id INTO dept FROM departments ORDER BY RAND() LIMIT 1;
        SELECT id INTO supp FROM suppliers ORDER BY RAND() LIMIT 1;

        SET debitAmt = ROUND((100 + RAND()*4900), 2);
        SET creditAmt = debitAmt;

        -- header (check_disbursement)
        INSERT INTO transactions (
            transaction_type, payment_form, reference_no, transaction_date,
            amount, account_id, payee_name, check_number, bank, created_by, created_at,
            project_id, department_id
        ) VALUES (
            'check_disbursement', 'check', refNo, trnDate, debitAmt, accBank,
            'Seed Check Payee', CONCAT('CHK', LPAD(FLOOR(RAND()*99999)+1,5,'0')), 'Seed Bank', 1, NOW(),
            proj, dept
        );
        SET headerId = LAST_INSERT_ID();
        SET dSeq = 1; SET cSeq = 1;

        -- debit (expense)
        INSERT INTO transactions (
            parent_transaction_id, transaction_type, reference_no, transaction_date,
            amount, account_id, supplier_id, project_id, department_id, description, created_by, created_at
        ) VALUES (
            headerId, 'debit', CONCAT(refNo,'-D', LPAD(dSeq,3,'0')), trnDate,
            debitAmt, accExpense, supp, proj, dept, 'Seed check debit', 1, NOW()
        );
        SET dSeq = dSeq + 1;

        -- credit (bank)
        INSERT INTO transactions (
            parent_transaction_id, transaction_type, reference_no, transaction_date,
            amount, account_id, supplier_id, project_id, department_id, description, created_by, created_at
        ) VALUES (
            headerId, 'credit', CONCAT(refNo,'-C', LPAD(cSeq,3,'0')), trnDate,
            creditAmt, accBank, supp, proj, dept, 'Seed check credit', 1, NOW()
        );
        SET cSeq = cSeq + 1;

        SET i = i + 1;
    END WHILE;
END $$
DELIMITER ;

-- To seed 100 check disbursements, run:
-- CALL seed_check_disbursements(100);

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

ALTER TABLE `transactions` MODIFY transaction_type enum('cash_receipt','cash_disbursement','check_disbursement') NOT NULL