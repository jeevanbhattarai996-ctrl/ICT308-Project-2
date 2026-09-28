<?php
include '../includes/db.php';
include '../includes/auth.php';

requireRole('doctor');

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare('SELECT doctor_id, consultation_fee FROM doctors WHERE user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc();
$doctor_id = $doctor['doctor_id'];
$fee = (float) ($doctor['consultation_fee'] ?? 50);

// Today's appointments
$stmt = $conn->prepare('SELECT COUNT(*) as total FROM appointments WHERE doctor_id = ? AND appointment_date = CURDATE()');
$stmt->bind_param('i', $doctor_id);
$stmt->execute();
$todayCount = $stmt->get_result()->fetch_assoc()['total'];

// Appointments per day, last 14 days
$stmt = $conn->prepare('SELECT appointment_date, COUNT(*) as total FROM appointments WHERE doctor_id = ? AND appointment_date BETWEEN DATE_SUB(CURDATE(), INTERVAL 13 DAY) AND CURDATE() GROUP BY appointment_date ORDER BY appointment_date');
$stmt->bind_param('i', $doctor_id);
$stmt->execute();
$dailyRaw = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$dailyMap = [];
foreach ($dailyRaw as $row) $dailyMap[$row['appointment_date']] = (int)$row['total'];
$dailyLabels = [];
$dailyData = [];
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i day"));
    $dailyLabels[] = date('M j', strtotime($d));
    $dailyData[] = $dailyMap[$d] ?? 0;
}

// This month's estimated income (completed appointments x consultation fee)
$stmt = $conn->prepare('SELECT COUNT(*) as total FROM appointments WHERE doctor_id = ? AND status = "completed" AND MONTH(appointment_date) = MONTH(CURDATE()) AND YEAR(appointment_date) = YEAR(CURDATE())');
$stmt->bind_param('i', $doctor_id);
$stmt->execute();
$completedThisMonth = (int) $stmt->get_result()->fetch_assoc()['total'];
$monthlyIncome = $completedThisMonth * $fee;

// Patient satisfaction (average rating)
$stmt = $conn->prepare('SELECT AVG(r.rating) as avg_rating, COUNT(r.rating) as total FROM appointment_ratings r JOIN appointments a ON r.appointment_id = a.appointment_id WHERE a.doctor_id = ?');
$stmt->bind_param('i', $doctor_id);
$stmt->execute();
$satisfaction = $stmt->get_result()->fetch_assoc();
$avgRating = $satisfaction['avg_rating'] ? round($satisfaction['avg_rating'], 1) : null;

// Most common diagnoses
$stmt = $conn->prepare('SELECT diagnosis, COUNT(*) as total FROM medical_records WHERE doctor_id = ? AND diagnosis IS NOT NULL AND diagnosis != "" GROUP BY diagnosis ORDER BY total DESC LIMIT 6');
$stmt->bind_param('i', $doctor_id);
$stmt->execute();
$diagnosesRaw = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$diagLabels = array_column($diagnosesRaw, 'diagnosis');
$diagData = array_map('intval', array_column($diagnosesRaw, 'total'));

// Appointment status breakdown (all-time, this doctor)
$stmt = $conn->prepare('SELECT status, COUNT(*) as total FROM appointments WHERE doctor_id = ? GROUP BY status');
$stmt->bind_param('i', $doctor_id);
$stmt->execute();
$statusRaw = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$statusMap = ['pending' => 0, 'confirmed' => 0, 'completed' => 0, 'cancelled' => 0];
foreach ($statusRaw as $row) $statusMap[$row['status']] = (int)$row['total'];

include '../includes/header.php';
?>

<div class="main-content">
    <?php include '../includes/sidebar.php'; ?>

    <div class="content">
        <h2>📊 My Analytics</h2>
        <p class="welcome-text">Your appointment activity, estimated income and patient feedback at a glance.</p>

        <div class="dashboard-grid">
            <div class="card highlight">
                <h3>Today's Appointments</h3>
                <p class="stat-number"><?php echo $todayCount; ?></p>
            </div>
            <div class="card">
                <h3>Completed This Month</h3>
                <p class="stat-number"><?php echo $completedThisMonth; ?></p>
            </div>
            <div class="card">
                <h3>Est. Monthly Income</h3>
                <p class="stat-number">$<?php echo number_format($monthlyIncome, 2); ?></p>
            </div>
            <div class="card">
                <h3>Patient Satisfaction</h3>
                <p class="stat-number"><?php echo $avgRating !== null ? $avgRating . ' / 5' : '—'; ?></p>
                <p class="welcome-text"><?php echo (int)($satisfaction['total'] ?? 0); ?> ratings received</p>
            </div>
        </div>

        <div class="chart-grid">
            <div class="chart-card">
                <h4>Appointments — Last 14 Days</h4>
                <div class="chart-canvas-wrap"><canvas id="dailyChart"></canvas></div>
            </div>
            <div class="chart-card">
                <h4>Appointment Status Breakdown</h4>
                <div class="chart-canvas-wrap"><canvas id="statusChart"></canvas></div>
            </div>
            <div class="chart-card">
                <h4>Most Common Diagnoses</h4>
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
new Chart(document.getElementById('dailyChart'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($dailyLabels); ?>,
        datasets: [{ label: 'Appointments', data: <?php echo json_encode($dailyData); ?>, borderColor: '#0a7fb9', backgroundColor: 'rgba(10,127,185,0.1)', fill: true, tension: 0.3 }]
    },
    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});

new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: ['Pending', 'Confirmed', 'Completed', 'Cancelled'],
        datasets: [{ data: <?php echo json_encode(array_values($statusMap)); ?>, backgroundColor: ['#f39c12', '#0a7fb9', '#27ae60', '#e74c3c'] }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});

<?php if (!empty($diagLabels)): ?>
new Chart(document.getElementById('diagChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($diagLabels); ?>,
        datasets: [{ label: 'Cases', data: <?php echo json_encode($diagData); ?>, backgroundColor: '#2c5aa0' }]
    },
    options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});
<?php endif; ?>
</script>

<?php include '../includes/footer.php'; ?>
