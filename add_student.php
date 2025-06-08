<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student</title>
    <link rel="stylesheet" href="assets/css/add_student.css">
    <link rel="icon" href="images/sti_logo.png" type="image/png">
</head>

<body>
    <div class="login-container">
        <h2>Add New Student</h2>
        <form action="#" method="post" enctype="multipart/form-data">
            Select file to upload:
            <input type="file" name="fileToUpload" id="fileToUpload"><br><br>
            <input type="submit" value="Upload File" name="submit">
        </form>
        <?php
        require 'vendor/autoload.php';

        use PhpOffice\PhpSpreadsheet\IOFactory;

        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "guidance_db";

        // Single connection for both tables
        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
            die("Database connection failed: " . $conn->connect_error);
        }

        if (isset($_POST["submit"])) {
            $file = $_FILES['fileToUpload']['tmp_name'];
            $file_ext = pathinfo($_FILES['fileToUpload']['name'], PATHINFO_EXTENSION);

            if ($file) {
                if ($file_ext == 'xlsx') {
                    try {
                        $spreadsheet = IOFactory::load($file);
                        $worksheet = $spreadsheet->getActiveSheet();
                        $rows = $worksheet->toArray();

                        // Get header and clean null/empty values
                        $header = array_map(function ($value) {
                            return $value === null ? '' : trim((string) $value);
                        }, $rows[0]);
                        unset($rows[0]);

                        // Create header index map (case-insensitive)
                        $headerMap = [];
                        foreach ($header as $index => $name) {
                            $headerMap[strtolower($name)] = $index;
                        }

                        $processed_student_ids = [];
                        $row_count = 0;
                        $skipped_rows = 0;

                        foreach ($rows as $row_number => $row) {
                            $row_count++;
                            // Skip empty rows
                            if (empty(array_filter($row))) {
                                echo "<p>Row " . ($row_number + 2) . ": Skipped - Empty row</p>";
                                $skipped_rows++;
                                continue;
                            }

                            // Normalize header names for flexibility
                            $student_id_col = $headerMap['student number'] ?? $headerMap['student_id_no'] ?? null;
                            $year_level_col = $headerMap['year level'] ?? $headerMap['year_level'] ?? null;
                            $program_col = $headerMap['program'] ?? $headerMap['college_program'] ?? null;
                            $grade_level_col = $headerMap['grade level'] ?? $headerMap['grade_level'] ?? null;
                            $shs_program_col = $headerMap['shs program'] ?? $headerMap['shs_program'] ?? null;

                            // Determine student type
                            $isSHS = isset($grade_level_col) && isset($shs_program_col) && !empty($row[$grade_level_col]) && !empty($row[$shs_program_col]);
                            $isCollege = isset($year_level_col) && isset($program_col) && !empty($row[$year_level_col]) && !empty($row[$program_col]);

                            if (!$isSHS && !$isCollege) {
                                echo "<p>Row " . ($row_number + 2) . ": Skipped - Unable to determine if SHS or College student (Year Level: '" . ($row[$year_level_col] ?? 'N/A') . "', Program: '" . ($row[$program_col] ?? 'N/A') . "', Grade Level: '" . ($row[$grade_level_col] ?? 'N/A') . "', SHS Program: '" . ($row[$shs_program_col] ?? 'N/A') . "')</p>";
                                $skipped_rows++;
                                continue;
                            }

                            // Get student ID
                            if (!isset($student_id_col) || empty(trim($row[$student_id_col]))) {
                                echo "<p>Row " . ($row_number + 2) . ": Skipped - Missing Student ID</p>";
                                $skipped_rows++;
                                continue;
                            }
                            $raw_id = trim($row[$student_id_col]);
                            $canonical_id = str_pad($raw_id, 11, '0', STR_PAD_LEFT);

                            // Check for duplicates in file
                            if (in_array($canonical_id, $processed_student_ids)) {
                                echo "<p>Row " . ($row_number + 2) . ": Skipped - Duplicate Student ID $raw_id in file</p>";
                                $skipped_rows++;
                                continue;
                            }
                            $processed_student_ids[] = $canonical_id;

                            // Check for duplicates in database
                            $table = $isSHS ? 'shs_students' : 'college_students';

                            $check_stmt = $conn->prepare("SELECT student_id_no FROM $table WHERE student_id_no = ? OR student_id_no = ?");
                            $check_stmt->bind_param("ss", $canonical_id, $raw_id);
                            $check_stmt->execute();
                            if ($check_stmt->get_result()->num_rows > 0) {
                                echo "<p>Row " . ($row_number + 2) . ": Skipped - Student ID $raw_id exists in $table</p>";
                                $skipped_rows++;
                                $check_stmt->close();
                                continue;
                            }
                            $check_stmt->close();

                            // Prepare insert query
                            $table = $isSHS ? 'shs_students' : 'college_students';
                            $program_field = $isSHS ? 'shs_program' : 'program';
                            $level_field = $isSHS ? 'grade_level' : 'year_level';

                            $stmt = $conn->prepare("INSERT INTO $table (
                                last_name, first_name, middle_name, student_id_no, $level_field,
                                $program_field, academic_career, status,
                                father_name, mother_name, contact_number, email_addresses,
                                age, nationality, gender, religion, civil_status,
                                emergency_contact_name, emergency_contact_number,
                                has_siblings, present_address, section
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                            // Get values
                            $values = [
                                $row[$headerMap['last name'] ?? $headerMap['last_name'] ?? ''] ?? '',
                                $row[$headerMap['first name'] ?? $headerMap['first_name'] ?? ''] ?? '',
                                $row[$headerMap['middle name'] ?? $headerMap['middle_name'] ?? ''] ?? '',
                                $canonical_id,
                                $row[$isSHS ? $grade_level_col : $year_level_col] ?? '',
                                $row[$isSHS ? $shs_program_col : $program_col] ?? '',
                                $isSHS ? 'SHS' : 'College',
                                $row[$headerMap['status'] ?? ''] ?? 'Active',
                                $row[$headerMap['father\'s name'] ?? $headerMap['father_name'] ?? ''] ?? '',
                                $row[$headerMap['mother\'s name'] ?? $headerMap['mother_name'] ?? ''] ?? '',
                                $row[$headerMap['contact number'] ?? $headerMap['contact_number'] ?? ''] ?? '',
                                $row[$headerMap['email'] ?? $headerMap['email_addresses'] ?? ''] ?? '',
                                $row[$headerMap['age'] ?? ''] ?? null,
                                $row[$headerMap['nationality'] ?? ''] ?? '',
                                $row[$headerMap['gender'] ?? ''] ?? '',
                                $row[$headerMap['religion'] ?? ''] ?? '',
                                $row[$headerMap['civil status'] ?? $headerMap['civil_status'] ?? ''] ?? '',
                                $row[$headerMap['emergency contact name'] ?? $headerMap['emergency_contact_name'] ?? ''] ?? '',
                                $row[$headerMap['emergency contact number'] ?? $headerMap['emergency_contact_number'] ?? ''] ?? '',
                                $row[$headerMap['has siblings'] ?? $headerMap['has_siblings'] ?? ''] ?? null,
                                $row[$headerMap['present address'] ?? $headerMap['present_address'] ?? ''] ?? '',
                                $row[$headerMap['section'] ?? ''] ?? ''
                            ];

                            $stmt->bind_param("ssssssssssssissssssiss", ...$values);

                            if ($stmt->execute()) {
                                echo "<p>Row " . ($row_number + 2) . ": Added " . ($isSHS ? 'SHS' : 'College') . " student: " . htmlspecialchars($values[1] . " " . $values[2] . " " . $values[0]) . "</p>";
                            } else {
                                echo "<p>Row " . ($row_number + 2) . ": Error adding student: " . $stmt->error . "</p>";
                                $skipped_rows++;
                            }
                            $stmt->close();
                        }

                        echo "<p>Import completed. Processed $row_count rows, skipped $skipped_rows.</p>";
                    } catch (Exception $e) {
                        echo "<p>Error loading file: " . $e->getMessage() . "</p>";
                    }
                } else {
                    echo "<p>Invalid File Format. Please upload an Excel (.xlsx) file.</p>";
                }
            } else {
                echo "<p>No file uploaded</p>";
            }
        }

        $conn->close();
        ?>
        <p><a href="dashboard.php">Back to Dashboard</a></p>
    </div>
</body>

</html>