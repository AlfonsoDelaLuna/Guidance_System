<?php
error_reporting(0); // Disable error reporting to prevent HTML output
session_start();
header('Content-Type: application/json');

// Helper function for path validation
function is_safe_path($filePath, $allowedBaseDir)
{
    $allowedBaseDir = rtrim($allowedBaseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $realFilePath = realpath($filePath);
    $realAllowedBaseDir = realpath($allowedBaseDir);

    error_log("is_safe_path: filePath=$filePath, realFilePath=" . ($realFilePath === false ? 'false' : $realFilePath) . ", allowedBaseDir=$allowedBaseDir, realAllowedBaseDir=" . ($realAllowedBaseDir === false ? 'false' : $realAllowedBaseDir));

    if ($realFilePath === false || $realAllowedBaseDir === false) {
        error_log("Path validation failed: realpath issue. File: $filePath, Base: $allowedBaseDir");
        return false;
    }

    if (strpos($realFilePath, $realAllowedBaseDir) !== 0) {
        error_log("Path validation failed: Path traversal attempt. File: $realFilePath, Base: $realAllowedBaseDir");
        return false;
    }

    $normalizedPath = str_replace('\\', '/', $filePath);
    if (strpos($normalizedPath, '../') !== false) {
        error_log("Path validation failed: Path traversal attempt in: $filePath");
        return false;
    }

    return true;
}

// Authentication check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "guidance_db";
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => "Database connection failed: " . $conn->connect_error]);
    exit();
}

// Action: Delete File
if (isset($_POST['action']) && $_POST['action'] === 'delete_file') {
    $file_to_delete = $_POST['file_to_delete'] ?? '';
    $student_id = $_POST['student_id'] ?? '';
    $folder = $_POST['folder'] ?? '';
    $student_type = $_POST['type'] ?? '';

    if (empty($file_to_delete) || empty($student_id) || empty($folder) || empty($student_type)) {
        echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
        exit();
    }

    if (!preg_match('/^(02000|10000)\d{6}$/', $student_id)) {
        echo json_encode(['success' => false, 'error' => 'Invalid student ID format']);
        exit();
    }

    $allowed_folders = ['consultation_records', 'endorsement_records', 'guidance_records'];
    if (!in_array($folder, $allowed_folders)) {
        echo json_encode(['success' => false, 'error' => 'Invalid folder type']);
        exit();
    }

    $table_name = ($student_type === 'shs' ? 'shs_students' : 'college_students');
    $stmt = $conn->prepare("SELECT last_name, first_name FROM $table_name WHERE student_id_no = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Failed to prepare DB statement: ' . $conn->error]);
        $conn->close();
        exit();
    }
    $stmt->bind_param("s", $student_id);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'error' => 'Failed to execute DB statement: ' . $stmt->error]);
        $stmt->close();
        $conn->close();
        exit();
    }
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $last_name = preg_replace("/[^a-zA-Z0-9_-]/", "", $row['last_name']);
        $first_name = preg_replace("/[^a-zA-Z0-9_-]/", "", $row['first_name']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Student not found']);
        $stmt->close();
        $conn->close();
        exit();
    }
    $stmt->close();

    $student_folder = "{$student_id}_{$last_name}_{$first_name}";
    $base_upload_dir = "Uploads/$folder/$student_folder/";

    $filename = basename($file_to_delete);
    $full_path = $base_upload_dir . $filename;

    if (!is_safe_path($full_path, "Uploads/$folder/$student_folder")) {
        error_log("Invalid file path for deletion: $full_path");
        echo json_encode(['success' => false, 'error' => 'Invalid file path format for deletion']);
        $conn->close();
        exit();
    }

    if (file_exists($full_path)) {
        if (unlink($full_path)) {
            error_log("File deleted successfully: $full_path");
            echo json_encode(['success' => true, 'message' => 'File deleted successfully']);
        } else {
            error_log("Failed to delete file: $full_path");
            echo json_encode(['success' => false, 'error' => 'Failed to delete file. Check permissions']);
        }
    } else {
        error_log("File not found: $full_path");
        echo json_encode(['success' => false, 'error' => 'File not found']);
    }

    $conn->close();
    exit();
}

// Action: Upload/Replace File(s)
$student_id = $_POST['student_id'] ?? null;
$folder = $_POST['folder'] ?? null;
$student_type = $_POST['type'] ?? 'college';

if (!$student_id || !$folder) {
    echo json_encode(['success' => false, 'error' => 'Student ID and folder required']);
    exit();
}
if (!preg_match('/^(02000|10000)\d{6}$/', $student_id)) {
    echo json_encode(['success' => false, 'error' => 'Invalid student ID format']);
    exit();
}
$allowed_folders = ['consultation_records', 'endorsement_records', 'guidance_records'];
if (!in_array($folder, $allowed_folders)) {
    echo json_encode(['success' => false, 'error' => 'Invalid folder type']);
    exit();
}

$table_name = ($student_type === 'shs' ? 'shs_students' : 'college_students');
$stmt = $conn->prepare("SELECT last_name, first_name FROM $table_name WHERE student_id_no = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Failed to prepare DB statement: ' . $conn->error]);
    $conn->close();
    exit();
}
$stmt->bind_param("s", $student_id);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'error' => 'Failed to execute DB statement: ' . $stmt->error]);
    $stmt->close();
    $conn->close();
    exit();
}
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $last_name = preg_replace("/[^a-zA-Z0-9_-]/", "", $row['last_name']);
    $first_name = preg_replace("/[^a-zA-Z0-9_-]/", "", $row['first_name']);
} else {
    echo json_encode(['success' => false, 'error' => 'Student not found']);
    $stmt->close();
    $conn->close();
    exit();
}
$stmt->close();

$student_folder = "{$student_id}_{$last_name}_{$first_name}";
$base_upload_dir = "Uploads/$folder/$student_folder/";

if (!file_exists($base_upload_dir)) {
    if (!mkdir($base_upload_dir, 0775, true)) {
        echo json_encode(['success' => false, 'error' => 'Failed to create directory: ' . $base_upload_dir]);
        $conn->close();
        exit();
    }
}

if (empty($_FILES['files']) || empty($_FILES['files']['tmp_name'][0])) {
    echo json_encode(['success' => false, 'error' => 'No files uploaded']);
    $conn->close();
    exit();
}

$allowed_mime_types = ['image/jpeg', 'image/png', 'application/pdf'];
$allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
$max_file_size = 50 * 1024 * 1024; // 50MB
$uploaded_file_details = [];
$errors = [];

$is_replace = isset($_POST['replace_file']) && !empty($_POST['replace_file']);
$file_to_replace = $is_replace ? $_POST['replace_file'] : null;

if ($is_replace) {
    $filename = basename($file_to_replace);
    $file_to_replace = $base_upload_dir . $filename;

    if (!is_safe_path($file_to_replace, $base_upload_dir)) {
        echo json_encode(['success' => false, 'error' => 'Invalid file path for replacement']);
        $conn->close();
        exit();
    }
    if (!file_exists($file_to_replace)) {
        echo json_encode(['success' => false, 'error' => 'File to replace does not exist']);
        $conn->close();
        exit();
    }
    if (count($_FILES['files']['tmp_name']) > 1) {
        echo json_encode(['success' => false, 'error' => 'Only one file allowed for replacement']);
        $conn->close();
        exit();
    }
}

foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
    if (empty($tmp_name) || $_FILES['files']['error'][$key] !== UPLOAD_ERR_OK) {
        $errors[] = "Upload error for file '" . htmlspecialchars($_FILES['files']['name'][$key]) . "': Error code " . $_FILES['files']['error'][$key];
        continue;
    }

    $original_name = $_FILES['files']['name'][$key];
    $file_size = $_FILES['files']['size'][$key];
    $file_ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $file_mime_type = finfo_file($finfo, $tmp_name);
    finfo_close($finfo);

    if (!in_array($file_mime_type, $allowed_mime_types) || !in_array($file_ext, $allowed_extensions)) {
        $errors[] = "File '$original_name': Invalid file type (Type: $file_mime_type, Ext: $file_ext)";
        continue;
    }
    if ($file_size > $max_file_size) {
        $errors[] = "File '$original_name': File size (" . round($file_size / 1024 / 1024, 2) . "MB) exceeds limit";
        continue;
    }

    $destination_path = '';
    $final_filename = '';

    if ($is_replace && $key === 0) {
        $original_filename = basename($file_to_replace);
        $base_name = pathinfo($original_filename, PATHINFO_FILENAME);
        $final_filename = $base_name . '.' . $file_ext;
        $destination_path = $base_upload_dir . $final_filename;

        if (file_exists($file_to_replace)) {
            if (!is_writable($file_to_replace)) {
                $errors[] = "File '$original_filename': No write permissions for replacement";
                error_log("No write permissions for $file_to_replace");
                continue;
            }
            if (!unlink($file_to_replace)) {
                $errors[] = "File '$original_filename': Failed to delete old file for replacement";
                error_log("Failed to delete $file_to_replace for replacement");
                continue;
            }
        }
    } else {
        $sanitized_name = preg_replace("/[^a-zA-Z0-9_.-]/", "", pathinfo($original_name, PATHINFO_FILENAME));
        $sanitized_name = substr($sanitized_name, 0, 50);
        $final_filename = $student_id . '_' . $sanitized_name . '_' . time() . '_' . $key . '.' . $file_ext;
        $destination_path = $base_upload_dir . $final_filename;
    }

    if (move_uploaded_file($tmp_name, $destination_path)) {
        $uploaded_file_details[] = ['original_name' => $original_name, 'new_name' => $final_filename];
        if ($is_replace && $key === 0) {
            break;
        }
        error_log("File uploaded: $destination_path");
    } else {
        $errors[] = "File '$original_name': Failed to move uploaded file";
        error_log("Failed to move file to $destination_path");
    }
}

$conn->close();

if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'error' => implode("\n", $errors),
        'uploaded_files' => $uploaded_file_details
    ]);
} else if (empty($uploaded_file_details)) {
    echo json_encode([
        'success' => false,
        'error' => 'No files processed successfully'
    ]);
} else {
    echo json_encode([
        'success' => true,
        'message' => $is_replace ? 'File replaced successfully' : (count($uploaded_file_details) . ' file(s) uploaded successfully'),
        'files' => $uploaded_file_details
    ]);
}
?>