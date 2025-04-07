<?php
include 'db_connect.php'; // Include database connection

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_role = $_SESSION['role_name'];
$create_roles = ['Manager', 'ITAdmin', 'Editor', 'Dept Admin','Op Manager' ];
$channels = [];


// Retrieve the user ID from the URL
$id = $_GET['id'] ?? '';
if ($id) {
    // Fetch user details from the database
    $qry = $conn->query("SELECT * FROM users WHERE id = " . intval($id));
    if ($qry->num_rows === 0) {
        die('User not found.');
    }
    $user = $qry->fetch_assoc();
    foreach ($user as $k => $v) {
        $$k = $v; // Create variables for each field
    }
}
?>

<style>
    img#cimg {
        height: 15vh;
        width: 15vh;
        object-fit: cover;
        border-radius: 100%;
    }
    .form-label {
        font-weight: 500;
    }
    .card-header h4, .card-header h6 {
        font-weight: 600;
    }
</style>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-user-edit me-2"></i>
                        <?= isset($id) ? 'Edit User' : 'Create New User' ?>
                    </h4>
                </div>
                
                <form id="manage_user" method="POST">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($id ?? '') ?>">
                    <input type="hidden" name="empid" value="<?= htmlspecialchars($empid ?? '') ?>">
                    
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Left Column -->
                            <div class="col-md-6 border-end">
                                <div class="mb-3">
                                    <label for="empid" class="form-label">Employee ID</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($empid ?? '') ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <div class="custom-control custom-switch my-2">
                                        <input type="checkbox" class="custom-control-input" id="sb_staff" name="sb_staff" value="1"  <?= isset($sb_staff) && $sb_staff == 1 ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="sb_staff">S&B Staff</label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="firstname" class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="firstname" name="firstname" 
                                           value="<?= htmlspecialchars($firstname ?? '') ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="lastname" class="form-label">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="lastname" name="lastname" 
                                           value="<?= htmlspecialchars($lastname ?? '') ?>" required>
                                </div>

                                <div class="mb-3" id="alis_div" <?= isset($sb_staff) && $sb_staff == 1 ? '' :  'style="display:none;"' ?>>
                                    <label for="alias" class="form-label">Alias <span class="text-muted">(optional)</span></label>
                                    <input type="text" class="form-control" id="alias" name="alias" 
                                            placeholder="e.g. DJ Cool Cat"
                                           value="<?= htmlspecialchars($alias ?? '') ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?= htmlspecialchars($email ?? '') ?>" required>
                                </div>
                                
                                  
                                <div class="card border-0 shadow-sm" id="station_div" <?= isset($sb_staff) && $sb_staff == 1 ? '' : 'style="display:none;"' ?>>
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Radio Station(s)</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" name="station[]" 
                                                   id="fyah" value="FYAH" 
                                                   <?= isset($station) && in_array('FYAH', explode(',', $station)) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="fyah">FYAH</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" name="station[]" 
                                                   id="edge" value="EDGE" 
                                                   <?= isset($station) && in_array('EDGE', explode(',', $station)) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="edge">EDGE</label>
                                        </div>
                                        
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Right Column -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="address" name="address" 
                                           value="<?= htmlspecialchars($address ?? '') ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="contact_number" class="form-label">Contact Number <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="contact_number" name="contact_number" 
                                           value="<?= htmlspecialchars($contact_number ?? '') ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="role_id" class="form-label">Role <span class="text-danger">*</span></label>
                                    <select class="form-select custom-select-sm" id="role_id" name="role_id" required>
                                        <option value="">Select a role</option>
                                        <?php
                                        $role_qry = $conn->query("SELECT * FROM roles ORDER BY role_name");
                                        while ($role_row = $role_qry->fetch_assoc()):
                                        ?>
                                            <option value="<?= htmlspecialchars($role_row['role_id']) ?>" 
                                                <?= isset($role_id) && $role_id == $role_row['role_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($role_row['role_name']) ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">
                                        Password <?= !isset($id) ? '<span class="text-danger">*</span>' : '' ?>
                                    </label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password" 
                                               <?= !isset($id) ? 'required' : '' ?> autocomplete="new-password">
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary toggle-password" type="button">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <?php if (isset($id)): ?>
                                    <small class="text-muted">Leave blank to keep current password</small>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="card border-0 shadow-sm">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Notification Preferences</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" name="channels[]" 
                                                   value="email" checked disabled>
                                            <label class="form-check-label">Email (required)</label>
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
                    
                    <!-- Footer -->
                    <?php if (in_array($user_role, $create_roles)): ?>
                    <div class="card-footer bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <button type="submit" class="btn btn-primary mx-3">
                                <i class="fas fa-save me-2"></i>
                                <?= isset($status) && $status == 'Approved' ? 'Update' : 'Save' ?> User
                            </button>
                            
                            <?php if (!empty($_SERVER['HTTP_REFERER'])): ?>
                            <a href="<?= htmlspecialchars($_SERVER['HTTP_REFERER']) ?>" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i> Cancel
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </form>
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
                    alert_toast('The user has been updated.', 'success');
                    setTimeout(() => {
                        location.href = 'index.php?page=user_list'; // Redirect after success
                    }, 3000);
                } else {
                    alert_toast(resp + ' already assigned!', 'error');
                    
                }
            }
        });
    });

    $('.toggle-password').click(function() {
        const passwordField = $('#password');
        const passwordFieldType = passwordField.attr('type');
        const icon = $(this).find('i');

        if (passwordFieldType === 'password') {
            passwordField.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            passwordField.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    $('#sb_staff').change(function() {
        if ($(this).is(':checked')) {
            $('#alis_div').show();
            $('#station_div').show();
        } else {
            $('#alis_div').hide();
            $('#station_div').hide();
        }
    });
});                                        
</script>