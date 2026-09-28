<?php
include '../includes/db.php';
include '../includes/auth.php';

requireRole('patient');

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare('SELECT patient_id, u.email, u.full_name FROM patients p JOIN users u ON p.user_id = u.user_id WHERE p.user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$patient_id = $patient['patient_id'];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_medication'])) {
        $name = trim($_POST['medicine_name'] ?? '');
        $dosage = trim($_POST['dosage'] ?? '');
        $freq = intval($_POST['frequency_per_day'] ?? 1);
        $times = trim($_POST['reminder_times'] ?? '');
        $start = $_POST['start_date'] ?? date('Y-m-d');
        $end = !empty($_POST['end_date']) ? $_POST['end_date'] : null;

        if ($name === '' || $times === '') {
            $error = 'Medicine name and reminder times are required.';
        } else {
            $stmt = $conn->prepare('INSERT INTO medications (patient_id, medicine_name, dosage, frequency_per_day, reminder_times, start_date, end_date) VALUES (?,?,?,?,?,?,?)');
            $stmt->bind_param('ississs', $patient_id, $name, $dosage, $freq, $times, $start, $end);
            $stmt->execute();
            $success = 'Medication reminder added.';
        }
    } elseif (isset($_POST['mark_taken'])) {
        $medId = intval($_POST['medication_id']);
        $now = new DateTime();
        $stmt = $conn->prepare('INSERT INTO medication_logs (medication_id, log_date, log_time, status) VALUES (?,?,?,"taken")');
        $date = $now->format('Y-m-d');
        $time = $now->format('H:i:s');
        $stmt->bind_param('iss', $medId, $date, $time);
        $stmt->execute();
        $success = 'Marked as taken.';
    } elseif (isset($_POST['deactivate'])) {
        $medId = intval($_POST['medication_id']);
        $stmt = $conn->prepare('UPDATE medications SET active = 0 WHERE medication_id = ? AND patient_id = ?');
        $stmt->bind_param('ii', $medId, $patient_id);
        $stmt->execute();
        $success = 'Medication reminder stopped.';
    }
}

$medStmt = $conn->prepare('SELECT * FROM medications WHERE patient_id = ? AND active = 1 ORDER BY created_at DESC');
$medStmt->bind_param('i', $patient_id);
$medStmt->execute();
$medications = $medStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Today's log counts per medication
$todayLogs = [];
$logRes = $conn->query("SELECT medication_id, COUNT(*) as cnt FROM medication_logs WHERE log_date = CURDATE() GROUP BY medication_id");
while ($row = $logRes->fetch_assoc()) { $todayLogs[$row['medication_id']] = $row['cnt']; }

include '../includes/header.php';
?>

<div class="main-content">
    <?php include '../includes/sidebar.php'; ?>

    <div class="content">
        <h2>Smart Medicine Reminder</h2>
        <p class="welcome-text">Set daily reminder times for each medication and track whether you've taken today's doses.</p>

        <?php if ($error): ?><div class="error-message"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if ($success): ?><div class="success-message"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

        <div class="section">
            <h3>Add Medication</h3>
            <form method="POST" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:15px;">
                <div class="form-group"><label>Medicine Name</label><input type="text" name="medicine_name" required></div>
                <div class="form-group"><label>Dosage</label><input type="text" name="dosage" placeholder="e.g. 500mg"></div>
                <div class="form-group"><label>Times per day</label><input type="number" name="frequency_per_day" value="1" min="1" max="6"></div>
                <div class="form-group"><label>Reminder Times (comma separated)</label><input type="text" name="reminder_times" placeholder="e.g. 08:00,20:00" required></div>
                <div class="form-group"><label>Start Date</label><input type="date" name="start_date" value="<?php echo date('Y-m-d'); ?>"></div>
                <div class="form-group"><label>End Date (optional)</label><input type="date" name="end_date"></div>
                <div class="form-group" style="align-self:end;"><button type="submit" name="add_medication" value="1" class="btn-primary" style="width:100%;">Add</button></div>
            </form>
        </div>

        <div class="section">
            <h3>Today's Schedule</h3>
            <?php if (empty($medications)): ?>
                <p>No active medications. Add one above.</p>
            <?php else: ?>
                <table class="data-table">
                    <thead><tr><th>Medicine</th><th>Dosage</th><th>Reminder Times</th><th>Taken Today</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($medications as $m): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($m['medicine_name']); ?></td>
                                <td><?php echo htmlspecialchars($m['dosage']); ?></td>
                                <td><?php echo htmlspecialchars($m['reminder_times']); ?></td>
                                <td><?php echo ($todayLogs[$m['medication_id']] ?? 0) . ' / ' . $m['frequency_per_day']; ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="medication_id" value="<?php echo $m['medication_id']; ?>">
                                        <button type="submit" name="mark_taken" value="1" class="btn-small btn-primary">Mark Taken</button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="medication_id" value="<?php echo $m['medication_id']; ?>">
                                        <button type="submit" name="deactivate" value="1" class="btn-small btn-secondary" onclick="return confirm('Stop this reminder?');">Stop</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="section">
            <p class="welcome-text">📧 Reminder emails are sent by a scheduled task (<code>cron/medication_reminder.php</code>) at each medicine's reminder time — see that file for setup instructions if deploying to a live server.</p>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
