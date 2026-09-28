<?php
include '../includes/db.php';
include '../includes/auth.php';
include '../includes/symptom_engine.php';

requireRole('patient');

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare('SELECT patient_id FROM patients WHERE user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$patient_id = $patient['patient_id'];

$symptomList = getSymptomList();
$checkResult = null;
$selectedSymptoms = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedSymptoms = $_POST['symptoms'] ?? [];
    $selectedSymptoms = array_values(array_intersect($selectedSymptoms, array_keys($symptomList)));

    if (!empty($selectedSymptoms)) {
        $checkResult = runSymptomCheck($selectedSymptoms);

        $resultsJson = json_encode($checkResult['results']);
        $symptomsStr = implode(',', $selectedSymptoms);
        $urgency = $checkResult['emergency'] ? 'emergency' : 'low';

        $stmt = $conn->prepare('INSERT INTO symptom_checks (patient_id, symptoms_selected, results_json, recommended_specialisation, urgency_level) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('issss', $patient_id, $symptomsStr, $resultsJson, $checkResult['recommended'], $urgency);
        $stmt->execute();
    }
}

include '../includes/header.php';
?>

<div class="main-content">
    <?php include '../includes/sidebar.php'; ?>

    <div class="content">
        <h2>AI Symptom Checker</h2>
        <p class="welcome-text">Select the symptoms you're experiencing. This tool gives an <strong>estimate</strong> based on symptom overlap with common conditions — it does not replace a doctor's diagnosis.</p>

        <?php if ($checkResult && $checkResult['emergency']): ?>
            <div class="error-message" style="font-size:16px;">
                ⚠️ <strong>Emergency warning:</strong> One or more symptoms you selected can indicate a medical emergency.
                Please call emergency services or go to your nearest emergency department immediately.
                You can also use the <a href="emergency_sos.php">Emergency SOS</a> feature.
            </div>
        <?php endif; ?>

        <form method="POST" class="form-container" style="max-width:800px;">
            <div class="form-group">
                <label>Symptoms</label>
                <div style="display:grid; grid-template-columns: repeat(auto-fill,minmax(220px,1fr)); gap:8px;">
                    <?php foreach ($symptomList as $key => $label): ?>
                        <label style="font-weight:normal; display:flex; align-items:center; gap:6px;">
                            <input type="checkbox" name="symptoms[]" value="<?php echo $key; ?>"
                                <?php echo in_array($key, $selectedSymptoms) ? 'checked' : ''; ?>>
                            <?php echo htmlspecialchars($label); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <button type="submit" class="btn-primary">Check Symptoms</button>
        </form>

        <?php if ($checkResult && !empty($checkResult['results'])): ?>
            <div class="section">
                <h3>Possible Conditions</h3>
                <table class="data-table">
                    <thead>
                        <tr><th>Condition</th><th>Likelihood share</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($checkResult['results'] as $r): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($r['condition']); ?></td>
                                <td><?php echo $r['percent']; ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="card highlight" style="margin-top:20px;">
                    <h3>Recommended Doctor</h3>
                    <p style="font-size:18px; font-weight:600;"><?php echo htmlspecialchars($checkResult['recommended']); ?></p>
                    <div class="action-buttons">
                        <a href="book_appointment.php?specialisation=<?php echo urlencode($checkResult['recommended']); ?>" class="btn-primary">Book Appointment</a>
                    </div>
                </div>
            </div>
        <?php elseif ($checkResult): ?>
            <div class="section"><p>No strong matches found for the selected symptoms. If symptoms persist, please book an appointment with a General Practitioner.</p></div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
