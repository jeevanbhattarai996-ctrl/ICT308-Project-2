<?php
include '../includes/db.php';
include '../includes/auth.php';

requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resolve'])) {
    $alertId = intval($_POST['alert_id'] ?? 0);
    $stmt = $conn->prepare("UPDATE sos_alerts SET status='resolved', resolved_at = NOW() WHERE alert_id = ?");
    $stmt->bind_param('i', $alertId);
    $stmt->execute();
    header('Location: sos_alerts.php');
    exit;
}

$stmt = $conn->query("SELECT s.*, u.full_name, u.phone, p.emergency_contact_name, p.emergency_contact_phone
    FROM sos_alerts s
    JOIN patients p ON s.patient_id = p.patient_id
    JOIN users u ON p.user_id = u.user_id
    ORDER BY (s.status = 'active') DESC, s.triggered_at DESC");
$alerts = $stmt->fetch_all(MYSQLI_ASSOC);
$activeCount = count(array_filter($alerts, fn($a) => $a['status'] === 'active'));

include '../includes/header.php';
?>

<div class="main-content">
    <?php include '../includes/sidebar.php'; ?>

    <div class="content">
        <h2>🔔 Emergency SOS Alerts</h2>

        <?php if ($activeCount > 0): ?>
            <div class="sos-alert-banner"><?php echo $activeCount; ?> active SOS alert<?php echo $activeCount > 1 ? 's' : ''; ?> — please respond immediately.</div>
        <?php else: ?>
            <p class="welcome-text">No active alerts right now.</p>
        <?php endif; ?>

        <?php if (!empty($alerts)): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Phone</th>
                        <th>Emergency Contact</th>
                        <th>Location</th>
                        <th>Triggered</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($alerts as $a): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($a['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($a['phone']); ?></td>
                            <td><?php echo htmlspecialchars($a['emergency_contact_name'] ?? '—'); ?> <?php echo htmlspecialchars($a['emergency_contact_phone'] ?? ''); ?></td>
                            <td>
                                <?php if ($a['latitude'] && $a['longitude']): ?>
                                    <a href="https://www.google.com/maps?q=<?php echo $a['latitude']; ?>,<?php echo $a['longitude']; ?>" target="_blank">View on map</a>
                                <?php else: ?>
                                    Not shared
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M j, Y g:i A', strtotime($a['triggered_at'])); ?></td>
                            <td><span class="status status-<?php echo $a['status'] === 'active' ? 'cancelled' : 'completed'; ?>"><?php echo ucfirst($a['status']); ?></span></td>
                            <td>
                                <?php if ($a['status'] === 'active'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="alert_id" value="<?php echo $a['alert_id']; ?>">
                                        <button type="submit" name="resolve" class="btn-small btn-primary">Mark Resolved</button>
                                    </form>
                                <?php else: ?>
                                    Resolved <?php echo $a['resolved_at'] ? date('M j, g:i A', strtotime($a['resolved_at'])) : ''; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="welcome-text">No SOS alerts have been triggered yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
