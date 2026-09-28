<?php
include '../includes/db.php';
include '../includes/auth.php';

requireRole('patient');

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

$stmt = $conn->prepare('
    SELECT u.full_name,u.email,u.phone,
           p.patient_id,p.date_of_birth,p.gender,p.blood_group,p.address,
           p.emergency_contact_name,p.emergency_contact_phone,p.family_history
    FROM users u
    JOIN patients p ON u.user_id=p.user_id
    WHERE u.user_id=?
');
$stmt->bind_param('i',$user_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();

if (!$patient) {
    die('Patient profile not found.');
}

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_profile'])) {
    $full_name=trim($_POST['full_name'] ?? '');
    $email=trim($_POST['email'] ?? '');
    $phone=trim($_POST['phone'] ?? '');
    $date_of_birth=$_POST['date_of_birth'] ?? '';
    $gender=$_POST['gender'] ?? '';
    $blood_group=trim($_POST['blood_group'] ?? '');
    $address=trim($_POST['address'] ?? '');
    $emergency_contact_name=trim($_POST['emergency_contact_name'] ?? '');
    $emergency_contact_phone=trim($_POST['emergency_contact_phone'] ?? '');
    $family_history=trim($_POST['family_history'] ?? '');

    $allowedGenders=['male','female','other'];
    $allowedBloodGroups=['A+','A-','B+','B-','AB+','AB-','O+','O-'];

    if ($full_name==='' || $email==='') {
        $error='Full name and email are required.';
    } elseif (!filter_var($email,FILTER_VALIDATE_EMAIL)) {
        $error='Please enter a valid email address.';
    } elseif ($gender!=='' && !in_array($gender,$allowedGenders,true)) {
        $error='Invalid gender selected.';
    } elseif ($blood_group!=='' && !in_array($blood_group,$allowedBloodGroups,true)) {
        $error='Invalid blood group selected.';
    } elseif ($date_of_birth!=='' && $date_of_birth>date('Y-m-d')) {
        $error='Date of birth cannot be in the future.';
    } else {
        $stmt=$conn->prepare('SELECT user_id FROM users WHERE email=? AND user_id!=?');
        $stmt->bind_param('si',$email,$user_id);
        $stmt->execute();

        if ($stmt->get_result()->num_rows>0) {
            $error='This email address is already being used.';
        } else {
            $conn->begin_transaction();

            try {
                $stmt=$conn->prepare('UPDATE users SET full_name=?,email=?,phone=? WHERE user_id=?');
                $stmt->bind_param('sssi',$full_name,$email,$phone,$user_id);
                $stmt->execute();

                $stmt=$conn->prepare('
                    UPDATE patients
                    SET date_of_birth=NULLIF(?,\'\'),
                        gender=NULLIF(?,\'\'),
                        blood_group=NULLIF(?,\'\'),
                        address=?,
                        emergency_contact_name=?,
                        emergency_contact_phone=?,
                        family_history=?
                    WHERE user_id=?
                ');
                $stmt->bind_param(
                    'sssssssi',
                    $date_of_birth,
                    $gender,
                    $blood_group,
                    $address,
                    $emergency_contact_name,
                    $emergency_contact_phone,
                    $family_history,
                    $user_id
                );
                $stmt->execute();

                $conn->commit();
                $success='Profile updated successfully.';

                $stmt=$conn->prepare('
                    SELECT u.full_name,u.email,u.phone,
                           p.patient_id,p.date_of_birth,p.gender,p.blood_group,p.address,
                           p.emergency_contact_name,p.emergency_contact_phone,p.family_history
                    FROM users u
                    JOIN patients p ON u.user_id=p.user_id
                    WHERE u.user_id=?
                ');
                $stmt->bind_param('i',$user_id);
                $stmt->execute();
                $patient=$stmt->get_result()->fetch_assoc();
            } catch (Throwable $e) {
                $conn->rollback();
                $error='Unable to update your profile. Please try again.';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['change_password'])) {
    $current_password=$_POST['current_password'] ?? '';
    $new_password=$_POST['new_password'] ?? '';
    $confirm_password=$_POST['confirm_password'] ?? '';

    $stmt=$conn->prepare('SELECT password_hash FROM users WHERE user_id=?');
    $stmt->bind_param('i',$user_id);
    $stmt->execute();
    $user=$stmt->get_result()->fetch_assoc();

    if (!password_verify($current_password,$user['password_hash'])) {
        $error='Current password is incorrect.';
    } elseif (strlen($new_password)<8) {
        $error='New password must contain at least 8 characters.';
    } elseif ($new_password!==$confirm_password) {
        $error='New passwords do not match.';
    } else {
        $password_hash=password_hash($new_password,PASSWORD_DEFAULT);

        $stmt=$conn->prepare('UPDATE users SET password_hash=? WHERE user_id=?');
        $stmt->bind_param('si',$password_hash,$user_id);
        $stmt->execute();

        $success='Password changed successfully.';
    }
}

include '../includes/header.php';
?>

<div class="main-content">
    <?php include '../includes/sidebar.php'; ?>

    <div class="content">
        <h2>My Profile</h2>
        <p class="welcome-text">
            Manage your personal details, emergency information and account security.
        </p>

        <?php if ($success): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="section">
            <h3>Personal Information</h3>

            <form method="POST" class="form-container">

                <div class="profile-grid">

                    <div class="form-group">
                        <label>Full Name *</label>
                        <input
                            type="text"
                            name="full_name"
                            value="<?php echo htmlspecialchars($patient['full_name']); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label>Email Address *</label>
                        <input
                            type="email"
                            name="email"
                            value="<?php echo htmlspecialchars($patient['email']); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label>Phone Number</label>
                        <input
                            type="text"
                            name="phone"
                            value="<?php echo htmlspecialchars($patient['phone'] ?? ''); ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input
                            type="date"
                            name="date_of_birth"
                            max="<?php echo date('Y-m-d'); ?>"
                            value="<?php echo htmlspecialchars($patient['date_of_birth'] ?? ''); ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender">
                            <option value="">Select Gender</option>
                            <option value="male" <?php echo $patient['gender']==='male'?'selected':''; ?>>Male</option>
                            <option value="female" <?php echo $patient['gender']==='female'?'selected':''; ?>>Female</option>
                            <option value="other" <?php echo $patient['gender']==='other'?'selected':''; ?>>Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Blood Group</label>
                        <select name="blood_group">
                            <option value="">Select Blood Group</option>
                            <?php
                            $bloodGroups=['A+','A-','B+','B-','AB+','AB-','O+','O-'];
                            foreach ($bloodGroups as $group):
                            ?>
                                <option value="<?php echo $group; ?>"
                                    <?php echo $patient['blood_group']===$group?'selected':''; ?>>
                                    <?php echo $group; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>

                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" rows="3"><?php echo htmlspecialchars($patient['address'] ?? ''); ?></textarea>
                </div>

                <h3 style="margin-top:30px;">Emergency Information</h3>

                <div class="profile-grid">

                    <div class="form-group">
                        <label>Emergency Contact Name</label>
                        <input
                            type="text"
                            name="emergency_contact_name"
                            value="<?php echo htmlspecialchars($patient['emergency_contact_name'] ?? ''); ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label>Emergency Contact Phone</label>
                        <input
                            type="text"
                            name="emergency_contact_phone"
                            value="<?php echo htmlspecialchars($patient['emergency_contact_phone'] ?? ''); ?>"
                        >
                    </div>

                </div>

                <div class="form-group">
                    <label>Family Medical History</label>
                    <textarea
                        name="family_history"
                        rows="4"
                        placeholder="Example: Diabetes, heart disease, high blood pressure..."
                    ><?php echo htmlspecialchars($patient['family_history'] ?? ''); ?></textarea>

                    <small>
                        Enter relevant family medical history that may help with your CarePlus health records.
                    </small>
                </div>

                <button type="submit" name="update_profile" class="btn-primary">
                    Update Profile
                </button>

            </form>
        </div>

        <div class="section" style="margin-top:25px;">
            <h3>Change Password</h3>

            <form method="POST" class="form-container">

                <div class="form-group">
                    <label>Current Password</label>
                    <input
                        type="password"
                        name="current_password"
                        required
                        autocomplete="current-password"
                    >
                </div>

                <div class="profile-grid">

                    <div class="form-group">
                        <label>New Password</label>
                        <input
                            type="password"
                            name="new_password"
                            minlength="8"
                            required
                            autocomplete="new-password"
                        >
                    </div>

                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input
                            type="password"
                            name="confirm_password"
                            minlength="8"
                            required
                            autocomplete="new-password"
                        >
                    </div>

                </div>

                <button type="submit" name="change_password" class="btn-primary">
                    Change Password
                </button>

            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>