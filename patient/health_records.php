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

$uploadDir = __DIR__ . '/../uploads/health_records/';
$allowedExt = ['pdf', 'jpg', 'jpeg', 'png'];
$maxSize = 8 * 1024 * 1024; // 8MB

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ---- Upload a lab report / prescription / X-ray ----
    if (isset($_POST['upload_report'])) {
        $test_name = trim($_POST['test_name'] ?? '');
        $result_summary = trim($_POST['result_summary'] ?? '');
        $report_date = $_POST['report_date'] ?? date('Y-m-d');

        if ($test_name === '') {
            $error = 'Please give the record a title.';
        } elseif (empty($_FILES['file']['name'])) {
            $error = 'Please choose a file to upload.';
        } else {
            $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt)) {
                $error = 'Only PDF, JPG or PNG files are allowed.';
            } elseif ($_FILES['file']['size'] > $maxSize) {
                $error = 'File is too large (max 8MB).';
            } elseif ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                $error = 'Upload failed, please try again.';
            } else {
                $safeName = 'p' . $patient_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadDir . $safeName)) {
                    $stmt = $conn->prepare('INSERT INTO lab_reports (patient_id, test_name, result_summary, file_path, report_date) VALUES (?,?,?,?,?)');
                    $stmt->bind_param('issss', $patient_id, $test_name, $result_summary, $safeName, $report_date);
                    $stmt->execute();
                    $success = 'Record uploaded.';
                } else {
                    $error = 'Could not save the uploaded file.';
                }
            }
        }
    }

    // ---- Add a vaccination record ----
    if (isset($_POST['add_vaccination'])) {
        $vname = trim($_POST['vaccine_name'] ?? '');
        $dose = intval($_POST['dose_number'] ?? 1);
        $date_admin = $_POST['date_administered'] ?? '';
        $admin_by = trim($_POST['administered_by'] ?? '');
        if ($vname === '' || $date_admin === '') {
            $error = 'Vaccine name and date are required.';
        } else {
            $stmt = $conn->prepare('INSERT INTO vaccinations (patient_id, vaccine_name, dose_number, date_administered, administered_by) VALUES (?,?,?,?,?)');
            $stmt->bind_param('isiss', $patient_id, $vname, $dose, $date_admin, $admin_by);
            $stmt->execute();
            $success = 'Vaccination record added.';
        }
    }

    // ---- Add an allergy ----
    if (isset($_POST['add_allergy'])) {
        $allergen = trim($_POST['allergen'] ?? '');
        $reaction = trim($_POST['reaction'] ?? '');
        $severity = in_array($_POST['severity'] ?? '', ['mild', 'moderate', 'severe']) ? $_POST['severity'] : 'mild';
        if ($allergen === '') {
            $error = 'Please name the allergen.';
        } else {
            $stmt = $conn->prepare('INSERT INTO allergies (patient_id, allergen, reaction, severity) VALUES (?,?,?,?)');
            $stmt->bind_param('isss', $patient_id, $allergen, $reaction, $severity);
            $stmt->execute();
            $success = 'Allergy added.';
        }
    }
}

$reports = $conn->prepare('SELECT * FROM lab_reports WHERE patient_id = ? ORDER BY report_date DESC');
$reports->bind_param('i', $patient_id);
$reports->execute();
$reports = $reports->get_result();

$vaccinations = $conn->prepare('SELECT * FROM vaccinations WHERE patient_id = ? ORDER BY date_administered DESC');
$vaccinations->bind_param('i', $patient_id);
$vaccinations->execute();
$vaccinations = $vaccinations->get_result();

$allergies = $conn->prepare('SELECT * FROM allergies WHERE patient_id = ?');
$allergies->bind_param('i', $patient_id);
$allergies->execute();
$allergies = $allergies->get_result();

include '../includes/header.php';
?>

<div class="main-content">
    <?php include '../includes/sidebar.php'; ?>

    <div class="content">
        <h2>Electronic Health Records</h2>
        <p class="welcome-text">Lab reports, prescriptions, X-rays, vaccinations and allergies, all in one place.</p>

        <?php if ($error): ?><div class="error-message"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if ($success): ?><div class="success-message"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

        <div class="section">
            <h3>📁 Lab Reports, Prescriptions & X-rays</h3>
            <form method="POST" enctype="multipart/form-data" class="form-container" style="max-width: 100%;">
                <div class="form-group"><label>Title</label><input type="text" name="test_name" placeholder="e.g. Full Blood Count" required></div>
                <div class="form-group"><label>Notes / summary (optional)</label><input type="text" name="result_summary"></div>
                <div class="form-group"><label>Date</label><input type="date" name="report_date" value="<?php echo date('Y-m-d'); ?>"></div>
                <div class="form-group"><label>File (PDF, JPG or PNG, max 8MB)</label><input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" required></div>
                <button type="submit" name="upload_report" class="btn-primary">Upload</button>
            </form>

            <?php if ($reports->num_rows > 0): ?>
                <table class="data-table">
                    <thead><tr><th>Title</th><th>Notes</th><th>Date</th><th>File</th></tr></thead>
                    <tbody>
                        <?php while ($r = $reports->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($r['test_name']); ?></td>
                                <td><?php echo htmlspecialchars($r['result_summary']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($r['report_date'])); ?></td>
                                <td><a href="../download_record.php?id=<?php echo $r['report_id']; ?>" class="btn-small btn-secondary">Download</a></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="welcome-text">No records uploaded yet.</p>
            <?php endif; ?>
        </div>

        <div class="section">
            <h3>💉 Vaccinations</h3>
            <form method="POST" class="form-container" style="max-width: 100%;">
                <div class="form-group"><label>Vaccine name</label><input type="text" name="vaccine_name" required></div>
                <div class="form-group"><label>Dose number</label><input type="number" name="dose_number" min="1" value="1"></div>
                <div class="form-group"><label>Date administered</label><input type="date" name="date_administered" required></div>
                <div class="form-group"><label>Administered by (optional)</label><input type="text" name="administered_by"></div>
                <button type="submit" name="add_vaccination" class="btn-primary">Add</button>
            </form>
            <?php if ($vaccinations->num_rows > 0): ?>
                <table class="data-table">
                    <thead><tr><th>Vaccine</th><th>Dose</th><th>Date</th><th>Administered by</th></tr></thead>
                    <tbody>
                        <?php while ($v = $vaccinations->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($v['vaccine_name']); ?></td>
                                <td><?php echo (int)$v['dose_number']; ?></td>
                                <td><?php echo date('M j, Y', strtotime($v['date_administered'])); ?></td>
                                <td><?php echo htmlspecialchars($v['administered_by']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="welcome-text">No vaccination records yet.</p>
            <?php endif; ?>
        </div>

        <div class="section">
            <h3>⚠️ Allergies</h3>
            <form method="POST" class="form-container" style="max-width: 100%;">
                <div class="form-group"><label>Allergen</label><input type="text" name="allergen" required></div>
                <div class="form-group"><label>Reaction (optional)</label><input type="text" name="reaction"></div>
                <div class="form-group">
                    <label>Severity</label>
                    <select name="severity">
                        <option value="mild">Mild</option>
                        <option value="moderate">Moderate</option>
                        <option value="severe">Severe</option>
                    </select>
                </div>
                <button type="submit" name="add_allergy" class="btn-primary">Add</button>
            </form>
            <?php if ($allergies->num_rows > 0): ?>
                <table class="data-table">
                    <thead><tr><th>Allergen</th><th>Reaction</th><th>Severity</th></tr></thead>
                    <tbody>
                        <?php while ($a = $allergies->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($a['allergen']); ?></td>
                                <td><?php echo htmlspecialchars($a['reaction']); ?></td>
                                <td><span class="status status-<?php echo $a['severity'] === 'severe' ? 'cancelled' : ($a['severity'] === 'moderate' ? 'pending' : 'completed'); ?>"><?php echo ucfirst($a['severity']); ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="welcome-text">No allergies on record.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
