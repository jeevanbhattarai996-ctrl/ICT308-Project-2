<?php
include '../includes/db.php';
include '../includes/auth.php';

requireRole('patient');

$user_id = $_SESSION['user_id'];
$error = '';
$success = false;

// Get patient ID
$stmt = $conn->prepare('SELECT patient_id FROM patients WHERE user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$patient_id = $patient['patient_id'];

// Get all doctors (optionally pre-filtered by a recommended specialisation, e.g. from the Symptom Checker)
$specialisation_filter = trim($_GET['specialisation'] ?? '');
if ($specialisation_filter !== '') {
    $doctors_stmt = $conn->prepare('SELECT d.doctor_id, u.full_name, d.specialisation FROM doctors d JOIN users u ON d.user_id = u.user_id WHERE d.specialisation LIKE CONCAT("%", ?, "%") ORDER BY u.full_name');
    $doctors_stmt->bind_param('s', $specialisation_filter);
    $doctors_stmt->execute();
    $doctors_result = $doctors_stmt->get_result();
    if ($doctors_result->num_rows === 0) {
        // fall back to full list if nothing matches the recommended specialisation
        $doctors_result = $conn->query('SELECT d.doctor_id, u.full_name, d.specialisation FROM doctors d JOIN users u ON d.user_id = u.user_id ORDER BY u.full_name');
    }
} else {
    $doctors_result = $conn->query('SELECT d.doctor_id, u.full_name, d.specialisation FROM doctors d JOIN users u ON d.user_id = u.user_id ORDER BY u.full_name');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id = intval($_POST['doctor_id'] ?? 0);
    $appointment_date = $_POST['appointment_date'] ?? '';
    $appointment_time = $_POST['appointment_time'] ?? '';
    $reason = trim($_POST['reason'] ?? '');
    $appointment_type = ($_POST['appointment_type'] ?? 'in_person') === 'video' ? 'video' : 'in_person';
    
    if (empty($doctor_id) || empty($appointment_date) || empty($appointment_time) || empty($reason)) {
        $error = 'Please fill in all fields';
    } else {
        // Check if slot is available
        $check = $conn->prepare('SELECT appointment_id FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status != "cancelled"');
        $check->bind_param('iss', $doctor_id, $appointment_date, $appointment_time);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $error = 'This time slot is already booked. Please choose another time.';
        } else {
            // Book appointment
            $status = 'pending';
            $video_room = $appointment_type === 'video' ? bin2hex(random_bytes(12)) : null;
            $stmt = $conn->prepare('INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, appointment_type, video_room, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('iissssss', $patient_id, $doctor_id, $appointment_date, $appointment_time, $reason, $appointment_type, $video_room, $status);
            
            if ($stmt->execute()) {
                $appointment_id = $conn->insert_id;
                
                // Create notification
                $message = 'Your appointment has been booked. Please wait for confirmation.';
                $notification_type = 'appointment';
                $notif_stmt = $conn->prepare('INSERT INTO notifications (user_id, appointment_id, message, notification_type) VALUES (?, ?, ?, ?)');
                $notif_stmt->bind_param('iiss', $user_id, $appointment_id, $message, $notification_type);
                $notif_stmt->execute();
                
                // Redirect to success page
                header("Location: appointment_success.php?id=$appointment_id");
                exit;
            } else {
                $error = 'Failed to book appointment. Please try again.';
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="main-content">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="content">
        <h2>Book an Appointment</h2>
        <?php if ($specialisation_filter !== ''): ?>
            <p class="welcome-text">Showing doctors recommended for: <strong><?php echo htmlspecialchars($specialisation_filter); ?></strong> (based on your Symptom Checker result). <a href="book_appointment.php">Clear filter</a></p>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" class="form-container">
            <div class="form-group">
                <label for="doctor_id">Select Doctor:</label>
                <select id="doctor_id" name="doctor_id" required>
                    <option value="">Choose a doctor</option>
                    <?php while ($doctor = $doctors_result->fetch_assoc()): ?>
                        <option value="<?php echo $doctor['doctor_id']; ?>">
                            <?php echo htmlspecialchars($doctor['full_name']); ?> - <?php echo htmlspecialchars($doctor['specialisation']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="appointment_date">Select Date:</label>
                <input type="date" id="appointment_date" name="appointment_date" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="appointment_time">Select Time:</label>
                <select id="appointment_time" name="appointment_time" required>
                    <option value="">Choose a time</option>
                    <option value="09:00:00">9:00 AM</option>
                    <option value="10:00:00">10:00 AM</option>
                    <option value="11:00:00">11:00 AM</option>
                    <option value="14:00:00">2:00 PM</option>
                    <option value="15:00:00">3:00 PM</option>
                    <option value="16:00:00">4:00 PM</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="reason">Reason for Visit:</label>
                <textarea id="reason" name="reason" rows="4" placeholder="Describe your reason for the appointment" required></textarea>
            </div>

            <div class="form-group">
                <label>Consultation Type:</label>
                <div class="symptom-grid" style="grid-template-columns: repeat(2, 1fr); margin: 0;">
                    <label class="symptom-option">
                        <input type="radio" name="appointment_type" value="in_person" checked> In-person visit
                    </label>
                    <label class="symptom-option">
                        <input type="radio" name="appointment_type" value="video"> 📹 Video consultation
                    </label>
                </div>
            </div>
            
            <button type="submit" class="btn-primary">Book Appointment</button>
            <a href="dashboard.php" class="btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
