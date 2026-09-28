<?php
// Lightweight WebRTC signalling endpoint: stores/retrieves SDP offers,
// answers, and ICE candidates for a given room via simple DB polling.
include 'includes/db.php';
include 'includes/auth.php';

requireLogin();
header('Content-Type: application/json');

$role = $_SESSION['role'];
if (!in_array($role, ['patient', 'doctor'])) {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room = trim($_POST['room_code'] ?? '');
    $senderRole = ($_POST['role'] ?? '') === 'doctor' ? 'doctor' : 'patient';
    $type = $_POST['type'] ?? '';
    $payload = $_POST['payload'] ?? '{}';

    if ($room === '' || !in_array($type, ['offer', 'answer', 'candidate', 'hangup'])) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid request']);
        exit;
    }

    // Confirm this room actually belongs to a video appointment this user is part of
    $check = $conn->prepare('SELECT appointment_id FROM appointments WHERE video_room = ?');
    $check->bind_param('s', $room);
    $check->execute();
    if ($check->get_result()->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'room not found']);
        exit;
    }

    $stmt = $conn->prepare('INSERT INTO video_signals (room_code, sender_role, signal_type, payload) VALUES (?,?,?,?)');
    $stmt->bind_param('ssss', $room, $senderRole, $type, $payload);
    $stmt->execute();
    echo json_encode(['ok' => true]);
    exit;
}

// GET: poll for new signals from the other participant
$room = trim($_GET['room_code'] ?? '');
$since = intval($_GET['since'] ?? 0);
$from = ($_GET['from'] ?? '') === 'doctor' ? 'doctor' : 'patient';

$stmt = $conn->prepare('SELECT signal_id, sender_role, signal_type, payload FROM video_signals WHERE room_code = ? AND sender_role = ? AND signal_id > ? ORDER BY signal_id ASC LIMIT 50');
$stmt->bind_param('ssi', $room, $from, $since);
$stmt->execute();
$signals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode(['signals' => $signals]);
