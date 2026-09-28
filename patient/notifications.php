<?php
include '../includes/db.php';
include '../includes/auth.php';

requireRole('patient');

$user_id = $_SESSION['user_id'];

// Get notifications
$stmt = $conn->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY sent_at DESC LIMIT 20');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$notifications = $stmt->get_result();

// Mark as read if notification_id is provided
if (isset($_GET['read'])) {
    $notification_id = intval($_GET['read']);
    $update = $conn->prepare('UPDATE notifications SET status = "read" WHERE notification_id = ? AND user_id = ?');
    $update->bind_param('ii', $notification_id, $user_id);
    $update->execute();
    header('Location: notifications.php');
    exit;
}

include '../includes/header.php';
?>

<div class="main-content">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="content">
        <h2>Notifications</h2>
        
        <?php if ($notifications->num_rows > 0): ?>
            <div class="notification-container">
                <?php while ($notif = $notifications->fetch_assoc()): ?>
                    <div class="notification-card <?php echo $notif['status'] === 'unread' ? 'unread' : ''; ?>">
                        <p><?php echo htmlspecialchars($notif['message']); ?></p>
                        <small><?php echo date('l, F j, Y g:i A', strtotime($notif['sent_at'])); ?></small>
                        <?php if ($notif['status'] === 'unread'): ?>
                            <a href="?read=<?php echo $notif['notification_id']; ?>" class="mark-read">Mark as read</a>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p>You have no notifications.</p>
        <?php endif; ?>
        
        <a href="dashboard.php" class="btn-secondary">Back to Dashboard</a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
