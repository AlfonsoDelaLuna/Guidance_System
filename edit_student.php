<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student</title>
    <link rel="stylesheet" href="assets/css/edit_student.css">
    <link rel="icon" href="images/sti_logo.png" type="image/png">
</head>

<body>
    <?php
    session_start();
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
        header("Location: login.php");
        exit();
    }

    $student_id_get = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_SANITIZE_STRING) : null;
    $student_type = isset($_GET['type']) ? filter_var($_GET['type'], FILTER_SANITIZE_STRING) : 'college';

    // Database connection
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "guidance_db";
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        echo "<div class='container'><p class='message error-message'>Connection failed: " . htmlspecialchars($conn->connect_error) . "</p>";
        echo "<div class='back-link' style='margin-top: 20px;'><a href='dashboard.php'>← Back to Dashboard</a></div></div>";
        exit();
    }

    if (!$student_id_get || !in_array($student_type, ['shs', 'college'])) {
        echo "<div class='container'><p class='message error-message'>Invalid student ID or type.</p>";
        echo "<div class='back-link' style='margin-top: 20px;'><a href='dashboard.php'>← Back to Dashboard</a></div></div>";
        $conn->close();
        exit();
    }

    // Sanitize input function
    function sanitizeInput($data, $conn)
    {
        if ($data === '' || $data === null)
            return null; // Allow empty strings to become NULL
        return mysqli_real_escape_string($conn, trim($data));
    }

    $current_student_id_for_form = $student_id_get; // This will be updated if student_id_no changes

    // Handle form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $original_student_id = $student_id_get;
        $new_student_id_no = sanitizeInput($_POST['student_id_no'] ?? '', $conn);

        $can_proceed_with_update = true;
        $update_message = '';

        // Check for duplicate student_id_no ONLY if it's being changed
        if ($new_student_id_no !== $original_student_id) {
            $table_check = $student_type == 'shs' ? 'shs_students' : 'college_students';
            $check_stmt = $conn->prepare("SELECT student_id_no FROM $table_check WHERE student_id_no = ?");
            $check_stmt->bind_param("s", $new_student_id_no);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            if ($check_result->num_rows > 0) {
                $update_message = "<p class='message error-message'>Error: Student ID No '" . htmlspecialchars($new_student_id_no) . "' already exists for another student.</p>";
                $can_proceed_with_update = false;
            }
            $check_stmt->close();
        }

        if ($can_proceed_with_update) {
            // Define base fields common to both or potentially present in POST
            $fields_map = [
                'last_name' => 'last_name',
                'first_name' => 'first_name',
                'middle_name' => 'middle_name',
                'student_id_no' => 'student_id_no',
                'section' => 'section',
                'status' => 'status',
                'contact_number' => 'contact_number',
                'email_addresses' => 'email_addresses',
                'age' => 'age',
                'nationality' => 'nationality',
                'gender' => 'gender',
                'religion' => 'religion',
                'civil_status' => 'civil_status',
                'father_name' => 'father_name',
                'mother_name' => 'mother_name',
                'emergency_contact_name' => 'emergency_contact_name',
                'emergency_contact_number' => 'emergency_contact_number',
                'has_siblings' => 'has_siblings',
                'present_address' => 'present_address'
            ];

            // Sanitize emergency contact number
            $emergency_contact_number = sanitizeInput($_POST['emergency_contact_number'] ?? null, $conn);
            if (strlen($emergency_contact_number) == 10 && substr($emergency_contact_number, 0, 1) !== '0') {
                $emergency_contact_number = '0' . $emergency_contact_number;
            }
            $_POST['emergency_contact_number'] = $emergency_contact_number;

            // Add type-specific fields
            if ($student_type == 'shs') {
                $fields_map['grade_level'] = 'grade_level';
                $fields_map['shs_program'] = 'shs_program';
            } else { // college
                $fields_map['year_level'] = 'year_level';
                $fields_map['college_program'] = 'program';
            }

            $sql_set_parts = [];
            $bind_params_values = [];
            $bind_params_types = "";

            foreach ($fields_map as $post_key => $db_column) {
                if (!isset($_POST[$post_key]) && $post_key !== 'student_id_no') {
                    if ($post_key === 'shs_program' && $student_type != 'shs')
                        continue;
                }

                if ($post_key === 'student_id_no') {
                    $value = $new_student_id_no;
                } else {
                    $value = sanitizeInput($_POST[$post_key] ?? null, $conn);
                }

                $sql_set_parts[] = "$db_column = ?";
                $bind_params_values[] = $value;
                if (in_array($db_column, ['age', 'has_siblings', 'grade_level', 'year_level'])) {
                    $bind_params_types .= ($value === null) ? "s" : "i";
                    if ($value === null)
                        $bind_params_values[count($bind_params_values) - 1] = null;
                    else
                        $bind_params_values[count($bind_params_values) - 1] = (int) $value;
                } else {
                    $bind_params_types .= "s";
                    if ($value === null)
                        $bind_params_values[count($bind_params_values) - 1] = null;
                }
            }

            $bind_params_values[] = $original_student_id; // For WHERE clause
            $bind_params_types .= "s";

            $table_update = $student_type == 'shs' ? 'shs_students' : 'college_students';
            $sql_update = "UPDATE $table_update SET " . implode(", ", $sql_set_parts) . " WHERE student_id_no = ?";

            $stmt_update = $conn->prepare($sql_update);
            if (!$stmt_update) {
                $update_message = "<p class='message error-message'>Error preparing update statement: " . htmlspecialchars($conn->error) . "</p>";
            } else {
                $stmt_update->bind_param($bind_params_types, ...$bind_params_values);

                if ($stmt_update->execute()) {
                    $update_message = "<p class='message success-message'>Student updated successfully!</p>";
                    $current_student_id_for_form = $new_student_id_no;
                } else {
                    $update_message = "<p class='message error-message'>Error updating student: " . htmlspecialchars($stmt_update->error) . "</p>";
                }
                $stmt_update->close();
            }
        }
    }

    // Fetch student data for display
    $table_fetch = $student_type == 'shs' ? 'shs_students' : 'college_students';
    $stmt_fetch = $conn->prepare("SELECT * FROM $table_fetch WHERE student_id_no = ?");
    $stmt_fetch->bind_param("s", $current_student_id_for_form);
    $stmt_fetch->execute();
    $result = $stmt_fetch->get_result();
    $student = $result->fetch_assoc();
    $stmt_fetch->close();

    $dashboard_link = $student_type == 'shs' ? 'shs_dashboard.php' : 'college_dashboard.php';

    if ($student) {
    ?>
        <div class="container">
            <div class="back-link">
                <a href="<?php echo $dashboard_link; ?>">← Back to Student List</a>
            </div>
            <h2 class="page-title">Edit <?php echo strtoupper(htmlspecialchars($student_type)); ?> Student:
                <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['middle_name'] . ' ' . $student['last_name']); ?>
                (ID:
                <?php echo htmlspecialchars($current_student_id_for_form); ?>)
            </h2>

            <?php if (!empty($update_message))
                echo $update_message; ?>

            <form
                action="edit_student.php?id=<?php echo urlencode($current_student_id_for_form); ?>&type=<?php echo urlencode($student_type); ?>"
                method="post">

                <div class="form-section">
                    <h3>Basic Information</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="student_id_no">Student ID No</label>
                            <input type="text" id="student_id_no" name="student_id_no"
                                value="<?php echo htmlspecialchars($student['student_id_no']); ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name"
                                value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="middle_name">Middle Name</label>
                            <input type="text" id="middle_name" name="middle_name"
                                value="<?php echo htmlspecialchars($student['middle_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name"
                                value="<?php echo htmlspecialchars($student['last_name']); ?>" required>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Academic Information</h3>
                    <div class="form-row">
                        <?php if ($student_type == 'shs'): ?>
                            <div class="form-group">
                                <label for="shs_program">Strand</label>
                                <select id="shs_program" name="shs_program" required>
                                    <option value="">Select Strand</option>
                                    <option value="Accountancy, Business, and Management" <?php echo ($student['shs_program'] ?? '') == 'Accountancy, Businesses, and Management' ? 'selected' : ''; ?>>Accountancy,
                                        Business, and Management</option>
                                    <option value="Science, Technology, Engineering, and Mathematics" <?php echo ($student['shs_program'] ?? '') == 'Science, Technology, Engineering, and Mathematics' ? 'selected' : ''; ?>>Science, Technology, Engineering, and Mathematics</option>
                                    <option value="Humanities and Social Sciences" <?php echo ($student['shs_program'] ?? '') == 'Humanities and Social Sciences' ? 'selected' : ''; ?>>Humanities and Social
                                        Sciences</option>
                                    <option value="General Academics" <?php echo ($student['shs_program'] ?? '') == 'General Academics' ? 'selected' : ''; ?>>General Academics</option>
                                    <option value="IT with Mobile App & Web Development" <?php echo ($student['shs_program'] ?? '') == 'IT with Mobile App & Web Development' ? 'selected' : ''; ?>>IT in Mobile App and
                                        Web Development</option>
                                    <option value="Digital Arts" <?php echo ($student['shs_program'] ?? '') == 'Digital Arts' ? 'selected' : ''; ?>>Digital Arts</option>
                                    <option value="Tourism Operations" <?php echo ($student['shs_program'] ?? '') == 'Tourism Operations' ? 'selected' : ''; ?>>Tourism Operations</option>
                                    <option value="Culinary Arts" <?php echo ($student['shs_program'] ?? '') == 'Culinary Arts' ? 'selected' : ''; ?>>Culinary Arts</option>
                                    <option value="Computer and Communications Technology" <?php echo ($student['shs_program'] ?? '') == 'Computer and Communications Technology' ? 'selected' : ''; ?>>Computer and
                                        Communications Technology</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="section">Section</label>
                                <select id="section" name="section" required>
                                    <option value="">Select Section</option>
                                </select>
                            </div>
                        <?php else: // college 
                        ?>
                            <div class="form-group">
                                <label for="college_program">Program</label>
                                <select id="college_program" name="college_program" required>
                                    <option value="">Select Program</option>
                                    <option value="2-yr. Information Technology (IT)" <?php echo ($student['program'] ?? '') == '2-yr. Information Technology (IT)' ? 'selected' : ''; ?>>2-yr. Information
                                        Technology (IT) </option>
                                    <option value="2-yr. Hospitality and Restaurant Services (HRS)" <?php echo ($student['program'] ?? '') == '2-yr. Hospitality and Restaurant Services (HRS)' ? 'selected' : ''; ?>>2-yr. Hospitality and Restaurant Services (HRS)</option>
                                    <option value="2-yr. Tourism and Events Management (TEM)" <?php echo ($student['program'] ?? '') == '2-yr. Tourism and Events Management (TEM)' ? 'selected' : ''; ?>>2-yr. Tourism and
                                        Events Management (TEM)</option>
                                    <option value="BS in Information Technology (BSIT)" <?php echo ($student['program'] ?? '') == 'BS in Information Technology (BSIT)' ? 'selected' : ''; ?>>BS in Information
                                        Technology (BSIT) </option>
                                    <option value="BS in Computer Science (BSCS)" <?php echo ($student['program'] ?? '') == 'BS in Computer Science (BSCS)' ? 'selected' : ''; ?>>BS in Computer Science (BSCS)</option>
                                    <option value="BS in Tourism Management (BSTM)" <?php echo ($student['program'] ?? '') == 'BS in Tourism Management (BSTM)' ? 'selected' : ''; ?>>BS in Tourism Management (BSTM)
                                    </option>
                                    <option value="BS in Accountancy (BSA)" <?php echo ($student['program'] ?? '') == 'BS in Accountancy (BSA)' ? 'selected' : ''; ?>>BS in Accountancy (BSA)</option>
                                    <option value="BS in Business Administration (BSBA)" <?php echo ($student['program'] ?? '') == 'BS in Business Administration (BSBA)' ? 'selected' : ''; ?>>BS in Business
                                        Administration (BSBA)</option>
                                    <option value="BS in Accounting Information System (BSAIS)" <?php echo ($student['program'] ?? '') == 'BS in Accounting Information System (BSAIS)' ? 'selected' : ''; ?>>BS in
                                        Accounting
                                        Information System (BSAIS)</option>
                                    <option value="BA in Communication (BACOMM)" <?php echo ($student['program'] ?? '') == 'BA in Communication (BACOMM)' ? 'selected' : ''; ?>>Bachelor in Communication (BACOMM)
                                    </option>
                                    <option value="Bachelor of Multimedia Arts (BMMA)" <?php echo ($student['program'] ?? '') == 'Bachelor of Multimedia Arts (BMMA)' ? 'selected' : ''; ?>>Bachelor of Multimedia
                                        Arts (BMMA)</option>
                                    <option value="BS in Computer Engineering (BSCpE)" <?php echo ($student['program'] ?? '') == 'BS in Computer Engineering (BSCpE)' ? 'selected' : ''; ?>>BS in Computer
                                        Engineering (BSCpE)
                                    </option>
                                    <option value="BS in Hospitality Management (BSHM)" <?php echo ($student['program'] ?? '') == 'BS in Hospitality Management (BSHM)' ? 'selected' : ''; ?>>BS in Hospitality
                                        Management (BSHM)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="section">Section</label>
                                <select id="section" name="section" required>
                                    <option value="">Select Section</option>
                                    <?php
                                    $program = ($student_type == 'shs') ? ($student['shs_program'] ?? '') : ($student['program'] ?? '');
                                    $selected_section = htmlspecialchars($student['section'] ?? '');

                                    // Function to generate section options
                                    function generateSectionOptions($program, $selected_section)
                                    {
                                        $sections = [];
                                        if ($program == 'BS in Information Technology') {
                                            $sections = [];
                                            for ($i = 101; $i <= 105; $i++) {
                                                $sections[] = 'BT' . $i;
                                            }
                                            for ($i = 201; $i <= 205; $i++) {
                                                $sections[] = 'BT' . $i;
                                            }
                                            for ($i = 301; $i <= 305; $i++) {
                                                $sections[] = 'BT' . $i;
                                            }
                                            for ($i = 401; $i <= 405; $i++) {
                                                $sections[] = 'BT' . $i;
                                            }
                                            for ($i = 501; $i <= 505; $i++) {
                                                $sections[] = 'BT' . $i;
                                            }
                                            for ($i = 601; $i <= 605; $i++) {
                                                $sections[] = 'BT' . $i;
                                            }
                                            for ($i = 701; $i <= 705; $i++) {
                                                $sections[] = 'BT' . $i;
                                            }
                                            for ($i = 801; $i <= 805; $i++) {
                                                $sections[] = 'BT' . $i;
                                            }
                                        } elseif ($program == 'BS in Computer Science') {
                                            for ($i = 201; $i <= 909; $i++) {
                                                $sections[] = 'CS' . $i;
                                            }
                                        } elseif ($program == 'BS in Tourism Management') {
                                            for ($i = 301; $i <= 709; $i++) {
                                                $sections[] = 'TM' . $i;
                                            }
                                        } elseif ($program == 'BS in Accountancy') {
                                            for ($i = 401; $i <= 609; $i++) {
                                                $sections[] = 'AC' . $i;
                                            }
                                        } elseif ($program == 'BS in Business Administration (BSBA)') {
                                            for ($i = 501; $i <= 509; $i++) {
                                                $sections[] = 'BM' . $i;
                                            }
                                        } elseif ($program == 'BS Accounting Information System') {
                                            for ($i = 601; $i <= 409; $i++) {
                                                $sections[] = 'AI' . $i;
                                            }
                                        } elseif ($program == 'Bachelor of Arts in Communications') {
                                            for ($i = 701; $i <= 309; $i++) {
                                                $sections[] = 'BC' . $i;
                                            }
                                        } elseif ($program == 'Bachelor in Multimedia Arts') {
                                            for ($i = 801; $i <= 209; $i++) {
                                                $sections[] = 'MM' . $i;
                                            }
                                        } elseif ($program == 'BS in Computer Engineering') {
                                            for ($i = 901; $i <= 109; $i++) {
                                                $sections[] = 'CE' . $i;
                                            }
                                        }

                                        foreach ($sections as $section_value) {
                                            $selected = ($selected_section == $section_value) ? 'selected' : '';
                                            echo "<option value='" . $section_value . "' " . $selected . ">" . $section_value . "</option>";
                                        }
                                    }

                                    generateSectionOptions($program, $selected_section);
                                    ?>
                                </select>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" required>
                                <option value="">Select Status</option>
                                <option value="Active" <?php echo ($student['status'] ?? '') == 'Active' ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo ($student['status'] ?? '') == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="Graduated" <?php echo ($student['status'] ?? '') == 'Graduated' ? 'selected' : ''; ?>>Graduated</option>
                                <option value="Dropped" <?php echo ($student['status'] ?? '') == 'Dropped' ? 'selected' : ''; ?>>Dropped</option>
                                <option value="Transferred" <?php echo ($student['status'] ?? '') == 'Transferred' ? 'selected' : ''; ?>>Transferred</option>
                            </select>
                        </div>
                        <?php if ($student_type == 'shs'): ?>
                            <div class="form-group">
                                <label for="grade_level">Grade Level</label>
                                <select id="grade_level" name="grade_level" required>
                                    <option value="">Select Grade Level</option>
                                    <option value="11" <?php echo ($student['grade_level'] ?? '') == 11 ? 'selected' : ''; ?>>11
                                    </option>
                                    <option value="12" <?php echo ($student['grade_level'] ?? '') == 12 ? 'selected' : ''; ?>>12
                                    </option>
                                </select>
                            </div>
                        <?php else: // college 
                        ?>
                            <div class="form-group">
                                <label for="year_level">Year Level</label>
                                <select id="year_level" name="year_level" required>
                                    <option value="">Select Year Level</option>
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo (($student['year_level'] ?? '') == $i ? 'selected' : ''); ?>><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Personal Information</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="age">Age</label>
                            <input type="number" id="age" name="age"
                                value="<?php echo htmlspecialchars($student['age'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="gender">Gender</label>
                            <select id="gender" name="gender">
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo ($student['gender'] ?? '') == 'Male' ? 'selected' : ''; ?>>
                                    Male</option>
                                <option value="Female" <?php echo ($student['gender'] ?? '') == 'Female' ? 'selected' : ''; ?>>Female</option>
                                <option value="Non-Binary" <?php echo ($student['gender'] ?? '') == 'Non-Binary' ? 'selected' : ''; ?>>
                                    Non-Binary</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nationality">Nationality</label>
                            <input type="text" id="nationality" name="nationality"
                                value="<?php echo htmlspecialchars($student['nationality'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="religion">Religion</label>
                            <input type="text" id="religion" name="religion"
                                value="<?php echo htmlspecialchars($student['religion'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-row_status">
                        <div class="form-group">
                            <label for="civil_status">Civil Status</label>
                            <select id="civil_status" name="civil_status">
                                <option value="">Select Civil Status</option>
                                <option value="Single" <?php echo ($student['civil_status'] ?? '') == 'Single' ? 'selected' : ''; ?>>Single</option>
                                <option value="Married" <?php echo ($student['civil_status'] ?? '') == 'Married' ? 'selected' : ''; ?>>Married</option>
                                <option value="Divorced" <?php echo ($student['civil_status'] ?? '') == 'Divorced' ? 'selected' : ''; ?>>Divorced</option>
                                <option value="Widowed" <?php echo ($student['civil_status'] ?? '') == 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                                <option value="Separated" <?php echo ($student['civil_status'] ?? '') == 'Separated' ? 'selected' : ''; ?>>Separated</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Contact Information</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email_addresses">Email</label>
                            <input type="email" id="email_addresses" name="email_addresses"
                                value="<?php echo htmlspecialchars($student['email_addresses'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="contact_number">Contact Number</label>
                            <input type="text" id="contact_number" name="contact_number" value="<?php
                                                                                                $contact_number = htmlspecialchars($student['contact_number'] ?? '');
                                                                                                if (strlen($contact_number) == 10 && substr($contact_number, 0, 1) == '9') {
                                                                                                    $contact_number = '0' . $contact_number;
                                                                                                }
                                                                                                echo $contact_number;
                                                                                                ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="present_address">Present Address</label>
                            <textarea id="present_address" name="present_address"
                                rows="3"><?php echo htmlspecialchars($student['present_address'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Emergency Contact & Family Information</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="father_name">Father's Name</label>
                            <input type="text" id="father_name" name="father_name"
                                value="<?php echo htmlspecialchars($student['father_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="mother_name">Mother's Name</label>
                            <input type="text" id="mother_name" name="mother_name"
                                value="<?php echo htmlspecialchars($student['mother_name'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="emergency_contact_name">Emergency Contact Name</label>
                            <input type="text" id="emergency_contact_name" name="emergency_contact_name"
                                value="<?php echo htmlspecialchars($student['emergency_contact_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="emergency_contact_number">Emergency Contact Number</label>
                            <input type="text" id="emergency_contact_number" name="emergency_contact_number" value="<?php
                                                                                                                    $emergency_contact_number = htmlspecialchars($student['emergency_contact_number'] ?? '');
                                                                                                                    if (strlen($emergency_contact_number) == 10 && substr($emergency_contact_number, 0, 1) == '9') {
                                                                                                                        $emergency_contact_number = '0' . $emergency_contact_number;
                                                                                                                    }
                                                                                                                    echo $emergency_contact_number;
                                                                                                                    ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="has_siblings">Number of Siblings</label>
                            <input type="number" id="has_siblings" name="has_siblings" min="0"
                                value="<?php echo htmlspecialchars($student['has_siblings'] ?? '0'); ?>">
                        </div>
                        <div class="form-group"></div>
                    </div>
                </div>

                <div class="form-actions">
                    <input type="submit" value="Update Student Information">
                </div>
            </form>
        </div>
    <?php
    } else {
        echo "<div class='back-link'><a href='" . $dashboard_link . "'>← Back to Student List</a></div>";
        echo "<h2 class='page-title'>Edit Student</h2>";
        echo "<div class='container'><p class='message error-message'>Student not found.</p></div>";
    }
    $conn->close();
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const programSelect = document.getElementById('college_program') || document.getElementById('shs_program');
            const sectionSelect = document.getElementById('section');
            const studentType = "<?php echo $student_type; ?>";
            const currentSection = "<?php echo htmlspecialchars($student['section'] ?? ''); ?>";

            function populateSections(program) {
                if (!program) {
                    sectionSelect.innerHTML = '<option value="">Select Section</option>';
                    return;
                }

                let url = 'get_sections.php?';
                url += studentType === 'shs' ? 'shs_program=' + encodeURIComponent(program) : 'program=' + encodeURIComponent(program);

                fetch(url)
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.json();
                    })
                    .then(sections => {
                        sectionSelect.innerHTML = '<option value="">Select Section</option>';
                        sections.forEach(section => {
                            const option = document.createElement('option');
                            option.value = section;
                            option.textContent = section;
                            if (section === currentSection) {
                                option.selected = true;
                            }
                            sectionSelect.appendChild(option);
                        });
                    })
                    .catch(error => {
                        console.error('Error fetching sections:', error);
                        sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
                    });
            }

            programSelect.addEventListener('change', function() {
                populateSections(this.value);
            });

            if (programSelect.value) {
                populateSections(programSelect.value);
            }
        });
    </script>
</body>

</html>