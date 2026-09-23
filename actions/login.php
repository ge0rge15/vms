<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/db.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit();
}

$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

// Validate inputs
if (empty($email) || empty($password)) {
    $_SESSION['error'] = "Please fill in all fields.";
    header("Location: ../index.php");
    exit();
}

// Look up the user by email
$stmt = $conn->prepare("SELECT id, username, email, password, role FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $_SESSION['error'] = "Invalid email or password.";
    header("Location: ../index.php");
    exit();
}

$user = $result->fetch_assoc();

// Verify password
if (!password_verify($password, $user['password'])) {
    $_SESSION['error'] = "Invalid email or password.";
    header("Location: ../index.php");
    exit();
}

// Password is correct — log the user in
$_SESSION['user_id']  = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['email']    = $user['email'];
$_SESSION['role']     = $user['role'];

// Redirect based on role
if ($user['role'] === 'admin') {
    header("Location: ../dashboard-admin.php");
} else {
    header("Location: ../dashboard-guard.php");
}
exit();
?>