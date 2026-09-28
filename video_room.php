<?php
include 'includes/db.php';
include 'includes/auth.php';

requireLogin();

$appointment_id = intval($_GET['appointment_id'] ?? 0);
$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

if (!in_array($role, ['patient', 'doctor'])) {
    header('Location: /careplus_hms/index.php');
    exit;
}

// Verify this user is actually a participant in this video appointment
if ($role === 'patient') {
    $stmt = $conn->prepare('SELECT a.*, u.full_name as other_name FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN doctors d ON a.doctor_id = d.doctor_id
        JOIN users u ON d.user_id = u.user_id
        WHERE a.appointment_id = ? AND p.user_id = ? AND a.appointment_type = "video"');
} else {
    $stmt = $conn->prepare('SELECT a.*, u.full_name as other_name FROM appointments a
        JOIN doctors d ON a.doctor_id = d.doctor_id
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN users u ON p.user_id = u.user_id
        WHERE a.appointment_id = ? AND d.user_id = ? AND a.appointment_type = "video"');
}
$stmt->bind_param('ii', $appointment_id, $user_id);
$stmt->execute();
$appt = $stmt->get_result()->fetch_assoc();

if (!$appt) {
    include 'includes/header.php';
    echo '<div class="main-content">';
    include 'includes/sidebar.php';
    echo '<div class="content"><div class="error-message">Video call not found, not a video appointment, or you do not have access to it.</div></div></div>';
    include 'includes/footer.php';
    exit;
}

if ($appt['status'] !== 'confirmed' && $appt['status'] !== 'completed') {
    include 'includes/header.php';
    echo '<div class="main-content">';
    include 'includes/sidebar.php';
    echo '<div class="content"><div class="error-message">This appointment is not confirmed yet, so the call is not open.</div></div></div>';
    include 'includes/footer.php';
    exit;
}

$roomCode = $appt['video_room'];
include 'includes/header.php';
?>

<div class="main-content">
    <?php include 'includes/sidebar.php'; ?>

    <div class="content">
        <h2>Video Consultation</h2>
        <p class="welcome-text">With <?php echo htmlspecialchars($appt['other_name']); ?> &middot; <?php echo date('M j, Y g:i A', strtotime($appt['appointment_date'] . ' ' . $appt['appointment_time'])); ?></p>

        <div id="call-status" class="welcome-text">Setting up your camera...</div>

        <div class="video-room">
            <div class="video-tile">
                <video id="local-video" autoplay playsinline muted></video>
                <span class="video-tile-label">You</span>
            </div>
            <div class="video-tile">
                <video id="remote-video" autoplay playsinline></video>
                <span class="video-tile-label"><?php echo htmlspecialchars($appt['other_name']); ?></span>
            </div>
        </div>

        <div class="video-controls">
            <button id="toggle-mic" class="btn-secondary">🎤 Mute</button>
            <button id="toggle-cam" class="btn-secondary">📷 Camera off</button>
            <button id="hangup" class="btn-primary" style="background: var(--danger-color);">📞 Leave call</button>
        </div>

        <p class="welcome-text" style="margin-top:20px; font-size:12px;">
            This uses real WebRTC peer-to-peer video via your browser's camera/microphone, with a lightweight
            database-polling signalling channel (no dedicated signalling server required). Video/audio never
            passes through the server once the connection is established.
        </p>
    </div>
</div>

<script>
const ROOM_CODE = <?php echo json_encode($roomCode); ?>;
const MY_ROLE = <?php echo json_encode($role); ?>;
const OTHER_ROLE = MY_ROLE === 'patient' ? 'doctor' : 'patient';
const SIGNAL_URL = '/careplus_hms/video_signal.php';

let pc, localStream, lastSignalId = 0, polling;

const statusEl = document.getElementById('call-status');
const localVideo = document.getElementById('local-video');
const remoteVideo = document.getElementById('remote-video');

async function sendSignal(type, payload) {
    const form = new FormData();
    form.append('room_code', ROOM_CODE);
    form.append('role', MY_ROLE);
    form.append('type', type);
    form.append('payload', JSON.stringify(payload));
    await fetch(SIGNAL_URL, { method: 'POST', body: form });
}

async function pollSignals() {
    try {
        const res = await fetch(`${SIGNAL_URL}?room_code=${encodeURIComponent(ROOM_CODE)}&since=${lastSignalId}&from=${OTHER_ROLE}`);
        const data = await res.json();
        for (const sig of data.signals) {
            lastSignalId = Math.max(lastSignalId, sig.signal_id);
            const payload = JSON.parse(sig.payload);
            if (sig.signal_type === 'offer') {
                await pc.setRemoteDescription(new RTCSessionDescription(payload));
                const answer = await pc.createAnswer();
                await pc.setLocalDescription(answer);
                sendSignal('answer', answer);
            } else if (sig.signal_type === 'answer') {
                await pc.setRemoteDescription(new RTCSessionDescription(payload));
            } else if (sig.signal_type === 'candidate') {
                try { await pc.addIceCandidate(payload); } catch (e) {}
            } else if (sig.signal_type === 'hangup') {
                statusEl.textContent = 'The other participant left the call.';
            }
        }
    } catch (e) { /* keep polling */ }
}

async function start() {
    try {
        localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
        localVideo.srcObject = localStream;
    } catch (e) {
        statusEl.textContent = 'Could not access camera/microphone: ' + e.message;
        return;
    }

    pc = new RTCPeerConnection({ iceServers: [{ urls: 'stun:stun.l.google.com:19302' }] });
    localStream.getTracks().forEach(track => pc.addTrack(track, localStream));

    pc.ontrack = (event) => {
        remoteVideo.srcObject = event.streams[0];
        statusEl.textContent = 'Connected.';
    };

    pc.onicecandidate = (event) => {
        if (event.candidate) sendSignal('candidate', event.candidate);
    };

    pc.onconnectionstatechange = () => {
        if (pc.connectionState === 'connecting') statusEl.textContent = 'Connecting to the other participant...';
    };

    // The patient initiates the offer; the doctor waits for it.
    if (MY_ROLE === 'patient') {
        statusEl.textContent = 'Waiting for the doctor to join...';
        const offer = await pc.createOffer();
        await pc.setLocalDescription(offer);
        sendSignal('offer', offer);
    } else {
        statusEl.textContent = 'Waiting for the patient to connect...';
    }

    polling = setInterval(pollSignals, 1500);
}

document.getElementById('toggle-mic').addEventListener('click', function () {
    if (!localStream) return;
    const track = localStream.getAudioTracks()[0];
    track.enabled = !track.enabled;
    this.textContent = track.enabled ? '🎤 Mute' : '🎤 Unmute';
});

document.getElementById('toggle-cam').addEventListener('click', function () {
    if (!localStream) return;
    const track = localStream.getVideoTracks()[0];
    track.enabled = !track.enabled;
    this.textContent = track.enabled ? '📷 Camera off' : '📷 Camera on';
});

document.getElementById('hangup').addEventListener('click', function () {
    sendSignal('hangup', {});
    if (polling) clearInterval(polling);
    if (pc) pc.close();
    if (localStream) localStream.getTracks().forEach(t => t.stop());
    window.location.href = MY_ROLE === 'patient' ? '/careplus_hms/patient/my_appointments.php' : '/careplus_hms/doctor/appointments.php';
});

start();
</script>

<?php include 'includes/footer.php'; ?>
