<?php
session_start();

// --- Database Connection ---
$db_host = 'localhost';
$db_user = 'root'; // Default XAMPP username
$db_pass = '';     // Default XAMPP password
$db_name = 'guidance_db'; // The database you created

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    // For a real app, log this error, don't just die
    // die("Connection failed: " . mysqli_connect_error());
    header("Location: login.php?error=dberror");
    exit();
}
// --- End Database Connection ---


if (isset($_POST['login_submit'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password']; // Don't escape password before password_verify

    if (empty($username) || empty($password)) {
        header("Location: login.php?error=empty");
        exit();
    }

    $sql = "SELECT id, username, password_hash FROM users WHERE username = ?";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($user = mysqli_fetch_assoc($result)) {
            // Verify password
            if (password_verify($password, $user['password_hash'])) {
                // Password is correct, start session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header("Location: dashboard.php");
                exit();
            } else {
                // Invalid password
                header("Location: login.php?error=invalid");
                exit();
            }
        } else {
            // No user found
            header("Location: login.php?error=invalid");
            exit();
        }
    } else {
        // SQL error
        // Log this error for debugging
        header("Location: login.php?error=dberror"); // Generic error for user
        exit();
    }
} else {
    // Not a POST request or form not submitted correctly
    header("Location: login.php");
    exit();
}

