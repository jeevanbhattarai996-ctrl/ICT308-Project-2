<?php
/**
 * Medication Reminder cron job.
 *
 * WHAT THIS DOES:
 *   Every minute (or every 5 minutes), checks which active medications have
 *   a reminder_time matching "now" and (a) creates an in-app notification
 *   and (b) emails the patient via PHP's mail().
 *
 * HOW TO SET IT UP ON A REAL SERVER:
 *   1. Make sure your server's PHP mail() is configured (or swap the
 *      sendReminderEmail() function below for PHPMailer + SMTP, which is
 *      more reliable than mail() on most hosts).
 *   2. Add a cron entry, e.g. run every minute:
 *        * * * * * php /path/to/careplus_hms/cron/medication_reminder.php
 *   3. Push notifications would additionally require a service worker +
 *      the Web Push API (or Firebase Cloud Messaging) with VAPID keys —
 *      not included here since it needs your own domain/HTTPS certificate.
 *   4. SMS reminders would require a paid SMS gateway (e.g. Twilio) — the
 *      sendReminderSms() stub below shows where that call would go.
 */

require_once __DIR__ . '/../includes/db.php';

$now = new DateTime();
$currentTime = $now->format('H:i');
$today = $now->format('Y-m-d');

$sql = "SELECT m.*, u.email, u.full_name, u.user_id
        FROM medications m
        JOIN patients p ON m.patient_id = p.patient_id
        JOIN users u ON p.user_id = u.user_id
        WHERE m.active = 1
          AND (m.end_date IS NULL OR m.end_date >= ?)
          AND m.start_date <= ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $today, $today);
$stmt->execute();
$result = $stmt->get_result();

$sentCount = 0;

while ($med = $result->fetch_assoc()) {
    $times = array_map('trim', explode(',', $med['reminder_times']));
    if (!in_array($currentTime, $times)) continue;

    // Avoid duplicate reminders for the same minute
    $checkStmt = $conn->prepare("SELECT log_id FROM medication_logs WHERE medication_id = ? AND log_date = ? AND log_time = ?");
    $logTime = $currentTime . ':00';
    $checkStmt->bind_param('iss', $med['medication_id'], $today, $logTime);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) continue;

    $message = "💊 Reminder: it's time to take {$med['medicine_name']}" . ($med['dosage'] ? " ({$med['dosage']})" : '') . '.';

    $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, message, notification_type) VALUES (?, ?, 'reminder')");
    $notifStmt->bind_param('is', $med['user_id'], $message);
    $notifStmt->execute();

    sendReminderEmail($med['email'], $med['full_name'], $message);
    // sendReminderSms($med['phone'], $message); // requires SMS gateway credentials

    $sentCount++;
}

echo "Reminder check complete at $currentTime. Sent: $sentCount\n";

function sendReminderEmail($email, $name, $message) {
    $subject = 'CarePlus Medication Reminder';
    $body = "Hi $name,\n\n$message\n\n— CarePlus Healthcare Management System";
    $headers = 'From: no-reply@careplus.example';
    @mail($email, $subject, $body, $headers);
}

function sendReminderSms($phone, $message) {
    // Example using Twilio (requires composer require twilio/sdk and API credentials):
    //
    // $sid = 'YOUR_TWILIO_SID';
    // $token = 'YOUR_TWILIO_TOKEN';
    // $twilio = new Twilio\Rest\Client($sid, $token);
    // $twilio->messages->create($phone, [
    //     'from' => 'YOUR_TWILIO_NUMBER',
    //     'body' => $message,
    // ]);
}
