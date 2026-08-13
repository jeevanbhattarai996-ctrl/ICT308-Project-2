<?php
include '../includes/db.php';
include '../includes/auth.php';

requireRole('admin');

// Get all patients
$stmt = $conn->prepare('SELECT u.user_id, u.full_name, u.email, u.phone, p.date_of_birth, p.gender FROM patients p JOIN users u ON p.user_id = u.user_id ORDER BY u.full_name');
$stmt->execute();
$patients = $stmt->get_result();

include '../includes/header.php';
?>

<div class="main-content">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="content">
        <h2>Manage Patients</h2>
        
        <?php if ($patients->num_rows > 0): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Gender</th>
                        <th>Date of Birth</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($patient = $patients->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($patient['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($patient['email']); ?></td>
                            <td><?php echo htmlspecialchars($patient['phone'] ?? 'N/A'); ?></td>
                            <td><?php echo ucfirst($patient['gender'] ?? 'N/A'); ?></td>
                            <td><?php echo !empty($patient['date_of_birth']) ? date('M j, Y', strtotime($patient['date_of_birth'])) : 'N/A'; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No patients found.</p>
        <?php endif; ?>
        
        <a href="dashboard.php" class="btn-secondary">Back to Dashboard</a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
