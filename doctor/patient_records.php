<?php
include '../includes/db.php';
include '../includes/auth.php';

requireRole('doctor');

$user_id = $_SESSION['user_id'];

// Get doctor ID
$stmt = $conn->prepare('SELECT doctor_id FROM doctors WHERE user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc();
$doctor_id = $doctor['doctor_id'];

// Search patients
$search = trim($_GET['search'] ?? '');
$query = 'SELECT DISTINCT p.patient_id, u.full_name, u.email, u.phone FROM patients p JOIN users u ON p.user_id = u.user_id WHERE p.patient_id IN (SELECT DISTINCT patient_id FROM appointments WHERE doctor_id = ?)';
$params = [$doctor_id];
$types = 'i';

if (!empty($search)) {
    $query .= ' AND (u.full_name LIKE ? OR u.email LIKE ?)';
    $search_param = '%' . $search . '%';
    $params = [$doctor_id, $search_param, $search_param];
    $types = 'iss';
}

$query .= ' ORDER BY u.full_name';

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$patients = $stmt->get_result();

include '../includes/header.php';
?>

<div class="main-content">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="content">
        <h2>Patient Records</h2>
        
        <form method="GET" class="search-form">
            <input type="text" name="search" placeholder="Search patient by name or email" value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn-primary">Search</button>
        </form>
        
        <?php if ($patients->num_rows > 0): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Patient Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($patient = $patients->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($patient['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($patient['email']); ?></td>
                            <td><?php echo htmlspecialchars($patient['phone'] ?? 'N/A'); ?></td>
                            <td>
                                <a href="add_record.php?patient_id=<?php echo $patient['patient_id']; ?>" class="btn-small">Add Record</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No patient records found.</p>
        <?php endif; ?>
        
        <a href="dashboard.php" class="btn-secondary">Back to Dashboard</a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
