<?php
include 'includes/db.php';

$token = $_GET['token'] ?? '';
$patient = null;

if ($token !== '') {
    $stmt = $conn->prepare('SELECT p.*, u.full_name, u.phone FROM patients p JOIN users u ON p.user_id = u.user_id WHERE p.qr_token = ?');
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $patient = $stmt->get_result()->fetch_assoc();
}

$allergies = [];
if ($patient) {
    $aStmt = $conn->prepare('SELECT allergen, reaction, severity FROM allergies WHERE patient_id = ?');
    $aStmt->bind_param('i', $patient['patient_id']);
    $aStmt->execute();
    $allergies = $aStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Medical Card</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-page">
    <div class="auth-container">
        <div class="auth-box" style="max-width:500px;">
            <?php if (!$patient): ?>
                <h1>Card not found</h1>
                <p class="auth-subtitle">This QR code is invalid or has expired.</p>
            <?php else: ?>
                <h1>🚑 Emergency Medical Card</h1>
                <p class="auth-subtitle">CarePlus Medical Centre</p>
                <table class="details-table">
                    <tr><td>Name</td><td><?php echo htmlspecialchars($patient['full_name']); ?></td></tr>
                    <tr><td>Date of Birth</td><td><?php echo htmlspecialchars($patient['date_of_birth'] ?? 'Not provided'); ?></td></tr>
                    <tr><td>Blood Group</td><td><strong style="color:var(--danger-color); font-size:18px;"><?php echo htmlspecialchars($patient['blood_group'] ?? 'Not recorded'); ?></strong></td></tr>
                    <tr><td>Allergies</td><td>
                        <?php if (empty($allergies)): ?>
                            None recorded
                        <?php else: ?>
                            <?php foreach ($allergies as $a): ?>
                                <div><strong><?php echo htmlspecialchars($a['allergen']); ?></strong> (<?php echo htmlspecialchars($a['severity']); ?>) — <?php echo htmlspecialchars($a['reaction']); ?></div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </td></tr>
                    <tr><td>Emergency Contact</td><td><?php echo htmlspecialchars($patient['emergency_contact_name'] ?? '—'); ?> — <?php echo htmlspecialchars($patient['emergency_contact_phone'] ?? '—'); ?></td></tr>
                    <tr><td>Patient Phone</td><td><?php echo htmlspecialchars($patient['phone'] ?? '—'); ?></td></tr>
                </table>
                <p class="welcome-text" style="margin-top:20px; font-size:12px;">This card shows information the patient has chosen to record for emergency use. It is not a full medical record.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
