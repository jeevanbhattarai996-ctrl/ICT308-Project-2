<?php
include '../includes/db.php';
include '../includes/auth.php';

requireRole('admin');

$error = '';

// Handle appointment status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $appointment_id = intval($_POST['appointment_id'] ?? 0);
    $action = $_POST['action'];
    
    if ($action === 'update' && isset($_POST['status'])) {
        $status = $_POST['status'];
        $update = $conn->prepare('UPDATE appointments SET status = ? WHERE appointment_id = ?');
        $update->bind_param('si', $status, $appointment_id);
        $update->execute();
    } elseif ($action === 'cancel') {
        $status = 'cancelled';
        $update = $conn->prepare('UPDATE appointments SET status = ? WHERE appointment_id = ?');
        $update->bind_param('si', $status, $appointment_id);
        $update->execute();
    }
    
    header('Location: manage_appointments.php');
    exit;
}

// Get all appointments
$stmt = $conn->prepare('SELECT a.*, u_patient.full_name as patient_name, u_doctor.full_name as doctor_name FROM appointments a JOIN patients p ON a.patient_id = p.patient_id JOIN users u_patient ON p.user_id = u_patient.user_id JOIN doctors d ON a.doctor_id = d.doctor_id JOIN users u_doctor ON d.user_id = u_doctor.user_id ORDER BY a.appointment_date DESC');
$stmt->execute();
$appointments = $stmt->get_result();

include '../includes/header.php';
?>

<div class="main-content">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="content">
        <h2>Manage Appointments</h2>
        
        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($appointments->num_rows > 0): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Doctor</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($appt = $appointments->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($appt['patient_name']); ?></td>
                            <td><?php echo htmlspecialchars($appt['doctor_name']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($appt['appointment_date'])); ?></td>
                            <td><?php echo date('g:i A', strtotime($appt['appointment_time'])); ?></td>
                            <td><span class="status status-<?php echo strtolower($appt['status']); ?>"><?php echo ucfirst($appt['status']); ?></span></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="appointment_id" value="<?php echo $appt['appointment_id']; ?>">
                                    <select name="status" onchange="this.form.submit()" class="status-select">
                                        <option value="">Update Status</option>
                                        <option value="pending" <?php echo $appt['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="confirmed" <?php echo $appt['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                        <option value="completed" <?php echo $appt['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="cancelled" <?php echo $appt['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                    <input type="hidden" name="action" value="update">
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No appointments found.</p>
        <?php endif; ?>
        
        <a href="dashboard.php" class="btn-secondary">Back to Dashboard</a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
