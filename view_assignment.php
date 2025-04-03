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

$edit_roles = ['Manager', 'ITAdmin', 'Editor', 'Multimedia', 'Dispatcher', 'Photo Editor', 'Dept Admin', 'Op Manager', 'Broadcast Coordinator', 'Programme Director'];
$user_role = $_SESSION['role_name'] ?? '';
$radio_staff = $_SESSION['login_sb_staff'] == 1 ? true : false;


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
    // Get permits
    $permits = [];
    if ($radio_staff) {
        $stmt = $conn->prepare("SELECT * FROM venue_inspections WHERE assignment_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $inspection = $stmt->get_result()->fetch_assoc();

        if ($inspection) {
            $result = $conn->query("SELECT permit_type FROM venue_permits WHERE inspection_id = " . $inspection['id']);
            while ($row = $result->fetch_assoc()) {
                $permits[] = $row['permit_type'];
            }
        }
       
        $stmt->close();
    }
    
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
// $is_exclusive = false;

try {
    if (empty($team_members)) {
        $team_members = '';
    }
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

    // if ($radio_staff) {
    //     $show_name = $station_show;
    //     $station_name = '';
    //     if (strpos($show_name, ':') !== false) {
    //         $show_parts = explode(':', $show_name, 2);
    //         $show_name = trim($show_parts[1]);
    //         $station_name = trim($show_parts[0]);
    //     }

    //     $show = $conn->prepare("SELECT * FROM station_shows WHERE station = ? and show_name = ?");
    //     $show->bind_param("ss", $station_name, $show_name);
    //     $show->execute();
    //     $exclusive = $show->get_result()->fetch_assoc();
    //     // if($exclusive)
    //     //     $is_exclusive = $exclusive['is_exclusive'] == 1 ? true : false;
    //     $show->close();
    // }
} catch (Exception $e) {
 echo "Error: " . $e->getMessage();

}


$conn->close();
?>


<div class="container mt-4">
    <div class="card widget-assignment shadow">
        <!-- Header with Assignment Date and Status -->
        <div class="card-header d-flex justify-content-between align-items-center <?= $is_cancelled ? 'bg-danger' : 'bg-primary' ?> text-white">
            <div>
                <h4 class="mb-0">
                    <?= date("l, M j, Y", strtotime($assignment_date)) ?>
                    <?php if (date("Y-m-d", strtotime($date_created)) > $assignment_date): ?>
                        <i class="fas fa-history ms-2" title="back-dated entry"></i>
                    <?php endif; ?>
                </h4>
                <?php if ($is_cancelled): ?>
                    <span class="badge bg-white text-danger mt-1">CANCELLED</span>
                <?php endif; ?>
            </div>
            <!-- <img src="assets/uploads/<?= str_contains($assignment['station_show'], 'FYAH') ? 'fyah':'edge' ?>_logo.png" 
                 alt="Station Logo" class="img-fluid float-right" style="max-height: 50px;"> -->
        </div>

        <!-- Card Body -->
        <div class="card-body">
            <!-- Main Assignment Info -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <h5 class="mb-3">
                        <?= $is_cancelled ? '<span class="text-danger">CANCELLED - </span>' : '' ?>
                        <?= htmlspecialchars_decode($title ?? 'No Title') ?>
                        <?php if ($is_exclusive): ?>
                            <span class="badge bg-danger ms-2">EXCLUSIVE</span>
                        <?php endif; ?>
                    </h5>
                    
                    <div class="d-flex flex-wrap gap-3 mb-3">
                        <div class="d-flex align-items-center mx-1">
                            <i class="fas fa-clock me-2 text-primary"></i>&nbsp;
                            <?= htmlspecialchars($start_time ?? 'N/A') ?> - <?= htmlspecialchars($end_time ?? 'N/A') ?>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-map-marker-alt mx-2 text-danger"></i>
                            <?= htmlspecialchars_decode($location ?? 'N/A') ?>
                        </div>
                        <?php if ($depart_time): ?>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-car mx-2 text-success"></i> 
                            Depart: <?= htmlspecialchars($depart_time) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($radio_staff): ?>
                    <div class="mb-3">
                        <span class="badge bg-info text-dark" style="font-size: 0.9rem;">
                            <?= htmlspecialchars($station_show ?? 'N/A') ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body">
                            <!-- <h6 class="card-title small">Quick Info</h6> -->
                            <div class="card-text">
                                <ul class="list-unstyled mb-0">
                                <li class="mb-2">
                                    <strong>Transport:</strong> 
                                    <?= $transport_confirmed ? '<span class="text-success">Confirmed</span>' : '<span class="text-warning">Pending</span>' ?>
                                </li>
                                <?php if (in_array($user_role, ['Dispatcher', 'Security'])): ?>
                                <li class="mb-3">
                                    <div class="d-flex align-items-center">
                                        <strong class="me-2">Vehicle:</strong>&nbsp;
                                        <span><?= htmlspecialchars($plate_number ?? 'N/A') ?></span>
                                    </div>
                                    <div class="text-muted">
                                        <em><?= htmlspecialchars($make_model ?? 'N/A') ?></em>
                                    </div>
                                    <div class="d-flex justify-content-between mt-2">
                                        <span>
                                            <span class="badge bg-secondary">Mileage</span> 
                                            <?= is_numeric($mileage) ? number_format($mileage) . ' km' : htmlspecialchars($mileage ?? 'N/A') ?>
                                        </span>
                                        <span><span class="badge bg-secondary">Gas</span> <?= htmlspecialchars($gas_level ?? 'N/A') ?></span>
                                    </div>
                                </li>
                                <?php endif; ?>
                                <?php if ($radio_staff): ?>
                                <li class="mb-2">
                                    <strong>Toll Required:</strong> 
                                    <?= isset($toll_required) && $toll_required ? 'Yes' : 'No' ?>
                                </li>
                                <?php endif; ?>
                                <li>
                                    <strong>Assigned By:</strong> 
                                    <?= htmlspecialchars($assigned_by_name ?? 'N/A') ?>
                                </li>
                            </ul>
                            </div>
                            

                        </div>
                    </div>
                </div>
            </div>

            <!-- Details Sections -->
            <div class="row">
                <div class="col-lg-8">
                    <!-- Description -->
                    <?php if ($radio_staff): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="text-bold mb-0">Contact Information</h6>
                        </div>
                        <div class="card-body">
                            <?= empty(trim($contact_information ?? '')) ? 'No contact details provided' : nl2br(htmlspecialchars_decode($contact_information)) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <!-- Description -->
                    <?php if (!in_array($user_role, ['Dispatcher', 'Security'])): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="text-bold mb-0">Assignment Details</h6>
                        </div>
                        <div class="card-body">
                            <?= empty(trim($description ?? '')) ? 'No details provided' : nl2br(htmlspecialchars_decode($description)) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Team Members -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="text-bold mb-0">Team Members</h6>
                        </div>
                        <div class="card-body">
                            <?php if (empty($team_member_names)): ?>
                                <p class="text-muted">No team members assigned</p>
                            <?php else: ?>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php 
                                    $charactersToRemove = ["/", "|"];
                                    foreach (explode(', ', $team_member_names) as $member): 
                                        $statusClass = strpos($member, '/') !== false ? 'badge-success' : 'badge-secondary';
                                        $member = str_replace($charactersToRemove, "", $member);
                                    ?>
                                        <span class="font-weight-normal badge <?= $statusClass ?> p-2 m-1" style="font-size: 0.9rem;">
                                            <?= htmlspecialchars($member) ?>
                                        </span>
                                    <?php endforeach; ?>
                                      <!-- Add badges for requested fields -->
                                      <?php if (!empty($photo_requested) && $photo_requested == 1): ?>
                                        <span class="font-weight-normal badge badge-warning p-2 m-1" style="font-size: 0.9rem;">
                                            Photographer Requested
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($video_requested) && $video_requested == 1): ?>
                                        <span class="font-weight-normal badge badge-warning p-2 m-1" style="font-size: 0.9rem;">
                                            Videographer Requested
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($social_requested) && $social_requested == 1): ?>
                                        <span class="font-weight-normal badge badge-warning p-2 m-1" style="font-size: 0.9rem;">
                                            Social Media Requested
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($driver_requested) && $driver_requested == 1): ?>
                                        <span class="font-weight-normal badge badge-warning p-2 m-1" style="font-size: 0.9rem;">
                                            Driver Requested
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($dj_requested) && $dj_requested == 1): ?>
                                        <span class="font-weight-normal badge badge-warning p-2 m-1" style="font-size: 0.9rem;">
                                            DJ Requested
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <!-- Permits -->
                    <?php if ($radio_staff): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="text-bold mb-0">Permits</h6>
                        </div>
                        <div class="card-body">
                            <?php 
                            $nonEmptyPermits = array_filter($permits, function($permit) {
                                return !empty($permit);
                            });
                            ?>
                            <?php if (!empty($nonEmptyPermits)): ?>
                                <ul class="list-unstyled mb-0">
                                    <?php foreach ($nonEmptyPermits as $permit): ?>
                                        <li class="mb-1">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            <?= strtoupper($permit) ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-muted mb-0">No permits recorded</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Equipment Request -->
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="text-bold mb-0">Equipment</h6>
                        </div>
                        <div class="card-body">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="equipment_requested" 
                                    <?= !empty($equipment_requested) ? 'checked' : '' ?>
                                    <?= $user_role == 'Security' ? 'disabled' : '' ?>>
                                <label class="form-check-label" for="equipment_requested">
                                    <?= !empty($equipment_requested) ? 'Equipment Requested' : 'Request Equipment' ?>
                                </label>
                            </div>
                            <?php if (!empty($equipment)): ?>
                            <p><?= htmlspecialchars_decode($equipment ?? '') ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer with Action Buttons -->
        <div class="card-footer bg-light">
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <?php if (!empty($_SERVER['HTTP_REFERER'])): ?>
                    <a href="#" onclick="goBack()" class="btn btn-outline-secondary mx-1">
                        <i class="fas fa-arrow-left me-1"></i> Back
                    </a>
                <?php endif; ?>
                
                <?php if (in_array($user_role, $edit_roles)): ?>
                    <a href="index.php?page=assignment&id=<?= $a_id ?>" class="btn btn-outline-primary mx-1">
                        <i class="fas fa-edit me-1"></i> Edit Assignment
                    </a>
                <?php endif; ?>
                <?php 
               
                
                if (in_array($user_role, $edit_roles)): ?>
                    <?php if (!str_contains($_SERVER['HTTP_REFERER'], 'page=assignment_')): ?>
                        <a href="index.php?page=assignment_list" class="btn btn-outline-secondary">
                            <i class="fas fa-list me-2"></i> Back to List
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php if (!$seen && in_array($db_empid, $current_team)): ?>
                    <button class="btn btn-success mx-1" id="confirm_seen" 
                        data-id="<?= $a_id ?>" 
                        data-empid="<?= $db_empid ?>" 
                        data-userid="<?= $user_id ?>">
                        <i class="fas fa-check-circle me-1"></i> Confirm Receipt
                    </button>
                <?php endif; ?>
                
                <?php if (in_array($user_role, ['Security'])): ?>
                    <button class="btn btn-outline-warning edit-transport-log" data-assignment-id="<?= $a_id ?>">
                        <i class="fas fa-car me-1"></i> Update Transport
                    </button>
                <?php endif; ?>
                
                <?php if (in_array($user_role, ['Broadcast Coordinator', 'ITAdmin', 'Op Manager'])): ?>
                    <a href="index.php?page=view_site_report&id=<?= $a_id ?>" class="btn btn-outline-info mx-1">
                        <i class="fas fa-clipboard-check mx-1"></i> View Report
                    </a>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
</div>

<!-- Hidden Fields -->
<input type="hidden" id="assignmentDate" value="<?= htmlspecialchars($assignment_date) ?>">
<input type="hidden" id="startTime" value="<?= htmlspecialchars($start_time) ?>">
<input type="hidden" id="endTime" value="<?= htmlspecialchars($end_time ?? '') ?>">
<input type="hidden" id="departTime" value="<?= htmlspecialchars($depart_time ?? '') ?>">
<input type="hidden" id="assignment" value="<?= htmlspecialchars($title) ?>">

<!-- Include Modals -->
<?php include('modal_equipment_request.php'); ?>
<?php include('modal_transport_log.php'); ?>

<script>

    $(document).ready(function(){

        $('.summernote').summernote({
            height: 150,
            toolbar: [
                ['font', ['bold', 'underline', 'clear']],
                ['para', ['ul', 'ol', 'paragraph']],
            ]
        });

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

            let assignmentId = "<?= $a_id ?>"; // Get assignment ID
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
                        setTimeout(() => {
                            location.reload(); // Reload the page to reflect changes
                        }, 1500); // Set timeout to 2 seconds
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
                            setTimeout(() => {
                            location.reload(); // Reload the page to reflect changes
                        }, 1500);
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