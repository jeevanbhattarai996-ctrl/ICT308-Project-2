<?php
include '../includes/db.php';
include '../includes/auth.php';

requireRole('patient');

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare('SELECT p.*, u.full_name FROM patients p JOIN users u ON p.user_id = u.user_id WHERE p.user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$patient_id = $patient['patient_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $family_history = trim($_POST['family_history'] ?? '');
    $stmt = $conn->prepare('UPDATE patients SET family_history = ? WHERE patient_id = ?');
    $stmt->bind_param('si', $family_history, $patient_id);
    $stmt->execute();
    $patient['family_history'] = $family_history;
}

// Latest vitals
$vStmt = $conn->prepare('SELECT * FROM health_vitals WHERE patient_id = ? ORDER BY recorded_at DESC LIMIT 1');
$vStmt->bind_param('i', $patient_id);
$vStmt->execute();
$vitals = $vStmt->get_result()->fetch_assoc();

$age = null;
if (!empty($patient['date_of_birth'])) {
    $dob = new DateTime($patient['date_of_birth']);
    $age = $dob->diff(new DateTime())->y;
}

$bmi = null;
if ($vitals && $vitals['weight_kg'] && $vitals['height_cm']) {
    $bmi = round($vitals['weight_kg'] / (($vitals['height_cm']/100) ** 2), 1);
}

$familyHistoryLower = strtolower($patient['family_history'] ?? '');

// --- Rule-based risk sub-scores (0-100, higher = higher risk) ---
// This is a transparent point-based estimate, not a clinical diagnostic tool.

// Heart disease risk
$heartRisk = 10;
if ($age !== null) { if ($age >= 60) $heartRisk += 25; elseif ($age >= 45) $heartRisk += 15; elseif ($age >= 30) $heartRisk += 5; }
if ($vitals && $vitals['systolic']) { if ($vitals['systolic'] >= 140) $heartRisk += 25; elseif ($vitals['systolic'] >= 130) $heartRisk += 12; }
if ($bmi) { if ($bmi >= 30) $heartRisk += 15; elseif ($bmi >= 25) $heartRisk += 7; }
if (str_contains($familyHistoryLower, 'heart')) $heartRisk += 20;
$heartRisk = min(100, $heartRisk);

// Diabetes risk
$diabetesRisk = 10;
if ($vitals && $vitals['blood_sugar']) { if ($vitals['blood_sugar'] >= 7.0) $diabetesRisk += 35; elseif ($vitals['blood_sugar'] >= 5.7) $diabetesRisk += 18; }
if ($bmi) { if ($bmi >= 30) $diabetesRisk += 20; elseif ($bmi >= 25) $diabetesRisk += 10; }
if ($age !== null && $age >= 45) $diabetesRisk += 10;
if (str_contains($familyHistoryLower, 'diabet')) $diabetesRisk += 20;
$diabetesRisk = min(100, $diabetesRisk);

// Hypertension risk
$hypertensionRisk = 10;
if ($vitals && $vitals['systolic']) {
    if ($vitals['systolic'] >= 140 || $vitals['diastolic'] >= 90) $hypertensionRisk += 40;
    elseif ($vitals['systolic'] >= 130 || $vitals['diastolic'] >= 85) $hypertensionRisk += 20;
}
if ($bmi && $bmi >= 30) $hypertensionRisk += 15;
if ($age !== null && $age >= 50) $hypertensionRisk += 10;
if (str_contains($familyHistoryLower, 'blood pressure') || str_contains($familyHistoryLower, 'hypertension')) $hypertensionRisk += 15;
$hypertensionRisk = min(100, $hypertensionRisk);

function riskLabel($score) {
    if ($score >= 60) return ['Elevated', 'var(--danger-color)'];
    if ($score >= 30) return ['Medium', 'var(--warning-color)'];
    return ['Low', 'var(--success-color)'];
}

$overallScore = 100 - round(($heartRisk + $diabetesRisk + $hypertensionRisk) / 3);
$overallScore = max(0, min(100, $overallScore));

[$heartLabel, $heartColor] = riskLabel($heartRisk);
[$diabLabel, $diabColor] = riskLabel($diabetesRisk);
[$hyperLabel, $hyperColor] = riskLabel($hypertensionRisk);

include '../includes/header.php';
?>

<div class="main-content">
    <?php include '../includes/sidebar.php'; ?>

    <div class="content">
        <h2>Health Risk Score</h2>
        <p class="welcome-text">A rule-based estimate from your logged vitals, age, BMI and family history — for awareness only, not a diagnosis. <a href="health_dashboard.php">Log vitals</a> for a more accurate score.</p>

        <div class="card highlight" style="max-width:300px; text-align:center;">
            <h3>Health Score</h3>
            <p class="stat-number" style="font-size:48px;"><?php echo $overallScore; ?> / 100</p>
        </div>

        <div class="dashboard-grid" style="margin-top:20px;">
            <div class="card" style="border-left-color: <?php echo $heartColor; ?>;">
                <h3>Heart Disease Risk</h3>
                <p class="stat-number" style="color: <?php echo $heartColor; ?>;"><?php echo $heartLabel; ?></p>
                <p class="welcome-text">Score: <?php echo $heartRisk; ?>/100</p>
            </div>
            <div class="card" style="border-left-color: <?php echo $diabColor; ?>;">
                <h3>Diabetes Risk</h3>
                <p class="stat-number" style="color: <?php echo $diabColor; ?>;"><?php echo $diabLabel; ?></p>
                <p class="welcome-text">Score: <?php echo $diabetesRisk; ?>/100</p>
            </div>
            <div class="card" style="border-left-color: <?php echo $hyperColor; ?>;">
                <h3>Hypertension Risk</h3>
                <p class="stat-number" style="color: <?php echo $hyperColor; ?>;"><?php echo $hyperLabel; ?></p>
                <p class="welcome-text">Score: <?php echo $hypertensionRisk; ?>/100</p>
            </div>
            <div class="card">
                <h3>BMI</h3>
                <p class="stat-number"><?php echo $bmi ?? '—'; ?></p>
            </div>
        </div>

        <div class="section">
            <h3>Family Medical History</h3>
            <p class="welcome-text">Mentioning conditions like "heart disease", "diabetes" or "high blood pressure" improves the estimate above.</p>
            <form method="POST">
                <div class="form-group">
                    <textarea name="family_history" rows="3" placeholder="e.g. Father had heart disease, mother has type 2 diabetes"><?php echo htmlspecialchars($patient['family_history'] ?? ''); ?></textarea>
                </div>
                <button type="submit" class="btn-primary">Save</button>
            </form>
        </div>

        <?php if ($overallScore < 40): ?>
            <div class="error-message" style="margin-top:20px;">Your estimated risk is on the higher side — consider booking an appointment to discuss these results with a doctor.</div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
