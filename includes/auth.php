<?php
// Authentication functions

session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /careplus_hms/login.php');
        exit;
    }
}

function requireRole($role) {
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        header('Location: /careplus_hms/index.php');
        exit;
    }
}

function logout() {
    session_destroy();
    header('Location: /careplus_hms/login.php');
    exit;
}

function getUserData($conn, $user_id) {
    $stmt = $conn->prepare('SELECT * FROM users WHERE user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getPatientData($conn, $user_id) {
    $stmt = $conn->prepare('SELECT * FROM patients WHERE user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getDoctorData($conn, $user_id) {
    $stmt = $conn->prepare('SELECT d.*, u.full_name, u.email FROM doctors d JOIN users u ON d.user_id = u.user_id WHERE d.user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}
?>
