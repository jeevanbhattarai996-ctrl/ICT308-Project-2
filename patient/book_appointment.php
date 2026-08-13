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

// Get all doctors
$doctors_result = $conn->query('SELECT d.doctor_id, u.full_name, d.specialisation FROM doctors d JOIN users u ON d.user_id = u.user_id ORDER BY u.full_name');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id = intval($_POST['doctor_id'] ?? 0);
    $appointment_date = $_POST['appointment_date'] ?? '';
    $appointment_time = $_POST['appointment_time'] ?? '';
    $reason = trim($_POST['reason'] ?? '');
    
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
            $stmt = $conn->prepare('INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('iissss', $patient_id, $doctor_id, $appointment_date, $appointment_time, $reason, $status);
            
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
            
            <button type="submit" class="btn-primary">Book Appointment</button>
            <a href="dashboard.php" class="btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
