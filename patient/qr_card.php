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

// Generate a QR token lazily if this patient doesn't have one yet
if (empty($patient['qr_token'])) {
    $token = bin2hex(random_bytes(32));
    $stmt = $conn->prepare('UPDATE patients SET qr_token = ? WHERE patient_id = ?');
    $stmt->bind_param('si', $token, $patient_id);
    $stmt->execute();
    $patient['qr_token'] = $token;
}

if (isset($_POST['blood_group'])) {
    $bg = trim($_POST['blood_group']);
    $stmt = $conn->prepare('UPDATE patients SET blood_group = ? WHERE patient_id = ?');
    $stmt->bind_param('si', $bg, $patient_id);
    $stmt->execute();
    $patient['blood_group'] = $bg;
}

$cardUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['SCRIPT_NAME'])) . '/medical_card_view.php?token=' . $patient['qr_token'];

include '../includes/header.php';
?>

<div class="main-content">
    <?php include '../includes/sidebar.php'; ?>

    <div class="content">
        <h2>QR Medical ID Card</h2>
        <p class="welcome-text">Emergency responders can scan this code to see your critical medical info instantly — no login required.</p>

        <div class="section" style="max-width:500px; text-align:center;">
            <div id="qrcode" style="display:inline-block; padding:15px; background:white;"></div>
            <p class="welcome-text" style="word-break:break-all; margin-top:10px;"><?php echo htmlspecialchars($cardUrl); ?></p>
            <a href="<?php echo htmlspecialchars($cardUrl); ?>" target="_blank" class="btn-secondary">Preview Card</a>
        </div>

        <div class="section" style="max-width:500px;">
            <h3>Blood Group</h3>
            <form method="POST" class="form-container" style="box-shadow:none; padding:0;">
                <div class="form-group">
                    <select name="blood_group">
                        <?php foreach (['O+','O-','A+','A-','B+','B-','AB+','AB-'] as $bg): ?>
                            <option value="<?php echo $bg; ?>" <?php echo ($patient['blood_group'] ?? '') === $bg ? 'selected' : ''; ?>><?php echo $bg; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-primary">Save</button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
new QRCode(document.getElementById("qrcode"), {
    text: <?php echo json_encode($cardUrl); ?>,
    width: 220,
    height: 220
});
</script>

<?php include '../includes/footer.php'; ?>
