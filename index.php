<?php
include 'includes/auth.php';

if (isLoggedIn()) {
    $role = $_SESSION['role'];
    if ($role === 'patient') {
        header('Location: patient/dashboard.php');
    } elseif ($role === 'doctor') {
        header('Location: doctor/dashboard.php');
    } elseif ($role === 'admin') {
        header('Location: admin/dashboard.php');
    } elseif ($role === 'manager') {
        header('Location: manager/dashboard.php');
    }
    exit;
}

header('Location: login.php');
exit;
?>
