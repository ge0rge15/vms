<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/db.php';

// Only admins can edit guards
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../guard.php");
    exit();
}

// Get form data
$id       = intval($_POST['id'] ?? 0);
$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');
$phone    = trim($_POST['phone'] ?? '');
$shift    = trim($_POST['shift'] ?? '');

// Validate
if ($id <= 0) {
    $_SESSION['error'] = "Invalid guard ID.";
    header("Location: ../guard.php");
    exit();
}

if (empty($username) || empty($email) || empty($shift)) {
    $_SESSION['error'] = "Please fill in all required fields.";
    header("Location: ../guard.php");
    exit();
}

// Check the email isn't already used by a DIFFERENT guard
$check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$check->bind_param("si", $email, $id);
$check->execute();

if ($check->get_result()->num_rows > 0) {
    $_SESSION['error'] = "Another user already has that email.";
    header("Location: ../guard.php");
    exit();
}

// If password is provided, hash it. Otherwise keep existing.
if (!empty($password)) {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("
        UPDATE users 
        SET username = ?, email = ?, password = ?, phone = ?, shift = ? 
        WHERE id = ? AND role = 'guard'
    ");
    $stmt->bind_param("sssssi", $username, $email, $hashed_password, $phone, $shift, $id);
} else {
    $stmt = $conn->prepare("
        UPDATE users 
        SET username = ?, email = ?, phone = ?, shift = ? 
        WHERE id = ? AND role = 'guard'
    ");
    $stmt->bind_param("ssssi", $username, $email, $phone, $shift, $id);
}

if ($stmt->execute()) {
    $_SESSION['success'] = "Guard updated successfully!";
} else {
    $_SESSION['error'] = "Failed to update guard: " . $stmt->error;
}

header("Location: ../guard.php");
exit();
?>