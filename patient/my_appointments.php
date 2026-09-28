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

// Handle a rating submission for a completed appointment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_rating'])) {
    $rate_appt_id = intval($_POST['appointment_id'] ?? 0);
    $rating = max(1, min(5, intval($_POST['rating'] ?? 0)));
    $comment = trim($_POST['comment'] ?? '');
    // Only allow rating your own completed appointments
    $own = $conn->prepare('SELECT appointment_id FROM appointments WHERE appointment_id = ? AND patient_id = ? AND status = "completed"');
    $own->bind_param('ii', $rate_appt_id, $patient['patient_id']);
    $own->execute();
    if ($own->get_result()->num_rows > 0) {
        $rstmt = $conn->prepare('INSERT INTO appointment_ratings (appointment_id, rating, comment) VALUES (?,?,?) ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment)');
        $rstmt->bind_param('iis', $rate_appt_id, $rating, $comment);
        $rstmt->execute();
    }
    header('Location: my_appointments.php');
    exit;
}

// Get all appointments (+ video room + any rating already given)
$stmt = $conn->prepare('SELECT a.*, u.full_name as doctor_name, d.specialisation, r.rating, r.comment as rating_comment
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.doctor_id
    JOIN users u ON d.user_id = u.user_id
    LEFT JOIN appointment_ratings r ON r.appointment_id = a.appointment_id
    WHERE a.patient_id = ? ORDER BY a.appointment_date DESC');
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
                        <th>Type</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($appt = $appointments->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($appt['doctor_name']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($appt['appointment_date'])); ?></td>
                            <td><?php echo date('g:i A', strtotime($appt['appointment_time'])); ?></td>
                            <td><?php echo $appt['appointment_type'] === 'video' ? '📹 Video' : '🏥 In-person'; ?></td>
                            <td><?php echo htmlspecialchars($appt['reason']); ?></td>
                            <td><span class="status status-<?php echo strtolower($appt['status']); ?>"><?php echo ucfirst($appt['status']); ?></span></td>
                            <td>
                                <?php if ($appt['appointment_type'] === 'video' && $appt['status'] === 'confirmed'): ?>
                                    <a href="/careplus_hms/video_room.php?appointment_id=<?php echo $appt['appointment_id']; ?>" class="btn-small btn-primary">Join Call</a>
                                <?php endif; ?>
                                <?php if ($appt['status'] === 'completed' && $appt['rating'] === null): ?>
                                    <button type="button" class="btn-small btn-secondary" onclick="document.getElementById('rate-<?php echo $appt['appointment_id']; ?>').style.display='block'">Rate visit</button>
                                    <div id="rate-<?php echo $appt['appointment_id']; ?>" style="display:none; margin-top:8px;">
                                        <form method="POST">
                                            <input type="hidden" name="appointment_id" value="<?php echo $appt['appointment_id']; ?>">
                                            <select name="rating" class="status-select">
                                                <option value="5">★★★★★ Excellent</option>
                                                <option value="4">★★★★ Good</option>
                                                <option value="3">★★★ Okay</option>
                                                <option value="2">★★ Poor</option>
                                                <option value="1">★ Very poor</option>
                                            </select>
                                            <input type="text" name="comment" placeholder="Optional comment" style="width:140px; padding:4px; font-size:12px;">
                                            <button type="submit" name="submit_rating" class="btn-small btn-primary">Send</button>
                                        </form>
                                    </div>
                                <?php elseif ($appt['rating'] !== null): ?>
                                    <span title="<?php echo htmlspecialchars($appt['rating_comment'] ?? ''); ?>"><?php echo str_repeat('★', (int)$appt['rating']); ?></span>
                                <?php endif; ?>
                            </td>
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
