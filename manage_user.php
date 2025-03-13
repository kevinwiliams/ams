<?php
include 'db_connect.php';

// Turn on error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verify if a valid session exists (e.g., email or id from session)
if (isset($_SESSION['login_email'])) {
    $user_email = $_SESSION['login_email']; // Assume the user email is stored in the session
    
    // Fetch user data based on the session email
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    if ($stmt === false) {
        die('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('s', $user_email);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $userinfo = $result->fetch_assoc();
            foreach ($userinfo as $k => $v) {
                $$k = $v;
            }
        } else {
            die('Assignment not found.');
        }
    }
    
} else {
    // If the session does not exist, show an error
    die('User is not logged in.');
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password = $_POST['password'] ?? '';
    $cpass = $_POST['cpass'] ?? '';

    if (!empty($password)) {
        // Check if passwords match
        if ($password !== $cpass) {
            echo "<script>alert('Passwords do not match.');</script>";
        } else {
            $password_hash = md5($password, PASSWORD_DEFAULT);

            // Update password for the logged-in user
            $sql = "UPDATE users SET password = '$password_hash' WHERE id = $user_id";

            if ($conn->query($sql)) {
                echo "<script>alert('Password updated successfully.');</script>";
            } else {
                echo "<script>alert('An error occurred while updating the password.');</script>";
            }
        }
    }
}

$conn->close();
?>

<!-- Centering the form horizontally -->
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form action="" id="manage_user" name="manage_user" method="POST">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
                    <h4>Profile - <?= htmlspecialchars($_SESSION['login_role_name'])?></h4>
                        <div class="row">
                            <div class="col-md-6 border-right">
                                <div class="form-group">
                                    <label for="empid" class="control-label">Employee ID</label>
                                    <input type="text" id="empid" name="empid" class="form-control form-control-sm" value="<?php echo htmlspecialchars($empid); ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label for="firstname" class="control-label">First Name</label>
                                    <input type="text" id="firstname" name="firstname" class="form-control form-control-sm" value="<?php echo htmlspecialchars($firstname); ?>" >
                                </div>
                                <div class="form-group">
                                    <label for="lastname" class="control-label">Last Name</label>
                                    <input type="text" id="lastname" name="lastname" class="form-control form-control-sm" value="<?php echo htmlspecialchars($lastname); ?>" >
                                </div>
                                <div class="form-group">
                                    <label for="contact_number" class="control-label">Contact Number</label>
                                    <input type="text" id="contact_number" name="contact_number" class="form-control form-control-sm" value="<?php echo htmlspecialchars($contact_number); ?>" >
                                </div>
                                <div class="form-group">
                                    <label for="address" class="control-label">Address</label>
                                    <textarea id="address" name="address" class="form-control form-control-sm" ><?php echo htmlspecialchars($address); ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email" class="control-label">Email</label>
                                    <input type="email" id="email" class="form-control form-control-sm" name="email" value="<?php echo htmlspecialchars($email); ?>" readonly>
                                </div>
                                <!-- preferred_channel -->
                            <?php
                            if (isset($preferred_channel)) {
                                $channels = explode(',', $preferred_channel);
                            }
                            ?>
                            <div class="form-group">
                                <label class="form-label">Preferred Channel</label>
                                <div class="form-check">
                                    <input 
                                        class="form-check-input" 
                                        type="checkbox" 
                                        disabled
                                        name="channels[]" 
                                        value="email" 
                                        <?php echo isset($preferred_channel) && in_array('email', $channels) ? 'checked' : '' ?>
                                    >
                                    <label class="form-check-label" for="email">Email</label>
                                </div>
                                <input type="hidden" name="channels[]" value="email" id="hEmail">

                                <div class="form-check">
                                    <input 
                                        class="form-check-input" 
                                        type="checkbox" 
                                        name="channels[]" 
                                        id="sms" 
                                        value="sms" 
                                        <?php echo isset($preferred_channel) && in_array('sms', $channels) ? 'checked' : '' ?>
                                    >
                                    <label class="form-check-label" for="sms">SMS Message</label>
                                </div>

                                <div class="form-check">
                                    <input 
                                        class="form-check-input" 
                                        type="checkbox" 
                                        name="channels[]" 
                                        id="whatsapp" 
                                        value="whatsapp" 
                                        disabled
                                        <?php echo isset($preferred_channel) && in_array('whatsapp', $channels) ? 'checked' : '' ?>
                                    >
                                    <label class="form-check-label" for="whatsapp">WhatsApp</label>
                                </div>
                                
                               
                            </div>
                             
                            </div>
                        </div>
                        <hr>
                        <div class="">
                            <button class="btn btn-primary mr-2 btn-block" type="submit"><i class="fa fa-save"></i> Update Profile</button>
                        </div>
                    </form>
                    <hr class="m-4">
                    <h4>Change Password</h4>
                    <!-- Update Password Form -->
                <form id="updatePasswordForm">
                    <div class="form-group">
                        <label for="currentPassword">Current Password</label>
                        <input type="password" class="form-control" id="currentPassword" name="currentPassword" required autocomplete="false">
                    </div>
                    <div class="form-group">
                        <label for="newPassword">New Password</label>
                        <input type="password" class="form-control" id="newPassword" name="newPassword" required autocomplete="false">
                    </div>
                    <div class="form-group">
                        <label for="confirmPassword">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required autocomplete="false">
                    </div>
                    <button type="submit" class="btn btn-warning btn-block">Change Password</button>
                </form>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Cleave.js -->
<script src="https://cdn.jsdelivr.net/npm/cleave.js@1.6.0/dist/cleave.min.js"></script>
<!-- Phone-Type-Formatter for US (includes Jamaica) -->
<script src="https://cdn.jsdelivr.net/npm/cleave.js@1.6.0/dist/addons/cleave-phone.us.js"></script>

<script>
$(document).ready(function(){
    const cleave = new Cleave('#contact_number', {
                phone: true,
                phoneRegionCode: 'US', // Jamaica country code
                prefix: '+1',
                delimiter: '-',
                blocks: [2, 3, 3, 4], // Format: +1-876-555-8888
            });

    // for multiselect dropdowns
    $('.custom-select-sm').select2();
    
    $('#manage_user').submit(function(e){
        e.preventDefault();
        start_load(); 
        
        $.ajax({
            url: 'ajax.php?action=save_user',
            method: 'POST',
            data: $(this).serialize(),
            error: function(err) {
                console.log(err);
                end_load(); 
                // Show error alert
                alert_toast('Something went wrong. Please try again later.', 'error');

            },
            success: function(resp) {
                console.log(resp);
                end_load(); 

                if (resp == 1) {
                    // Show success alert
                    alert_toast('Profile updated', 'success');
                    setTimeout(() => {
                        location.href = 'index.php?page=manage_user'; // Redirect after success
                    }, 2800);

                } else {
                    alert_toast(resp + ' already exists!', 'error');
                }
            }
        });
    });

    // Update Password
    $('#updatePasswordForm').on('submit', function (e) {
        e.preventDefault();
        if ($('#newPassword').val() !== $('#confirmPassword').val()) {
            alert_toast('New password and confirm password do not match!', 'error');
            return;
        }

        $.ajax({
            url: 'ajax.php?action=update_password',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (response) {
                console.log(response);
                if (response.status === 'success') {
                    // Show success alert
                    alert_toast('Password updated successfully!', 'success');
                    setTimeout(() => {
                        location.href = 'index.php?page=manage_user'; // Redirect after success
                    }, 2500);
                    
                } else {
                    alert_toast(response.message, 'error');
                }
            }
        });
    });
});                                        
</script>
