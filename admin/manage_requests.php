<?php
session_start();
require '../config/config.php';

// Remove User
if (isset($_POST['delete_request_id'])) {
    $id = $_POST['delete_request_id'];
    $conn->query("DELETE FROM requests WHERE id = $id");
}

// Check if request status is being updated
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_id']) && isset($_POST['status'])) {
    $id = $_POST['request_id'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE requests SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    
    if ($stmt->execute()) {
        header("Location: ../dashboard/admin_dashboard.php?success=Status Updated");
        exit;
    } else {
        header("Location: ../dashboard/admin_dashboard.php?error=Failed to update status");
        exit;
    }
}
header("Location: ../dashboard/admin_dashboard.php");
exit;
?>
