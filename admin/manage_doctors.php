<?php
include '../includes/db.php';
include '../includes/auth.php';

requireRole('admin');

$error = '';
$success = false;

// Handle add new doctor
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $specialisation = trim($_POST['specialisation'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($full_name) || empty($email) || empty($password) || empty($specialisation)) {
        $error = 'Please fill in all required fields';
    } else {
        // Check if email exists
        $check = $conn->prepare('SELECT user_id FROM users WHERE email = ?');
        $check->bind_param('s', $email);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $error = 'Email already registered';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $role = 'doctor';
            
            // Create user
            $stmt = $conn->prepare('INSERT INTO users (full_name, email, password_hash, role, phone) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('sssss', $full_name, $email, $password_hash, $role, $phone);
            
            if ($stmt->execute()) {
                $user_id = $conn->insert_id;
                
                // Create doctor record
                $availability = 'Monday to Friday 9AM to 5PM';
                $stmt2 = $conn->prepare('INSERT INTO doctors (user_id, specialisation, availability, department) VALUES (?, ?, ?, ?)');
                $stmt2->bind_param('isss', $user_id, $specialisation, $availability, $department);
                
                if ($stmt2->execute()) {
                    $success = true;
                }
            } else {
                $error = 'Failed to add doctor';
            }
        }
    }
}

// Get all doctors
$stmt = $conn->prepare('SELECT u.user_id, u.full_name, u.email, u.phone, d.specialisation, d.department FROM doctors d JOIN users u ON d.user_id = u.user_id ORDER BY u.full_name');
$stmt->execute();
$doctors = $stmt->get_result();

include '../includes/header.php';
?>

<div class="main-content">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="content">
        <h2>Manage Doctors</h2>
        
        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message">Doctor added successfully</div>
        <?php endif; ?>
        
        <div class="form-container">
            <h3>Add New Doctor</h3>
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
                    <label for="phone">Phone:</label>
                    <input type="tel" id="phone" name="phone">
                </div>
                
                <div class="form-group">
                    <label for="specialisation">Specialisation:</label>
                    <input type="text" id="specialisation" name="specialisation" required>
                </div>
                
                <div class="form-group">
                    <label for="department">Department:</label>
                    <input type="text" id="department" name="department">
                </div>
                
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn-primary">Add Doctor</button>
            </form>
        </div>
        
        <h3>Current Doctors</h3>
        <?php if ($doctors->num_rows > 0): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Specialisation</th>
                        <th>Department</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($doctor = $doctors->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($doctor['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($doctor['email']); ?></td>
                            <td><?php echo htmlspecialchars($doctor['phone'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($doctor['specialisation']); ?></td>
                            <td><?php echo htmlspecialchars($doctor['department'] ?? 'N/A'); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No doctors found.</p>
        <?php endif; ?>
        
        <a href="dashboard.php" class="btn-secondary">Back to Dashboard</a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
