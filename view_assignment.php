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

$edit_roles = ['Manager', 'ITAdmin', 'Editor', 'Multimedia', 'Dispatcher', 'Photo Editor', 'Dept Admin'];
$user_role = $_SESSION['role_name'] ?? '';

$options = [
    'dropOffOnly' => 'Drop Off Only',
    'dropOffReturn' => 'Drop Off/Return',
    'pickupOnly' => 'Pick Up Only',
    '' => 'N/A'
];

$user_id = $_SESSION['login_id'] ?? 0;
$db_empid = $_SESSION['empid'] ?? '';

// Check if ID is provided and valid
$id = isset($_GET['id']) ? intval($_GET['id']) : 0; // Ensure ID is an integer

if ($id > 0) {
    // Prepare and execute the query to fetch assignment details
    $stmt = $conn->prepare("SELECT 
                            a.*,
                            a.id AS a_id,
                            t.*,
                            tv.*,
                            -- Get comma-separated team member names
                            (SELECT GROUP_CONCAT(
                                CONCAT(u.firstname, ' ', u.lastname, ' (', r.role_name, ')', 
                                    CASE 
                                        WHEN EXISTS (
                                            SELECT 1 
                                            FROM confirmed_logs cl 
                                            WHERE cl.assignment_id = a.id AND cl.empid = u.empid
                                        ) THEN ' /' 
                                        ELSE ' |' 
                                    END
                                ) SEPARATOR ', ') 
                            FROM users u
                            LEFT JOIN roles r
                            ON u.role_id = r.role_id
                            WHERE FIND_IN_SET(u.empid, REPLACE(a.team_members, ' ', '')) > 0
                            ) AS team_member_names,
                            -- Get assigned_by user name
                            CONCAT(assigned_by_user.firstname, ' ', assigned_by_user.lastname) AS assigned_by_name,
                            -- Get edited_by user name
                            CONCAT(edited_by_user.firstname, ' ', edited_by_user.lastname) AS edited_by_name,
                            -- Get approved_by user name
                            CONCAT(approved_by_user.firstname, ' ', approved_by_user.lastname) AS approved_by_name
                            FROM 
                                assignment_list a
                            -- Join to get assigned_by user details
                            LEFT JOIN users assigned_by_user 
                                ON a.assigned_by = assigned_by_user.id
                            -- Join to get edited_by user details
                            LEFT JOIN users edited_by_user 
                                ON a.edited_by = edited_by_user.id
                            -- Join to get approved_by user details
                            LEFT JOIN users approved_by_user 
                                ON a.approved_by = approved_by_user.id
                            -- Join to get transporttion details
                            LEFT JOIN transport_log t 
                                ON a.id = t.assignment_id
                           LEFT JOIN transport_vehicles tv
                                ON t.transport_id = tv.id
                             WHERE a.id = ?");
    if ($stmt === false) {
        die('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $assignment = $result->fetch_assoc();
            foreach ($assignment as $k => $v) {
                $$k = $v;
            }
        } else {
            die('Assignment not found.');
        }
    } else {
        die('Query failed: ' . $stmt->error);
    }

    $stmt->close();
} else {
    die('Invalid assignment ID.');
}

$confirm = isset($_GET['confirm']) ? boolval($_GET['confirm']) : false; // Ensure confirm is bool

$seen = true;

try {
    $current_team = explode(',', $team_members);
    if (in_array($db_empid, $current_team)) {

        $seenCheck = "
        SELECT id 
        FROM confirmed_logs 
        WHERE assignment_id = ? AND empid = ?
        ";
        $stmt = $conn->prepare($seenCheck);
        $stmt->bind_param('ss', $a_id, $db_empid);
        $stmt->execute();

        $result = $stmt->get_result();
        // Check if an assignment exists
        $seen = $result->num_rows > 0;
        $stmt->close();
    }
} catch (Exception $e) {
 echo "Error: " . $e->getMessage();

}

$conn->close();
?>
<style>
        .assignment-card {
            max-width: 600px; /* Adjust card width */
            margin: 0 auto; /* Center horizontally */
        }
        .widget-assignment-header {
            padding: 1rem; /* Adjust header padding */
        }
        .widget-assignment-header h3 {
            margin-bottom: 0;
        }
        .card-footer dl dt {
            font-weight: bold;
        }
        .card-footer dl dd {
            margin-bottom: 0.5rem;
        }
        .modal-footer {
            border-top: none; /* Remove border for a cleaner look */
        }
    </style>

<div class="container mt-4">
    <div class=" card card-widget widget-assignment shadow">
        <!-- Header -->
        <div class="widget-assignment-header <?php echo ($is_cancelled == 1) ? 'bg-danger' : 'bg-light'; ?> text-dark text-center p-4">
        <h5 class="widget-assignment-title">
                <?php
                        echo date("l, M j, Y", strtotime($assignment_date));
                        if (date("Y-m-d", strtotime($date_created)) > $assignment_date){
                            echo ' <i class="fas fa-history" title="back-dated entry"></i>';
                        }
                        echo ($is_cancelled == 1) ? '<br>CANCELLED' : ''; 
                    ?></h5>
        </div>

        <!-- Card Body -->
        <div class="card-body">
            <!-- Assignment Details Section -->
            <div class="mb-4">
                <h5>Assignment Details</h5>
                <div class="row mb-3">
                    <div class="col-4"><strong>Assignment:</strong></div>
                    <div class="col-8"><?php echo ($is_cancelled == 1) ? 'CANCELLED - ' : ''; ?><?php echo htmlspecialchars_decode($title ?? 'No Title'); ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-4"><strong>Start Time:</strong></div>
                    <div class="col-8"><?php echo htmlspecialchars($start_time ?? 'N/A'); ?></div>
                </div>
                <?php if ($end_time){?>
                <div class="row mb-3">
                    <div class="col-4"><strong>End Time:</strong></div>
                    <div class="col-8"><?php echo htmlspecialchars($end_time ?? 'N/A'); ?></div>
                </div>
                <?php } ?>
                
                <?php if ($depart_time){?>
                <div class="row mb-3">
                    <div class="col-4"><strong>Depart Time:</strong></div>
                    <div class="col-8"><?php echo htmlspecialchars($depart_time ?? 'N/A'); ?></div>
                </div>
                <?php } ?>

                <div class="row mb-3">
                    <div class="col-4"><strong>Venue:</strong></div>
                    <div class="col-8"><?php echo htmlspecialchars($location ?? 'N/A'); ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-4"><strong>Transport Confirmed:</strong></div>
                    <div class="col-8"><?php echo ($transport_confirmed == 1) ? 'Yes' :  'No'; ?></div>
                </div>
            </div>

            <!-- Additional Information Section -->
            <div class="mb-4">
                <h5>Additional Information</h5>
                <?php if(!in_array($user_role, ['Dispatcher', 'Security'])){?>
                
                <div class="row mb-3">
                    <div class="col-4"><strong>Details:</strong></div>
                    <div class="col-8"><?php echo nl2br(htmlspecialchars_decode($description ?? 'N/A')); ?></div>
                </div>
                <?php } ?>
                <div class="row mb-3">
                    <div class="col-4"><strong>Assigned To:</strong></div>
                    <div class="col-8"><?php 
                    // echo htmlspecialchars($team_member_names ?? 'N/A'); 
                    $teamMembers = explode(', ', $team_member_names);
                    $charactersToRemove = ["/", "|"];

                    foreach ($teamMembers as $member) {
                        // Check if status is "Confirmed" or "Pending"
                        if (strpos($member, '/') !== false) {
                            $member = str_replace($charactersToRemove, "", $member);
                            echo "<span class='text-success fw-bold'>$member</span><br>";
                        } else {
                            $member = str_replace($charactersToRemove, "", $member);
                            echo "<span class='text-danger'>$member</span><br>";
                        }
                    }
                    
                    ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-4"><strong>Assigned By:</strong></div>
                    <div class="col-8"><?php echo htmlspecialchars($assigned_by_name ?? 'N/A'); ?></div>
                </div>
                <div class="row mb-3 d-none">
                    <div class="col-4"><strong>Transport Option:</strong></div>
                    <div class="col-8"><?php echo isset($options[$drop_option]) ? htmlspecialchars($options[$drop_option]) : 'No transport assigned'; ?></div>
                </div>
                <?php if(in_array($user_role, ['Dispatcher', 'Security'])){?>
                <div class="row mb-3">
                    <div class="col-4"><strong>Vehicle:</strong></div>
                    <div class="col-8"><?php echo htmlspecialchars($plate_number ?? 'N/A'); ?> <?php echo htmlspecialchars($make_model ?? 'N/A'); ?>
                    <br><small>Mileage: <?php echo htmlspecialchars($mileage ?? 'N/A'); ?><br>Gas Level: <?php echo htmlspecialchars($gas_level ?? 'N/A'); ?></small></div>
                </div>
                <?php } ?>
                <!-- <div class="row mb-3">
                    <div class="col-4"><strong>Date Created:</strong></div>
                    <div class="col-8"><?php echo htmlspecialchars($date_created ?? 'N/A'); ?></div>
                </div> -->
                <!-- <div class="row mb-3">
                    <div class="col-4"><strong>Status:</strong></div>
                    <div class="col-8"><?php echo htmlspecialchars($status ?? 'N/A'); ?></div>
                </div> -->
            <!-- </div> -->
            <div class="text-start small ">
                <!-- <a href="index.php?page=vehicle_request&id=<?= $a_id; ?>" class="text-dark"><i class="fas fa-print"></i> Print Transport Form</a> -->
                <!-- Equipment Request Checkbox -->
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" id="equipment_requested" <?php echo (!empty($equipment_requested)) ? 'checked=checked' : ''; ?><?php echo ($user_role == 'Security') ? 'disabled' : ''; ?>>
                    <label class="form-check-label" for="equipment_requested"> <?php echo !empty($equipment_requested) ? 'Equipment Requested' : 'Request Equipment'; ?> </label>
                </div>    
            </div>
        </div>
        <input type="hidden" id="assignmentDate" name="assignmentDate" value="<?php echo htmlspecialchars($assignment_date); ?>">
        <input type="hidden" id="startTime" name="startTime" value="<?php echo htmlspecialchars($start_time); ?>">
        <input type="hidden" id="endTime" name="endTime" value="<?php echo htmlspecialchars($end_time); ?>">
        <input type="hidden" id="departTime" name="departTime" value="<?php echo htmlspecialchars($depart_time); ?>">
        <input type="hidden" id="assignment" name="assignment" value="<?php echo htmlspecialchars($title); ?>">

        <?php 

       ?>
        <div class="card-footer text-center">

        <?php if (!empty($_SERVER['HTTP_REFERER'])) { ?>
            <a href="#" onclick="goBack()" class="mx-5 cursor-pointer">Back</a>  
        <?php } ?> 
        <?php if (in_array($user_role, $edit_roles)){?>
            <a href="index.php?page=assignment&id=<?= $a_id; ?>" class="mx-5"> Edit / Update Assignment</a>
        <?php } ?>
        <?php if(!$seen && in_array($db_empid, $current_team)){ ?>
           <button class="mx-5 text-success pe-auto cursor-pointer btn btn-link" id="confirm_seen" 
            
            data-id="<?= $a_id; ?>" 
            data-empid="<?= $db_empid; ?>" 
            data-userid="<?= $user_id; ?>">Confirm Seen Receipt</button>
        <?php } ?>
        <?php if (in_array($user_role, ['Security'])){?>
            <a href="#" class="mx-5 edit-transport-log" data-assignment-id="<?= $a_id ?>"> Update Transport Log</a>
        <?php } ?>
        </div>

        <?php include('modal_equipment_request.php'); ?>
        <?php include('modal_transport_log.php'); ?>
        
       
        <!-- Back Button -->
        
      
    </div>
</div>

<script>

    $(document).ready(function(){

        // Pass PHP variables to JavaScript
        var confirm = <?php echo json_encode($confirm); ?>;
        var seen = <?php echo json_encode($seen); ?>;
        var db_empid = <?php echo json_encode($db_empid); ?>;
        var current_team = <?php echo json_encode($current_team); ?>;
        var user_id = <?php echo json_encode($user_id); ?>;
        var assignment_id = <?php echo json_encode($a_id); ?>;
        // alert(seen + db_empid + current_team);

        if (!seen && current_team.includes(db_empid) && confirm) {
           
            $.ajax({
                url: 'ajax.php?action=log_confirmed',
                method: 'POST',
                data: { assignment_id: assignment_id, user_id: user_id, empid: db_empid },
                success: function (resp) {
                    console.log(resp);

                    if (resp == 1) {
                        // Show success alert
                        Swal.fire({
                            title: 'Success!',
                            text: 'Thanks for confirming.',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false,
                        }).then(() => {
                            $('#confirm_seen').hide();
                            // location.href = 'index.php?page=request_list'; // Redirect after success
                    });

                    } else {
                        // Show failure alert
                        alert_toast('Failed to confirm!', 'error');
                    }
                },
                error: function (err) {
                    // Show error alert
                    alert_toast('Something went wrong. Please try again later.', 'error');
                    console.log(err);
                },
            });
        }

        // Show modal when checkbox is checked
        $('#equipment_requested').on('change', function () {
            if (this.checked) {
                $('#equipmentModal').modal('show');
            }
        });

        $('#submitEquipmentRequest').on('click', function () {
            event.preventDefault();
            $('#submitEquipmentRequest').prop('disabled', true);

            let assignmentId = "<?= $id ?>"; // Get assignment ID
            let assignmentDate = $('#assignmentDate').val().trim(); //
            let startTime = $('#startTime').val().trim(); //
            let endTime = $('#endTime').val().trim(); //
            let departTime = $('#departTime').val().trim(); //
            let assignment = $('#assignment').val().trim(); //
            let equipmentDetails = $('#equipment_details').val().trim(); //
            let request = $('#equipment_requested').is(':checked') ? 1 : 0;

            if (equipmentDetails === "") {
                alert_toast('Please enter equipment details!', 'error');
                return;
            }

            $.ajax({
                url: "ajax.php?action=equipment_request",
                type: "POST",
                data: {
                    assignment_id: assignmentId,
                    equipment_requested: request,
                    equipment_details: equipmentDetails,
                    assignment_date: assignmentDate,
                    start_time: startTime,
                    end_time: endTime,
                    depart_time: departTime,
                    title: assignment
                },
                success: function(response) {
                    let res = JSON.parse(response);
                    if (res.status === "success") {
                        alert_toast('Equipment request submitted!', 'success');
                        $('#equipmentModal').modal('hide');
                        $('#submitEquipmentRequest').prop('disabled', false);
                        // $('#equipment_requested').prop("checked", false); // Uncheck after submission
                    } else {
                        $('#submitEquipmentRequest').prop('disabled', false);
                        alert_toast('Failed to submit request.', 'error');
                    }
                },
                error: function() {
                    $('#submitEquipmentRequest').prop('disabled', false);
                    alert_toast('Something went wrong!', 'error');
                }
            });
        });

        $('#confirm_seen').on('click', function () {
            event.preventDefault();
            var assignment_id = $(this).data('id'); 
            var empid = $(this).data('empid'); 
            var user_id = $(this).data('userid'); 
            // alert('Id - ' + assignment_id+ ' Empid - '+ empid + ' User - '+ user_id);

            $.ajax({
                url: 'ajax.php?action=log_confirmed',
                method: 'POST',
                data: { assignment_id: assignment_id, user_id: user_id, empid: empid },
                success: function (resp) {
                    console.log(resp);

                    if (resp == 1) {
                        // Show success alert
                        Swal.fire({
                            title: 'Success!',
                            text: 'Thanks for confirming.',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false,
                        }).then(() => {
                            $('#confirm_seen').hide();
                            // location.href = 'index.php?page=request_list'; // Redirect after success
                    });

                    } else {
                        // Show failure alert
                        alert_toast('Failed to confirm!', 'error');
                    }
                },
                error: function (err) {
                    // Show error alert
                    alert_toast('Something went wrong. Please try again later.', 'error');
                    console.log(err);
                },
            });

            
        });

        $('.edit-transport-log').on('click', function() {
            var assignment_id = $(this).data('assignment-id');
            $('#assignment_id').val(assignment_id);
            $('#updateTransportLogModal').modal('show');
        });

        
        $('#updateTransportLogBtn').on('click', function() {
            var formData = $('#updateTransportLogForm').serialize();

            $.ajax({
                url: 'ajax.php?action=update_transport_log',
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response == 1) {
                        alert_toast('Transport log updated successfully!', 'success');
                        $('#updateTransportLogModal').modal('hide');
                        setTimeout(() => {
                            location.href = 'index.php?page=home'; // Redirect after success
                        }, 2500);
                    } else {
                        alert_toast('Failed to update transport log.', 'error');
                    }
                },
                error: function() {
                    alert_toast('An error occurred. Please try again.', 'error');
                }
            });
        });
    });

    </script>