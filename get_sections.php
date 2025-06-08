<?php
$program = isset($_GET['program']) ? $_GET['program'] : '';
$shs_program = isset($_GET['shs_program']) ? $_GET['shs_program'] : '';

$sections = [];

// Function to generate sections for a given prefix
function generateSections($prefix)
{
    $sections = [];
    for ($j = 1; $j <= 8; $j++) { // Ranges from 100s to 800s
        for ($i = 1; $i <= 5; $i++) { // 01 to 05 within each range
            $sections[] = $prefix . ($j * 100 + $i);
        }
    }
    return $sections;
}

// College programs
if ($program == 'BS in Information Technology (BSIT)') {
    $sections = generateSections('BT');
} elseif ($program == '2-yr. Information Technology (IT)') {
    $sections = generateSections('BT');
} elseif ($program == '2-yr. Hospitality and Restaurant Services (HRS)') {
    $sections = generateSections('HM');
} elseif ($program == '2-yr. Tourism and Events Management (TEM)') {
    $sections = generateSections('TM');
} elseif ($program == 'BS in Computer Science (BSCS)') {
    $sections = generateSections('CS');
} elseif ($program == 'BS in Tourism Management (BSTM)') {
    $sections = generateSections('TM');
} elseif ($program == 'BS in Accountancy (BSA)') {
    $sections = generateSections('BSA');
} elseif ($program == 'BS in Business Administration (BSBA)') {
    $sections = generateSections('BM');
} elseif ($program == 'BS in Accounting Information System (BSAIS)') {
    $sections = generateSections('AT');
} elseif ($program == 'BA in Communications (BACOMM)') {
    $sections = generateSections('BACOMM');
} elseif ($program == 'Bachelor of Multimedia Arts (BMMA)') {
    $sections = generateSections('MA');
} elseif ($program == 'BS in Computer Engineering (BSCpE)') {
    $sections = generateSections('CPE');
} elseif ($program == 'BS in Hospitality Management (BSHM)') {
    $sections = generateSections('HM');
}
// SHS programs
elseif ($shs_program == 'Accountancy, Business, and Management') {
    $sections = generateSections('ABM');
} elseif ($shs_program == 'Science, Technology, Engineering, and Mathematics') {
    $sections = generateSections('STEM');
} elseif ($shs_program == 'Humanities and Social Sciences') {
    $sections = generateSections('HUMSS');
} elseif ($shs_program == 'General Academics') {
    $sections = generateSections('GA');
} elseif ($shs_program == 'IT with Mobile App & Web Development') {
    $sections = generateSections('ICT');
} elseif ($shs_program == 'Digital Arts') {
    $sections = generateSections('DIG');
} elseif ($shs_program == 'Tourism Operations') {
    $sections = generateSections('TOU');
} elseif ($shs_program == 'Culinary Arts') {
    $sections = generateSections('CUL');
} elseif ($shs_program == 'Computer and Communications Technology') {
    $sections = generateSections('CCT');
}

// Output the sections as JSON
header('Content-Type: application/json');
echo json_encode($sections);
