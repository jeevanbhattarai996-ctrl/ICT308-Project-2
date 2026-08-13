<?php
include '../includes/db.php';
include '../includes/auth.php';

requireRole('admin');

// Get total appointments today
$today = date('Y-m-d');
$stmt = $conn->query('SELECT COUNT(*) as total FROM appointments WHERE appointment_date = "' . $today . '"');
$today_appts = $stmt->fetch_assoc();

// Get new patients
$result = $conn->query('SELECT COUNT(*) as total FROM users WHERE role = "patient" AND DATE(created_at) = CURDATE()');
$new_patients = $result->fetch_assoc();

// Get pending appointments
$stmt = $conn->query('SELECT COUNT(*) as total FROM appointments WHERE status = "pending"');
$pending = $stmt->fetch_assoc();

// Get available doctors
$stmt = $conn->query('SELECT COUNT(*) as total FROM doctors');
$doctors = $stmt->fetch_assoc();

include '../includes/header.php';
?>

<div class="main-content">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="content">
        <h2>Admin Dashboard</h2>
        
        <div class="dashboard-grid">
            <div class="card">
                <h3>Appointments Today</h3>
                <p class="stat-number"><?php echo $today_appts['total']; ?></p>
            </div>
            
            <div class="card">
                <h3>New Patients</h3>
                <p class="stat-number"><?php echo $new_patients['total']; ?></p>
            </div>
            
            <div class="card">
                <h3>Pending Appointments</h3>
                <p class="stat-number"><?php echo $pending['total']; ?></p>
            </div>
            
            <div class="card">
                <h3>Available Doctors</h3>
                <p class="stat-number"><?php echo $doctors['total']; ?></p>
            </div>
        </div>
        
        <div class="action-buttons">
            <a href="manage_appointments.php" class="btn-primary">Manage Appointments</a>
            <a href="manage_patients.php" class="btn-secondary">Manage Patients</a>
            <a href="manage_doctors.php" class="btn-secondary">Manage Doctors</a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
