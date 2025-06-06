<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

requireLogin();

if ($_POST && isset($_POST['enrollment_id']) && isset($_POST['status'])) {
    $enrollment_id = (int)$_POST['enrollment_id'];
    $status = sanitize($_POST['status']);
    $user_id = $_SESSION['user_id'];

    // Validate status
    $allowed_statuses = ['active', 'completed', 'cancelled'];
    if (!in_array($status, $allowed_statuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit();
    }

    // Verify that the enrollment belongs to the current user
    $stmt = $pdo->prepare("SELECT id FROM enrollments WHERE id = ? AND user_id = ?");
    $stmt->execute([$enrollment_id, $user_id]);

    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Enrollment not found']);
        exit();
    }

    // Update enrollment status
    $stmt = $pdo->prepare("UPDATE enrollments SET status = ? WHERE id = ? AND user_id = ?");

    if ($stmt->execute([$status, $enrollment_id, $user_id])) {
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
