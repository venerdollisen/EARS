<?php
require_once 'core/Controller.php';
require_once 'models/TrialBalanceReportModel.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

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
            $pdf->SetHeaderData('', 0, 'Trial Balance Report', 'Generated on ' . date('Y-m-d H:i:s'));
            
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
            
            // Add summary information
            $pdf->Cell(0, 10, 'Summary:', 0, 1, 'L');
            $pdf->Cell(0, 8, 'Total Accounts: ' . number_format($summary['total_accounts']), 0, 1, 'L');
            $pdf->Cell(0, 8, 'Total Debits: ' . number_format($summary['total_debits'], 2), 0, 1, 'L');
            $pdf->Cell(0, 8, 'Total Credits: ' . number_format($summary['total_credits'], 2), 0, 1, 'L');
            $pdf->Cell(0, 8, 'Net Balance: ' . number_format($summary['net_balance'], 2), 0, 1, 'L');
            $pdf->Ln(5);
            
            // Add table headers
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell(25, 8, 'Account', 1, 0, 'C');
            $pdf->Cell(60, 8, 'Account Name', 1, 0, 'C');
            $pdf->Cell(30, 8, 'Type', 1, 0, 'C');
            $pdf->Cell(25, 8, 'Debits', 1, 0, 'C');
            $pdf->Cell(25, 8, 'Credits', 1, 0, 'C');
            $pdf->Cell(25, 8, 'Balance', 1, 1, 'C');
            
            // Add table data
            $pdf->SetFont('helvetica', '', 8);
            foreach ($reportData as $row) {
                $pdf->Cell(25, 6, $row['account_code'], 1, 0, 'L');
                $pdf->Cell(60, 6, substr($row['account_name'], 0, 25), 1, 0, 'L');
                $pdf->Cell(30, 6, $row['account_type'], 1, 0, 'L');
                $pdf->Cell(25, 6, number_format($row['total_debits'], 2), 1, 0, 'R');
                $pdf->Cell(25, 6, number_format($row['total_credits'], 2), 1, 0, 'R');
                $pdf->Cell(25, 6, number_format($row['balance'], 2), 1, 1, 'R');
            }
            
            // Output PDF
            $filename = 'trial_balance_report_' . date('Y-m-d_H-i-s') . '.pdf';
            $pdf->Output($filename, 'D');
            
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
            
            // Create new Spreadsheet object
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Set document properties
            $spreadsheet->getProperties()
                ->setCreator('EARS System')
                ->setLastModifiedBy('EARS System')
                ->setTitle('Trial Balance Report')
                ->setSubject('Trial Balance Report')
                ->setDescription('Trial Balance Report generated by EARS System');
            
            // Add title
            $sheet->setCellValue('A1', 'TRIAL BALANCE REPORT');
            $sheet->mergeCells('A1:F1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // Add generation date
            $sheet->setCellValue('A2', 'Generated on: ' . date('Y-m-d H:i:s'));
            $sheet->mergeCells('A2:F2');
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // Add summary
            $sheet->setCellValue('A4', 'Summary:');
            $sheet->getStyle('A4')->getFont()->setBold(true);
            $sheet->setCellValue('A5', 'Total Accounts:');
            $sheet->setCellValue('B5', $summary['total_accounts']);
            $sheet->setCellValue('A6', 'Total Debits:');
            $sheet->setCellValue('B6', $summary['total_debits']);
            $sheet->setCellValue('A7', 'Total Credits:');
            $sheet->setCellValue('B7', $summary['total_credits']);
            $sheet->setCellValue('A8', 'Net Balance:');
            $sheet->setCellValue('B8', $summary['net_balance']);
            
            // Add table headers
            $sheet->setCellValue('A10', 'Account Code');
            $sheet->setCellValue('B10', 'Account Name');
            $sheet->setCellValue('C10', 'Account Type');
            $sheet->setCellValue('D10', 'Total Debits');
            $sheet->setCellValue('E10', 'Total Credits');
            $sheet->setCellValue('F10', 'Balance');
            
            // Style headers
            $headerStyle = [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E0E0E0']
                ]
            ];
            $sheet->getStyle('A10:F10')->applyFromArray($headerStyle);
            
            // Add data
            $row = 11;
            foreach ($reportData as $data) {
                $sheet->setCellValue('A' . $row, $data['account_code']);
                $sheet->setCellValue('B' . $row, $data['account_name']);
                $sheet->setCellValue('C' . $row, $data['account_type']);
                $sheet->setCellValue('D' . $row, $data['total_debits']);
                $sheet->setCellValue('E' . $row, $data['total_credits']);
                $sheet->setCellValue('F' . $row, $data['balance']);
                $row++;
            }
            
            // Style data
            $dataStyle = [
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                ]
            ];
            $sheet->getStyle('A11:F' . ($row - 1))->applyFromArray($dataStyle);
            
            // Format numbers
            $sheet->getStyle('D11:F' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');
            
            // Auto-size columns
            foreach (range('A', 'F') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            
            // Create Excel file
            $writer = new Xlsx($spreadsheet);
            $filename = 'trial_balance_report_' . date('Y-m-d_H-i-s') . '.xlsx';
            
            // Set headers for download
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            
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
