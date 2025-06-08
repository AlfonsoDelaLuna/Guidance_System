<?php

require 'vendor/autoload.php';

use TCPDF;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Database connection
$servername = 'localhost';
$username = 'root';
$password = '';
$dbname = 'guidance_db';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Get parameters
$type = $_GET['type'] ?? '';
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$active_filters = [];

// Build query based on student type
if ($type === 'shs') {
    $table = 'shs_students';
    $gradeLevel = $_GET['gradeLevel'] ?? '';
    $strand = $_GET['strand'] ?? '';

    $sql = "SELECT * FROM $table WHERE 1=1";
    if ($gradeLevel) {
        $sql .= " AND grade_level = '$gradeLevel'";
        $active_filters[] = "Grade Level: " . htmlspecialchars($gradeLevel);
    }
    if ($strand) {
        $sql .= " AND shs_program = '$strand'";
        $active_filters[] = "Strand: " . htmlspecialchars($strand);
    }
} else {
    $table = 'college_students';
    $yearLevel = $_GET['yearLevel'] ?? '';
    $program = $_GET['program'] ?? '';

    $sql = "SELECT * FROM $table WHERE 1=1";
    if ($yearLevel) {
        $sql .= " AND year_level = '$yearLevel'";
        $active_filters[] = "Year Level: " . htmlspecialchars($yearLevel);
    }
    if ($program) {
        $sql .= " AND program = '$program'";
        $active_filters[] = "Program: " . htmlspecialchars($program);
    }
}

if ($status) {
    $sql .= " AND status = '$status'";
    $active_filters[] = "Status: " . htmlspecialchars($status);
}

if ($search) {
    $sql .= " AND (student_id_no LIKE '%$search%'
             OR first_name LIKE '%$search%'
             OR last_name LIKE '%$search%')";
    $active_filters[] = "Search: '" . htmlspecialchars($search) . "'";
}

$sql .= " ORDER BY last_name ASC, first_name ASC";

$result = $conn->query($sql);

// Try TCPDF first
try {
    if (!class_exists('TCPDF')) {
        throw new Exception('TCPDF class not found. Run composer require tecnickcom/tcpdf and composer dump-autoload.');
    }

    ob_clean();
    ob_start();
    $pdf = new TCPDF('P', 'mm', 'LETTER', true, 'UTF-8', false);

    $pdf->SetCreator('Guidance System');
    $pdf->SetAuthor('Guidance Office');
    $pdf->SetTitle($type === 'shs' ? 'SHS Student List' : 'College Student List');
    $pdf->SetSubject('Filtered Student Data');

    $pdf->SetMargins(10, 25, 10);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);
    $pdf->setHeaderFont(['helvetica', '', 10]);
    $pdf->setFooterFont(['helvetica', '', 8]);
    $pdf->SetAutoPageBreak(true, 10);

    $pdf->setHeaderData('', 0, $type === 'shs' ? 'SHS Student List' : 'College Student List', 'Generated on: ' . date('Y-m-d H:i:s'), [0, 0, 0], [255, 255, 255]);

    $pdf->AddPage();

    if ($type === 'shs') {
        $headers = ['Student Number', 'Last Name', 'First Name', 'Grade Level', 'SHS Program', 'Section', 'Status'];
    } else {
        $headers = ['Student Number', 'Last Name', 'First Name', 'Year Level', 'Program', 'Section', 'Status'];
    }

    // Adjusted widths: Increased fifth column (SHS Program/Program) to 50mm
    $widths = [25, 30, 34, 20, 60, 20, 15];// Total: 185mm, fits within 195.9mm

    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(0, 8, 'Applied Filters:', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 8);
    if (!empty($active_filters)) {
        $filterText = implode(' | ', $active_filters);
        $pdf->MultiCell(0, 4, $filterText, 0, 'L', 0, 1);
    } else {
        $pdf->Cell(0, 4, 'None (Showing all students)', 0, 1, 'L');
    }
    $pdf->Ln(3);

    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetFillColor(43, 87, 154);
    $pdf->SetTextColor(255, 255, 255);
    foreach ($headers as $i => $header) {
        $pdf->Cell($widths[$i], 8, $header, 1, 0, 'C', 1);
    }
    $pdf->Ln();

    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(0, 0, 0);
    $fill = false;
    while ($data = $result->fetch_assoc()) {
        if ($fill) {
            $pdf->SetFillColor(245, 246, 245);
        } else {
            $pdf->SetFillColor(255, 255, 255);
        }
        $pdf->Cell($widths[0], 6, $data['student_id_no'], 1, 0, 'L', 1);
        $pdf->Cell($widths[1], 6, ucwords(strtolower($data['last_name'])), 1, 0, 'L', 1);
        $pdf->Cell($widths[2], 6, ucwords(strtolower($data['first_name'])), 1, 0, 'L', 1);
        $pdf->Cell($widths[3], 6, $type === 'shs' ? $data['grade_level'] : $data['year_level'], 1, 0, 'L', 1);
        $pdf->Cell($widths[4], 6, $type === 'shs' ? $data['shs_program'] : $data['program'], 1, 0, 'L', 1);
        $pdf->Cell($widths[5], 6, $data['section'], 1, 0, 'L', 1);
        $pdf->Cell($widths[6], 6, ucfirst(strtolower($data['status'])), 1, 0, 'L', 1);
        $pdf->Ln();
        $fill = !$fill;
    }

    $fileName = $type . '_students_report_' . date('Y-m-d_H-i-s') . '.pdf';
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment;filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');

    $pdf->Output($fileName, 'D');
} catch (Exception $e) {
    error_log('TCPDF Error: ' . $e->getMessage());

    // Fallback to mPDF
    try {
        if (!class_exists('Mpdf\Mpdf')) {
            throw new Exception('mPDF class not found. Run composer require mpdf/mpdf and composer dump-autoload.');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $spreadsheet->getProperties()
            ->setCreator('Guidance System')
            ->setTitle($type === 'shs' ? 'SHS Student List' : 'College Student List')
            ->setSubject('Filtered Student Data');

        $headerStyle = [
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2B579A']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ];
        $headerRange = 'A1:G1'; // Fixed to 7 columns
        $sheet->getStyle($headerRange)->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(30);

        // Set headers
        foreach (range('A', 'G') as $i => $col) {
            $sheet->setCellValue($col . '1', $headers[$i]);
        }

        // Define custom column widths (matching TCPDF proportions where possible)
        $columnWidths = [15, 20, 20, 10, 40, 20, 15]; // Fifth column (E) increased to 30 units
        foreach (range('A', 'G') as $i => $col) {
            $sheet->getColumnDimension($col)->setWidth($columnWidths[$i]);
        }

        $filterRow = 2;
        $sheet->setCellValue('A' . $filterRow, 'Applied Filters:');
        $sheet->getStyle('A' . $filterRow)->getFont()->setBold(true);
        $filterRow++;
        $sheet->setCellValue('A' . $filterRow, !empty($active_filters) ? implode(' | ', $active_filters) : 'None (Showing all students)');
        $sheet->getStyle('A' . $filterRow)->getFont()->setSize(8);
        $sheet->mergeCells('A' . $filterRow . ':G' . $filterRow);
        $filterRow++;

        $row = $filterRow + 1;
        $fill = false;
        mysqli_data_seek($result, 0);
        while ($data = $result->fetch_assoc()) {
            $col = 'A';
            $sheet->setCellValue($col++ . $row, $data['student_id_no']);
            $sheet->setCellValue($col++ . $row, ucwords(strtolower($data['last_name'])));
            $sheet->setCellValue($col++ . $row, ucwords(strtolower($data['first_name'])));
            $sheet->setCellValue($col++ . $row, $type === 'shs' ? $data['grade_level'] : $data['year_level']);
            $sheet->setCellValue($col++ . $row, $type === 'shs' ? $data['shs_program'] : $data['program']);
            $sheet->setCellValue($col++ . $row, $data['section']);
            $sheet->setCellValue($col . $row, ucfirst(strtolower($data['status'])));

            // Updated row style with text wrapping
            $rowStyle = [
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $fill ? 'F5F6F5' : 'FFFFFF']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D3D3D3']]],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true // Enable text wrapping for long content
                ],
            ];
            $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($rowStyle);
            $sheet->getRowDimension($row)->setRowHeight(20);
            $fill = !$fill;
            $row++;
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf($spreadsheet);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
    } catch (Exception $e) {
        error_log('mPDF Error: ' . $e->getMessage());

        // Fallback to Excel
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $fileName = str_replace('.pdf', '.xlsx', $fileName);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
    }
}

$conn->close();
?>