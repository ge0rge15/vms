<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $stmt = $conn->prepare("DELETE FROM visitors WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

header("Location: ../visitor-admin.php");
exit();
?>