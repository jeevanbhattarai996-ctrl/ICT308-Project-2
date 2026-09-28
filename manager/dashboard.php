<?php
include '../includes/db.php';
include '../includes/auth.php';

requireRole('manager');

$this_week_start = date('Y-m-d', strtotime('monday this week'));
$this_week_end = date('Y-m-d', strtotime('sunday this week'));

// Total appointments this week
$stmt = $conn->prepare('SELECT COUNT(*) as total FROM appointments WHERE appointment_date BETWEEN ? AND ?');
$stmt->bind_param('ss', $this_week_start, $this_week_end);
$stmt->execute();
$week_appts = $stmt->get_result()->fetch_assoc();

// Missed or cancelled appointments
$stmt = $conn->query('SELECT COUNT(*) as total FROM appointments WHERE status IN ("cancelled") AND appointment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)');
$cancelled = $stmt->fetch_assoc();

// New patients this week
$stmt = $conn->prepare('SELECT COUNT(*) as total FROM users WHERE role = "patient" AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)');
$stmt->execute();
$new_patients = $stmt->get_result()->fetch_assoc();

// Doctor utilization
$stmt = $conn->query('SELECT u.full_name, COUNT(a.appointment_id) as appointment_count FROM doctors d JOIN users u ON d.user_id = u.user_id LEFT JOIN appointments a ON d.doctor_id = a.doctor_id GROUP BY d.doctor_id ORDER BY appointment_count DESC LIMIT 5');
$doctor_util = $stmt;

// Appointment status summary
$stmt = $conn->query('SELECT status, COUNT(*) as total FROM appointments WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY status');
$status_summary = $stmt;

include '../includes/header.php';
?>

<div class="main-content">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="content">
        <h2>Clinic Manager Dashboard</h2>
        
        <div class="dashboard-grid">
            <div class="card">
                <h3>Appointments This Week</h3>
                <p class="stat-number"><?php echo $week_appts['total']; ?></p>
            </div>
            
            <div class="card">
                <h3>Cancelled Appointments (30 days)</h3>
                <p class="stat-number"><?php echo $cancelled['total']; ?></p>
            </div>
            
            <div class="card">
                <h3>New Patients (7 days)</h3>
                <p class="stat-number"><?php echo $new_patients['total']; ?></p>
            </div>
        </div>
        
        <div class="section">
            <h3>Doctor Utilization</h3>
            <?php if ($doctor_util->num_rows > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Doctor Name</th>
                            <th>Total Appointments</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($doctor = $doctor_util->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($doctor['full_name']); ?></td>
                                <td><?php echo $doctor['appointment_count']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No doctor data available.</p>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h3>Appointment Summary (Last 30 Days)</h3>
            <?php if ($status_summary->num_rows > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($status = $status_summary->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo ucfirst($status['status']); ?></td>
                                <td><?php echo $status['total']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No appointment data available.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
