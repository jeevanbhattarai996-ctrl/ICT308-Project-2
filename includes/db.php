<?php
// Database connection

$host = 'localhost';
$db = 'careplus_hms';
$user = 'root';
$password = '';

try {
    $conn = new mysqli($host, $user, $password, $db);
    
    if ($conn->connect_error) {
        die('Database connection failed: ' . $conn->connect_error);
    }
    
    $conn->set_charset('utf8');
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
?>
