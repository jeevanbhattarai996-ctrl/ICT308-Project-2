<?php
include '../includes/db.php';
include '../includes/auth.php';

requireRole('admin');

// Monthly patients (new registrations), last 6 months
$stmt = $conn->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as total FROM users WHERE role = 'patient' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY ym ORDER BY ym");
$patientsRaw = $stmt->fetch_all(MYSQLI_ASSOC);
$patientsMap = [];
foreach ($patientsRaw as $row) $patientsMap[$row['ym']] = (int)$row['total'];
$monthLabels = [];
$monthPatients = [];
for ($i = 5; $i >= 0; $i--) {
    $ym = date('Y-m', strtotime("-$i month"));
    $monthLabels[] = date('M Y', strtotime("-$i month"));
    $monthPatients[] = $patientsMap[$ym] ?? 0;
}

// Estimated revenue by month (completed appointments x each doctor's fee), last 6 months
$stmt = $conn->query("SELECT DATE_FORMAT(a.appointment_date, '%Y-%m') as ym, SUM(d.consultation_fee) as revenue
    FROM appointments a JOIN doctors d ON a.doctor_id = d.doctor_id
    WHERE a.status = 'completed' AND a.appointment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY ym ORDER BY ym");
$revenueRaw = $stmt->fetch_all(MYSQLI_ASSOC);
$revenueMap = [];
foreach ($revenueRaw as $row) $revenueMap[$row['ym']] = (float)$row['revenue'];
$monthRevenue = [];
for ($i = 5; $i >= 0; $i--) {
    $ym = date('Y-m', strtotime("-$i month"));
    $monthRevenue[] = round($revenueMap[$ym] ?? 0, 2);
}

// Doctor performance (appointment counts)
$stmt = $conn->query("SELECT u.full_name, COUNT(a.appointment_id) as total, SUM(a.status='completed') as completed
    FROM doctors d JOIN users u ON d.user_id = u.user_id
    LEFT JOIN appointments a ON a.doctor_id = d.doctor_id
    GROUP BY d.doctor_id ORDER BY total DESC");
$doctorPerf = $stmt->fetch_all(MYSQLI_ASSOC);
$doctorLabels = array_column($doctorPerf, 'full_name');
$doctorTotals = array_map('intval', array_column($doctorPerf, 'total'));

// Appointment trend, last 14 days (all doctors)
$stmt = $conn->query("SELECT appointment_date, COUNT(*) as total FROM appointments WHERE appointment_date BETWEEN DATE_SUB(CURDATE(), INTERVAL 13 DAY) AND CURDATE() GROUP BY appointment_date ORDER BY appointment_date");
$trendRaw = $stmt->fetch_all(MYSQLI_ASSOC);
$trendMap = [];
foreach ($trendRaw as $row) $trendMap[$row['appointment_date']] = (int)$row['total'];
$trendLabels = [];
$trendData = [];
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i day"));
    $trendLabels[] = date('M j', strtotime($d));
    $trendData[] = $trendMap[$d] ?? 0;
}

// Disease / diagnosis statistics (hospital-wide)
$stmt = $conn->query('SELECT diagnosis, COUNT(*) as total FROM medical_records WHERE diagnosis IS NOT NULL AND diagnosis != "" GROUP BY diagnosis ORDER BY total DESC LIMIT 8');
$diagRaw = $stmt->fetch_all(MYSQLI_ASSOC);
$diagLabels = array_column($diagRaw, 'diagnosis');
$diagData = array_map('intval', array_column($diagRaw, 'total'));

// Headline numbers
$totalPatients = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='patient'")->fetch_assoc()['c'];
$totalDoctors = $conn->query("SELECT COUNT(*) as c FROM doctors")->fetch_assoc()['c'];
$activeSos = $conn->query("SELECT COUNT(*) as c FROM sos_alerts WHERE status='active'")->fetch_assoc()['c'];
$totalRevenueYtd = $conn->query("SELECT COALESCE(SUM(d.consultation_fee),0) as r FROM appointments a JOIN doctors d ON a.doctor_id = d.doctor_id WHERE a.status='completed' AND YEAR(a.appointment_date) = YEAR(CURDATE())")->fetch_assoc()['r'];

include '../includes/header.php';
?>

<div class="main-content">
    <?php include '../includes/sidebar.php'; ?>

    <div class="content">
        <h2>📊 Hospital Analytics</h2>
        <p class="welcome-text">Revenue figures are an estimate (completed appointments &times; each doctor's consultation fee), not real billing data.</p>

        <div class="dashboard-grid">
            <div class="card highlight"><h3>Total Patients</h3><p class="stat-number"><?php echo $totalPatients; ?></p></div>
            <div class="card"><h3>Total Doctors</h3><p class="stat-number"><?php echo $totalDoctors; ?></p></div>
            <div class="card"><h3>Est. Revenue (YTD)</h3><p class="stat-number">$<?php echo number_format($totalRevenueYtd, 2); ?></p></div>
            <div class="card" style="<?php echo $activeSos > 0 ? 'border-left-color: var(--danger-color);' : ''; ?>">
                <h3>Active SOS Alerts</h3>
                <p class="stat-number" style="<?php echo $activeSos > 0 ? 'color: var(--danger-color);' : ''; ?>"><?php echo $activeSos; ?></p>
                <?php if ($activeSos > 0): ?><a href="sos_alerts.php" class="btn-small btn-primary" style="margin-top:8px;">View alerts</a><?php endif; ?>
            </div>
        </div>

        <div class="chart-grid">
            <div class="chart-card">
                <h4>New Patients / Month</h4>
                <div class="chart-canvas-wrap"><canvas id="patientsChart"></canvas></div>
            </div>
            <div class="chart-card">
                <h4>Estimated Revenue / Month</h4>
                <div class="chart-canvas-wrap"><canvas id="revenueChart"></canvas></div>
            </div>
            <div class="chart-card">
                <h4>Doctor Performance (Total Appointments)</h4>
                <div class="chart-canvas-wrap"><canvas id="doctorChart"></canvas></div>
            </div>
            <div class="chart-card">
                <h4>Appointment Trend — Last 14 Days</h4>
                <div class="chart-canvas-wrap"><canvas id="trendChart"></canvas></div>
            </div>
            <div class="chart-card" style="grid-column: 1 / -1;">
                <h4>Disease / Diagnosis Statistics</h4>
                <div class="chart-canvas-wrap">
                    <?php if (!empty($diagLabels)): ?>
                        <canvas id="diagChart"></canvas>
                    <?php else: ?>
                        <p class="welcome-text">No diagnoses recorded yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('patientsChart'), {
    type: 'bar',
    data: { labels: <?php echo json_encode($monthLabels); ?>, datasets: [{ label: 'New Patients', data: <?php echo json_encode($monthPatients); ?>, backgroundColor: '#0a7fb9' }] },
    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});

new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: { labels: <?php echo json_encode($monthLabels); ?>, datasets: [{ label: 'Revenue ($)', data: <?php echo json_encode($monthRevenue); ?>, borderColor: '#27ae60', backgroundColor: 'rgba(39,174,96,0.1)', fill: true, tension: 0.3 }] },
    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
});

new Chart(document.getElementById('doctorChart'), {
    type: 'bar',
    data: { labels: <?php echo json_encode($doctorLabels); ?>, datasets: [{ label: 'Appointments', data: <?php echo json_encode($doctorTotals); ?>, backgroundColor: '#2c5aa0' }] },
    options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});

new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: { labels: <?php echo json_encode($trendLabels); ?>, datasets: [{ label: 'Appointments', data: <?php echo json_encode($trendData); ?>, borderColor: '#f39c12', backgroundColor: 'rgba(243,156,18,0.1)', fill: true, tension: 0.3 }] },
    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});

<?php if (!empty($diagLabels)): ?>
new Chart(document.getElementById('diagChart'), {
    type: 'bar',
    data: { labels: <?php echo json_encode($diagLabels); ?>, datasets: [{ label: 'Cases', data: <?php echo json_encode($diagData); ?>, backgroundColor: '#0a7fb9' }] },
    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});
<?php endif; ?>
</script>

<?php include '../includes/footer.php'; ?>
