-- Journal Entries Migration
-- Enhanced version with project, department, subsidiary support

-- Create journal_entries table
CREATE TABLE IF NOT EXISTS journal_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference_no VARCHAR(50) UNIQUE NOT NULL,
    transaction_date DATE NOT NULL,
    description TEXT,
    total_amount DECIMAL(15,2) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    jv_status ENUM('active', 'inactive') DEFAULT 'active',
    for_posting ENUM('for_posting', 'for_checking') DEFAULT 'for_checking',
    reference_number1 VARCHAR(100),
    reference_number2 VARCHAR(100),
    cwo_number VARCHAR(100),
    bill_invoice_ref VARCHAR(100),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    rejected_by INT NULL,
    rejected_at TIMESTAMP NULL,
    rejection_reason TEXT NULL,
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id),
    FOREIGN KEY (rejected_by) REFERENCES users(id)
);

-- Create journal_entry_details table with enhanced fields
CREATE TABLE IF NOT EXISTS journal_entry_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    journal_entry_id INT NOT NULL,
    account_id INT NOT NULL,
    project_id INT NULL,
    department_id INT NULL,
    supplier_id INT NULL,
    transaction_type ENUM('debit', 'credit') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    description TEXT,
    FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES chart_of_accounts(id),
    FOREIGN KEY (project_id) REFERENCES projects(id),
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
);

-- Create indexes for better performance
CREATE INDEX idx_journal_entries_date ON journal_entries(transaction_date);
CREATE INDEX idx_journal_entries_status ON journal_entries(status);
CREATE INDEX idx_journal_entries_created_by ON journal_entries(created_by);
CREATE INDEX idx_journal_entry_details_entry_id ON journal_entry_details(journal_entry_id);
CREATE INDEX idx_journal_entry_details_account_id ON journal_entry_details(account_id);
CREATE INDEX idx_journal_entry_details_project_id ON journal_entry_details(project_id);
CREATE INDEX idx_journal_entry_details_department_id ON journal_entry_details(department_id);
CREATE INDEX idx_journal_entry_details_supplier_id ON journal_entry_details(supplier_id); 