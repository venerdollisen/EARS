<?php

require_once 'core/Controller.php';
require_once 'models/CashReceiptReportModel.php';

class CashReceiptReportController extends Controller {
    
    private $reportModel;
    
    public function __construct() {
        parent::__construct();
        $this->reportModel = new CashReceiptReportModel();
    }
    
    /**
     * Display the cash receipt report page
     */
    public function index() {
        // $this->requireAuth();
        
        // Force no caching
        header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
        
        $this->render('reports/cash-receipt', [
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
        // $this->requireAuth();
        
        // Clear any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set JSON response headers
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');
        
        try {
            // Accept both form-encoded POST and JSON bodies
            $input = [];
            $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
            if (stripos($contentType, 'application/json') !== false) {
                $input = json_decode(file_get_contents('php://input'), true) ?: [];
            } else {
                $input = $_POST ?? [];
                if (empty($input)) {
                    $raw = file_get_contents('php://input');
                    $json = @json_decode($raw, true);
                    if (is_array($json)) {
                        $input = $json;
                    }
                }
            }

            // Trim and normalize incoming filter values
            $filters = [
                'start_date' => isset($input['start_date']) ? trim($input['start_date']) : '',
                'end_date' => isset($input['end_date']) ? trim($input['end_date']) : '',
                'account_id' => isset($input['account_id']) ? trim($input['account_id']) : '',
                'supplier_id' => isset($input['supplier_id']) ? trim($input['supplier_id']) : '',
                'project_id' => isset($input['project_id']) ? trim($input['project_id']) : '',
                'department_id' => isset($input['department_id']) ? trim($input['department_id']) : '',
                'payment_form' => isset($input['payment_form']) ? trim($input['payment_form']) : '',
                'status' => isset($input['status']) ? trim($input['status']) : ''
            ];

            // If no dates provided, default to fiscal year boundaries from the model
            if (empty($filters['start_date']) && empty($filters['end_date'])) {
                try {
                    $fy = $this->reportModel->getFiscalYearDates();
                    if (!empty($fy['start_date']) && !empty($fy['end_date'])) {
                        $filters['start_date'] = $fy['start_date'];
                        $filters['end_date'] = $fy['end_date'];
                    }
                } catch (Exception $e) {
                    // ignore and continue with no dates
                }
            }

            // Validate dates format (YYYY-MM-DD) and ensure start <= end
            if (!empty($filters['start_date']) && !$this->isValidDate($filters['start_date'])) {
                throw new Exception('Invalid start date format');
            }
            if (!empty($filters['end_date']) && !$this->isValidDate($filters['end_date'])) {
                throw new Exception('Invalid end date format');
            }

            if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                if (strtotime($filters['start_date']) > strtotime($filters['end_date'])) {
                    // swap to correct order
                    $tmp = $filters['start_date'];
                    $filters['start_date'] = $filters['end_date'];
                    $filters['end_date'] = $tmp;
                }
            }

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
            
            error_log("Cash Receipt Report - Response: " . json_encode($response));
            
            echo json_encode($response);
            
        } catch (Exception $e) {
            error_log("Error generating cash receipt report: " . $e->getMessage());
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
        // $this->requireAuth();
        
        // Clear any output buffers first
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        try {
            $filters = $this->getExportFilters();
            
            // Debug logging
            error_log("Cash Receipt PDF Export - Filters: " . json_encode($filters));
            
            $data = $this->reportModel->generateReport($filters);
            $summary = $this->reportModel->getSummaryStats($filters);
            
            // Debug logging
            error_log("Cash Receipt PDF Export - Data count: " . count($data));
            error_log("Cash Receipt PDF Export - Summary: " . json_encode($summary));
            
            // Check if data exists
            if (empty($data)) {
                throw new Exception('No data found for the selected criteria');
            }
            
            $this->generatePDF($data, $summary, $filters);
            
        } catch (Exception $e) {
            error_log("Error exporting cash receipt report to PDF: " . $e->getMessage());
            // Send proper error response
            header('Content-Type: text/plain');
            echo "Error generating PDF: " . $e->getMessage();
            exit;
        }
    }
    
    /**
     * Export report to Excel
     */
    public function exportExcel() {
        // $this->requireAuth();
        
        // Clear any output buffers first
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        try {
            $filters = $this->getExportFilters();
            
            // Debug logging
            error_log("Cash Receipt Excel Export - Filters: " . json_encode($filters));
            
            $data = $this->reportModel->generateReport($filters);
            $summary = $this->reportModel->getSummaryStats($filters);
            
            // Debug logging
            error_log("Cash Receipt Excel Export - Data count: " . count($data));
            error_log("Cash Receipt Excel Export - Summary: " . json_encode($summary));
            
            // Check if data exists
            if (empty($data)) {
                throw new Exception('No data found for the selected criteria');
            }
            
            $this->generateExcel($data, $summary, $filters);
            
        } catch (Exception $e) {
            error_log("Error exporting cash receipt report to Excel: " . $e->getMessage());
            // Send proper error response
            header('Content-Type: text/plain');
            echo "Error generating Excel: " . $e->getMessage();
            exit;
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
            $pdf->SetTitle('Cash Receipt Report');
            $pdf->SetSubject('Cash Receipt Report');
            
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
            $pdf->Cell(0, 10, 'CASH RECEIPT REPORT', 0, 1, 'C');
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
            $pdf->Cell(25, 7, 'Invoice Amount', 1, 0, 'C', true);
            $pdf->Cell(25, 7, 'Output Tax', 1, 0, 'C', true);
            $pdf->Cell(25, 7, 'Net Purchase', 1, 0, 'C', true);
            $pdf->Cell(20, 7, 'Diff.', 1, 1, 'C', true);
            
            // Table data
            $pdf->SetFont('helvetica', '', 8);
            $rowCount = 0;
            $totalInvoiceAmount = 0;
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
                    // $inputTax = $invoiceAmount * 0.12; // 12% VAT
                    $inputTax = 0;
                    if($record['vat_subject']=='VAT'){
                        $inputTax = $invoiceAmount * 0.12;
                    }
                    $netPurchase = $invoiceAmount - $inputTax;
                    $diff = 0.00; // Always 0.00 as per the image
                    
                    // Add to totals
                    $totalInvoiceAmount += $invoiceAmount;
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
            $pdf->Cell(25, 7, number_format($totalInvoiceAmount, 2), 1, 0, 'R', true);
            $pdf->Cell(25, 7, number_format($totalInputTax, 2), 1, 0, 'R', true);
            $pdf->Cell(25, 7, number_format($totalNetPurchase, 2), 1, 0, 'R', true);
            $pdf->Cell(20, 7, number_format($totalDiff, 2), 1, 1, 'R', true);
            
            // Footer with record count
            $pdf->Ln(5);
            $pdf->SetFont('helvetica', '', 9);
            $pdf->Cell(0, 6, 'Total Records: ' . $rowCount, 0, 1, 'L');
            
            // Set headers for download
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="cash_receipt_report_' . date('Y-m-d_H-i-s') . '.pdf"');
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            
            // Output PDF for download
            $pdf->Output('cash_receipt_report_' . date('Y-m-d_H-i-s') . '.pdf', 'D');
            
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
     * Generate Excel using PhpSpreadsheet with best practices
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
                ->setTitle('Cash Receipt Report')
                ->setSubject('Cash Receipt Report')
                ->setDescription('Cash Receipt Report generated by EARS System');
            
            // Set title
            $sheet->setCellValue('A1', 'CASH RECEIPT REPORT');
            $sheet->mergeCells('A1:H1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            
            // Report period
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
            
            // Generated date
            $sheet->setCellValue('A' . $row, 'Generated on: ' . date('F j, Y \a\t g:i A'));
            $sheet->mergeCells('A' . $row . ':H' . $row);
            $row += 2;
            
            // Table header with better styling
            $headers = ['Date', 'Particulars', 'TIN', 'Address', 'Invoice Amount', 'Input Tax', 'Net Purchase', 'Diff.'];
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . $row, $header);
                $sheet->getStyle($col . $row)->getFont()->setBold(true);
                $sheet->getStyle($col . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E6E6E6');
                $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)->setWrapText(true);
                $col++;
            }
            $row++;
            
            // Populate data
            $totalInvoiceAmount = 0;
            $totalInputTax = 0;
            $totalNetPurchase = 0;
            $totalDiff = 0;
            
            if (is_array($data) && !empty($data)) {
                foreach ($data as $record) {
                    // Calculate values
                    $invoiceAmount = $record['amount'];
                    $inputTax = $invoiceAmount * 0.12; // 12% VAT
                    $netPurchase = $invoiceAmount - $inputTax;
                    $diff = 0.00; // Always 0.00 as per the image
                    
                    // Add to totals
                    $totalInvoiceAmount += $invoiceAmount;
                    $totalInputTax += $inputTax;
                    $totalNetPurchase += $netPurchase;
                    $totalDiff += $diff;
                    
                    $sheet->setCellValue('A' . $row, date('m/d/Y', strtotime($record['transaction_date'])));
                    $sheet->setCellValue('B' . $row, $record['supplier_name'] ?? '');
                    $sheet->setCellValue('C' . $row, $record['supplier_tin'] ?? 'N/A');
                    $sheet->setCellValue('D' . $row, $record['supplier_address'] ?? '');
                    $sheet->setCellValue('E' . $row, number_format($invoiceAmount, 2));
                    $sheet->setCellValue('F' . $row, number_format($inputTax, 2));
                    $sheet->setCellValue('G' . $row, number_format($netPurchase, 2));
                    $sheet->setCellValue('H' . $row, number_format($diff, 2));
                    
                    // Format amount columns
                    $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                    $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                    $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                    $sheet->getStyle('H' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                    
                    // Add text wrapping for long text columns with proper alignment
                    $sheet->getStyle('B' . $row)->getAlignment()->setWrapText(true)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
                    $sheet->getStyle('D' . $row)->getAlignment()->setWrapText(true)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
                    
                    $row++;
                }
                
                // Add totals row
                $sheet->setCellValue('A' . $row, 'TOTAL:');
                $sheet->mergeCells('A' . $row . ':D' . $row);
                $sheet->setCellValue('E' . $row, number_format($totalInvoiceAmount, 2));
                $sheet->setCellValue('F' . $row, number_format($totalInputTax, 2));
                $sheet->setCellValue('G' . $row, number_format($totalNetPurchase, 2));
                $sheet->setCellValue('H' . $row, number_format($totalDiff, 2));
                
                // Format totals row
                $sheet->getStyle('A' . $row . ':H' . $row)->getFont()->setBold(true);
                $sheet->getStyle('A' . $row . ':H' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E6E6E6');
                $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('H' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                
                $row++;
            }
            
            // Auto-size columns
            foreach (range('A', 'H') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            
            // Set specific column widths to prevent overlap
            $sheet->getColumnDimension('A')->setWidth(12); // Date
            $sheet->getColumnDimension('B')->setWidth(18); // Particulars
            $sheet->getColumnDimension('C')->setWidth(10); // TIN
            $sheet->getColumnDimension('D')->setWidth(35); // Address
            $sheet->getColumnDimension('E')->setWidth(15); // Invoice Amount
            $sheet->getColumnDimension('F')->setWidth(15); // Input Tax
            $sheet->getColumnDimension('G')->setWidth(15); // Net Purchase
            $sheet->getColumnDimension('H')->setWidth(10); // Diff.
            
            // Apply borders to data range (including totals row)
            $dataStartRow = $row - count($data) - 1; // -1 for totals row
            $lastRow = $row - 1;
            
            if ($dataStartRow < $row) {
                $sheet->getStyle('A' . $dataStartRow . ':H' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            }
            
            // Create the Excel file
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            
            // Ensure no output has been sent yet
            if (headers_sent()) {
                throw new Exception('Headers already sent, cannot output Excel file');
            }
            
            // Set headers for download
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="cash_receipt_report_' . date('Y-m-d_H-i-s') . '.xlsx"');
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            header('Content-Length: 0'); // Will be set by the writer
            
            // Save file to PHP output
            $writer->save('php://output');
            
        } catch (Exception $e) {
            error_log("Error in generateExcel method: " . $e->getMessage());
            // If we're already outputting headers, we can't change to JSON
            // So we'll output a simple error message
            if (!headers_sent()) {
                header('Content-Type: text/plain');
            }
            echo "Error generating Excel: " . $e->getMessage();
            exit;
        }
    }
    
    /**
     * Get data by payment form for charts
     */
    public function getDataByPaymentForm() {
        // $this->requireAuth();
        
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
        // $this->requireAuth();
        
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
    
    /**
     * Get export filters from request
     */
    private function getExportFilters() {
        return [
            'start_date' => $_GET['start_date'] ?? '',
            'end_date' => $_GET['end_date'] ?? '',
            'account_id' => $_GET['account_id'] ?? '',
            'supplier_id' => $_GET['supplier_id'] ?? '',
            'project_id' => $_GET['project_id'] ?? '',
            'department_id' => $_GET['department_id'] ?? '',
            'payment_form' => $_GET['payment_form'] ?? '',
            'status' => $_GET['status'] ?? ''
        ];
    }
    
    /**
     * Validate export filters
     */
    private function validateExportFilters($filters) {
        // Basic validation
        if (!empty($filters['start_date']) && !$this->isValidDate($filters['start_date'])) {
            return false;
        }
        
        if (!empty($filters['end_date']) && !$this->isValidDate($filters['end_date'])) {
            return false;
        }
        
        // Validate date range
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            if (strtotime($filters['start_date']) > strtotime($filters['end_date'])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Validate date format
     */
    private function isValidDate($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    /**
     * Debug endpoint to check data availability
     */
    public function debug() {
        try {
            // Check if cash_receipts table has data
            $sql = "SELECT COUNT(*) as total FROM cash_receipts";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Check date range
            $sql = "SELECT MIN(transaction_date) as min_date, MAX(transaction_date) as max_date FROM cash_receipts";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $dateRange = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Check total amount
            $sql = "SELECT SUM(total_amount) as total_amount FROM cash_receipts";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $totalAmount = $stmt->fetch(PDO::FETCH_ASSOC)['total_amount'];
            
            // Check sample data
            $sql = "SELECT id, reference_no, transaction_date, total_amount, payment_form, status FROM cash_receipts ORDER BY transaction_date DESC LIMIT 5";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $sampleData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response = [
                'success' => true,
                'total_records' => $totalRecords,
                'total_amount' => $totalAmount,
                'date_range' => $dateRange,
                'sample_data' => $sampleData,
                'message' => 'Debug information retrieved successfully'
            ];
            
            echo json_encode($response);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
?>
