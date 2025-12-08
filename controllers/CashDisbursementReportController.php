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
            // Clean buffer
            while (ob_get_level()) ob_end_clean();
            if (headers_sent()) throw new Exception('Headers already sent, cannot output PDF file');

            // ----------------------------------------------------------
            //  COLUMN SETUP — EXACT 267mm WIDTH (NO OVERLAP)
            // ----------------------------------------------------------
            $columns = [
                ['field' => 'transaction_date', 'label' => 'Date',           'width' => 20, 'align' => 'L'],
                ['field' => 'supplier_name',    'label' => 'Particulars',    'width' => 50, 'align' => 'L'],
                ['field' => 'supplier_tin',     'label' => 'TIN',            'width' => 25, 'align' => 'L'],
                ['field' => 'supplier_address', 'label' => 'Address',        'width' => 50, 'align' => 'L'],

                ['field' => 'amount',           'label' => 'Invoice Amount', 'width' => 25, 'align' => 'R'],
                ['field' => 'output_tax',       'label' => 'Output Tax',     'width' => 22, 'align' => 'R'],
                ['field' => 'net_purchase',     'label' => 'Net Purchase',   'width' => 22, 'align' => 'R'],

                // NEW REQUESTED COLUMNS (TEXT UNCHANGED)
                ['field' => 'expanded',         'label' => 'Expanded',       'width' => 18, 'align' => 'R'],
                ['field' => 'compensation',     'label' => 'Compensation',   'width' => 25, 'align' => 'R'],

                ['field' => 'diff',             'label' => 'Diff.',          'width' => 10, 'align' => 'R'],
            ];

            $lineHeight = 6;

            // ----------------------------------------------------------
            //  TCPDF SETUP
            // ----------------------------------------------------------
            $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
            $pdf->SetCreator('EARS System');
            $pdf->SetAuthor('EARS System');
            $pdf->SetTitle('Cash Disbursement Report');
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            $pdf->SetMargins(15, 15, 15);
            $pdf->SetAutoPageBreak(true, 15);
            $pdf->AddPage();

            // ----------------------------------------------------------
            //  HEADER
            // ----------------------------------------------------------
            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->Cell(0, 10, 'CASH DISBURSEMENT REPORT', 0, 1, 'C');
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
                // CLEAN VALUES (NO MODIFICATION OF TEXT)
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

                $record['output_tax']   = $inputTax;
                $record['net_purchase'] = $netPurchase;
                $record['diff']         = 0;

                // ------------------------------------------------------
                // KEEP expanded & compensation EXACTLY AS PROVIDED
                // ------------------------------------------------------
                $record['expanded']     = $record['expanded'] ?? '';
                $record['compensation'] = $record['compensation'] ?? '';

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
            header('Content-Disposition: attachment; filename="cash_disbursement_report_' . date('Y-m-d_H-i-s') . '.pdf"');
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
     * FORMATTER FOR CELL VALUES (TEXT NOT CHANGED)
     */
    private function formatCellValue($field, $record) {
        switch ($field) {

            case 'transaction_date':
                return !empty($record[$field]) ? date('m/d/Y', strtotime($record[$field])) : '';

            case 'amount':
            case 'output_tax':
            case 'net_purchase':
            case 'diff':
                return number_format((float)$record[$field], 2);

            // Expanded and Compensation — RETURN AS IS (your request)
            case 'expanded':
            case 'compensation':
                return $record[$field] ?? '';

            case 'supplier_tin':
                return $record[$field] ?? 'N/A';

            default:
                return $record[$field] ?? '';
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
