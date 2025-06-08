<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$servername = "localhost";
$dbusername = "root";
$password = "";
$dbname = "guidance_db";

$conn = new mysqli($servername, $dbusername, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Include PHPSpreadsheet
require 'vendor/autoload.php'; // Ensure PHPSpreadsheet is installed via Composer

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

// Get filter parameters
$yearLevel = isset($_GET['yearLevel']) ? $_GET['yearLevel'] : '';
$program = isset($_GET['program']) ? $_GET['program'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Get the table type
$type = isset($_GET['type']) ? $_GET['type'] : 'college';

// Determine the table name based on the type
$tableName = ($type === 'shs') ? 'shs_students' : 'college_students';

// Build SQL query
$sql = "SELECT * FROM $tableName WHERE 1=1";
$params = [];
$types = '';

if ($yearLevel !== '') {
    $sql .= " AND year_level = ?";
    $params[] = $yearLevel;
    $types .= 's';
}
if ($program !== '') {
    $sql .= " AND program = ?";
    $params[] = $program;
    $types .= 's';
}
if ($status !== '') {
    $sql .= " AND status = ?";
    $params[] = $status;
    $types .= 's';
}
if ($search !== '') {
    $sql .= " AND (student_id_no LIKE ? OR last_name LIKE ? OR first_name LIKE ? OR middle_name LIKE ? OR section LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'sssss';
}

// Prepare and execute query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Fetch all data into an array
$data = $result->fetch_all(MYSQLI_ASSOC);

// Sort the data by last name
usort($data, function ($a, $b) {
    return strcmp($a['last_name'], $b['last_name']);
});

// Create new Spreadsheet object
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set default column width
$sheet->getDefaultColumnDimension()->setWidth(30);

// Set headers based on student_record.php fields
$headers = [
    'ID',
    'Student Number',
    'Last Name',
    'First Name',
    'Middle Name',
    'Year Level',
    'college_program',
    'Section',
    'Status',
    'Contact Number', // Changed 'Program' to 'college_program'
    'Email',
    'Age',
    'Nationality',
    'Gender',
    'Religion',
    'Civil Status',
    'Father\'s Name',
    'Mother\'s Name',
    'Emergency Contact Name',
    'Emergency Contact Number',
    'Has Siblings',
    'Present Address'
];
$column = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($column . '1', $header);

    // Apply styles to header
    $sheet->getStyle($column . '1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle($column . '1')->getFont()->setBold(true);
    $sheet->getStyle($column . '1')->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('FF000080'); // Dark blue color
    $sheet->getStyle($column . '1')->getFont()->setColor(new Color(Color::COLOR_WHITE));

    $column++;
}

// Set column width for college_program
$sheet->getColumnDimension('K')->setWidth(40);
$sheet->getColumnDimension('S')->setWidth(40);
$sheet->getColumnDimension('T')->setWidth(40);
$sheet->getColumnDimension('G')->setWidth(40);
$sheet->getColumnDimension('V')->setWidth(150);

// Populate data
$rowNumber = 2;
$id_counter = 1;
foreach ($data as $row) {
    $sheet->setCellValue('A' . $rowNumber, $id_counter);
    $sheet->setCellValue('B' . $rowNumber, $row['student_id_no']);
    $sheet->setCellValue('C' . $rowNumber, $row['last_name']);
    $sheet->setCellValue('D' . $rowNumber, $row['first_name']);
    $sheet->setCellValue('E' . $rowNumber, $row['middle_name']);
    $sheet->setCellValue('F' . $rowNumber, $row['year_level']);
    $sheet->setCellValue('G' . $rowNumber, $row['program']);
    $sheet->setCellValue('H' . $rowNumber, $row['section']);
    $sheet->setCellValue('I' . $rowNumber, $row['status']);
    $contact_number = $row['contact_number'] ?: 'Not provided';
    if (strlen($contact_number) == 10 && substr($contact_number, 0, 1) !== '0') {
        $contact_number = '0' . $contact_number;
    }
    $sheet->setCellValue('J' . $rowNumber, $contact_number);
    $sheet->setCellValue('K' . $rowNumber, $row['email_addresses'] ?: 'N/A');
    $sheet->setCellValue('L' . $rowNumber, $row['age'] ?: 'N/A');
    $sheet->setCellValue('M' . $rowNumber, $row['nationality'] ?: 'N/A');
    $sheet->setCellValue('N' . $rowNumber, $row['gender'] ?: 'N/A');
    $sheet->setCellValue('O' . $rowNumber, $row['religion'] ?: 'N/A');
    $sheet->setCellValue('P' . $rowNumber, $row['civil_status'] ?: 'N/A');
    $sheet->setCellValue('Q' . $rowNumber, $row['father_name'] ?: 'N/A');
    $sheet->setCellValue('R' . $rowNumber, $row['mother_name'] ?: 'N/A');
    $sheet->setCellValue('S' . $rowNumber, $row['emergency_contact_name'] ?: 'N/A');
    $emergency_contact_number = $row['emergency_contact_number'] ?: 'N/A';
    if (strlen($emergency_contact_number) == 10 && substr($emergency_contact_number, 0, 1) !== '0') {
        $emergency_contact_number = '0' . $emergency_contact_number;
    }
    $sheet->setCellValue('T' . $rowNumber, $emergency_contact_number);
    $sheet->setCellValue('U' . $rowNumber, $row['has_siblings'] ?: 'N/A');
    $sheet->setCellValue('V' . $rowNumber, $row['present_address'] ?: 'N/A');

    // Center align data
    $sheet->getStyle('A' . $rowNumber . ':V' . $rowNumber)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $rowNumber++;
    $id_counter++;
}

// Set headers for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="college_student_records.xlsx"');
header('Cache-Control: max-age=0');

// Write file to output
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

$stmt->close();
$conn->close();
exit();
