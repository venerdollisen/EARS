-- Migration to update transactions table for journal entries support
-- Run this script to add missing enum values

-- Update transaction_type enum to include journal_entry
ALTER TABLE transactions 
MODIFY COLUMN transaction_type ENUM('cash_receipt', 'cash_disbursement', 'check_disbursement', 'debit', 'credit', 'journal_entry') DEFAULT NULL;

-- Update STATUS enum to include journal entry statuses
ALTER TABLE transactions 
MODIFY COLUMN STATUS ENUM('draft', 'posted', 'cancelled', 'approved', 'pending', 'rejected') DEFAULT 'posted';

-- Update existing transactions to have proper status
UPDATE transactions SET STATUS = 'posted' WHERE STATUS IS NULL; 