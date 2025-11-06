<?php
require_once 'core/Controller.php';
require_once 'models/CheckDisbursementReportModel.php';

class CheckDisbursementReportController extends Controller {
    
    private $model;
    
    public function __construct() {
        parent::__construct();
        $this->model = new CheckDisbursementReportModel();
    }
    
    public function index() {
        // Set no-cache headers
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        // die("tdst");
        $this->render('reports/check-disbursement', [
            'user' => $this->auth->getCurrentUser(),
            'title' => 'Check Disbursement Report',
            'accounts' => $this->model->getActiveAccounts(),
            'suppliers' => $this->model->getActiveSuppliers(),
            'projects' => $this->model->getActiveProjects(),
            'departments' => $this->model->getActiveDepartments()
        ]);
    }
    
    public function generate() {
        try {
            $filters = [
                'start_date' => $_POST['start_date'] ?? '',
                'end_date' => $_POST['end_date'] ?? '',
                'account_id' => $_POST['account_id'] ?? '',
                'supplier_id' => $_POST['supplier_id'] ?? '',
                'project_id' => $_POST['project_id'] ?? '',
                'department_id' => $_POST['department_id'] ?? '',
                'status' => $_POST['status'] ?? ''
            ];
            
            $reportData = $this->model->generateReport($filters);
            $summary = $this->model->getSummaryStats($filters);
            $dataByBank = $this->model->getDataByBank($filters);
            $dataByAccount = $this->model->getDataByAccount($filters);
            $monthlyTrend = $this->model->getMonthlyTrend($filters);
            $topPayees = $this->model->getTopPayees($filters, 10);
            $checkNumberRange = $this->model->getCheckNumberRange($filters);
            
            echo json_encode([
                'success' => true,
                'data' => $reportData,
                'summary' => $summary,
                'charts' => [
                    'byBank' => $dataByBank,
                    'byAccount' => $dataByAccount,
                    'monthlyTrend' => $monthlyTrend,
                    'topPayees' => $topPayees,
                    'checkNumberRange' => $checkNumberRange
                ]
            ]);
        } catch (Exception $e) {
            error_log("Error generating check disbursement report: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error generating report: ' . $e->getMessage()
            ]);
        }
    }

    public function exportPDF() {
        // $this->requireAuth();
        
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
                'status' => $_GET['status'] ?? ''
            ];
            
            $data = $this->model->generateReport($filters);
            $summary = $this->model->getSummaryStats($filters);
            
            if (empty($data)) {
                throw new Exception('No data found for the selected criteria');
            }
            
            $this->generatePDF($data, $summary, $filters);
            
        } catch (Exception $e) {
            // Send proper error response
            header('Content-Type: text/plain');
            echo "Error generating PDF: " . $e->getMessage();
            exit;
        }
    }

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
            $pdf->SetTitle('Check Disbursement Report');
            $pdf->SetSubject('Check Disbursement Report');
            
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
            $pdf->Cell(0, 10, 'CHECK DISBURSEMENT REPORT', 0, 1, 'C');
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
            $pdf->Cell(25, 7, 'Input Tax', 1, 0, 'C', true);
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
            header('Content-Disposition: attachment; filename="check_disbursement_report_' . date('Y-m-d_H-i-s') . '.pdf"');
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            
            // Output PDF for download
            $pdf->Output('check_disbursement_report_' . date('Y-m-d_H-i-s') . '.pdf', 'D');
            
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
    
    public function exportPDF2() {

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
                'status' => $_GET['status'] ?? ''
            ];
            
            $reportData = $this->model->generateReport($filters);
            $summary = $this->model->getSummaryStats($filters);
            
            // Create PDF
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            
            // Set document information
            $pdf->SetCreator('EARS System');
            $pdf->SetAuthor('EARS System');
            $pdf->SetTitle('Check Disbursement Report');
            
            // Set default header data
            $pdf->SetHeaderData('', 0, 'EARS System', 'Check Disbursement Report');
            
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
            
            // Title
            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->Cell(0, 10, 'CHECK DISBURSEMENT REPORT', 0, 1, 'C');
            $pdf->Ln(5);
            
            // Report period
            $pdf->SetFont('helvetica', 'B', 11);
            $periodText = 'Report Period: ';
            if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                $periodText .= date('F j, Y', strtotime($filters['start_date'])) . ' to ' . date('F j, Y', strtotime($filters['end_date']));
            } else {
                $periodText .= 'All Periods';
            }
            $pdf->Cell(0, 8, $periodText, 0, 1, 'L');
            
            // Generated date
            $pdf->SetFont('helvetica', '', 9);
            $pdf->Cell(0, 6, 'Generated on: ' . date('F j, Y \a\t g:i A'), 0, 1, 'L');
            $pdf->Ln(3);
            
            // Summary section
            if ($summary && $summary['total_checks'] > 0) {
                $pdf->SetFont('helvetica', 'B', 11);
                $pdf->Cell(0, 8, 'SUMMARY', 0, 1, 'L');
                $pdf->SetFont('helvetica', '', 9);
                
                // Summary table
                $pdf->Cell(50, 6, 'Total Checks:', 0, 0);
                $pdf->Cell(30, 6, number_format($summary['total_checks']), 0, 1);
                
                $pdf->Cell(50, 6, 'Total Amount:', 0, 0);
                $pdf->Cell(30, 6, '₱' . number_format($summary['total_amount'], 2), 0, 1);
                
                $pdf->Cell(50, 6, 'Average Amount:', 0, 0);
                $pdf->Cell(30, 6, '₱' . number_format($summary['average_amount'], 2), 0, 1);
                
                $pdf->Ln(5);
            }
            
            // Table header
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->SetFillColor(240, 240, 240);
            
            // Define column widths for landscape A4
            $colWidths = [30, 25, 50, 30, 35, 25, 30];
            $headers = ['Check Number', 'Check Date', 'Payee Name', 'Account Code', 'Amount', 'Status', 'Created By'];
            
            // Header row
            foreach ($headers as $i => $header) {
                $pdf->Cell($colWidths[$i], 8, $header, 1, 0, 'C', true);
            }
            $pdf->Ln();
            
            // Table data
            $pdf->SetFont('helvetica', '', 8);
            $pdf->SetFillColor(255, 255, 255);
            
            $rowCount = 0;
            foreach ($reportData as $row) {
                // Check if we need a new page
                if ($pdf->GetY() > 180) {
                    $pdf->AddPage();
                    // Repeat header on new page
                    $pdf->SetFont('helvetica', 'B', 9);
                    $pdf->SetFillColor(240, 240, 240);
                    foreach ($headers as $i => $header) {
                        $pdf->Cell($colWidths[$i], 8, $header, 1, 0, 'C', true);
                    }
                    $pdf->Ln();
                    $pdf->SetFont('helvetica', '', 8);
                    $pdf->SetFillColor(255, 255, 255);
                }
                
                // Data row
                $pdf->Cell($colWidths[0], 6, $row['check_number'], 1, 0, 'L');
                $pdf->Cell($colWidths[1], 6, date('m/d/Y', strtotime($row['check_date'])), 1, 0, 'C');
                $pdf->Cell($colWidths[2], 6, $row['payee_name'], 1, 0, 'L');
                $pdf->Cell($colWidths[3], 6, $row['account_code'], 1, 0, 'L');
                $pdf->Cell($colWidths[4], 6, '₱' . number_format($row['amount'], 2), 1, 0, 'R');
                $pdf->Cell($colWidths[5], 6, $row['status'] ?? 'Active', 1, 0, 'C');
                $pdf->Cell($colWidths[6], 6, $row['created_by'] ?? 'System', 1, 0, 'L');
                $pdf->Ln();
                
                $rowCount++;
            }
            
            // Footer with record count
            $pdf->Ln(5);
            $pdf->SetFont('helvetica', '', 9);
            $pdf->Cell(0, 6, 'Total Records: ' . $rowCount, 0, 1, 'L');
            
            // Set headers for inline viewing
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="check_disbursement_report_' . date('Y-m-d_H-i-s') . '.pdf"');
            header('Cache-Control: public, must-revalidate, max-age=0');
            header('Pragma: public');
            
            // Output PDF for inline viewing
            $pdf->Output('check_disbursement_report_' . date('Y-m-d_H-i-s') . '.pdf', 'I');
            
        } catch (Exception $e) {
            error_log("Error exporting check disbursement PDF: " . $e->getMessage());
            echo "Error generating PDF: " . $e->getMessage();
        }
    }
    
    public function exportExcel() {
        try {
            $filters = [
                'start_date' => $_GET['start_date'] ?? '',
                'end_date' => $_GET['end_date'] ?? '',
                'account_id' => $_GET['account_id'] ?? '',
                'supplier_id' => $_GET['supplier_id'] ?? '',
                'project_id' => $_GET['project_id'] ?? '',
                'department_id' => $_GET['department_id'] ?? '',
                'status' => $_GET['status'] ?? ''
            ];
            
            $reportData = $this->model->generateReport($filters);
            $summary = $this->model->getSummaryStats($filters);
            
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
                ->setTitle('Check Disbursement Report')
                ->setSubject('Check Disbursement Report')
                ->setDescription('Check Disbursement Report generated by EARS System');
            
            // Set title
            $sheet->setCellValue('A1', 'CHECK DISBURSEMENT REPORT');
            $sheet->mergeCells('A1:G1');
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
            $sheet->mergeCells('A' . $row . ':G' . $row);
            $row++;
            
            $sheet->setCellValue('A' . $row, 'Generated on: ' . date('F j, Y \a\t g:i A'));
            $sheet->mergeCells('A' . $row . ':G' . $row);
            $row += 2;
            
            // Summary section
            if ($summary && isset($summary['total_checks']) && $summary['total_checks'] > 0) {
                $sheet->setCellValue('A' . $row, 'SUMMARY');
                $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
                $row++;
                
                // Summary table with better formatting
                $summaryData = [
                    ['Total Checks', $summary['total_checks']],
                    ['Total Amount', '₱' . number_format($summary['total_amount'], 2)],
                    ['Average Amount', '₱' . number_format($summary['average_amount'], 2)]
                ];
                
                foreach ($summaryData as $summaryRow) {
                    $sheet->setCellValue('A' . $row, $summaryRow[0]);
                    $sheet->setCellValue('B' . $row, $summaryRow[1]);
                    $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                    $row++;
                }
                $row += 2;
            }
            
            // Table header with better styling
            $headers = ['Check Number', 'Check Date', 'Payee Name', 'Account Code', 'Amount', 'Status', 'Created By'];
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
            if (is_array($reportData) && !empty($reportData)) {
                foreach ($reportData as $data) {
                    $sheet->setCellValue('A' . $row, $data['check_number']);
                    $sheet->setCellValue('B' . $row, date('m/d/Y', strtotime($data['check_date'])));
                    $sheet->setCellValue('C' . $row, $data['payee_name']);
                    $sheet->setCellValue('D' . $row, $data['account_code']);
                    $sheet->setCellValue('E' . $row, $data['amount']);
                    $sheet->setCellValue('F' . $row, $data['status'] ?? 'Active');
                    $sheet->setCellValue('G' . $row, $data['created_by'] ?? 'System');
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
            foreach (range('A', 'G') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            
            // Add borders to data table
            $lastRow = $row - 1;
            if ($dataStartRow <= $lastRow) {
                $sheet->getStyle('A' . ($dataStartRow - 1) . ':G' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                
                // Add alternating row colors for better readability
                for ($i = $dataStartRow; $i <= $lastRow; $i++) {
                    if ($i % 2 == 0) {
                        $sheet->getStyle('A' . $i . ':G' . $i)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F9F9F9');
                    }
                }
                
                // Add total row
                $row++;
                $sheet->setCellValue('A' . $row, 'TOTAL');
                $sheet->mergeCells('A' . $row . ':D' . $row);
                $sheet->setCellValue('E' . $row, '=SUM(E' . $dataStartRow . ':E' . ($row - 1) . ')');
                $sheet->getStyle('A' . $row . ':G' . $row)->getFont()->setBold(true);
                $sheet->getStyle('A' . $row . ':G' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('D9D9D9');
                $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            }
            
            // Create the Excel file
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            
            // Set headers for download
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="check_disbursement_report_' . date('Y-m-d_H-i-s') . '.xlsx"');
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            
            $writer->save('php://output');
            
        } catch (Exception $e) {
            error_log("Error exporting check disbursement Excel: " . $e->getMessage());
            echo "Error generating Excel file: " . $e->getMessage();
        }
    }
    
    public function getDataByBank() {
        try {
            $filters = [
                'start_date' => $_POST['start_date'] ?? '',
                'end_date' => $_POST['end_date'] ?? '',
                'account_id' => $_POST['account_id'] ?? '',
                'supplier_id' => $_POST['supplier_id'] ?? '',
                'project_id' => $_POST['project_id'] ?? '',
                'department_id' => $_POST['department_id'] ?? '',
                'status' => $_POST['status'] ?? ''
            ];
            
            $data = $this->model->getDataByBank($filters);
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    public function getDataByAccount() {
        try {
            $filters = [
                'start_date' => $_POST['start_date'] ?? '',
                'end_date' => $_POST['end_date'] ?? '',
                'account_id' => $_POST['account_id'] ?? '',
                'supplier_id' => $_POST['supplier_id'] ?? '',
                'project_id' => $_POST['project_id'] ?? '',
                'department_id' => $_POST['department_id'] ?? '',
                'status' => $_POST['status'] ?? ''
            ];
            
            $data = $this->model->getDataByAccount($filters);
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    public function getTopPayees() {
        try {
            $filters = [
                'start_date' => $_POST['start_date'] ?? '',
                'end_date' => $_POST['end_date'] ?? '',
                'account_id' => $_POST['account_id'] ?? '',
                'supplier_id' => $_POST['supplier_id'] ?? '',
                'project_id' => $_POST['project_id'] ?? '',
                'department_id' => $_POST['department_id'] ?? '',
                'status' => $_POST['status'] ?? ''
            ];
            
            $limit = $_POST['limit'] ?? 10;
            $data = $this->model->getTopPayees($filters, $limit);
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    public function getCheckNumberRange() {
        try {
            $filters = [
                'start_date' => $_POST['start_date'] ?? '',
                'end_date' => $_POST['end_date'] ?? '',
                'account_id' => $_POST['account_id'] ?? '',
                'supplier_id' => $_POST['supplier_id'] ?? '',
                'project_id' => $_POST['project_id'] ?? '',
                'department_id' => $_POST['department_id'] ?? '',
                'status' => $_POST['status'] ?? ''
            ];
            
            $data = $this->model->getCheckNumberRange($filters);
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
