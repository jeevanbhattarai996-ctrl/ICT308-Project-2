<?php
include '../includes/db.php';
include '../includes/auth.php';

requireRole('patient');

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare('SELECT patient_id FROM patients WHERE user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$patient_id = $patient['patient_id'];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $systolic = !empty($_POST['systolic']) ? intval($_POST['systolic']) : null;
    $diastolic = !empty($_POST['diastolic']) ? intval($_POST['diastolic']) : null;
    $heart_rate = !empty($_POST['heart_rate']) ? intval($_POST['heart_rate']) : null;
    $blood_sugar = !empty($_POST['blood_sugar']) ? floatval($_POST['blood_sugar']) : null;
    $weight_kg = !empty($_POST['weight_kg']) ? floatval($_POST['weight_kg']) : null;
    $height_cm = !empty($_POST['height_cm']) ? floatval($_POST['height_cm']) : null;
    $steps = !empty($_POST['steps']) ? intval($_POST['steps']) : null;

    if (!$systolic && !$diastolic && !$heart_rate && !$blood_sugar && !$weight_kg && !$steps) {
        $error = 'Please enter at least one value.';
    } else {
        $stmt = $conn->prepare('INSERT INTO health_vitals (patient_id, systolic, diastolic, heart_rate, blood_sugar, weight_kg, height_cm, steps) VALUES (?,?,?,?,?,?,?,?)');
        $stmt->bind_param('iiiidddi', $patient_id, $systolic, $diastolic, $heart_rate, $blood_sugar, $weight_kg, $height_cm, $steps);
        if ($stmt->execute()) {
            $success = 'Vitals logged successfully.';
        } else {
            $error = 'Failed to log vitals.';
        }
    }
}

// Last known height (for BMI calc) if not entered this time
$heightStmt = $conn->prepare('SELECT height_cm FROM health_vitals WHERE patient_id = ? AND height_cm IS NOT NULL ORDER BY recorded_at DESC LIMIT 1');
$heightStmt->bind_param('i', $patient_id);
$heightStmt->execute();
$lastHeight = $heightStmt->get_result()->fetch_assoc();

// History (last 20 entries, oldest first for charting)
$histStmt = $conn->prepare('SELECT * FROM health_vitals WHERE patient_id = ? ORDER BY recorded_at DESC LIMIT 20');
$histStmt->bind_param('i', $patient_id);
$histStmt->execute();
$rows = array_reverse($histStmt->get_result()->fetch_all(MYSQLI_ASSOC));

$labels = [];
$systolicData = []; $diastolicData = []; $hrData = []; $sugarData = []; $weightData = []; $bmiData = []; $stepsData = [];

foreach ($rows as $r) {
    $labels[] = date('M j', strtotime($r['recorded_at']));
    $systolicData[] = $r['systolic'];
    $diastolicData[] = $r['diastolic'];
    $hrData[] = $r['heart_rate'];
    $sugarData[] = $r['blood_sugar'];
    $weightData[] = $r['weight_kg'];
    $steps = $r['steps'];
    $stepsData[] = $steps;
    $h = $r['height_cm'] ?: ($lastHeight['height_cm'] ?? null);
    if ($r['weight_kg'] && $h) {
        $bmi = round($r['weight_kg'] / (($h/100) ** 2), 1);
    } else {
        $bmi = null;
    }
    $bmiData[] = $bmi;
}

$latest = end($rows) ?: null;
$latestBmi = null;
if ($latest && $latest['weight_kg']) {
    $h = $latest['height_cm'] ?: ($lastHeight['height_cm'] ?? null);
    if ($h) $latestBmi = round($latest['weight_kg'] / (($h/100) ** 2), 1);
}

include '../includes/header.php';
?>

<div class="main-content">
    <?php include '../includes/sidebar.php'; ?>

    <div class="content">
        <h2>Health Dashboard</h2>
        <p class="welcome-text">Track your blood pressure, heart rate, blood sugar, weight, BMI and daily activity over time.</p>

        <?php if ($error): ?><div class="error-message"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if ($success): ?><div class="success-message"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

        <div class="dashboard-grid">
            <div class="card"><h3>Blood Pressure</h3><p class="stat-number"><?php echo $latest && $latest['systolic'] ? $latest['systolic'].'/'.$latest['diastolic'] : '—'; ?></p></div>
            <div class="card"><h3>Heart Rate</h3><p class="stat-number"><?php echo $latest['heart_rate'] ?? '—'; ?></p></div>
            <div class="card"><h3>Blood Sugar</h3><p class="stat-number"><?php echo $latest['blood_sugar'] ?? '—'; ?></p></div>
            <div class="card"><h3>BMI</h3><p class="stat-number"><?php echo $latestBmi ?? '—'; ?></p></div>
        </div>

        <div class="section">
            <h3>Log New Vitals</h3>
            <form method="POST" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:15px;">
                <div class="form-group"><label>Systolic (mmHg)</label><input type="number" name="systolic"></div>
                <div class="form-group"><label>Diastolic (mmHg)</label><input type="number" name="diastolic"></div>
                <div class="form-group"><label>Heart Rate (bpm)</label><input type="number" name="heart_rate"></div>
                <div class="form-group"><label>Blood Sugar (mmol/L)</label><input type="number" step="0.1" name="blood_sugar"></div>
                <div class="form-group"><label>Weight (kg)</label><input type="number" step="0.1" name="weight_kg"></div>
                <div class="form-group"><label>Height (cm) <?php echo $lastHeight ? '— last: '.$lastHeight['height_cm'] : ''; ?></label><input type="number" step="0.1" name="height_cm"></div>
                <div class="form-group"><label>Steps today</label><input type="number" name="steps"></div>
                <div class="form-group" style="align-self:end;"><button type="submit" class="btn-primary" style="width:100%;">Save Vitals</button></div>
            </form>
        </div>

        <div class="section">
            <h3>Blood Pressure & Heart Rate</h3>
            <canvas id="bpChart" height="90"></canvas>
        </div>
        <div class="section">
            <h3>Blood Sugar</h3>
            <canvas id="sugarChart" height="90"></canvas>
        </div>
        <div class="section">
            <h3>Weight & BMI</h3>
            <canvas id="weightChart" height="90"></canvas>
        </div>
        <div class="section">
            <h3>Daily Activity (Steps)</h3>
            <canvas id="stepsChart" height="90"></canvas>
        </div>
    </div>
</div>

<script>
const labels = <?php echo json_encode($labels); ?>;

new Chart(document.getElementById('bpChart'), {
    type: 'line',
    data: { labels, datasets: [
        { label: 'Systolic', data: <?php echo json_encode($systolicData); ?>, borderColor: '#e74c3c', tension: 0.3 },
        { label: 'Diastolic', data: <?php echo json_encode($diastolicData); ?>, borderColor: '#2c5aa0', tension: 0.3 },
        { label: 'Heart Rate', data: <?php echo json_encode($hrData); ?>, borderColor: '#27ae60', tension: 0.3 }
    ]},
    options: { responsive: true }
});

new Chart(document.getElementById('sugarChart'), {
    type: 'line',
    data: { labels, datasets: [{ label: 'Blood Sugar (mmol/L)', data: <?php echo json_encode($sugarData); ?>, borderColor: '#f39c12', tension: 0.3 }] },
    options: { responsive: true }
});

new Chart(document.getElementById('weightChart'), {
    type: 'line',
    data: { labels, datasets: [
        { label: 'Weight (kg)', data: <?php echo json_encode($weightData); ?>, borderColor: '#0a7fb9', tension: 0.3 },
        { label: 'BMI', data: <?php echo json_encode($bmiData); ?>, borderColor: '#8e44ad', tension: 0.3, yAxisID: 'y1' }
    ]},
    options: { responsive: true, scales: { y1: { position: 'right', grid: { drawOnChartArea: false } } } }
});

new Chart(document.getElementById('stepsChart'), {
    type: 'bar',
    data: { labels, datasets: [{ label: 'Steps', data: <?php echo json_encode($stepsData); ?>, backgroundColor: '#2c5aa0' }] },
    options: { responsive: true }
});
</script>

<?php include '../includes/footer.php'; ?>
