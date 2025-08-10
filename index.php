<?php
session_start();


// Define base path
define('BASE_PATH', __DIR__);

// Include configuration
require_once 'config/database.php';
require_once 'config/config.php';

// Include core files
require_once 'core/Router.php';
require_once 'core/Controller.php';
require_once 'core/Model.php';
require_once 'core/Auth.php';

// Initialize router
$router = new Router();

// Define routes
$router->addRoute('/', 'DashboardController@index');
$router->addRoute('/login', 'AuthController@login');
$router->addRoute('/logout', 'AuthController@logout');
$router->addRoute('/api/login', 'AuthController@apiLogin');
$router->addRoute('/api/logout', 'AuthController@apiLogout');

// Dashboard routes
$router->addRoute('/dashboard', 'DashboardController@index');
$router->addRoute('/parameters', 'ParametersController@index');
$router->addRoute('/file-maintenance', 'FileMaintenanceController@index');

// Parameters submenu routes
$router->addRoute('/parameters/accounting', 'ParametersController@accounting');

// Settings routes
$router->addRoute('/settings', 'SettingsController@index');
$router->addRoute('/settings/profile', 'SettingsController@profile');
$router->addRoute('/settings/general', 'SettingsController@general');
$router->addRoute('/settings/security', 'SettingsController@security');
$router->addRoute('/settings/backup', 'SettingsController@backup');
$router->addRoute('/settings/notifications', 'SettingsController@notifications');

// File Maintenance submenu routes
$router->addRoute('/file-maintenance/account-title-group', 'FileMaintenanceController@accountTitleGroup');
$router->addRoute('/file-maintenance/coa-account-type', 'FileMaintenanceController@coaAccountType');
$router->addRoute('/file-maintenance/chart-of-accounts', 'FileMaintenanceController@chartOfAccounts');
$router->addRoute('/file-maintenance/subsidiary-account', 'FileMaintenanceController@subsidiaryAccount');
$router->addRoute('/file-maintenance/projects', 'FileMaintenanceController@projects');
$router->addRoute('/file-maintenance/departments', 'FileMaintenanceController@departments');

// Transaction Entries routes
$router->addRoute('/transaction-entries', 'TransactionEntriesController@index');
$router->addRoute('/transaction-entries/cash-receipt', 'CashReceiptController@index');
$router->addRoute('/transaction-entries/cash-disbursement', 'CashDisbursementController@index');
$router->addRoute('/transaction-entries/disbursement', 'CashDisbursementController@index');
$router->addRoute('/transaction-entries/check-disbursement', 'CheckDisbursementController@index');

// Summary Routes
$router->addRoute('/summary', 'SummaryController@index');

// Audit Trail Routes
$router->addRoute('/audit-trail', 'AuditTrailController@index');

// API routes
$router->addRoute('/api/dashboard/stats', 'DashboardController@getStats');
$router->addRoute('/api/dashboard/monthly-data', 'DashboardController@getMonthlyData');
$router->addRoute('/api/dashboard/transaction-distribution', 'DashboardController@getTransactionDistribution');
$router->addRoute('/api/dashboard/account-balance', 'DashboardController@getAccountBalance');
$router->addRoute('/api/parameters/save', 'ParametersController@save');
$router->addRoute('/api/file-maintenance/save', 'FileMaintenanceController@save');
$router->addRoute('/api/file-maintenance/delete', 'FileMaintenanceController@delete');
$router->addRoute('/api/file-maintenance/get-project/{id}', 'FileMaintenanceController@getProject');
$router->addRoute('/api/file-maintenance/get-department/{id}', 'FileMaintenanceController@getDepartment');
$router->addRoute('/api/transaction/save', 'TransactionController@saveEnhancedTransaction');
$router->addRoute('/api/transaction/details/{id}', 'TransactionController@getTransactionDetails');
$router->addRoute('/api/transactions/save', 'TransactionController@saveEnhancedTransaction');
$router->addRoute('/api/transactions/recent', 'TransactionController@getRecentTransactions');
$router->addRoute('/api/accounts/list', 'TransactionController@getAccountsList');
$router->addRoute('/api/transactions/{id}/approve', 'TransactionController@approveTransaction');
$router->addRoute('/api/transactions/{id}/reject', 'TransactionController@rejectTransaction');

// Cash Receipt API routes
$router->addRoute('/api/cash-receipt/save', 'CashReceiptController@save');
$router->addRoute('/api/cash-receipt/get/{id}', 'CashReceiptController@get');
$router->addRoute('/api/cash-receipt/update/{id}', 'CashReceiptController@update');
$router->addRoute('/api/cash-receipt/delete/{id}', 'CashReceiptController@delete');
$router->addRoute('/api/cash-receipt/recent', 'CashReceiptController@recent');
$router->addRoute('/api/cash-receipt/stats', 'CashReceiptController@stats');
$router->addRoute('/api/cash-receipt/debug-accounts', 'CashReceiptController@debugAccounts');

// Cash Disbursement API routes
$router->addRoute('/api/cash-disbursement/save', 'CashDisbursementController@save');
$router->addRoute('/api/cash-disbursement/get/{id}', 'CashDisbursementController@get');
$router->addRoute('/api/cash-disbursement/update/{id}', 'CashDisbursementController@update');
$router->addRoute('/api/cash-disbursement/delete/{id}', 'CashDisbursementController@delete');
$router->addRoute('/api/cash-disbursement/recent', 'CashDisbursementController@recent');
$router->addRoute('/api/cash-disbursement/stats', 'CashDisbursementController@stats');
$router->addRoute('/api/cash-disbursement/by-status/{status}', 'CashDisbursementController@byStatus');

// Check Disbursement API routes
$router->addRoute('/api/check-disbursement/save', 'CheckDisbursementController@save');
$router->addRoute('/api/check-disbursement/get/{id}', 'CheckDisbursementController@get');
$router->addRoute('/api/check-disbursement/update/{id}', 'CheckDisbursementController@update');
$router->addRoute('/api/check-disbursement/delete/{id}', 'CheckDisbursementController@delete');
$router->addRoute('/api/check-disbursement/recent', 'CheckDisbursementController@recent');
$router->addRoute('/api/check-disbursement/stats', 'CheckDisbursementController@stats');
$router->addRoute('/api/check-disbursement/by-status/{status}', 'CheckDisbursementController@byStatus');
$router->addRoute('/api/check-disbursement/by-payment-status/{status}', 'CheckDisbursementController@byPaymentStatus');

// Settings API routes
$router->addRoute('/api/settings/save-profile', 'SettingsController@saveProfile');

// Summary API routes
$router->addRoute('/api/summary/monthly-data', 'SummaryController@getMonthlyData');
$router->addRoute('/api/summary/account-balance', 'SummaryController@getAccountBalance');
$router->addRoute('/api/summary/overview', 'SummaryController@getOverview');
$router->addRoute('/api/summary/status-counts', 'SummaryController@getStatusCounts');
$router->addRoute('/api/summary/pending-approvals', 'SummaryController@getPendingApprovals');

// Audit Trail API routes
$router->addRoute('/api/audit-trail/get', 'AuditTrailController@getAuditTrail');
$router->addRoute('/api/audit-trail/stats', 'AuditTrailController@getStats');
$router->addRoute('/api/audit-trail/record/{table}/{id}', 'AuditTrailController@getRecordAuditTrail');
$router->addRoute('/api/audit-trail/export', 'AuditTrailController@export');
$router->addRoute('/api/settings/change-password', 'SettingsController@changePassword');
$router->addRoute('/api/settings/save-general', 'SettingsController@saveGeneral');
$router->addRoute('/api/settings/save-security', 'SettingsController@saveSecurity');
$router->addRoute('/api/settings/save-backup', 'SettingsController@saveBackup');
$router->addRoute('/api/settings/save-notifications', 'SettingsController@saveNotifications');
$router->addRoute('/api/settings/create-backup', 'SettingsController@createBackup');
$router->addRoute('/api/settings/test-email', 'SettingsController@testEmail');
$router->addRoute('/api/settings/test-sms', 'SettingsController@testSMS');

// Notifications API routes
$router->addRoute('/api/notifications/recent', 'NotificationController@recent');
$router->addRoute('/api/notifications/count', 'NotificationController@count');
$router->addRoute('/api/notifications/view/{id}', 'NotificationController@view');
$router->addRoute('/api/notifications/comment', 'NotificationController@comment');

// User management routes
$router->addRoute('/users', 'UserManagementController@index');
$router->addRoute('/users/create', 'UserManagementController@create');
$router->addRoute('/users/edit/{id}', 'UserManagementController@edit');
$router->addRoute('/api/users/create', 'UserManagementController@store');
$router->addRoute('/api/users/update/{id}', 'UserManagementController@update');
$router->addRoute('/api/users/delete/{id}', 'UserManagementController@destroy');

// Handle the request
$router->dispatch();
?> 