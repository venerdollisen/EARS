<?php
require_once 'core/Controller.php';
require_once 'models/IncomeStatementReportModel.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class IncomeStatementReportController extends Controller {
    
    private $model;
    
    public function __construct() {
        parent::__construct();
        $this->model = new IncomeStatementReportModel();
    }
    
    public function index() {
        // Set no-cache headers
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $this->render('reports/income-statement', [
            'title' => 'Income Statement Report',
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
            $revenueByCategory = $this->model->getRevenueByCategory($filters);
            $expensesByCategory = $this->model->getExpensesByCategory($filters);
            $monthlyTrend = $this->model->getMonthlyTrend($filters);
            $topRevenueAccounts = $this->model->getTopRevenueAccounts($filters, 10);
            $topExpenseAccounts = $this->model->getTopExpenseAccounts($filters, 10);
            $profitabilityRatios = $this->model->getProfitabilityRatios($filters);
            
            echo json_encode([
                'success' => true,
                'data' => $reportData,
                'summary' => $summary,
                'charts' => [
                    'revenueByCategory' => $revenueByCategory,
                    'expensesByCategory' => $expensesByCategory,
                    'monthlyTrend' => $monthlyTrend,
                    'topRevenueAccounts' => $topRevenueAccounts,
                    'topExpenseAccounts' => $topExpenseAccounts
                ],
                'ratios' => $profitabilityRatios
            ]);
        } catch (Exception $e) {
            error_log("Error generating income statement report: " . $e->getMessage());
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
            $pdf->SetTitle('Income Statement Report');
            
            // Set default header data
            $pdf->SetHeaderData('', 0, 'Income Statement Report', 'Generated on ' . date('Y-m-d H:i:s'));
            
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
            $pdf->Cell(0, 8, 'Total Revenue: ₱' . number_format($summary['total_revenue'], 2), 0, 1, 'L');
            $pdf->Cell(0, 8, 'Total Expenses: ₱' . number_format($summary['total_expenses'], 2), 0, 1, 'L');
            $pdf->Cell(0, 8, 'Net Income: ₱' . number_format($summary['net_income'], 2), 0, 1, 'L');
            $pdf->Ln(5);
            
            // Add Revenue section
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, 'REVENUE', 0, 1, 'L');
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell(25, 8, 'Account', 1, 0, 'C');
            $pdf->Cell(60, 8, 'Account Name', 1, 0, 'C');
            $pdf->Cell(30, 8, 'Type', 1, 0, 'C');
            $pdf->Cell(35, 8, 'Total Revenue', 1, 1, 'C');
            
            // Add Revenue data
            $pdf->SetFont('helvetica', '', 8);
            foreach ($reportData['revenue'] as $row) {
                $pdf->Cell(25, 6, $row['account_code'], 1, 0, 'L');
                $pdf->Cell(60, 6, substr($row['account_name'], 0, 25), 1, 0, 'L');
                $pdf->Cell(30, 6, $row['account_type'], 1, 0, 'L');
                $pdf->Cell(35, 6, '₱' . number_format($row['total_revenue'], 2), 1, 1, 'R');
            }
            
            $pdf->Ln(5);
            
            // Add Expenses section
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, 'EXPENSES', 0, 1, 'L');
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell(25, 8, 'Account', 1, 0, 'C');
            $pdf->Cell(60, 8, 'Account Name', 1, 0, 'C');
            $pdf->Cell(30, 8, 'Type', 1, 0, 'C');
            $pdf->Cell(35, 8, 'Total Expense', 1, 1, 'C');
            
            // Add Expenses data
            $pdf->SetFont('helvetica', '', 8);
            foreach ($reportData['expenses'] as $row) {
                $pdf->Cell(25, 6, $row['account_code'], 1, 0, 'L');
                $pdf->Cell(60, 6, substr($row['account_name'], 0, 25), 1, 0, 'L');
                $pdf->Cell(30, 6, $row['account_type'], 1, 0, 'L');
                $pdf->Cell(35, 6, '₱' . number_format($row['total_expense'], 2), 1, 1, 'R');
            }
            
            // Output PDF
            $filename = 'income_statement_report_' . date('Y-m-d_H-i-s') . '.pdf';
            $pdf->Output($filename, 'D');
            
        } catch (Exception $e) {
            error_log("Error exporting income statement PDF: " . $e->getMessage());
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
                ->setTitle('Income Statement Report')
                ->setSubject('Income Statement Report')
                ->setDescription('Income Statement Report generated by EARS System');
            
            // Add title
            $sheet->setCellValue('A1', 'INCOME STATEMENT REPORT');
            $sheet->mergeCells('A1:D1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // Add generation date
            $sheet->setCellValue('A2', 'Generated on: ' . date('Y-m-d H:i:s'));
            $sheet->mergeCells('A2:D2');
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // Add summary
            $sheet->setCellValue('A4', 'Summary:');
            $sheet->getStyle('A4')->getFont()->setBold(true);
            $sheet->setCellValue('A5', 'Total Revenue:');
            $sheet->setCellValue('B5', $summary['total_revenue']);
            $sheet->setCellValue('A6', 'Total Expenses:');
            $sheet->setCellValue('B6', $summary['total_expenses']);
            $sheet->setCellValue('A7', 'Net Income:');
            $sheet->setCellValue('B7', $summary['net_income']);
            
            // Add Revenue section
            $sheet->setCellValue('A9', 'REVENUE');
            $sheet->getStyle('A9')->getFont()->setBold(true)->setSize(12);
            $sheet->mergeCells('A9:D9');
            
            $sheet->setCellValue('A10', 'Account Code');
            $sheet->setCellValue('B10', 'Account Name');
            $sheet->setCellValue('C10', 'Account Type');
            $sheet->setCellValue('D10', 'Total Revenue');
            
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
            $sheet->getStyle('A10:D10')->applyFromArray($headerStyle);
            
            // Add Revenue data
            $row = 11;
            foreach ($reportData['revenue'] as $data) {
                $sheet->setCellValue('A' . $row, $data['account_code']);
                $sheet->setCellValue('B' . $row, $data['account_name']);
                $sheet->setCellValue('C' . $row, $data['account_type']);
                $sheet->setCellValue('D' . $row, $data['total_revenue']);
                $row++;
            }
            
            $row += 2;
            
            // Add Expenses section
            $sheet->setCellValue('A' . $row, 'EXPENSES');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
            $sheet->mergeCells('A' . $row . ':D' . $row);
            $row++;
            
            $sheet->setCellValue('A' . $row, 'Account Code');
            $sheet->setCellValue('B' . $row, 'Account Name');
            $sheet->setCellValue('C' . $row, 'Account Type');
            $sheet->setCellValue('D' . $row, 'Total Expense');
            
            $sheet->getStyle('A' . $row . ':D' . $row)->applyFromArray($headerStyle);
            $row++;
            
            // Add Expenses data
            foreach ($reportData['expenses'] as $data) {
                $sheet->setCellValue('A' . $row, $data['account_code']);
                $sheet->setCellValue('B' . $row, $data['account_name']);
                $sheet->setCellValue('C' . $row, $data['account_type']);
                $sheet->setCellValue('D' . $row, $data['total_expense']);
                $row++;
            }
            
            // Style data
            $dataStyle = [
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                ]
            ];
            $sheet->getStyle('A11:D' . ($row - 1))->applyFromArray($dataStyle);
            
            // Format numbers
            $sheet->getStyle('D11:D' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('B5:B7')->getNumberFormat()->setFormatCode('#,##0.00');
            
            // Auto-size columns
            foreach (range('A', 'D') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            
            // Create Excel file
            $writer = new Xlsx($spreadsheet);
            $filename = 'income_statement_report_' . date('Y-m-d_H-i-s') . '.xlsx';
            
            // Set headers for download
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            
            $writer->save('php://output');
            
        } catch (Exception $e) {
            error_log("Error exporting income statement Excel: " . $e->getMessage());
            echo "Error generating Excel file: " . $e->getMessage();
        }
    }
    
    public function getRevenueByCategory() {
        try {
            $filters = [
                'start_date' => $_POST['start_date'] ?? '',
                'end_date' => $_POST['end_date'] ?? '',
                'account_id' => $_POST['account_id'] ?? '',
                'account_type_id' => $_POST['account_type_id'] ?? ''
            ];
            
            $data = $this->model->getRevenueByCategory($filters);
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    public function getExpensesByCategory() {
        try {
            $filters = [
                'start_date' => $_POST['start_date'] ?? '',
                'end_date' => $_POST['end_date'] ?? '',
                'account_id' => $_POST['account_id'] ?? '',
                'account_type_id' => $_POST['account_type_id'] ?? ''
            ];
            
            $data = $this->model->getExpensesByCategory($filters);
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    public function getMonthlyTrend() {
        try {
            $filters = [
                'start_date' => $_POST['start_date'] ?? '',
                'end_date' => $_POST['end_date'] ?? '',
                'account_id' => $_POST['account_id'] ?? '',
                'account_type_id' => $_POST['account_type_id'] ?? ''
            ];
            
            $data = $this->model->getMonthlyTrend($filters);
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    public function getTopRevenueAccounts() {
        try {
            $filters = [
                'start_date' => $_POST['start_date'] ?? '',
                'end_date' => $_POST['end_date'] ?? '',
                'account_id' => $_POST['account_id'] ?? '',
                'account_type_id' => $_POST['account_type_id'] ?? ''
            ];
            
            $limit = $_POST['limit'] ?? 10;
            $data = $this->model->getTopRevenueAccounts($filters, $limit);
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    public function getTopExpenseAccounts() {
        try {
            $filters = [
                'start_date' => $_POST['start_date'] ?? '',
                'end_date' => $_POST['end_date'] ?? '',
                'account_id' => $_POST['account_id'] ?? '',
                'account_type_id' => $_POST['account_type_id'] ?? ''
            ];
            
            $limit = $_POST['limit'] ?? 10;
            $data = $this->model->getTopExpenseAccounts($filters, $limit);
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
