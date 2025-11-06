<?php
require_once 'core/Controller.php';
require_once 'models/TrialBalanceReportModel.php';

class TrialBalanceReportController extends Controller {
    
    private $model;
    
    public function __construct() {
        parent::__construct();
        $this->model = new TrialBalanceReportModel();
    }
    
    public function index() {
        // Set no-cache headers
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $this->render('reports/trial-balance', [
            'title' => 'Trial Balance Report',
            'accounts' => $this->model->getActiveAccounts(),
            'accountTypes' => $this->model->getActiveAccountTypes()
        ]);
    }
    
    public function generate() {
        try {
            $filters = [
                'start_date' => $_POST['start_date'] ?? '',
                'end_date' => $_POST['end_date'] ?? '',
                'account_id' => $_POST['account_id'] ?? '',
                'account_type_id' => $_POST['account_type_id'] ?? ''
            ];
            
            $reportData = $this->model->generateReport($filters);
            $summary = $this->model->getSummaryStats($filters);
            $dataByAccountType = $this->model->getDataByAccountType($filters);
            $balanceDistribution = $this->model->getBalanceDistribution($filters);
            $topAccounts = $this->model->getTopAccounts($filters, 10);
            
            echo json_encode([
                'success' => true,
                'data' => $reportData,
                'summary' => $summary,
                'charts' => [
                    'byAccountType' => $dataByAccountType,
                    'balanceDistribution' => $balanceDistribution,
                    'topAccounts' => $topAccounts
                ]
            ]);
        } catch (Exception $e) {
            error_log("Error generating trial balance report: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error generating report: ' . $e->getMessage()
            ]);
        }
    }
    
    public function exportPDF() {
        try {
            $filters = [
                'start_date' => $_GET['start_date'] ?? '',
                'end_date' => $_GET['end_date'] ?? '',
                'account_id' => $_GET['account_id'] ?? '',
                'account_type_id' => $_GET['account_type_id'] ?? ''
            ];
            
            $reportData = $this->model->generateReport($filters);
            $summary = $this->model->getSummaryStats($filters);
            
            // Create PDF
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            
            // Set document information
            $pdf->SetCreator('EARS System');
            $pdf->SetAuthor('EARS System');
            $pdf->SetTitle('Trial Balance Report');
            
            // Set default header data
            $pdf->SetHeaderData('', 0, 'EARS System', 'Trial Balance Report');
            
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
            $pdf->Cell(0, 10, 'TRIAL BALANCE REPORT', 0, 1, 'C');
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
            if ($summary && $summary['total_accounts'] > 0) {
                $pdf->SetFont('helvetica', 'B', 11);
                $pdf->Cell(0, 8, 'SUMMARY', 0, 1, 'L');
                $pdf->SetFont('helvetica', '', 9);
                
                // Summary table
                $pdf->Cell(50, 6, 'Total Accounts:', 0, 0);
                $pdf->Cell(30, 6, number_format($summary['total_accounts']), 0, 1);
                
                $pdf->Cell(50, 6, 'Total Debits:', 0, 0);
                $pdf->Cell(30, 6, '₱' . number_format($summary['total_debits'], 2), 0, 1);
                
                $pdf->Cell(50, 6, 'Total Credits:', 0, 0);
                $pdf->Cell(30, 6, '₱' . number_format($summary['total_credits'], 2), 0, 1);
                
                $pdf->Cell(50, 6, 'Net Balance:', 0, 0);
                $pdf->Cell(30, 6, '₱' . number_format($summary['net_balance'], 2), 0, 1);
                
                $pdf->Ln(5);
            }
            
            // Table header
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->SetFillColor(240, 240, 240);
            
            // Define column widths for landscape A4
            $colWidths = [25, 60, 30, 25, 25, 25];
            $headers = ['Account', 'Account Name', 'Type', 'Debits', 'Credits', 'Balance'];
            
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
                $pdf->Cell($colWidths[0], 6, $row['account_code'], 1, 0, 'L');
                $pdf->Cell($colWidths[1], 6, $row['account_name'], 1, 0, 'L');
                $pdf->Cell($colWidths[2], 6, $row['account_type'], 1, 0, 'L');
                $pdf->Cell($colWidths[3], 6, '₱' . number_format($row['total_debits'], 2), 1, 0, 'R');
                $pdf->Cell($colWidths[4], 6, '₱' . number_format($row['total_credits'], 2), 1, 0, 'R');
                $pdf->Cell($colWidths[5], 6, '₱' . number_format($row['balance'], 2), 1, 1, 'R');
                
                $rowCount++;
            }
            
            // Footer with record count
            $pdf->Ln(5);
            $pdf->SetFont('helvetica', '', 9);
            $pdf->Cell(0, 6, 'Total Records: ' . $rowCount, 0, 1, 'L');
            
            // Set headers for inline viewing
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="trial_balance_report_' . date('Y-m-d_H-i-s') . '.pdf"');
            header('Cache-Control: public, must-revalidate, max-age=0');
            header('Pragma: public');
            
            // Output PDF for inline viewing
            $pdf->Output('trial_balance_report_' . date('Y-m-d_H-i-s') . '.pdf', 'I');
            
        } catch (Exception $e) {
            error_log("Error exporting trial balance PDF: " . $e->getMessage());
            echo "Error generating PDF: " . $e->getMessage();
        }
    }
    
    public function exportExcel() {
        try {
            $filters = [
                'start_date' => $_GET['start_date'] ?? '',
                'end_date' => $_GET['end_date'] ?? '',
                'account_id' => $_GET['account_id'] ?? '',
                'account_type_id' => $_GET['account_type_id'] ?? ''
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
                ->setTitle('Trial Balance Report')
                ->setSubject('Trial Balance Report')
                ->setDescription('Trial Balance Report generated by EARS System');
            
            // Set title
            $sheet->setCellValue('A1', 'TRIAL BALANCE REPORT');
            $sheet->mergeCells('A1:F1');
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
            $sheet->mergeCells('A' . $row . ':F' . $row);
            $row++;
            
            $sheet->setCellValue('A' . $row, 'Generated on: ' . date('F j, Y \a\t g:i A'));
            $sheet->mergeCells('A' . $row . ':F' . $row);
            $row += 2;
            
            // Summary section
            if ($summary && isset($summary['total_accounts']) && $summary['total_accounts'] > 0) {
                $sheet->setCellValue('A' . $row, 'SUMMARY');
                $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
                $row++;
                
                // Summary table with better formatting
                $summaryData = [
                    ['Total Accounts', $summary['total_accounts']],
                    ['Total Debits', '₱' . number_format($summary['total_debits'], 2)],
                    ['Total Credits', '₱' . number_format($summary['total_credits'], 2)],
                    ['Net Balance', '₱' . number_format($summary['net_balance'], 2)]
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
            $headers = ['Account Code', 'Account Name', 'Account Type', 'Total Debits', 'Total Credits', 'Balance'];
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
                    $sheet->setCellValue('A' . $row, $data['account_code']);
                    $sheet->setCellValue('B' . $row, $data['account_name']);
                    $sheet->setCellValue('C' . $row, $data['account_type']);
                    $sheet->setCellValue('D' . $row, $data['total_debits']);
                    $sheet->setCellValue('E' . $row, $data['total_credits']);
                    $sheet->setCellValue('F' . $row, $data['balance']);
                    $row++;
                }
            }
            
            // Format amount columns as currency
            $amountColumns = ['D', 'E', 'F'];
            if ($dataStartRow < $row) {
                foreach ($amountColumns as $col) {
                    $sheet->getStyle($col . $dataStartRow . ':' . $col . ($row - 1))
                        ->getNumberFormat()
                        ->setFormatCode('#,##0.00');
                }
            }
            
            // Auto-size columns
            foreach (range('A', 'F') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            
            // Add borders to data table
            $lastRow = $row - 1;
            if ($dataStartRow <= $lastRow) {
                $sheet->getStyle('A' . ($dataStartRow - 1) . ':F' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                
                // Add alternating row colors for better readability
                for ($i = $dataStartRow; $i <= $lastRow; $i++) {
                    if ($i % 2 == 0) {
                        $sheet->getStyle('A' . $i . ':F' . $i)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F9F9F9');
                    }
                }
                
                // Add total row
                $row++;
                $sheet->setCellValue('A' . $row, 'TOTAL');
                $sheet->mergeCells('A' . $row . ':C' . $row);
                $sheet->setCellValue('D' . $row, '=SUM(D' . $dataStartRow . ':D' . ($row - 1) . ')');
                $sheet->setCellValue('E' . $row, '=SUM(E' . $dataStartRow . ':E' . ($row - 1) . ')');
                $sheet->setCellValue('F' . $row, '=SUM(F' . $dataStartRow . ':F' . ($row - 1) . ')');
                $sheet->getStyle('A' . $row . ':F' . $row)->getFont()->setBold(true);
                $sheet->getStyle('A' . $row . ':F' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('D9D9D9');
                foreach ($amountColumns as $col) {
                    $sheet->getStyle($col . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                }
            }
            
            // Create the Excel file
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            
            // Set headers for download
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="trial_balance_report_' . date('Y-m-d_H-i-s') . '.xlsx"');
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            
            $writer->save('php://output');
            
        } catch (Exception $e) {
            error_log("Error exporting trial balance Excel: " . $e->getMessage());
            echo "Error generating Excel file: " . $e->getMessage();
        }
    }
    
    public function getDataByAccountType() {
        try {
            $filters = [
                'start_date' => $_POST['start_date'] ?? '',
                'end_date' => $_POST['end_date'] ?? '',
                'account_id' => $_POST['account_id'] ?? '',
                'account_type_id' => $_POST['account_type_id'] ?? ''
            ];
            
            $data = $this->model->getDataByAccountType($filters);
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    public function getBalanceDistribution() {
        try {
            $filters = [
                'start_date' => $_POST['start_date'] ?? '',
                'end_date' => $_POST['end_date'] ?? '',
                'account_id' => $_POST['account_id'] ?? '',
                'account_type_id' => $_POST['account_type_id'] ?? ''
            ];
            
            $data = $this->model->getBalanceDistribution($filters);
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    public function getTopAccounts() {
        try {
            $filters = [
                'start_date' => $_POST['start_date'] ?? '',
                'end_date' => $_POST['end_date'] ?? '',
                'account_id' => $_POST['account_id'] ?? '',
                'account_type_id' => $_POST['account_type_id'] ?? ''
            ];
            
            $limit = $_POST['limit'] ?? 10;
            $data = $this->model->getTopAccounts($filters, $limit);
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
