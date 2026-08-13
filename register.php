<?php
include 'includes/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    
    if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        // Check if email exists
        $check = $conn->prepare('SELECT user_id FROM users WHERE email = ?');
        $check->bind_param('s', $email);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $error = 'Email already registered';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Register as patient
            $stmt = $conn->prepare('INSERT INTO users (full_name, email, password_hash, role, phone) VALUES (?, ?, ?, ?, ?)');
            $role = 'patient';
            $stmt->bind_param('sssss', $full_name, $email, $password_hash, $role, $phone);
            
            if ($stmt->execute()) {
                $user_id = $conn->insert_id;
                
                // Create patient record
                $date_of_birth = '2000-01-01';
                $gender = 'other';
                $stmt2 = $conn->prepare('INSERT INTO patients (user_id, date_of_birth, gender) VALUES (?, ?, ?)');
                $stmt2->bind_param('iss', $user_id, $date_of_birth, $gender);
                $stmt2->execute();
                
                $success = 'Registration successful! You can now login.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - CarePlus Healthcare Management System</title>
    <link rel="stylesheet" href="/careplus_hms/assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-box">
            <h1>CarePlus Healthcare Management System</h1>
            <p class="auth-subtitle">Create a patient account</p>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
                <a href="/careplus_hms/login.php" class="btn-primary" style="display: inline-block; margin-top: 10px;">Go to Login</a>
            <?php else: ?>
                <form method="POST">
                    <div class="form-group">
                        <label for="full_name">Full Name:</label>
                        <input type="text" id="full_name" name="full_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone (optional):</label>
                        <input type="tel" id="phone" name="phone">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password:</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="btn-primary">Register</button>
                </form>
                
                <p class="auth-link">
                    Already have an account? <a href="/careplus_hms/login.php">Login here</a>
                </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
