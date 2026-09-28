<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$input = trim($_POST['message'] ?? '');
$reply = getBotReply($input, $conn);
echo json_encode(['reply' => $reply]);

function getBotReply($msg, $conn) {
    $m = strtolower($msg);

    if ($m === '') return "I didn't catch that — could you rephrase?";

    if (str_contains($m, 'hour') || str_contains($m, 'open') || str_contains($m, 'close')) {
        return "CarePlus Medical Centre is open Monday–Friday, 9:00 AM–6:00 PM. Our emergency department is open 24/7.";
    }

    if (str_contains($m, 'doctor') || str_contains($m, 'specialist') || str_contains($m, 'available')) {
        $res = $conn->query("SELECT u.full_name, d.specialisation FROM doctors d JOIN users u ON d.user_id = u.user_id LIMIT 8");
        $list = [];
        while ($row = $res->fetch_assoc()) {
            $list[] = $row['full_name'] . ' (' . $row['specialisation'] . ')';
        }
        return "Here are our doctors:\n" . implode("\n", $list);
    }

    if (str_contains($m, 'book') || str_contains($m, 'appointment') || str_contains($m, 'schedule')) {
        return "You can book an appointment from your Dashboard → 'Book New Appointment', or go directly to patient/book_appointment.php. Not sure which doctor to see? Try the Symptom Checker first.";
    }

    if (str_contains($m, 'symptom') || str_contains($m, 'sick') || str_contains($m, 'unwell') || str_contains($m, 'pain')) {
        return "I'm sorry you're not feeling well. Try our AI Symptom Checker (patient/symptom_checker.php) for a quick assessment and doctor recommendation. If this is an emergency, please use the Emergency SOS button or call emergency services.";
    }

    if (str_contains($m, 'emergency') || str_contains($m, 'urgent') || str_contains($m, 'help me')) {
        return "If this is a medical emergency, please call your local emergency number immediately, or use the Emergency SOS button in your dashboard.";
    }

    if (str_contains($m, 'medication') || str_contains($m, 'medicine') || str_contains($m, 'reminder')) {
        return "You can set up medicine reminders under 'Medicine Reminders' in your dashboard — I'll notify you at your chosen times.";
    }

    if (str_contains($m, 'password') || str_contains($m, 'login') || str_contains($m, 'account')) {
        return "For login issues, use the 'Forgot password' link on the login page, or contact reception at admin@careplus.com.";
    }

    if (str_contains($m, 'hi') || str_contains($m, 'hello') || str_contains($m, 'hey')) {
        return "Hello! I'm the CarePlus Assistant. I can help with hospital hours, finding a doctor, booking appointments, or general questions.";
    }

    if (str_contains($m, 'thank')) {
        return "You're welcome! Let me know if there's anything else I can help with.";
    }

    return "I can help with hospital hours, doctor availability, booking appointments, medication reminders, or symptom guidance. Could you tell me a bit more about what you need?";
}
