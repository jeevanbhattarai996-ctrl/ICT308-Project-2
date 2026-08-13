<?php
include 'includes/db.php';
include 'includes/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    
    if (empty($email) || empty($password) || empty($role)) {
        $error = 'Please fill in all fields';
    } else {
        $stmt = $conn->prepare('SELECT user_id, full_name, password_hash, role FROM users WHERE email = ? AND role = ?');
        $stmt->bind_param('ss', $email, $role);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                
                header('Location: index.php');
                exit;
            } else {
                $error = 'Invalid password';
            }
        } else {
            $error = 'Invalid email or role';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CarePlus Healthcare Management System</title>
    <link rel="stylesheet" href="/careplus_hms/assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-box">
            <h1>CarePlus Healthcare Management System</h1>
            <p class="auth-subtitle">Login to your account</p>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="role">Login as:</label>
                    <select id="role" name="role" required>
                        <option value="">Select Role</option>
                        <option value="patient">Patient</option>
                        <option value="doctor">Doctor</option>
                        <option value="admin">Admin Staff</option>
                        <option value="manager">Clinic Manager</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-primary">Login</button>
            </form>
            
            <p class="auth-link">
                Don't have an account? <a href="/careplus_hms/register.php">Register here</a>
            </p>
            
            <div class="sample-credentials">
                <h3>Sample Login Credentials:</h3>
                <p><strong>Patient:</strong> patient1@example.com / patient123</p>
                <p><strong>Doctor:</strong> dr.smith@careplus.com / doctor123</p>
                <p><strong>Admin:</strong> admin@careplus.com / admin123</p>
                <p><strong>Manager:</strong> manager@careplus.com / manager123</p>
            </div>
        </div>
    </div>
</body>
</html>
