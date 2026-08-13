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
                <li><a href="/careplus_hms/patient/notifications.php" class="nav-link">Notifications</a></li>
            <?php elseif ($role === 'doctor'): ?>
                <li><a href="/careplus_hms/doctor/dashboard.php" class="nav-link">Dashboard</a></li>
                <li><a href="/careplus_hms/doctor/appointments.php" class="nav-link">My Appointments</a></li>
                <li><a href="/careplus_hms/doctor/patient_records.php" class="nav-link">Patient Records</a></li>
            <?php elseif ($role === 'admin'): ?>
                <li><a href="/careplus_hms/admin/dashboard.php" class="nav-link">Dashboard</a></li>
                <li><a href="/careplus_hms/admin/manage_appointments.php" class="nav-link">Manage Appointments</a></li>
                <li><a href="/careplus_hms/admin/manage_patients.php" class="nav-link">Manage Patients</a></li>
                <li><a href="/careplus_hms/admin/manage_doctors.php" class="nav-link">Manage Doctors</a></li>
            <?php elseif ($role === 'manager'): ?>
                <li><a href="/careplus_hms/manager/dashboard.php" class="nav-link">Dashboard</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</aside>
