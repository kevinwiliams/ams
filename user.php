<?php
include 'db_connect.php'; // Include database connection

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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
</style>

<div class="container">
    <div class="col-lg-12">
        <div class="card">
         <form action="" id="manage_user" method="POST">

            <div class="card-body">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($id ?? ''); ?>">
                    <div class="row">
                        <div class="col-md-6 border-right">
                            <input type="hidden" name="empid" id="empid" value="<?php echo htmlspecialchars($empid ?? ''); ?>">
                          

                                     <!-- preferred_channel -->
                                     <div class="col-md-6">
                                                
                                    
                            <div class="form-group">
                                <label for="firstname" class="control-label">First Name</label>
                                <input type="text" name="firstname" id="firstname" class="form-control form-control-sm" required value="<?php echo htmlspecialchars($firstname ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="lastname" class="control-label">Last Name</label>
                                <input type="text" name="lastname" id="lastname" class="form-control form-control-sm" required value="<?php echo htmlspecialchars($lastname ?? ''); ?>">
                            </div>
                              
                                    </div>
                                 <br>
                            <div class="form-group">
                                <label for="email" class="control-label">Email</label>
                                <input type="email" name="email" id="email" class="form-control form-control-sm" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
                            </div>
                            <!-- preferred_channel -->
                            <?php
                            
                            if (isset($preferred_channel)) {
                                // Convert the comma-delimited string into an array
                                $channels = explode(',', $preferred_channel);
             
                            }
                            ?>
                            <div class="form-group">
                                <label class="form-label">Preferred Channel</label>
                                
                                <div class="form-check">
                                    <input 
                                        class="form-check-input" 
                                        type="checkbox" 
                                        name="channels[]" 
                                        value="email" 
                                        checked
                                        <?php echo isset($preferred_channel) && in_array('email', $channels) ? 'checked' : '' ?>
                                    >
                                    <label class="form-check-label" for="email">Email</label>
                                </div>
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

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="address" class="control-label">Address</label>
                                <input type="text" name="address" id="address" class="form-control form-control-sm" required value="<?php echo htmlspecialchars($address ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="contact_number" class="control-label">Contact Number</label>
                                <input type="text" name="contact_number" id="contact_number" class="form-control form-control-sm" required value="<?php echo htmlspecialchars($contact_number ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="role_id" class="control-label">Role</label>
                                <select name="role_id" id="role_id" class="custom-select custom-select-sm" required>
                                    <option value="">Select a role</option>
                                    <?php
                                    $role_qry = $conn->query("SELECT * FROM roles");
                                    if ($role_qry) {
                                        while ($role_row = $role_qry->fetch_assoc()):
                                    ?>
                                        <option value="<?php echo htmlspecialchars($role_row['role_id']); ?>" <?php echo isset($role_id) && $role_id == $role_row['role_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($role_row['role_name']); ?>
                                        </option>
                                    <?php 
                                        endwhile;
                                    } else {
                                        echo "<option>No roles available</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="password" class="control-label">Password</label>
                                <input type="password" name="password" id="password" class="form-control form-control-sm" <?php echo $id ? '' : 'required'; ?> autocomplete="false">
                                <small class="text-muted"><?php echo $id ? 'Leave blank to keep the current password.' : ''; ?></small>
                            </div>
                            
                            </div>
                           </div>                

                        </div>      
                    </div>
                    
            </div>
            <!-- Footer (only visible to users with role_id < 5) -->
            <?php if (isset($login_role_id) && $login_role_id < 5) { ?>
            <div class="card-footer text-center">
            <button class="btn btn-danger mx-5"><i class="fa fa-save"></i> <?php echo isset($status) && $status == 'Approved' ? 'Update' : 'Save' ?> User</button>
                <?php if (!empty($_SERVER['HTTP_REFERER'])) { ?>
                    <a href="<?php echo htmlspecialchars($_SERVER['HTTP_REFERER']); ?>" class="btn btn-secondary mx-5">Cancel</a>
                <?php } ?>

               
            </div>
                    <?php } ?>
         </form>
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
});                                        
</script>
