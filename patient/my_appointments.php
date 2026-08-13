<?php
include '../includes/db.php';
include '../includes/auth.php';

requireRole('patient');

$user_id = $_SESSION['user_id'];

// Get patient ID
$stmt = $conn->prepare('SELECT patient_id FROM patients WHERE user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();

// Get all appointments
$stmt = $conn->prepare('SELECT a.*, u.full_name as doctor_name, d.specialisation FROM appointments a JOIN doctors d ON a.doctor_id = d.doctor_id JOIN users u ON d.user_id = u.user_id WHERE a.patient_id = ? ORDER BY a.appointment_date DESC');
$stmt->bind_param('i', $patient['patient_id']);
$stmt->execute();
$appointments = $stmt->get_result();

include '../includes/header.php';
?>

<div class="main-content">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="content">
        <h2>My Appointments</h2>
        
        <?php if ($appointments->num_rows > 0): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Doctor</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Reason</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($appt = $appointments->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($appt['doctor_name']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($appt['appointment_date'])); ?></td>
                            <td><?php echo date('g:i A', strtotime($appt['appointment_time'])); ?></td>
                            <td><?php echo htmlspecialchars($appt['reason']); ?></td>
                            <td><span class="status status-<?php echo strtolower($appt['status']); ?>"><?php echo ucfirst($appt['status']); ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>You have no appointments yet.</p>
        <?php endif; ?>
        
        <div class="action-buttons">
            <a href="book_appointment.php" class="btn-primary">Book New Appointment</a>
            <a href="dashboard.php" class="btn-secondary">Back to Dashboard</a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
