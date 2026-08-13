<?php
include '../includes/db.php';
include '../includes/auth.php';

requireRole('doctor');

$user_id = $_SESSION['user_id'];

// Get doctor ID
$stmt = $conn->prepare('SELECT doctor_id FROM doctors WHERE user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc();
$doctor_id = $doctor['doctor_id'];

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = intval($_POST['appointment_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    
    if (in_array($status, ['confirmed', 'completed', 'cancelled'])) {
        $update = $conn->prepare('UPDATE appointments SET status = ? WHERE appointment_id = ? AND doctor_id = ?');
        $update->bind_param('sii', $status, $appointment_id, $doctor_id);
        $update->execute();
    }
    
    header('Location: appointments.php');
    exit;
}

// Get appointments for this doctor
$stmt = $conn->prepare('SELECT a.*, u.full_name as patient_name FROM appointments a JOIN patients p ON a.patient_id = p.patient_id JOIN users u ON p.user_id = u.user_id WHERE a.doctor_id = ? ORDER BY a.appointment_date DESC, a.appointment_time DESC');
$stmt->bind_param('i', $doctor_id);
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
                        <th>Patient Name</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($appt = $appointments->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($appt['patient_name']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($appt['appointment_date'])); ?></td>
                            <td><?php echo date('g:i A', strtotime($appt['appointment_time'])); ?></td>
                            <td><?php echo htmlspecialchars($appt['reason']); ?></td>
                            <td><span class="status status-<?php echo strtolower($appt['status']); ?>"><?php echo ucfirst($appt['status']); ?></span></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="appointment_id" value="<?php echo $appt['appointment_id']; ?>">
                                    <?php if ($appt['status'] === 'pending'): ?>
                                        <button type="submit" name="status" value="confirmed" class="btn-small">Confirm</button>
                                    <?php elseif ($appt['status'] === 'confirmed'): ?>
                                        <button type="submit" name="status" value="completed" class="btn-small">Complete</button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No appointments.</p>
        <?php endif; ?>
        
        <a href="dashboard.php" class="btn-secondary">Back to Dashboard</a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
