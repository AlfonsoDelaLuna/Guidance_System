<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header("Location: login.php"); // Redirect to login page if not logged in
    exit();
}

$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="icon" href="images/sti_logo.png" type="image/png">
</head>

<body>
    <div class="dashboard-container">
        <img src="images/sti_logo.png" alt="STI Logo" class="logo">
        <h1 class="main-title">Guidance Management System</h1>
        <p class="welcome-message">Welcome!</p>
        <div class="button-group">
            <a href="shs_dashboard.php" class="dashboard-button" name="senior_high">Senior High School</a>
            <a href="college_dashboard.php" class="dashboard-button" name="college">College</a>
            <a href="add_student.php" class="dashboard-button" name="add_student">Add Student</a>
        </div>
        <a href="logout.php" class="logout-link">Logout</a>
    </div>
    <footer class="footer">
        &copy; 2025 STI College Caloocan Guidance System
    </footer>
</body>

</html>