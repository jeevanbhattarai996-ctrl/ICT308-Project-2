<?php
// Streams a lab report file only if the requester is the owning patient,
// a doctor who has treated that patient, or an admin.
include 'includes/db.php';
include 'includes/auth.php';

requireLogin();

$id = intval($_GET['id'] ?? 0);
$stmt = $conn->prepare('SELECT lr.*, p.user_id as patient_user_id FROM lab_reports lr JOIN patients p ON lr.patient_id = p.patient_id WHERE lr.report_id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$record = $stmt->get_result()->fetch_assoc();

if (!$record) {
    http_response_code(404);
    die('Record not found.');
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$allowed = false;

if ($role === 'admin') {
    $allowed = true;
} elseif ($role === 'patient' && $user_id === (int)$record['patient_user_id']) {
    $allowed = true;
} elseif ($role === 'doctor') {
    $check = $conn->prepare('SELECT 1 FROM appointments a JOIN doctors d ON a.doctor_id = d.doctor_id WHERE d.user_id = ? AND a.patient_id = ? LIMIT 1');
    $check->bind_param('ii', $user_id, $record['patient_id']);
    $check->execute();
    $allowed = $check->get_result()->num_rows > 0;
}

if (!$allowed) {
    http_response_code(403);
    die('You do not have permission to view this record.');
}

$filePath = __DIR__ . '/uploads/health_records/' . basename($record['file_path']);
if (!file_exists($filePath)) {
    http_response_code(404);
    die('File missing on server.');
}

$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
$mime = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'][$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . basename($record['test_name']) . '.' . $ext . '"');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
