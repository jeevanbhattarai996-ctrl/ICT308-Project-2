<?php
// Sidebar navigation
$role = $_SESSION['role'] ?? '';
?>
<aside class="sidebar">
    <nav>
        <ul>
            <?php if ($role === 'patient'): ?>
                <li><a href="/careplus_hms/patient/dashboard.php" class="nav-link">Dashboard</a></li>
                <li><a href="/careplus_hms/patient/book_appointment.php" class="nav-link">Book Appointment</a></li>
                <li><a href="/careplus_hms/patient/my_appointments.php" class="nav-link">My Appointments</a></li>
                <li><a href="/careplus_hms/patient/symptom_checker.php" class="nav-link">🩺 Symptom Checker</a></li>
                <li><a href="/careplus_hms/patient/health_dashboard.php" class="nav-link">❤️ Health Dashboard</a></li>
                <li><a href="/careplus_hms/patient/risk_score.php" class="nav-link">🧠 Risk Assessment</a></li>
                <li><a href="/careplus_hms/patient/medications.php" class="nav-link">💊 Medicine Reminder</a></li>
                <li><a href="/careplus_hms/patient/health_records.php" class="nav-link">📁 Health Records</a></li>
                <li><a href="/careplus_hms/patient/qr_card.php" class="nav-link">📱 Medical Card</a></li>
                <li><a href="/careplus_hms/patient/ai_chat.php" class="nav-link">🤖 AI Chat Assistant</span> </a></li>
                <li><a href="/careplus_hms/patient/notifications.php" class="nav-link">Notifications</a></li>
                <li><a href="/careplus_hms/patient/profile.php" class="nav-link">👤 My Profile</span> </a></li>
                <li><a href="/careplus_hms/patient/emergency_sos.php" class="nav-link sos-nav-link">🔔 Emergency SOS</a></li>
            <?php elseif ($role === 'doctor'): ?>
                <li><a href="/careplus_hms/doctor/dashboard.php" class="nav-link">Dashboard</a></li>
                <li><a href="/careplus_hms/doctor/appointments.php" class="nav-link">My Appointments</a></li>
                <li><a href="/careplus_hms/doctor/patient_records.php" class="nav-link">Patient Records</a></li>
                <li><a href="/careplus_hms/doctor/analytics.php" class="nav-link">📊 Analytics</a></li>
            <?php elseif ($role === 'admin'): ?>
                <li><a href="/careplus_hms/admin/dashboard.php" class="nav-link">Dashboard</a></li>
                <li><a href="/careplus_hms/admin/manage_appointments.php" class="nav-link">Manage Appointments</a></li>
                <li><a href="/careplus_hms/admin/manage_patients.php" class="nav-link">Manage Patients</a></li>
                <li><a href="/careplus_hms/admin/manage_doctors.php" class="nav-link">Manage Doctors</a></li>
                <li><a href="/careplus_hms/admin/analytics.php" class="nav-link">📊 Analytics</a></li>
                <li><a href="/careplus_hms/admin/sos_alerts.php" class="nav-link sos-nav-link">🔔 SOS Alerts</a></li>
            <?php elseif ($role === 'manager'): ?>
                <li><a href="/careplus_hms/manager/dashboard.php" class="nav-link">Dashboard</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</aside>
