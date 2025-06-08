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

// Get student ID and type from the request
$student_id_no = $_GET['student_id_no']; // Changed from 'id'
$type = $_GET['type'];

// Determine the table name based on the type
if ($type == 'shs') {
    $table = 'shs_students';
} elseif ($type == 'college') {
    $table = 'college_students';
} else {
    echo "Invalid student type.";
    exit();
}

// Prepare and execute the delete query
$sql = "DELETE FROM $table WHERE student_id_no = ?"; // Changed from 'id' to 'student_id_no'
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_id_no); // Bind $student_id_no

if ($stmt->execute()) {
    echo "Student deleted successfully.";
} else {
    echo "Error deleting student: " . $conn->error;
}

// Close the database connection
$stmt->close();
$conn->close();
?>