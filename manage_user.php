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
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-user-circle me-2"></i> My Profile - <?= htmlspecialchars($_SESSION['login_role_name']) ?></h4>
                </div>
                
                <div class="card-body">
                    <!-- Profile Update Form -->
                    <form id="manage_user" method="POST" class="mb-4">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
                        
                        <div class="row g-3">
                            <!-- Left Column -->
                            <div class="col-md-6 border-end">
                                <div class="mb-3">
                                    <label for="empid" class="form-label">Employee ID</label>
                                    <input type="text" class="form-control" id="empid" name="empid" 
                                           value="<?= htmlspecialchars($empid) ?>" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="firstname" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="firstname" name="firstname" 
                                           value="<?= htmlspecialchars($firstname) ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="lastname" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="lastname" name="lastname" 
                                           value="<?= htmlspecialchars($lastname) ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="contact_number" class="form-label">Contact Number</label>
                                    <input type="tel" class="form-control" id="contact_number" name="contact_number" 
                                           value="<?= htmlspecialchars($contact_number) ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea class="form-control" id="address" name="address" 
                                              rows="3"><?= htmlspecialchars($address) ?></textarea>
                                </div>
                            </div>
                            
                            <!-- Right Column -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?= htmlspecialchars($email) ?>" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Notification Preferences</label>
                                    <div class="card border-0 shadow-sm">
                                        <div class="card-body">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" disabled checked>
                                                <label class="form-check-label">Email (required)</label>
                                                <input type="hidden" name="channels[]" value="email">
                                            </div>
                                            
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="channels[]" 
                                                       id="sms" value="sms" 
                                                       <?= isset($preferred_channel) && in_array('sms', explode(',', $preferred_channel)) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="sms">SMS Text Message</label>
                                            </div>
                                            
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" disabled
                                                       <?= isset($preferred_channel) && in_array('whatsapp', explode(',', $preferred_channel)) ? 'checked' : '' ?>>
                                                <label class="form-check-label">WhatsApp (coming soon)</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i> Update Profile
                            </button>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                    
                    <!-- Password Change Form -->
                    <div class="card border-warning">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="fas fa-key me-2"></i> Change Password</h5>
                        </div>
                        <div class="card-body">
                            <form id="updatePasswordForm">
                                <div class="mb-3">
                                    <label for="currentPassword" class="form-label">Current Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="currentPassword" 
                                               name="currentPassword" required autocomplete="off">
                                        <button class="btn btn-outline-secondary toggle-password" type="button">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="newPassword" class="form-label">New Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="newPassword" 
                                               name="newPassword" required autocomplete="off">
                                        <button class="btn btn-outline-secondary toggle-password" type="button">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Minimum 8 characters with at least one number</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirmPassword" class="form-label">Confirm New Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="confirmPassword" 
                                               name="confirmPassword" required autocomplete="off">
                                        <button class="btn btn-outline-secondary toggle-password" type="button">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-lock me-2"></i> Change Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
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

    // Toggle password visibility
    $('.toggle-password').click(function() {
        const input = $(this).siblings('input');
        const icon = $(this).find('i');
        const type = input.attr('type') === 'password' ? 'text' : 'password';
        input.attr('type', type);
        icon.toggleClass('fa-eye fa-eye-slash');
    });
});                                        
</script>
