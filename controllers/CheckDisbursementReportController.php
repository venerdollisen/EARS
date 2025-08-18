<?php
require_once 'core/Controller.php';
require_once 'models/CheckDisbursementReportModel.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

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
        
        $this->render('reports/check-disbursement', [
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
            $pdf->SetHeaderData('', 0, 'Check Disbursement Report', 'Generated on ' . date('Y-m-d H:i:s'));
            
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
            $pdf->Cell(0, 8, 'Total Checks: ' . number_format($summary['total_checks']), 0, 1, 'L');
            $pdf->Cell(0, 8, 'Total Amount: ₱' . number_format($summary['total_amount'], 2), 0, 1, 'L');
            $pdf->Cell(0, 8, 'Average Check Amount: ₱' . number_format($summary['average_amount'], 2), 0, 1, 'L');
            $pdf->Ln(5);
            
            // Add table headers
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell(25, 8, 'Check No.', 1, 0, 'C');
            $pdf->Cell(30, 8, 'Date', 1, 0, 'C');
            $pdf->Cell(40, 8, 'Payee', 1, 0, 'C');
            $pdf->Cell(30, 8, 'Account', 1, 0, 'C');
            $pdf->Cell(35, 8, 'Amount', 1, 1, 'C');
            
            // Add table data
            $pdf->SetFont('helvetica', '', 8);
            foreach ($reportData as $row) {
                $pdf->Cell(25, 6, $row['check_number'], 1, 0, 'L');
                $pdf->Cell(30, 6, $row['check_date'], 1, 0, 'L');
                $pdf->Cell(40, 6, substr($row['payee_name'], 0, 20), 1, 0, 'L');
                $pdf->Cell(30, 6, $row['account_code'], 1, 0, 'L');
                $pdf->Cell(35, 6, '₱' . number_format($row['amount'], 2), 1, 1, 'R');
            }
            
            // Output PDF
            $filename = 'check_disbursement_report_' . date('Y-m-d_H-i-s') . '.pdf';
            $pdf->Output($filename, 'D');
            
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
            
            // Create new Spreadsheet object
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Set document properties
            $spreadsheet->getProperties()
                ->setCreator('EARS System')
                ->setLastModifiedBy('EARS System')
                ->setTitle('Check Disbursement Report')
                ->setSubject('Check Disbursement Report')
                ->setDescription('Check Disbursement Report generated by EARS System');
            
            // Add title
            $sheet->setCellValue('A1', 'CHECK DISBURSEMENT REPORT');
            $sheet->mergeCells('A1:E1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // Add generation date
            $sheet->setCellValue('A2', 'Generated on: ' . date('Y-m-d H:i:s'));
            $sheet->mergeCells('A2:E2');
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // Add summary
            $sheet->setCellValue('A4', 'Summary:');
            $sheet->getStyle('A4')->getFont()->setBold(true);
            $sheet->setCellValue('A5', 'Total Checks:');
            $sheet->setCellValue('B5', $summary['total_checks']);
            $sheet->setCellValue('A6', 'Total Amount:');
            $sheet->setCellValue('B6', $summary['total_amount']);
            $sheet->setCellValue('A7', 'Average Check Amount:');
            $sheet->setCellValue('B7', $summary['average_amount']);
            
            // Add table headers
            $sheet->setCellValue('A9', 'Check Number');
            $sheet->setCellValue('B9', 'Check Date');
            $sheet->setCellValue('C9', 'Payee Name');
            $sheet->setCellValue('D9', 'Account Code');
            $sheet->setCellValue('E9', 'Amount');
            
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
            $sheet->getStyle('A9:E9')->applyFromArray($headerStyle);
            
            // Add data
            $row = 10;
            foreach ($reportData as $data) {
                $sheet->setCellValue('A' . $row, $data['check_number']);
                $sheet->setCellValue('B' . $row, $data['check_date']);
                $sheet->setCellValue('C' . $row, $data['payee_name']);
                $sheet->setCellValue('D' . $row, $data['account_code']);
                $sheet->setCellValue('E' . $row, $data['amount']);
                $row++;
            }
            
            // Style data
            $dataStyle = [
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                ]
            ];
            $sheet->getStyle('A10:E' . ($row - 1))->applyFromArray($dataStyle);
            
            // Format numbers
            $sheet->getStyle('E10:E' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('B6:B7')->getNumberFormat()->setFormatCode('#,##0.00');
            
            // Auto-size columns
            foreach (range('A', 'E') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            
            // Create Excel file
            $writer = new Xlsx($spreadsheet);
            $filename = 'check_disbursement_report_' . date('Y-m-d_H-i-s') . '.xlsx';
            
            // Set headers for download
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            
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
