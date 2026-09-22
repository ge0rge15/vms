<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $stmt = $conn->prepare("UPDATE visitors SET out_time = NOW(), status = 'Out' WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

if ($_SESSION['role'] === 'admin') {
    header("Location: ../visitor-admin.php");
} else {
    header("Location: ../visitor.php");
}
exit();
?>