<?php
session_start();

// Authentication check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Validate URL parameters
$student_id = isset($_GET['student_id']) ? filter_var($_GET['student_id'], FILTER_SANITIZE_STRING) : null;
$folder = isset($_GET['folder']) ? filter_var($_GET['folder'], FILTER_SANITIZE_STRING) : null;
$student_type = isset($_GET['type']) ? filter_var($_GET['type'], FILTER_SANITIZE_STRING) : 'college';
$file_to_replace = isset($_GET['file']) ? filter_var($_GET['file'], FILTER_SANITIZE_STRING) : null;

$allowed_folders = ['consultation_records', 'endorsement_records', 'guidance_records'];
if (!$student_id || !preg_match('/^(02000|10000)\d{6}$/', $student_id) || !$folder || !in_array($folder, $allowed_folders) || !$file_to_replace) {
    echo "<div class='student-record-container'>";
    echo "<p class='error-message'>Invalid or missing parameters</p>";
    echo "<a href='dashboard.php' class='back-to-list'>Back to Dashboard</a>";
    echo "</div>";
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "guidance_db";
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo "<div class='student-record-container'>";
    echo "<p class='error-message'>Database connection failed: " . $conn->connect_error . "</p>";
    echo "<a href='dashboard.php' class='back-to-list'>Back to Dashboard</a>";
    echo "</div>";
    exit();
}

// Validate student and file path
$table_name = ($student_type === 'shs' ? 'shs_students' : 'college_students');
$stmt = $conn->prepare("SELECT last_name, first_name FROM $table_name WHERE student_id_no = ?");
$stmt->bind_param("s", $student_id);
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
    $last_name = preg_replace("/[^a-zA-Z0-9_-]/", "", $row['last_name']);
    $first_name = preg_replace("/[^a-zA-Z0-9_-]/", "", $row['first_name']);
} else {
    echo "<div class='student-record-container'>";
    echo "<p class='error-message'>Student not found</p>";
    echo "<a href='dashboard.php' class='back-to-list'>Back to Dashboard</a>";
    echo "</div>";
    $stmt->close();
    $conn->close();
    exit();
}
$stmt->close();

$student_folder = "{$student_id}_{$last_name}_{$first_name}";
$base_upload_dir = "Uploads/$folder/$student_folder/";
$filename = basename($file_to_replace);
$full_path = $base_upload_dir . $filename;

// Validate file path
function is_safe_path($filePath, $allowedBaseDir)
{
    $allowedBaseDir = rtrim($allowedBaseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $realFilePath = realpath($filePath);
    $realAllowedBaseDir = realpath($allowedBaseDir);
    if ($realFilePath === false || $realAllowedBaseDir === false || strpos($realFilePath, $realAllowedBaseDir) !== 0) {
        return false;
    }
    $normalizedPath = str_replace('\\', '/', $filePath);
    if (strpos($normalizedPath, '../') !== false) {
        return false;
    }
    return true;
}

if (!is_safe_path($full_path, $base_upload_dir) || !file_exists($full_path)) {
    echo "<div class='student-record-container'>";
    echo "<p class='error-message'>Invalid or non-existent file path</p>";
    echo "<a href='dashboard.php' class='back-to-list'>Back to Dashboard</a>";
    echo "</div>";
    $conn->close();
    exit();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Replace File</title>
    <link rel="stylesheet" href="assets/css/student_record.css">
    <link rel="icon" href="images/sti_logo.png" type="image/png">
    <style>
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

        .upload-form {
            margin-top: 20px;
        }

        .file-label {
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .file-browser {
            margin-bottom: 10px;
        }

        .upload-button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }

        .upload-button:hover {
            background-color: #0056b3;
        }

        .progress-bar {
            width: 100%;
            height: 10px;
            background-color: #f0f0f0;
            margin-top: 10px;
        }

        .progress-fill {
            width: 0%;
            height: 100%;
            background-color: #28a745;
            transition: width 0.2s;
        }

        .preview-section {
            margin-bottom: 20px;
            border: 1px solid #ccc;
            padding: 10px;
            max-height: 500px;
            overflow: auto;
        }

        .preview-section img {
            max-width: 100%;
            max-height: 400px;
        }

        .preview-section embed {
            width: 100%;
            height: 500px;
        }
    </style>
</head>

<body>
    <div class="student-record-container">
        <h1>Replace File: <?php echo htmlspecialchars($filename); ?></h1>
        <a href="student_record.php?id=<?php echo htmlspecialchars($student_id); ?>&type=<?php echo htmlspecialchars($student_type); ?>"
            class="back-to-list">← Back to Student Record</a>
        <div class="preview-section" id="filePreviewContent">
            <p>Loading preview...</p>
        </div>
        <form id="upload-form" class="upload-form" action="upload_files.php" method="post"
            enctype="multipart/form-data">
            <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student_id); ?>">
            <input type="hidden" name="folder" value="<?php echo htmlspecialchars($folder); ?>">
            <input type="hidden" name="type" value="<?php echo htmlspecialchars($student_type); ?>">
            <input type="hidden" name="replace_file" value="<?php echo htmlspecialchars($full_path); ?>">
            <label for="file" class="file-label">Choose New File:</label>
            <input type="file" id="file" name="files[]" class="file-browser" accept=".pdf,.jpg,.jpeg,.png" required>
            <button type="submit" class="upload-button">Replace File</button>
        </form>
        <div class="progress-bar">
            <div class="progress-fill"></div>
        </div>
    </div>
    <script>
        // Load file preview
        function loadFilePreview(filepath) {
            const previewContent = document.getElementById('filePreviewContent');
            previewContent.innerHTML = '<p>Loading preview...</p>';

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
                        reader.onloadend = function () {
                            previewContent.innerHTML = `<img src="${reader.result}" alt="File Preview">`;
                        };
                        reader.readAsDataURL(blob);
                    } else if (fileType === 'application/pdf') {
                        previewContent.innerHTML = `<embed src="${filepath}" type="application/pdf" />`;
                    } else {
                        previewContent.innerHTML = `<p>Preview not available. <a href="${filepath}" target="_blank">Download file</a></p>`;
                    }
                })
                .catch((error) => {
                    previewContent.innerHTML = `<p>Error loading preview: ${error.message}</p>`;
                });
        }

        // Load preview on page load
        loadFilePreview('<?php echo htmlspecialchars($full_path, ENT_QUOTES, 'UTF-8'); ?>');

        // Form submission with confirmation
        document.getElementById('upload-form').addEventListener('submit', function (e) {
            e.preventDefault();
            if (!confirm('Are you sure you want to replace this file? This action cannot be undone.')) {
                return;
            }
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
                        alert('File replaced successfully.');
                        // Refresh the opener window (student_record.php) with cache-busting
                        if (window.opener && !window.opener.closed) {
                            var studentId = "<?php echo htmlspecialchars($student_id); ?>";
                            var folder = "<?php echo htmlspecialchars($folder); ?>";
                            var studentType = "<?php echo htmlspecialchars($student_type); ?>";
                            window.opener.refreshFolder(studentId, folder, studentType);
                        }
                        setTimeout(() => {
                            window.close(); // Attempt to close the tab
                        }, 1000);
                    } else {
                        alert(data.error || 'Replacement failed');
                        progressBar.style.width = '0%';
                    }
                })
                .catch((error) => {
                    alert('Replacement failed: ' + error.message);
                    progressBar.style.width = '0%';
                });

            let progress = 0;
            const interval = setInterval(() => {
                progress += 5;
                if (progress > 90) clearInterval(interval);
                progressBar.style.width = progress + '%';
            }, 100);
        });
    </script>
</body>

</html>