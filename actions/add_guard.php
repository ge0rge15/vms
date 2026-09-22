<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/db.php';

// Only admins can add guards
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../guard.php");
    exit();
}

// Get form data
$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');
$phone    = trim($_POST['phone'] ?? '');
$shift    = trim($_POST['shift'] ?? '');

// Validate
if (empty($username) || empty($email) || empty($password) || empty($shift)) {
    $_SESSION['error'] = "Please fill in all required fields.";
    header("Location: ../guard.php");
    exit();
}

// Check if email already exists
$check = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();

if ($check->get_result()->num_rows > 0) {
    $_SESSION['error'] = "A user with that email already exists.";
    header("Location: ../guard.php");
    exit();
}

// Insert the guard
$stmt = $conn->prepare("
    INSERT INTO users (username, email, password, role, phone, shift) 
    VALUES (?, ?, ?, 'guard', ?, ?)
");

$stmt->bind_param("sssss", $username, $email, $password, $phone, $shift);

if ($stmt->execute()) {
    $_SESSION['success'] = "Guard added successfully!";
} else {
    $_SESSION['error'] = "Failed to add guard: " . $stmt->error;
}

header("Location: ../guard.php");
exit();
?>