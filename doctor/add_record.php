<?php
include '../includes/db.php';
include '../includes/auth.php';

requireRole('doctor');

$user_id = $_SESSION['user_id'];
$patient_id = intval($_GET['patient_id'] ?? 0);

if ($patient_id === 0) {
    header('Location: patient_records.php');
    exit;
}

// Get doctor ID
$stmt = $conn->prepare('SELECT doctor_id FROM doctors WHERE user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc();
$doctor_id = $doctor['doctor_id'];

// Get patient info
$stmt = $conn->prepare('SELECT u.full_name, p.* FROM patients p JOIN users u ON p.user_id = u.user_id WHERE p.patient_id = ?');
$stmt->bind_param('i', $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();

if (!$patient) {
    header('Location: patient_records.php');
    exit;
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = intval($_POST['appointment_id'] ?? 0);
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $treatment = trim($_POST['treatment'] ?? '');
    $consultation_notes = trim($_POST['consultation_notes'] ?? '');
    
    if (empty($diagnosis) || empty($treatment)) {
        $error = 'Please fill in diagnosis and treatment';
    } else {
        $stmt = $conn->prepare('INSERT INTO medical_records (patient_id, doctor_id, appointment_id, diagnosis, treatment, consultation_notes) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('iiisss', $patient_id, $doctor_id, $appointment_id, $diagnosis, $treatment, $consultation_notes);
        
        if ($stmt->execute()) {
            $success = true;
        } else {
            $error = 'Failed to save record';
        }
    }
}

// Get patient's appointments
$stmt = $conn->prepare('SELECT appointment_id FROM appointments WHERE patient_id = ? AND doctor_id = ? AND status IN ("confirmed", "completed") ORDER BY appointment_date DESC');
$stmt->bind_param('ii', $patient_id, $doctor_id);
$stmt->execute();
$appointments = $stmt->get_result();

include '../includes/header.php';
?>

<div class="main-content">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="content">
        <h2>Add Medical Record</h2>
        <p><strong>Patient:</strong> <?php echo htmlspecialchars($patient['full_name']); ?></p>
        
        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message">Medical record saved successfully</div>
        <?php endif; ?>
        
        <form method="POST" class="form-container">
            <div class="form-group">
                <label for="appointment_id">Appointment (optional):</label>
                <select id="appointment_id" name="appointment_id">
                    <option value="">Select appointment</option>
                    <?php while ($appt = $appointments->fetch_assoc()): ?>
                        <option value="<?php echo $appt['appointment_id']; ?>">Appointment <?php echo $appt['appointment_id']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="diagnosis">Diagnosis:</label>
                <textarea id="diagnosis" name="diagnosis" rows="3" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="treatment">Treatment:</label>
                <textarea id="treatment" name="treatment" rows="4" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="consultation_notes">Consultation Notes:</label>
                <textarea id="consultation_notes" name="consultation_notes" rows="4"></textarea>
            </div>
            
            <button type="submit" class="btn-primary">Save Record</button>
            <a href="patient_records.php" class="btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
