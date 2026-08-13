<?php
include '../includes/db.php';
include '../includes/auth.php';

requireRole('patient');

$appointment_id = intval($_GET['id'] ?? 0);

$stmt = $conn->prepare('SELECT a.*, u.full_name as doctor_name, d.specialisation FROM appointments a JOIN doctors d ON a.doctor_id = d.doctor_id JOIN users u ON d.user_id = u.user_id WHERE a.appointment_id = ? AND a.patient_id = (SELECT patient_id FROM patients WHERE user_id = ?)');
$stmt->bind_param('ii', $appointment_id, $_SESSION['user_id']);
$stmt->execute();
$appointment = $stmt->get_result()->fetch_assoc();

if (!$appointment) {
    header('Location: dashboard.php');
    exit;
}

include '../includes/header.php';
?>

<div class="main-content">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="content">
        <div class="success-container">
            <h2>Appointment Booked Successfully</h2>
            
            <div class="card highlight">
                <h3>Appointment Details</h3>
                <table class="details-table">
                    <tr>
                        <td><strong>Doctor:</strong></td>
                        <td><?php echo htmlspecialchars($appointment['doctor_name']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Specialisation:</strong></td>
                        <td><?php echo htmlspecialchars($appointment['specialisation']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Date:</strong></td>
                        <td><?php echo date('l, F j, Y', strtotime($appointment['appointment_date'])); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Time:</strong></td>
                        <td><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Reason:</strong></td>
                        <td><?php echo htmlspecialchars($appointment['reason']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td><?php echo ucfirst($appointment['status']); ?></td>
                    </tr>
                </table>
            </div>
            
            <p class="confirmation-message">Your appointment has been successfully booked. The clinic will contact you for confirmation.</p>
            
            <div class="action-buttons">
                <a href="dashboard.php" class="btn-primary">Back to Dashboard</a>
                <a href="my_appointments.php" class="btn-secondary">View All Appointments</a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
