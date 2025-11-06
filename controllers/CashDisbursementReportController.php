<?php
require_once 'core/Controller.php';
require_once 'models/CashDisbursementReportModel.php';
require_once 'config/tcpdf_config.php';

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
        
        // Clear any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set JSON response headers
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');
        
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
            
            error_log("Cash Disbursement Report - Filters: " . json_encode($filters));
            
            $data = $this->reportModel->generateReport($filters);
            $summary = $this->reportModel->getSummaryStats($filters);
            $byPaymentForm = $this->reportModel->getDataByPaymentForm($filters);
            $byAccount = $this->reportModel->getDataByAccount($filters);
            $monthlyTrend = $this->reportModel->getMonthlyTrend($filters);
            
            $response = [
                'success' => true,
                'data' => $data,
                'summary' => $summary,
                'byPaymentForm' => $byPaymentForm,
                'byAccount' => $byAccount,
                'monthlyTrend' => $monthlyTrend
            ];
            
            error_log("Cash Disbursement Report - Response: " . json_encode($response));
            
            echo json_encode($response);
            
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
        
        // Clear any output buffers first
        while (ob_get_level()) {
            ob_end_clean();
        }
        
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
            
            error_log("Cash Disbursement PDF Export - Filters: " . json_encode($filters));
            
            $data = $this->reportModel->generateReport($filters);
            $summary = $this->reportModel->getSummaryStats($filters);
            
            error_log("Cash Disbursement PDF Export - Data count: " . count($data));
            
            $this->generatePDF($data, $summary, $filters);
            
        } catch (Exception $e) {
            error_log("Error exporting cash disbursement report to PDF: " . $e->getMessage());
            error_log("Error trace: " . $e->getTraceAsString());
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
     * Generate PDF using TCPDF with best practices
     */
    private function generatePDF($data, $summary, $filters) {
        try {
            // Ensure no output has been sent yet
            if (headers_sent()) {
                throw new Exception('Headers already sent, cannot output PDF file');
            }
            
            // Create new PDF document with landscape orientation for better table layout
            $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        
            // Set document information
            $pdf->SetCreator('EARS System');
            $pdf->SetAuthor('EARS System');
            $pdf->SetTitle('Cash Disbursement Report');
            $pdf->SetSubject('Cash Disbursement Report');
            
            // Remove default header/footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            
            // Set margins
            $pdf->SetMargins(15, 15, 15);
            
            // Set auto page breaks
            $pdf->SetAutoPageBreak(TRUE, 15);
            
            // Add a page
            $pdf->AddPage();
            
            // Set font
            $pdf->SetFont('helvetica', '', 10);
            
            // Title
            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->Cell(0, 10, 'CASH DISBURSEMENT REPORT', 0, 1, 'C');
            $pdf->Ln(5);
            
            // Report period
            $pdf->SetFont('helvetica', '', 10);
            $periodText = 'Report Period: ';
            if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                $periodText .= date('F j, Y', strtotime($filters['start_date'])) . ' to ' . date('F j, Y', strtotime($filters['end_date']));
            } else {
                $periodText .= 'All Periods';
            }
            $pdf->Cell(0, 6, $periodText, 0, 1, 'L');
            
            // Generated date
            $pdf->Cell(0, 6, 'Generated on: ' . date('F j, Y \a\t g:i A'), 0, 1, 'L');
            $pdf->Ln(5);
            
            // Table header
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->SetFillColor(230, 230, 230);
            $pdf->Cell(25, 7, 'Date', 1, 0, 'C', true);
            $pdf->Cell(40, 7, 'Particulars', 1, 0, 'C', true);
            $pdf->Cell(35, 7, 'TIN', 1, 0, 'C', true);
            $pdf->Cell(50, 7, 'Address', 1, 0, 'C', true);
            $pdf->Cell(25, 7, 'Total Invoice', 1, 0, 'C', true);
            $pdf->Cell(25, 7, 'Input Tax', 1, 0, 'C', true);
            $pdf->Cell(25, 7, 'Net Purchase', 1, 0, 'C', true);
            $pdf->Cell(20, 7, 'Diff.', 1, 1, 'C', true);
            
            // Table data
            $pdf->SetFont('helvetica', '', 8);
            $rowCount = 0;
            $totalAmount = 0;
            $totalInputTax = 0;
            $totalNetPurchase = 0;
            $totalDiff = 0;
            
            if (is_array($data) && !empty($data)) {
                foreach ($data as $record) {
                    // Calculate text height for wrapping text
                    $particularsText = $record['supplier_name'] ?? '';
                    $addressText = $record['supplier_address'] ?? '';
                    
                    // Calculate how many lines each text will take
                    $particularsLines = ceil($pdf->GetStringWidth($particularsText) / 40); // 40mm width
                    $addressLines = ceil($pdf->GetStringWidth($addressText) / 50); // 50mm width
                    
                    // Use the maximum number of lines
                    $maxLines = max(1, $particularsLines, $addressLines);
                    $cellHeight = $maxLines * 6; // 6mm per line
                    
                    // Calculate values
                    $invoiceAmount = $record['amount'];
                     // 12% VAT
                    $inputTax = 0;
                    if($record['vat_subject']=='VAT'){
                        $inputTax = $invoiceAmount * 0.12;
                    }
                    $netPurchase = $invoiceAmount - $inputTax;
                    $diff = 0.00; // Always 0.00 as per the cash receipt format
                    
                    // Add to totals
                    $totalAmount += $invoiceAmount;
                    $totalInputTax += $inputTax;
                    $totalNetPurchase += $netPurchase;
                    $totalDiff += $diff;
                    
                    // Create all cells with the same height
                    $pdf->Cell(25, $cellHeight, date('m/d/Y', strtotime($record['transaction_date'])), 1, 0, 'L');
                    $pdf->Cell(40, $cellHeight, $particularsText, 1, 0, 'L');
                    $pdf->Cell(35, $cellHeight, $record['supplier_tin'] ?? 'N/A', 1, 0, 'L');
                    $pdf->Cell(50, $cellHeight, $addressText, 1, 0, 'L');
                    $pdf->Cell(25, $cellHeight, number_format($invoiceAmount, 2), 1, 0, 'R');
                    $pdf->Cell(25, $cellHeight, number_format($inputTax, 2), 1, 0, 'R');
                    $pdf->Cell(25, $cellHeight, number_format($netPurchase, 2), 1, 0, 'R');
                    $pdf->Cell(20, $cellHeight, number_format($diff, 2), 1, 1, 'R');
                    
                    $rowCount++;
                }
            }
            
            // Add totals row
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell(150, 7, 'TOTAL:', 1, 0, 'R', true);
            $pdf->Cell(25, 7, number_format($totalAmount, 2), 1, 0, 'R', true);
            $pdf->Cell(25, 7, number_format($totalInputTax, 2), 1, 0, 'R', true);
            $pdf->Cell(25, 7, number_format($totalNetPurchase, 2), 1, 0, 'R', true);
            $pdf->Cell(20, 7, number_format($totalDiff, 2), 1, 1, 'R', true);
            
            // Footer with record count
            $pdf->Ln(5);
            $pdf->SetFont('helvetica', '', 9);
            $pdf->Cell(0, 6, 'Total Records: ' . $rowCount, 0, 1, 'L');
            
            // Set headers for download
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="cash_disbursement_report_' . date('Y-m-d_H-i-s') . '.pdf"');
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            
            // Output PDF for download
            $pdf->Output('cash_disbursement_report_' . date('Y-m-d_H-i-s') . '.pdf', 'D');
            
        } catch (Exception $e) {
            error_log("Error in generatePDF method: " . $e->getMessage());
            // If we're already outputting headers, we can't change to JSON
            // So we'll output a simple error message
            if (!headers_sent()) {
                header('Content-Type: text/plain');
            }
            echo "Error generating PDF: " . $e->getMessage();
            exit;
        }
    }
    
    /**
     * Generate Excel using PhpSpreadsheet
     */
    private function generateExcel($data, $summary, $filters) {
        try {
            // Check if PhpSpreadsheet is available
            if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                throw new Exception('PhpSpreadsheet library not found. Please install it via composer.');
            }
            
            // Create new Spreadsheet object
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
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
            $sheet->mergeCells('A1:H1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            
            // Set report period
            $row = 3;
            $periodText = 'Report Period: ';
            if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                $periodText .= date('F j, Y', strtotime($filters['start_date'])) . ' to ' . date('F j, Y', strtotime($filters['end_date']));
            } else {
                $periodText .= 'All Periods';
            }
            $sheet->setCellValue('A' . $row, $periodText);
            $sheet->mergeCells('A' . $row . ':H' . $row);
            $row++;
            
            $sheet->setCellValue('A' . $row, 'Generated on: ' . date('F j, Y \a\t g:i A'));
            $sheet->mergeCells('A' . $row . ':H' . $row);
            $row += 2;
            
            // Summary section
            if ($summary && isset($summary['total_transactions']) && $summary['total_transactions'] > 0) {
                $sheet->setCellValue('A' . $row, 'SUMMARY');
                $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
                $row++;
                
                // Summary table with better formatting
                $summaryData = [
                    ['Total Transactions', $summary['total_transactions']],
                    ['Total Amount', '₱' . number_format($summary['total_amount'], 2)],
                    ['Average Amount', '₱' . number_format($summary['average_amount'], 2)]
                ];
                
                if (isset($summary['max_amount'])) {
                    $summaryData[] = ['Highest Amount', '₱' . number_format($summary['max_amount'], 2)];
                }
                
                foreach ($summaryData as $summaryRow) {
                    $sheet->setCellValue('A' . $row, $summaryRow[0]);
                    $sheet->setCellValue('B' . $row, $summaryRow[1]);
                    $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                    $row++;
                }
                $row += 2;
            }
            
            // Table header with better styling
            $headers = ['Date', 'Reference No', 'Account', 'Supplier', 'Amount', 'Payment Form', 'Status', 'Created By'];
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . $row, $header);
                $sheet->getStyle($col . $row)->getFont()->setBold(true);
                $sheet->getStyle($col . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E6E6E6');
                $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $col++;
            }
            $row++;
            
            // Table data with proper formatting
            $dataStartRow = $row;
            if (is_array($data) && !empty($data)) {
                foreach ($data as $record) {
                    $sheet->setCellValue('A' . $row, date('m/d/Y', strtotime($record['transaction_date'])));
                    $sheet->setCellValue('B' . $row, $record['reference_number']);
                    $sheet->setCellValue('C' . $row, $record['account_code'] . ' - ' . $record['account_name']);
                    $sheet->setCellValue('D' . $row, $record['supplier_name']);
                    $sheet->setCellValue('E' . $row, $record['amount']);
                    $sheet->setCellValue('F' . $row, $record['payment_form']);
                    $sheet->setCellValue('G' . $row, $record['status']);
                    $sheet->setCellValue('H' . $row, $record['created_by']);
                    $row++;
                }
            }
            
            // Format amount column as currency
            $amountColumn = 'E';
            if ($dataStartRow < $row) {
                $sheet->getStyle($amountColumn . $dataStartRow . ':' . $amountColumn . ($row - 1))
                    ->getNumberFormat()
                    ->setFormatCode('#,##0.00');
            }
            
            // Auto-size columns
            foreach (range('A', 'H') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            
            // Add borders to data table
            $lastRow = $row - 1;
            if ($dataStartRow <= $lastRow) {
                $sheet->getStyle('A' . ($dataStartRow - 1) . ':H' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                
                // Add alternating row colors for better readability
                for ($i = $dataStartRow; $i <= $lastRow; $i++) {
                    if ($i % 2 == 0) {
                        $sheet->getStyle('A' . $i . ':H' . $i)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F9F9F9');
                    }
                }
                
                // Add total row
                $row++;
                $sheet->setCellValue('A' . $row, 'TOTAL');
                $sheet->mergeCells('A' . $row . ':D' . $row);
                $sheet->setCellValue('E' . $row, '=SUM(E' . $dataStartRow . ':E' . ($row - 1) . ')');
                $sheet->getStyle('A' . $row . ':H' . $row)->getFont()->setBold(true);
                $sheet->getStyle('A' . $row . ':H' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('D9D9D9');
                $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            }
            
            // Create the Excel file
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            
            // Set headers for download
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="cash_disbursement_report_' . date('Y-m-d_H-i-s') . '.xlsx"');
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            
            // Save file to PHP output
            $writer->save('php://output');
            
        } catch (Exception $e) {
            error_log("Error in generateExcel method: " . $e->getMessage());
            throw new Exception('Failed to generate Excel file: ' . $e->getMessage());
        }
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
