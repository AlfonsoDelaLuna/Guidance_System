<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Database connection (Highly discouraged to put here)
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "guidance_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to sanitize input (Discouraged to define here)
function sanitizeInput($data)
{
    global $conn; // Access the database connection
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return mysqli_real_escape_string($conn, $data); // Prevent SQL injection
}

// Function to display files in a folder (Discouraged to define here)
function displayFolderFiles($student_id_from_url, $folder, $student_type)
{
    global $conn;

    // Check if this is a refresh request to avoid infinite loop
    if (isset($_GET['refresh']) && $_GET['refresh'] === 'true') {
        // Only display the file list, without the surrounding HTML
        global $last_name, $first_name;
        $last_name = '';
        $first_name = '';
        $student_folder = $student_id_from_url . "_" . $last_name . "_" . $first_name;
        $upload_dir = "Uploads/$folder/$student_folder/";
        if (file_exists($upload_dir)) {
            $files = glob($upload_dir . "*");
            if ($files) {
                echo "<ul>";
                foreach ($files as $file) {
                    $filename = basename($file);
                    $relative_path = "$upload_dir$filename";
                    $file_url = $relative_path;
                    echo "<li>";
                    $escapedFile = htmlspecialchars($relative_path, ENT_QUOTES, 'UTF-8');
                    $escapedStudentId = htmlspecialchars($student_id_from_url, ENT_QUOTES, 'UTF-8');
                    $escapedFolder = htmlspecialchars($folder, ENT_QUOTES, 'UTF-8');
                    $escapedStudentType = htmlspecialchars($student_type, ENT_QUOTES, 'UTF-8');
                    echo " <button onclick='deleteFile(\"{$escapedFile}\", \"{$escapedStudentId}\", \"{$escapedFolder}\", \"{$escapedStudentType}\")'>Delete</button>";
                    echo "</li>";
                }
                echo "</ul>";
            } else {
                echo "<p>No files uploaded yet.</p>";
            }
        } else {
            echo "<p>Folder does not exist.</p>";
        }
        return; // Exit to prevent full page render
    }

    $stmt = $conn->prepare("SELECT last_name, first_name FROM " . ($student_type === 'shs' ? 'shs_students' : 'college_students') . " WHERE student_id_no = ?");
    $stmt->bind_param("s", $student_id_from_url);
    if (!$stmt->execute()) {
        echo "<p>Database error: " . $stmt->error . "</p>";
        return;
    }
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $last_name = preg_replace("/[^a-zA-Z0-9_-]/", "", $row['last_name']);
        $first_name = preg_replace("/[^a-zA-Z0-9_-]/", "", $row['first_name']);
    } else {
        echo "<p>Student not found.</p>";
        return;
    }
    $stmt->close();
    $student_folder = $student_id_from_url . "_" . $last_name . "_" . $first_name;
    $upload_dir = "Uploads/$folder/$student_folder/";
    if (file_exists($upload_dir)) {
        $files = glob($upload_dir . "*");
        if ($files) {
            echo "<ul>";
            foreach ($files as $file) {
                $filename = basename($file);
                $relative_path = "$upload_dir$filename";
                $file_url = $relative_path;
                echo "<li>";
                $escapedFile = htmlspecialchars($relative_path, ENT_QUOTES, 'UTF-8');
                $escapedStudentId = htmlspecialchars($student_id_from_url, ENT_QUOTES, 'UTF-8');
                $escapedFolder = htmlspecialchars($folder, ENT_QUOTES, 'UTF-8');
                $escapedStudentType = htmlspecialchars($student_type, ENT_QUOTES, 'UTF-8');
                echo "<a href='replace_file.php?student_id={$escapedStudentId}&folder={$escapedFolder}&type={$escapedStudentType}&file={$escapedFile}' target='_blank'>$filename</a>";
                echo " <button onclick='deleteFile(\"{$escapedFile}\", \"{$escapedStudentId}\", \"{$escapedFolder}\", \"{$escapedStudentType}\")'>Delete</button>";
                echo "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>No files uploaded yet.</p>";
        }
    } else {
        echo "<p>Folder does not exist.</p>";
    }
}
// Get and sanitize student ID
if (!isset($_GET['id'])) {
    echo "<div class='student-record-container'>";
    echo "<p class='error-message'>No student ID provided</p>";
    echo "<a href='dashboard.php' class='back-to-list'>Back to Dashboard</a>";
    echo "</div>";
    exit();
}

$student_id_from_url = sanitizeInput($_GET['id']); // Keep the original ID from URL

// Validate student ID length (10-11 digits for the URL version)
if (!preg_match('/^\d{10,11}$/', $student_id_from_url)) { // Validate the URL version
    echo "<div class='student-record-container'>";
    echo "<p class='error-message'>Invalid Student ID format. ID must be 10-11 digits</p>";
    echo "<a href='dashboard.php' class='back-to-list'>Back to Dashboard</a>";
    echo "</div>";
    exit();
}

// Determine student type and table name
$student_type = $_GET['type'] ?? 'college';
$table_name = ($student_type === 'shs' ? 'shs_students' : 'college_students');

// Fetch student data
$stmt = $conn->prepare("SELECT * FROM $table_name WHERE student_id_no = ?");
$stmt->bind_param("s", $student_id_from_url); // Use the original ID from the URL for the query

if (!$stmt->execute()) {
    echo "<div class='student-record-container'>";
    echo "<p class='error-message'>Database error: " . $stmt->error . "</p>";
    echo "<a href='dashboard.php' class='back-to-list'>Back to Dashboard</a>";
    echo "</div>";
    $stmt->close();
    $conn->close();
    exit();
}

$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $student_name = htmlspecialchars($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']);
    // $student_id_no will now be the one from the URL, which should have the leading zero if applicable
    $contact_number = htmlspecialchars($row['contact_number'] ?: 'Not provided');

    // The "Modify Student Number" block is removed as the dashboard should pass the correctly formatted ID

    // Modify Contact Number
    if (strlen($contact_number) == 10 && substr($contact_number, 0, 1) !== '0') {
        $contact_number = '0' . $contact_number;
    }
?>

    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Student Record</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
            integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
            crossorigin="anonymous" referrerpolicy="no-referrer" />
        <link rel="stylesheet" href="assets/css/student_record.css">
        <link rel="icon" href="images/sti_logo.png" type="image/png">
        <style>
            /* Styles from assets/css/student_record.css (Example) */
            .student-record-container {
                width: 80%;
                margin: 20px auto;
                font-family: Arial, sans-serif;
            }

            .error-message {
                color: red;
            }

            .back-to-list {
                display: block;
                margin-top: 10px;
                color: blue;
                text-decoration: none;
            }

            /* More styles here */
        </style>
    </head>

    <body>
        <div class="student-record-container">
            <div class="student-record-header">
                <h1>Student Record for <?php echo $student_name; ?></h1>
                <a href="<?php echo ($student_type === 'shs' ? 'shs_dashboard.php' : 'college_dashboard.php'); ?>"
                    class="back-to-list">← Back to List</a>
            </div>

            <!-- Display Student Information -->
            <div class="info-section">
                <h2>Academic Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">
                            <?php echo ($student_type === 'shs' ? 'Grade Level' : 'Year Level'); ?>
                        </span>
                        <span class="info-value">
                            <?php echo htmlspecialchars($row['grade_level'] ?? $row['year_level']); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">
                            <?php echo ($student_type === 'shs' ? 'SHS Program' : 'Program'); ?>
                        </span>
                        <span class="info-value">
                            <?php
                            $program = htmlspecialchars($row['shs_program'] ?? $row['program']);
                            $section = htmlspecialchars($row['section']);

                            // SHS Program List
                            $shs_programs = [
                                'Accountancy, Businesses, and Management',
                                'Science, Technology, Engineering, and Mathematics',
                                'Humanities and Social Sciences',
                                'General Academics',
                                'IT in Mobile App and Web Development',
                                'Digital Arts',
                                'Tourism Operations',
                                'Culinary Arts',
                                'Computer and Communications Technology',
                            ];
                            $sections = [
                                'ABM101 - ABM809',
                                'STEM101 - STEM809',
                                'HUMSS101 - HUMSS809',
                                'GAS101 - GAS809',
                                'MAWD101 - MAWD809',
                                'DIGAR101 - DIGAR809',
                                'TOPER101 - TOPER809',
                                'CUA101 - CUA809',
                                'CCT101 - CCT809',
                            ];

                            // College Program List
                            $college_programs = [
                                'BS in Information Technology',
                                'BS in Computer Science',
                                'BS in Tourism Management',
                                'BS in Accountancy',
                                'BS Business Administration',
                                'BS Accounting Information System',
                                'Bachelor of Arts in Communications',
                                'Bachelor in Multimedia Arts',
                                'BS Computer Engineering',
                            ];
                            $sections = [
                                'BT101 - BT809',
                                'CS101 - CS809',
                                'TM101 - TM809',
                                'BSA101 - BSA809',
                                'BSBA101 - BSBA809',
                                'BSAIS101 - BSAIS809',
                                'BACOMM101 - BACOMM809',
                                'BMMA101 - BMMA809',
                                'BSCpE101 - BSCpE809',
                            ];
                            if ($student_type === 'shs') {
                                echo htmlspecialchars($row['shs_program']);
                            } else {
                                echo htmlspecialchars($row['program']);
                            }
                            ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Section</span>
                        <span class="info-value">
                            <?php echo $section; ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="info-section">
                <h2>Basic Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Student Number</span>
                        <span class="info-value">
                            <?php echo htmlspecialchars($student_id_from_url); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Status</span>
                        <span class="info-value">
                            <?php echo htmlspecialchars($row['status']); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Phone/Contact Number</span>
                        <span class="info-value">
                            <?php echo htmlspecialchars($contact_number ?: 'Not provided'); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email</span>
                        <span class="info-value">
                            <?php echo htmlspecialchars($row['email_addresses']); ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="info-section">
                <h2>Additional Details</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Age</span>
                        <span class="info-value">
                            <?php echo htmlspecialchars($row['age'] ?: 'N/A'); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Nationality</span>
                        <span class="info-value">
                            <?php echo htmlspecialchars($row['nationality'] ?: 'N/A'); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Gender</span>
                        <span class="info-value">
                            <?php echo htmlspecialchars($row['gender'] ?: 'N/A'); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Religion</span>
                        <span class="info-value">
                            <?php echo htmlspecialchars($row['religion'] ?: 'N/A'); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Civil Status</span>
                        <span class="info-value">
                            <?php echo htmlspecialchars($row['civil_status'] ?: 'N/A'); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Father's Name</span>
                        <span class="info-value">
                            <?php echo htmlspecialchars($row['father_name'] ?: 'N/A'); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Mother's Name</span>
                        <span class="info-value">
                            <?php echo htmlspecialchars($row['mother_name'] ?: 'N/A'); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Emergency Contact Name</span>
                        <span class="info-value">
                            <?php echo htmlspecialchars($row['emergency_contact_name'] ?: 'N/A'); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Emergency Contact Number</span>
                        <span class="info-value">
                            <?php
                            $emergency_contact_number = htmlspecialchars($row['emergency_contact_number'] ?: 'N/A');
                            if (strlen($emergency_contact_number) == 10 && substr($emergency_contact_number, 0, 1) !== '0') {
                                $emergency_contact_number = '0' . $emergency_contact_number;
                            }
                            echo $emergency_contact_number;
                            ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Has Siblings</span>
                        <span class="info-value">
                            <?php echo htmlspecialchars($row['has_siblings'] ?: 'N/A'); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Present Address</span>
                        <span class="info-value">
                            <?php echo htmlspecialchars($row['present_address'] ?: 'N/A'); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Folder Section -->
            <div class="folders-section">
                <div class="folder-grid">
                    <div class="folder-item" onclick="showUploadSection('consultation_records')">
                        <div class="folder-container">
                            <i class="fa-regular fa-folder"></i>
                            <span>Consultation/Conference Form</span>
                        </div>
                        <?php displayFolderFiles($student_id_from_url, 'consultation_records', $student_type); ?>
                    </div>
                    <div class="folder-item" onclick="showUploadSection('endorsement_records')">
                        <div class="folder-container">
                            <i class="fa-regular fa-folder"></i>
                            <span>Endorsement - Custody Form</span>
                        </div>
                        <?php displayFolderFiles($student_id_from_url, 'endorsement_records', $student_type); ?>
                    </div>
                    <div class="folder-item" onclick="showUploadSection('guidance_records')">
                        <div class="folder-container">
                            <i class="fa-regular fa-folder"></i>
                            <span>Guidance/Counselling Form</span>
                        </div>
                        <?php displayFolderFiles($student_id_from_url, 'guidance_records', $student_type); ?>
                    </div>
                </div>
            </div>

            <!-- Upload Section -->
            <div id="upload-section" class="upload-section" style="display: none;">
                <form id="upload-form" class="upload-form" action="upload_files.php" method="post"
                    enctype="multipart/form-data">
                    <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student_id_from_url); ?>">
                    <input type="hidden" name="folder" id="upload-folder">
                    <input type="hidden" name="type" value="<?php echo htmlspecialchars($student_type); ?>">
                    <label for="files" class="file-label">Choose Files:</label>
                    <input type="file" id="files" name="files[]" multiple class="file-browser" accept=".pdf,.jpg,.jpeg,.png"
                        placeholder="Select files to upload">
                    <button type="submit" class="upload-button">Upload Files</button>
                </form>
                <div class="progress-bar">
                    <div class="progress-fill"></div>
                </div>
            </div>
        </div>

        <!-- Modal for file preview -->
        <div id="filePreviewModal" class="modal" style="display: none;">
            <div class="modal-content">
                <span class="close">&times;</span>
                <div id="filePreviewContent"></div>
                <button onclick="replaceFile(document.getElementById('filePreviewContent').dataset.filepath)">Replace
                    File</button>
            </div>
        </div>

        <script>
            // JavaScript from assets/js/student_record.js (Example)
            function refreshFolder(studentId, folder, studentType) {
                var xhr = new XMLHttpRequest();
                xhr.open("GET", "student_record.php?id=" + studentId + "&type=" + studentType + "&folder=" + folder + "&refresh=true", true);
                xhr.onload = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        // Find the folder item to replace
                        var folderItem = document.querySelector(".folder-item[onclick*='" + folder + "']");
                        if (folderItem) {
                            // Create a temporary element to hold the response HTML
                            var tempElement = document.createElement('div');
                            tempElement.innerHTML = xhr.responseText;

                            // Find the updated file list within the response
                            var updatedFileList = tempElement.querySelector(".folder-item[onclick*='" + folder + "'] > ul");

                            if (updatedFileList) {
                                // Replace the existing file list with the updated one
                                var existingFileList = folderItem.querySelector("ul");
                                if (existingFileList) {
                                    folderItem.replaceChild(updatedFileList, existingFileList);
                                } else {
                                    folderItem.appendChild(updatedFileList); // If there was no list before
                                }
                            }
                        }
                    }
                };
                xhr.send();
            }

            function showUploadSection(folder) {
                const uploadSection = document.getElementById('upload-section');
                const uploadFolder = document.getElementById('upload-folder');

                // Remove active class from all folder items
                document.querySelectorAll('.folder-item').forEach(item => {
                    item.classList.remove('active');
                });

                // Add active class to the clicked folder
                const folderItem = document.querySelector(`.folder-item[onclick="showUploadSection('${folder}')"]`);
                if (folderItem) {
                    folderItem.classList.add('active');
                }

                uploadSection.style.display = 'block';
                uploadFolder.value = folder;
                document.querySelector('.progress-fill').style.width = '0%';
            }

            document
                .getElementById('upload-form')
                .addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    const progressBar = document.querySelector('.progress-fill');

                    fetch('upload_files.php', {
                            method: 'POST',
                            body: formData,
                        })
                        .then((response) => response.json())
                        .then((data) => {
                            if (data.success) {
                                progressBar.style.width = '100%';
                                setTimeout(() => {
                                    location.reload();
                                }, 1000);
                            } else {
                                alert(data.error || 'Upload failed');
                                progressBar.style.width = '0%';
                            }
                        })
                        .catch((error) => {
                            alert('Upload failed: ' + error.message);
                            progressBar.style.width = '0%';
                        });

                    // Simulate upload progress
                    let progress = 0;
                    const interval = setInterval(() => {
                        progress += 5;
                        if (progress > 90) clearInterval(interval);
                        progressBar.style.width = progress + '%';
                    }, 100);
                });

            function previewFile(filepath) {
                const modal = document.getElementById('filePreviewModal');
                const modalContent = document.getElementById('filePreviewContent');
                modalContent.dataset.filepath = filepath; // Store filepath for replace function

                modalContent.innerHTML = '<p>Loading preview...</p>'; // Default loading message

                fetch(filepath)
                    .then((response) => {
                        if (!response.ok) {
                            throw new Error('File could not be loaded.');
                        }
                        return response.blob();
                    })
                    .then((blob) => {
                        const fileType = blob.type;

                        if (fileType.startsWith('image/')) {
                            const reader = new FileReader();
                            reader.onloadend = function() {
                                modalContent.innerHTML = `<img src="${reader.result}" alt="File Preview" style="max-width:100%; max-height:400px;">`;
                            };
                            reader.readAsDataURL(blob);
                        } else if (fileType === 'application/pdf') {
                            modalContent.innerHTML = `<embed src="${filepath}" type="application/pdf" width="100%" height="500px" />`;
                        } else {
                            // For other file types, just open in a new tab
                            window.open(filepath, '_blank');
                            return;
                        }

                        modal.style.display = 'block';
                    })
                    .catch((error) => {
                        modalContent.innerHTML = `<p>Error loading file: ${error.message}</p>`;
                        modal.style.display = 'block';
                    });
            }

            function replaceFile(filepath) {
                const uploadSection = document.getElementById('upload-section');
                const uploadForm = document.getElementById('upload-form');
                const uploadFolder = document.getElementById('upload-folder');
                const uploadButton = uploadForm.querySelector('.upload-button');

                // Extract folder name from filepath
                const folder = filepath.split('/')[1]; // e.g., medical_records

                uploadSection.style.display = 'block';
                uploadFolder.value = folder;
                uploadButton.textContent = 'Replace File';

                // Add a hidden input to store the filepath of the file to be replaced
                const replaceFileInput = document.createElement('input');
                replaceFileInput.setAttribute('type', 'hidden');
                replaceFileInput.setAttribute('name', 'replace_file');
                replaceFileInput.setAttribute('value', filepath);
                uploadForm.appendChild(replaceFileInput);
            }

            // Close the modal
            document.querySelector('.close').addEventListener('click', function() {
                document.getElementById('filePreviewModal').style.display = 'none';
            });

            window.onclick = function(event) {
                if (event.target == document.getElementById('filePreviewModal')) {
                    document.getElementById('filePreviewModal').style.display = 'none';
                }
            };

            // NEW:
            function deleteFile(filepath, studentId, folderName, studentType) { // Added studentId, folderName, studentType
                if (confirm('Are you sure you want to delete this file?')) {
                    const formData = new URLSearchParams(); // Easier to build query string
                    formData.append('action', 'delete_file');
                    formData.append('file_to_delete', filepath);
                    formData.append('student_id', studentId); // Pass student_id
                    formData.append('folder', folderName); // Pass the folder category (e.g., 'consultation_records')
                    formData.append('type', studentType); // Pass student_type

                    fetch('upload_files.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: formData.toString(), // Send the constructed form data
                        })
                        .then((response) => response.json())
                        .then((data) => {
                            if (data.success) {
                                alert('File deleted successfully.');
                                location.reload(); // Reload the page to update the file list
                            } else {
                                alert('Error deleting file: ' + (data.error || 'Unknown error'));
                            }
                        })
                        .catch((error) => {
                            console.error('Error:', error);
                            alert('Error deleting file: ' + error.message);
                        });
                }
            }
        </script>
    </body>

    </html>

<?php
} else {
    echo "<div class='student-record-container'>";
    echo "<p class='error-message'>Student not found</p>";
    echo "<a href='dashboard.php' class='back-to-list'>Back to Dashboard</a>";
    echo "</div>";
}

$stmt->close();
$conn->close();
?>