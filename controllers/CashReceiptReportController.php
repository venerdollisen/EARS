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
            // Clean buffer
            while (ob_get_level()) ob_end_clean();
            if (headers_sent()) throw new Exception('Headers already sent, cannot output PDF file');

            // ----------------------------------------------------------
            //  COLUMN SETUP — EXACT 267mm WIDTH (NO OVERLAP)
            // ----------------------------------------------------------
            $columns = [
                ['field' => 'transaction_date', 'label' => 'Date',           'width' => 20, 'align' => 'L'],
                ['field' => 'supplier_name',    'label' => 'Particulars',    'width' =>75, 'align' => 'L'],
                ['field' => 'supplier_tin',     'label' => 'TIN',            'width' => 25, 'align' => 'L'],
                ['field' => 'supplier_address', 'label' => 'Address',        'width' => 50, 'align' => 'L'],

                ['field' => 'amount',           'label' => 'Invoice Amount', 'width' => 25, 'align' => 'R'],
                ['field' => 'output_tax',       'label' => 'Output Tax',     'width' => 22, 'align' => 'R'],
                ['field' => 'net_purchase',     'label' => 'Net Purchase',   'width' => 22, 'align' => 'R'],

                ['field' => 'expanded',         'label' => 'Expanded',       'width' => 18, 'align' => 'R'],
                // ['field' => 'compensation',     'label' => 'Compensation',   'width' => 25, 'align' => 'R'],

                ['field' => 'diff',             'label' => 'Diff.',          'width' => 10, 'align' => 'R'],
            ];

            $lineHeight = 6;

            // ----------------------------------------------------------
            //  TCPDF SETUP
            // ----------------------------------------------------------
            $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
            $pdf->SetCreator('EARS System');
            $pdf->SetAuthor('EARS System');
            $pdf->SetTitle('Cash Receipt Report');
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            $pdf->SetMargins(15, 15, 15);
            $pdf->SetAutoPageBreak(true, 15);
            $pdf->AddPage();

            // ----------------------------------------------------------
            //  HEADER
            // ----------------------------------------------------------
            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->Cell(0, 10, 'CASH RECEIPT REPORT', 0, 1, 'C');
            $pdf->Ln(4);

            $pdf->SetFont('helvetica', '', 10);
            $periodText = 'Report Period: ' .
                (!empty($filters['start_date']) && !empty($filters['end_date'])
                    ? date('F j, Y', strtotime($filters['start_date'])) . ' to ' . date('F j, Y', strtotime($filters['end_date']))
                    : 'All Periods');

            $pdf->Cell(0, 6, $periodText, 0, 1);
            $pdf->Cell(0, 6, 'Generated on: ' . date('F j, Y \a\t g:i A'), 0, 1);
            $pdf->Ln(4);


            // ----------------------------------------------------------
            //  TABLE HEADER
            // ----------------------------------------------------------
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->SetFillColor(230, 230, 230);

            foreach ($columns as $col) {
                $pdf->Cell($col['width'], 7, $col['label'], 1, 0, 'C', true);
            }
            $pdf->Ln();


            // ----------------------------------------------------------
            //  TABLE BODY
            // ----------------------------------------------------------
            $pdf->SetFont('helvetica', '', 8);

            $totals = [
                'amount'       => 0,
                'output_tax'   => 0,
                'net_purchase' => 0,
                'diff'         => 0,
            ];

            $rowCount = 0;

            foreach ($data as $record) {

                // ------------------------------------------------------
                // CLEAN VALUES (FIX LEADING COMMAS / SPACES)
                // ------------------------------------------------------
                foreach (['supplier_name', 'supplier_tin', 'supplier_address'] as $f) {
                    if (isset($record[$f])) {
                        $record[$f] = ltrim($record[$f], " ,");
                    }
                }

                // ------------------------------------------------------
                // COMPUTED FIELDS
                // ------------------------------------------------------
                $invoiceAmount = (float) ($record['amount'] ?? 0);
                $inputTax      = (($record['vat_subject'] ?? '') === 'VAT') ? $invoiceAmount * 0.12 : 0;
                $netPurchase   = $invoiceAmount - $inputTax;
                $diff          = 0;

                // Attach computed fields
                $record['output_tax']   = $inputTax;
                $record['net_purchase'] = $netPurchase;
                $record['diff']         = $diff;

                // NEW FIELDS (BLANK)
                $record['expanded']     = '';
                $record['compensation'] = '';

                // ------------------------------------------------------
                // DYNAMIC ROW HEIGHT
                // ------------------------------------------------------
                $maxLines = 1;
                foreach ($columns as $col) {
                    $value = $this->formatCellValue($col['field'], $record);
                    $lines = $pdf->getNumLines($value, $col['width']);
                    if ($lines > $maxLines) $maxLines = $lines;
                }
                $rowHeight = $maxLines * $lineHeight;

                // ------------------------------------------------------
                // PRINT ROW
                // ------------------------------------------------------
                foreach ($columns as $i => $col) {
                    $value = $this->formatCellValue($col['field'], $record);

                    $pdf->MultiCell(
                        $col['width'], $rowHeight,
                        $value, 1, $col['align'], 0,
                        ($i === count($columns) - 1 ? 1 : 0),
                        '', '', true, 0, false, true, 0, 'M'
                    );
                }

                // Totals
                $totals['amount']       += $invoiceAmount;
                $totals['output_tax']   += $inputTax;
                $totals['net_purchase'] += $netPurchase;
                $totals['diff']         += $diff;

                $rowCount++;
            }


            // ----------------------------------------------------------
            //  TOTALS ROW
            // ----------------------------------------------------------
            $pdf->SetFont('helvetica', 'B', 9);

            foreach ($columns as $col) {
                if (isset($totals[$col['field']])) {
                    $pdf->Cell($col['width'], 7, number_format($totals[$col['field']], 2), 1, 0, 'R', true);
                } else {
                    $pdf->Cell($col['width'], 7, '', 1, 0, 'R', true);
                }
            }
            $pdf->Ln();


            // ----------------------------------------------------------
            // FOOTER
            // ----------------------------------------------------------
            $pdf->Ln(5);
            $pdf->SetFont('helvetica', '', 9);
            $pdf->Cell(0, 6, 'Total Records: ' . $rowCount, 0, 1);


            // ----------------------------------------------------------
            // OUTPUT PDF
            // ----------------------------------------------------------
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="cash_receipt_report_' . date('Y-m-d_H-i-s') . '.pdf"');
            header('Cache-Control: no-cache, must-revalidate');

            $pdf->Output('cash_receipt_report.pdf', 'D');
            exit;

        } catch (Exception $e) {
            if (!headers_sent()) header('Content-Type: text/plain');
            echo "Error generating PDF: " . $e->getMessage();
            exit;
        }
    }


    /**
     * FORMATTER FOR CELL VALUES
     */
    private function formatCellValue($field, $record) {
        switch ($field) {
            case 'transaction_date':
                return !empty($record[$field]) ? date('m/d/Y', strtotime($record[$field])) : '';

            case 'amount':
            case 'output_tax':
            case 'net_purchase':
            case 'diff':
            case 'expanded':
            case 'compensation':
                return $record[$field] === '' ? '' : number_format((float)$record[$field], 2);

            case 'supplier_tin':
                return $record[$field] ?? 'N/A';

            default:
                return $record[$field] ?? '';
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
