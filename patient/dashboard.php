<?php
include '../includes/db.php';
include '../includes/auth.php';

requireRole('patient');

$user_id = $_SESSION['user_id'];

// Get patient info
$stmt = $conn->prepare('SELECT u.*, p.* FROM users u JOIN patients p ON u.user_id = p.user_id WHERE u.user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();

// Get upcoming appointments
$stmt = $conn->prepare('SELECT a.*, u.full_name as doctor_name FROM appointments a JOIN doctors d ON a.doctor_id = d.doctor_id JOIN users u ON d.user_id = u.user_id WHERE a.patient_id = ? AND a.appointment_date >= CURDATE() ORDER BY a.appointment_date ASC LIMIT 1');
$stmt->bind_param('i', $patient['patient_id']);
$stmt->execute();
$next_appointment = $stmt->get_result()->fetch_assoc();

// Get total appointments
$stmt = $conn->prepare('SELECT COUNT(*) as total FROM appointments WHERE patient_id = ?');
$stmt->bind_param('i', $patient['patient_id']);
$stmt->execute();
$appt_count = $stmt->get_result()->fetch_assoc();

// Get unread notifications
$stmt = $conn->prepare('SELECT * FROM notifications WHERE user_id = ? AND status = "unread" ORDER BY sent_at DESC LIMIT 3');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$notifications = $stmt->get_result();

include '../includes/header.php';
?>

<div class="main-content">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="content">
        <h2>Patient Dashboard</h2>
        <p class="welcome-text">Welcome, <?php echo htmlspecialchars($patient['full_name']); ?></p>
        
        <div class="dashboard-grid">
            <div class="card">
                <h3>Total Appointments</h3>
                <p class="stat-number"><?php echo $appt_count['total']; ?></p>
            </div>
            
            <?php if ($next_appointment): ?>
                <div class="card highlight">
                    <h3>Next Appointment</h3>
                    <p><strong><?php echo htmlspecialchars($next_appointment['doctor_name']); ?></strong></p>
                    <p><?php echo date('D, M j', strtotime($next_appointment['appointment_date'])); ?> at <?php echo date('g:i A', strtotime($next_appointment['appointment_time'])); ?></p>
                    <p><?php echo htmlspecialchars($next_appointment['reason']); ?></p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h3>Recent Notifications</h3>
            <?php if ($notifications->num_rows > 0): ?>
                <ul class="notification-list">
                    <?php while ($notif = $notifications->fetch_assoc()): ?>
                        <li class="notification-item">
                            <p><?php echo htmlspecialchars($notif['message']); ?></p>
                            <small><?php echo date('M j, Y g:i A', strtotime($notif['sent_at'])); ?></small>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>No notifications at the moment.</p>
            <?php endif; ?>
        </div>
        
        <div class="action-buttons">
            <a href="book_appointment.php" class="btn-primary">Book New Appointment</a>
            <a href="my_appointments.php" class="btn-secondary">View All Appointments</a>
        </div>

        <div class="section">
            <h3>Quick Access</h3>
            <div class="dashboard-grid">
                <a href="symptom_checker.php" class="card" style="text-decoration:none; color:inherit;"><h3>🩺 Symptom Checker</h3><p class="welcome-text">Get a quick condition estimate</p></a>
                <a href="health_dashboard.php" class="card" style="text-decoration:none; color:inherit;"><h3>❤️ Health Dashboard</h3><p class="welcome-text">Track your vitals over time</p></a>
                <a href="risk_score.php" class="card" style="text-decoration:none; color:inherit;"><h3>🧠 Risk Score</h3><p class="welcome-text">Diabetes / heart / hypertension</p></a>
                <a href="medications.php" class="card" style="text-decoration:none; color:inherit;"><h3>💊 Medicine Reminder</h3><p class="welcome-text">Never miss a dose</p></a>
                <a href="health_records.php" class="card" style="text-decoration:none; color:inherit;"><h3>📁 Health Records</h3><p class="welcome-text">Labs, prescriptions, X-rays</p></a>
                <a href="qr_card.php" class="card" style="text-decoration:none; color:inherit;"><h3>📱 Medical Card</h3><p class="welcome-text">QR code for emergencies</p></a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
