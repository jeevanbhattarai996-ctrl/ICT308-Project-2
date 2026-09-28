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

$triggered = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trigger_sos'])) {
    $lat = !empty($_POST['latitude']) ? floatval($_POST['latitude']) : null;
    $lng = !empty($_POST['longitude']) ? floatval($_POST['longitude']) : null;

    $stmt = $conn->prepare('INSERT INTO sos_alerts (patient_id, latitude, longitude) VALUES (?,?,?)');
    $stmt->bind_param('idd', $patient_id, $lat, $lng);
    $stmt->execute();

    // Notify hospital staff: create notifications for all admin users
    $locText = ($lat && $lng) ? "location $lat, $lng" : 'location not shared';
    $msg = "🚨 SOS ALERT: {$patient['full_name']} has triggered an emergency alert ($locText).";
    $adminRes = $conn->query("SELECT user_id FROM users WHERE role = 'admin'");
    while ($admin = $adminRes->fetch_assoc()) {
        $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, message, notification_type) VALUES (?, ?, 'general')");
        $notifStmt->bind_param('is', $admin['user_id'], $msg);
        $notifStmt->execute();
    }

    $triggered = true;
}

include '../includes/header.php';
?>

<div class="main-content">
    <?php include '../includes/sidebar.php'; ?>

    <div class="content">
        <h2>Emergency SOS</h2>
        <p class="welcome-text">Pressing this button alerts CarePlus staff and shares your location (if allowed) with your emergency contact details on file.</p>

        <?php if ($triggered): ?>
            <div class="error-message" style="font-size:16px;">
                🚨 SOS Alert sent. Hospital staff have been notified.<br>
                If this is a life-threatening emergency, also call your local emergency number immediately.
            </div>
            <div class="card" style="max-width:400px;">
                <h3>Your Emergency Contact</h3>
                <p><?php echo htmlspecialchars($patient['emergency_contact_name'] ?? 'Not set'); ?></p>
                <p><?php echo htmlspecialchars($patient['emergency_contact_phone'] ?? ''); ?></p>
            </div>
        <?php else: ?>
            <div style="text-align:center; margin:50px 0;">
                <form method="POST" id="sosForm">
                    <input type="hidden" name="latitude" id="lat_field">
                    <input type="hidden" name="longitude" id="lng_field">
                    <button type="submit" name="trigger_sos" value="1"
                        style="width:200px; height:200px; border-radius:50%; background:var(--danger-color); color:white; font-size:28px; font-weight:bold; border:none; cursor:pointer; box-shadow:0 4px 20px rgba(231,76,60,0.5);">
                        SOS
                    </button>
                </form>
            </div>
            <p class="welcome-text" style="text-align:center;">We'll try to include your GPS location if your browser allows it.</p>
        <?php endif; ?>
    </div>
</div>

<script>
if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(function(pos) {
        const latField = document.getElementById('lat_field');
        const lngField = document.getElementById('lng_field');
        if (latField) latField.value = pos.coords.latitude;
        if (lngField) lngField.value = pos.coords.longitude;
    });
}
</script>

<?php include '../includes/footer.php'; ?>
