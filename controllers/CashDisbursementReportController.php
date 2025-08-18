<?php
require_once 'core/Controller.php';
require_once 'models/CashDisbursementReportModel.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CashDisbursementReportController extends Controller {
    
    private $reportModel;
    
    public function __construct() {
        parent::__construct();
        $this->reportModel = new CashDisbursementReportModel();
    }
    
    /**
     * Display the cash disbursement report page
     */
    public function index() {
        $this->requireAuth();
        
        // Force no caching
        header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
        
        $this->render('reports/cash-disbursement', [
            'user' => $this->auth->getCurrentUser(),
            'accounts' => $this->reportModel->getActiveAccounts(),
            'suppliers' => $this->reportModel->getActiveSuppliers(),
            'projects' => $this->reportModel->getActiveProjects(),
            'departments' => $this->reportModel->getActiveDepartments(),
            'paymentForms' => $this->reportModel->getPaymentForms(),
            'statuses' => $this->reportModel->getStatuses()
        ]);
    }
    
    /**
     * Generate report data via AJAX
     */
    public function generate() {
        $this->requireAuth();
        
        try {
            $filters = [
                'start_date' => $_POST['start_date'] ?? '',
                'end_date' => $_POST['end_date'] ?? '',
                'account_id' => $_POST['account_id'] ?? '',
                'supplier_id' => $_POST['supplier_id'] ?? '',
                'project_id' => $_POST['project_id'] ?? '',
                'department_id' => $_POST['department_id'] ?? '',
                'payment_form' => $_POST['payment_form'] ?? '',
                'status' => $_POST['status'] ?? ''
            ];
            
            $data = $this->reportModel->generateReport($filters);
            $summary = $this->reportModel->getSummaryStats($filters);
            $byPaymentForm = $this->reportModel->getDataByPaymentForm($filters);
            $byAccount = $this->reportModel->getDataByAccount($filters);
            $monthlyTrend = $this->reportModel->getMonthlyTrend($filters);
            
            echo json_encode([
                'success' => true,
                'data' => $data,
                'summary' => $summary,
                'byPaymentForm' => $byPaymentForm,
                'byAccount' => $byAccount,
                'monthlyTrend' => $monthlyTrend
            ]);
            
        } catch (Exception $e) {
            error_log("Error generating cash disbursement report: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error generating report: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Export report to PDF
     */
    public function exportPDF() {
        $this->requireAuth();
        
        try {
            $filters = [
                'start_date' => $_GET['start_date'] ?? '',
                'end_date' => $_GET['end_date'] ?? '',
                'account_id' => $_GET['account_id'] ?? '',
                'supplier_id' => $_GET['supplier_id'] ?? '',
                'project_id' => $_GET['project_id'] ?? '',
                'department_id' => $_GET['department_id'] ?? '',
                'payment_form' => $_GET['payment_form'] ?? '',
                'status' => $_GET['status'] ?? ''
            ];
            
            $data = $this->reportModel->generateReport($filters);
            $summary = $this->reportModel->getSummaryStats($filters);
            
            $this->generatePDF($data, $summary, $filters);
            
        } catch (Exception $e) {
            error_log("Error exporting cash disbursement report to PDF: " . $e->getMessage());
            echo "Error generating PDF: " . $e->getMessage();
        }
    }
    
    /**
     * Export report to Excel
     */
    public function exportExcel() {
        $this->requireAuth();
        
        try {
            $filters = [
                'start_date' => $_GET['start_date'] ?? '',
                'end_date' => $_GET['end_date'] ?? '',
                'account_id' => $_GET['account_id'] ?? '',
                'supplier_id' => $_GET['supplier_id'] ?? '',
                'project_id' => $_GET['project_id'] ?? '',
                'department_id' => $_GET['department_id'] ?? '',
                'payment_form' => $_GET['payment_form'] ?? '',
                'status' => $_GET['status'] ?? ''
            ];
            
            $data = $this->reportModel->generateReport($filters);
            $summary = $this->reportModel->getSummaryStats($filters);
            
            $this->generateExcel($data, $summary, $filters);
            
        } catch (Exception $e) {
            error_log("Error exporting cash disbursement report to Excel: " . $e->getMessage());
            echo "Error generating Excel: " . $e->getMessage();
        }
    }
    
    /**
     * Generate PDF using TCPDF
     */
    private function generatePDF($data, $summary, $filters) {
        require_once 'vendor/autoload.php';
        
        // Create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('EARS System');
        $pdf->SetAuthor('EARS System');
        $pdf->SetTitle('Cash Disbursement Report');
        $pdf->SetSubject('Cash Disbursement Report');
        
        // Set default header data
        $pdf->SetHeaderData('', 0, 'EARS System', 'Cash Disbursement Report');
        
        // Set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        
        // Set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        
        // Set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        
        // Set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        
        // Set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        
        // Add a page
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont('helvetica', '', 10);
        
        // Build filter text
        $filterText = "Filters: ";
        if (!empty($filters['start_date'])) $filterText .= "From: " . $filters['start_date'] . " ";
        if (!empty($filters['end_date'])) $filterText .= "To: " . $filters['end_date'] . " ";
        if (!empty($filters['account_id'])) $filterText .= "Account ID: " . $filters['account_id'] . " ";
        if (!empty($filters['supplier_id'])) $filterText .= "Supplier ID: " . $filters['supplier_id'] . " ";
        if (!empty($filters['project_id'])) $filterText .= "Project ID: " . $filters['project_id'] . " ";
        if (!empty($filters['department_id'])) $filterText .= "Department ID: " . $filters['department_id'] . " ";
        if (!empty($filters['payment_form'])) $filterText .= "Payment Form: " . $filters['payment_form'] . " ";
        if (!empty($filters['status'])) $filterText .= "Status: " . $filters['status'] . " ";
        
        $pdf->Cell(0, 10, $filterText, 0, 1);
        $pdf->Cell(0, 10, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1);
        $pdf->Ln(5);
        
        // Summary section
        if ($summary) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, 'Summary', 0, 1);
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 8, 'Total Transactions: ' . $summary['total_transactions'], 0, 1);
            $pdf->Cell(0, 8, 'Total Amount: ' . number_format($summary['total_amount'], 2), 0, 1);
            $pdf->Cell(0, 8, 'Average Amount: ' . number_format($summary['average_amount'], 2), 0, 1);
            $pdf->Ln(5);
        }
        
        // Table header
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(25, 8, 'Date', 1);
        $pdf->Cell(30, 8, 'Ref No', 1);
        $pdf->Cell(30, 8, 'Account', 1);
        $pdf->Cell(40, 8, 'Supplier', 1);
        $pdf->Cell(25, 8, 'Amount', 1);
        $pdf->Cell(25, 8, 'Status', 1);
        $pdf->Ln();
        
        // Table data
        $pdf->SetFont('helvetica', '', 8);
        foreach ($data as $row) {
            $pdf->Cell(25, 6, date('Y-m-d', strtotime($row['transaction_date'])), 1);
            $pdf->Cell(30, 6, $row['reference_number'], 1);
            $pdf->Cell(30, 6, $row['account_code'], 1);
            $pdf->Cell(40, 6, $row['supplier_name'], 1);
            $pdf->Cell(25, 6, number_format($row['amount'], 2), 1);
            $pdf->Cell(25, 6, $row['status'], 1);
            $pdf->Ln();
        }
        
        // Output PDF
        $filename = 'cash_disbursement_report_' . date('Y-m-d_H-i-s') . '.pdf';
        $pdf->Output($filename, 'D');
    }
    
    /**
     * Generate Excel using PhpSpreadsheet
     */
    private function generateExcel($data, $summary, $filters) {
        require_once 'vendor/autoload.php';
        
        // Create new Spreadsheet object
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set document properties
        $spreadsheet->getProperties()
            ->setCreator('EARS System')
            ->setLastModifiedBy('EARS System')
            ->setTitle('Cash Disbursement Report')
            ->setSubject('Cash Disbursement Report')
            ->setDescription('Cash Disbursement Report generated by EARS System');
        
        // Set title
        $sheet->setCellValue('A1', 'CASH DISBURSEMENT REPORT');
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Set filter information
        $row = 3;
        $filterText = "Filters: ";
        if (!empty($filters['start_date'])) $filterText .= "From: " . $filters['start_date'] . " ";
        if (!empty($filters['end_date'])) $filterText .= "To: " . $filters['end_date'] . " ";
        if (!empty($filters['account_id'])) $filterText .= "Account ID: " . $filters['account_id'] . " ";
        if (!empty($filters['supplier_id'])) $filterText .= "Supplier ID: " . $filters['supplier_id'] . " ";
        if (!empty($filters['project_id'])) $filterText .= "Project ID: " . $filters['project_id'] . " ";
        if (!empty($filters['department_id'])) $filterText .= "Department ID: " . $filters['department_id'] . " ";
        if (!empty($filters['payment_form'])) $filterText .= "Payment Form: " . $filters['payment_form'] . " ";
        if (!empty($filters['status'])) $filterText .= "Status: " . $filters['status'] . " ";
        
        $sheet->setCellValue('A' . $row, $filterText);
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $row++;
        
        $sheet->setCellValue('A' . $row, 'Generated on: ' . date('Y-m-d H:i:s'));
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $row += 2;
        
        // Summary section
        if ($summary) {
            $sheet->setCellValue('A' . $row, 'SUMMARY');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
            $row++;
            
            $sheet->setCellValue('A' . $row, 'Total Transactions:');
            $sheet->setCellValue('B' . $row, $summary['total_transactions']);
            $row++;
            
            $sheet->setCellValue('A' . $row, 'Total Amount:');
            $sheet->setCellValue('B' . $row, number_format($summary['total_amount'], 2));
            $row++;
            
            $sheet->setCellValue('A' . $row, 'Average Amount:');
            $sheet->setCellValue('B' . $row, number_format($summary['average_amount'], 2));
            $row += 2;
        }
        
        // Table header
        $headers = ['Date', 'Reference No', 'Account Code', 'Account Name', 'Supplier', 'Amount', 'Payment Form', 'Status'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
            $sheet->getStyle($col . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('CCCCCC');
            $col++;
        }
        $row++;
        
        // Table data
        foreach ($data as $record) {
            $sheet->setCellValue('A' . $row, date('Y-m-d', strtotime($record['transaction_date'])));
            $sheet->setCellValue('B' . $row, $record['reference_number']);
            $sheet->setCellValue('C' . $row, $record['account_code']);
            $sheet->setCellValue('D' . $row, $record['account_name']);
            $sheet->setCellValue('E' . $row, $record['supplier_name']);
            $sheet->setCellValue('F' . $row, $record['amount']);
            $sheet->setCellValue('G' . $row, $record['payment_form']);
            $sheet->setCellValue('H' . $row, $record['status']);
            $row++;
        }
        
        // Auto-size columns
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Add borders
        $lastRow = $row - 1;
        $sheet->getStyle('A' . ($row - count($data) - 1) . ':H' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        // Create the Excel file
        $writer = new Xlsx($spreadsheet);
        
        // Set headers for download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="cash_disbursement_report_' . date('Y-m-d_H-i-s') . '.xlsx"');
        header('Cache-Control: max-age=0');
        
        // Save file to PHP output
        $writer->save('php://output');
    }
    
    /**
     * Get data by payment form for charts
     */
    public function getDataByPaymentForm() {
        $this->requireAuth();
        
        try {
            $filters = [
                'start_date' => $_GET['start_date'] ?? '',
                'end_date' => $_GET['end_date'] ?? '',
                'account_id' => $_GET['account_id'] ?? '',
                'supplier_id' => $_GET['supplier_id'] ?? '',
                'project_id' => $_GET['project_id'] ?? '',
                'department_id' => $_GET['department_id'] ?? '',
                'payment_form' => $_GET['payment_form'] ?? '',
                'status' => $_GET['status'] ?? ''
            ];
            
            $data = $this->reportModel->getDataByPaymentForm($filters);
            
            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get data by account for charts
     */
    public function getDataByAccount() {
        $this->requireAuth();
        
        try {
            $filters = [
                'start_date' => $_GET['start_date'] ?? '',
                'end_date' => $_GET['end_date'] ?? '',
                'account_id' => $_GET['account_id'] ?? '',
                'supplier_id' => $_GET['supplier_id'] ?? '',
                'project_id' => $_GET['project_id'] ?? '',
                'department_id' => $_GET['department_id'] ?? '',
                'payment_form' => $_GET['payment_form'] ?? '',
                'status' => $_GET['status'] ?? ''
            ];
            
            $data = $this->reportModel->getDataByAccount($filters);
            
            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
