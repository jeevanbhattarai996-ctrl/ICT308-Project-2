<?php
include '../includes/db.php';
include '../includes/auth.php';

requireRole('doctor');

$user_id = $_SESSION['user_id'];

// Get doctor info
$stmt = $conn->prepare('SELECT d.*, u.full_name FROM doctors d JOIN users u ON d.user_id = u.user_id WHERE d.user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc();

// Get today's appointments
$today = date('Y-m-d');
$stmt = $conn->prepare('SELECT a.*, u.full_name as patient_name, p.patient_id FROM appointments a JOIN patients p ON a.patient_id = p.patient_id JOIN users u ON p.user_id = u.user_id WHERE a.doctor_id = ? AND a.appointment_date = ? ORDER BY a.appointment_time');
$stmt->bind_param('is', $doctor['doctor_id'], $today);
$stmt->execute();
$today_appointments = $stmt->get_result();

// Get total patients
$stmt = $conn->prepare('SELECT COUNT(DISTINCT a.patient_id) as total FROM appointments a WHERE a.doctor_id = ?');
$stmt->bind_param('i', $doctor['doctor_id']);
$stmt->execute();
$patient_count = $stmt->get_result()->fetch_assoc();

// Get pending appointments
$stmt = $conn->prepare('SELECT COUNT(*) as total FROM appointments WHERE doctor_id = ? AND status = "pending"');
$stmt->bind_param('i', $doctor['doctor_id']);
$stmt->execute();
$pending = $stmt->get_result()->fetch_assoc();

include '../includes/header.php';
?>

<div class="main-content">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="content">
        <h2>Doctor Dashboard</h2>
        <p class="welcome-text">Welcome, Dr. <?php echo htmlspecialchars($doctor['full_name']); ?></p>
        
        <div class="dashboard-grid">
            <div class="card">
                <h3>Patients</h3>
                <p class="stat-number"><?php echo $patient_count['total']; ?></p>
            </div>
            
            <div class="card">
                <h3>Pending Appointments</h3>
                <p class="stat-number"><?php echo $pending['total']; ?></p>
            </div>
            
            <div class="card">
                <h3>Specialisation</h3>
                <p><?php echo htmlspecialchars($doctor['specialisation']); ?></p>
            </div>
        </div>
        
        <div class="section">
            <h3>Today's Appointments</h3>
            <?php if ($today_appointments->num_rows > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Patient Name</th>
                            <th>Time</th>
                            <th>Reason</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($appt = $today_appointments->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($appt['patient_name']); ?></td>
                                <td><?php echo date('g:i A', strtotime($appt['appointment_time'])); ?></td>
                                <td><?php echo htmlspecialchars($appt['reason']); ?></td>
                                <td><span class="status status-<?php echo strtolower($appt['status']); ?>"><?php echo ucfirst($appt['status']); ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No appointments scheduled for today.</p>
            <?php endif; ?>
        </div>
        
        <div class="action-buttons">
            <a href="appointments.php" class="btn-primary">View All Appointments</a>
            <a href="patient_records.php" class="btn-secondary">Patient Records</a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
