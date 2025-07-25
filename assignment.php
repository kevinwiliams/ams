<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['login_id'])) {
    header("Location: login.php?returnUrl=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}


include 'db_connect.php'; // Removed sidebar and topbar includes
include 'admin_class.php'; // Removed sidebar and topbar includes
$admin = new Action($conn);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$radio_staff = $_SESSION['login_sb_staff'] == 1 ? true : false;
$login_role_id = $_SESSION['role_id']? $_SESSION['role_id'] : 0;
$user_role = $_SESSION['role_name'] ?? '';

//Declare user roles for filtering
$editor_roles = ['Editor', 'Manager', 'Dept Admin'];
$manager_roles = ['Op Manager'];
$media_roles = ['Multimedia'];
$digital_roles = ['Photo Editor'];
$dispatch_roles = ['Dispatcher'];
$dj_roles = ['Programme Director'];
$broadcast_roles = ['Broadcast Coordinator'];

$readonly = '';
$disabled = '';
$disabledDispatch = '';
$readonlyDispatch = '';
$requiredDispatch = '';
$disabledEditors = '';
$disabledMedia = '';
$disabledDigital = '';
$disabledBroadcast = '';
$disabledPersonality = '';
$readonlyPersonality = '';
$requiredPersonality = '';
$required = '';
$studio_engineer = '';

// Check user role and set attributes
switch (true) {
    case in_array($user_role, $media_roles):
        $readonly = 'readonly = readonly';
        $disabled = 'disabled = disabled';
        $disabledMedia = 'disabled = disabled'; 
        break;
    case in_array($user_role, $dispatch_roles):
        $disabledDispatch = 'disabled = disabled';
        $readonlyDispatch = 'readonly = readonly';
        $requiredDispatch = 'required';
        break;
    case in_array($user_role, $editor_roles):
        $disabledEditors = 'disabled = disabled';
        break;
    case in_array($user_role, $broadcast_roles):
        $disabledBroadcast = 'disabled = disabled';
        $required = 'required';
        break;
    case in_array($user_role, $digital_roles):
        $readonly = 'readonly = readonly';
        $disabled = 'disabled = disabled';
        $disabledDigital = 'disabled = disabled';
        $required = 'required';
        break;
    case in_array($user_role, $dj_roles):
        $disabledPersonality = 'disabled = disabled';
        $readonlyPersonality = 'readonly = readonly';
        $requiredPersonality = 'required';
        break;
}

// Retrieve the assignment ID from the URL
$id = $_GET['id'] ?? '';
if ($id) {
    // Fetch assignment details from the database
    $qry = $conn->query("SELECT * FROM assignment_list WHERE id = " . intval($id));
    if ($qry->num_rows === 0) {
        die('Assignment not found.');
    }
    $assignment = $qry->fetch_assoc();
    foreach ($assignment as $k => $v) {
        $$k = $v; // Create variables for each field
    }
} else {
    //die('Invalid assignment ID.');
    $assignment_date = isset($assignment_date); // Replace $existingDate with your actual variable or logic
    $assignment_date = $assignment_date ? $assignment_date : date('Y-m-d');
    $title = "";
}
?>

<style>
    img#cimg {
        height: 15vh;
        width: 15vh;
        object-fit: cover;
        border-radius: 100%;
    }

    .assignee-wrapper {
        margin-bottom: 10px;
        position: relative;
        display: flex;
        align-items: center;
    }
    .card-header {
        font-weight: 600;
    }
    h4 {
        font-size: 1rem;
        font-weight: 600;
        /* margin-top: 0.5rem; */
    }
</style>

<div class="container">
    <?php // 'SB STaff:'.$_SESSION['login_sb_staff'] ?>
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h3 class="card-title mb-0">
                    <i class="fas fa-calendar-check mr-2"></i>
                    Assignment Details
                </h3>
            </div>
            <div class="card-body">
                <form action="" id="manage_assignment" method="POST">
                    <div class="row">
                        <div class="col-md-6 pr-3">
                            <!-- Schedule Section -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h4 class="mb-0"><i class="far fa-clock mr-2"></i>Schedule</h4>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="assignment_date" class="control-label">Assignment Date</label>
                                        <input type="date" name="assignment_date" id="assignment_date" class="form-control form-control-sm" required value="<?php echo htmlspecialchars($assignment_date ?? ''); ?>" <?= $readonly.$readonlyDispatch.$readonlyPersonality ?>>
                                    </div>
                                    <div class="form-group row">
                                        <!-- Start Time -->
                                        <?php if($disabled.$disabledDispatch.$disabledPersonality){ ?>
                                        <input type="hidden" name="start_time" value="<?= $start_time ?>" />
                                        <?php } ?>
                                        <div class="col-md-6">
                                            <label for="start_time" class="control-label">Start Time</label>
                                            <select name="start_time" id="start_time" class="custom-select custom-select-sm" required <?= $disabled.$disabledDispatch.$disabledPersonality ?>>
                                                <option value="" selected="selected">Select Start Time</option>
                                                <?php 
                                                    $times = [];
                                                    for ($i = 0; $i < 24; $i++) {
                                                        for ($j = 0; $j < 60; $j += 15) {
                                                            $time = sprintf('%02d:%02d', $i, $j);
                                                            $display_time = date('h:i A', strtotime($time));
                                                            if ($time == '00:00') {
                                                                $display_time = 'Midnight';
                                                            }
                                                            $times[] = [$time, $display_time];
                                                        }
                                                    }

                                                    foreach ($times as $t) {
                                                        echo '<option value="' . htmlspecialchars($t[1]) . '"' .
                                                            (isset($start_time) && $start_time == $t[1] ? ' selected' : '') .
                                                            '>' . htmlspecialchars($t[1]) . '</option>';
                                                    }
                                                ?>
                                            </select>
                                        </div>

                                        <!-- End Time -->
                                        <?php if($disabled.$disabledDispatch.$disabledPersonality){ ?>
                                        <input type="hidden" name="end_time" value="<?= $end_time ?>" />
                                        <?php } ?>
                                        <div class="col-md-6">
                                            <label for="end_time" class="control-label">End Time</label>
                                            <select name="end_time" id="end_time" class="custom-select custom-select-sm" <?= $disabled.$disabledDispatch.$disabledPersonality ?>>
                                                <option value="" selected="selected">Select End Time</option>
                                                <?php 
                                                foreach ($times as $t) {
                                                    echo '<option value="' . htmlspecialchars($t[1]) . '"' .
                                                        (isset($end_time) && $end_time == $t[1] ? ' selected' : '') .
                                                        '>' . htmlspecialchars($t[1]) . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <?php if($disabled.$disabledDispatch.$disabledPersonality){ ?>
                                        <input type="hidden" name="depart_time" value="<?= $depart_time ?>" />
                                        <?php } ?>
                                        <label for="depart_time" class="control-label">Depart Time (optional)</label>
                                        <select name="depart_time" id="depart_time" class="custom-select custom-select-sm" <?= $disabled.$disabledDispatch.$disabledPersonality ?>>
                                            <option value="" selected="selected">Select Depart Time</option>
                                            <?php 
                                            // Generate time options
                                            foreach ($times as $t) {
                                                echo '<option value="' . htmlspecialchars($t[1]) . '"' .
                                                    (isset($depart_time) && $depart_time == $t[1] ? ' selected' : '') .
                                                    '>' . htmlspecialchars($t[1]) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Assignment Details Section -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h4 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Assignment Information</h4>
                                </div>
                                <div class="card-body">
                                    <?php if($radio_staff){?>
                                    <div class="form-group">
                                        <label for="station_show" class="control-label">Show</label>
                                        <select name="station_show" id="station_show" class="custom-select custom-select-sm" <?= $disabled.$disabledPersonality.$required ?>>
                                            <option value="" selected="selected">Select a Show</option>
                                            <?php
                                            // Fetch shows from the database
                                            $shows_query = $conn->query("SELECT id, show_name, station FROM station_shows ORDER BY station ASC, show_name ASC");
                                            while ($show = $shows_query->fetch_assoc()) {
                                                //$station_show = $show['id'];
                                                $show_name = htmlspecialchars($show['show_name']);
                                                $station = htmlspecialchars($show['station']);
                                                $display_text = "$station : $show_name"; // Concatenate show name and station
                                                $selected = (isset($station_show) && $station_show == $display_text) ? 'selected' : ''; // Check if selected
                                                echo '<option value="' . $display_text . '" data-station="' . $station . '" '.  $selected .'>' . $display_text . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <div class="custom-control custom-switch my-2">
                                            <input type="checkbox" class="custom-control-input" id="is_exclusive" name="is_exclusive" <?php echo isset($is_exclusive) && $is_exclusive == 1 ? 'checked' : '' ?><?= $readonly.$readonlyPersonality ?><?= $disabled ?>>
                                            <label class="custom-control-label font-weight-light" for="is_exclusive">
                                            Exclusive Show
                                            </label>
                                        </div>
                                    </div>
                                    <?php } ?>
                                    <div class="form-group">
                                        <label for="title" class="control-label">Assignment</label>
                                        <input type="text" name="title" id="title" class="form-control form-control-sm" required value="<?php echo htmlspecialchars_decode($title ?? ''); ?>" <?= $readonly.$readonlyDispatch.$readonlyPersonality ?>>
                                    </div>
                                    <div class="form-group">
                                        <label for="location" class="control-label">Venue</label>
                                        <input type="text" name="location" id="location" class="form-control form-control-sm" required value="<?php echo htmlspecialchars_decode($location ?? ''); ?>" <?= $readonly.$readonlyDispatch.$readonlyPersonality ?>>
                                    </div>
                                    <?php if ($radio_staff){ ?>
                                    <div class="form-group">
                                        <!-- Contact Information -->
                                        <label for="contact_information">Contact Information</label>
                                        <textarea 
                                                class="form-control form-control-sm summernote textarea" 
                                                name="contact_information" id="contact_information" 
                                                data-readonly="<?= ($readonly || $readonlyDispatch || $readonlyPersonality) ? 'true' : 'false'; ?>"
                                                ><?= htmlspecialchars_decode($assignment['contact_information'] ?? '') ?>
                                        </textarea>
                                    </div>
                                    <?php } ?>
                                    <div class="form-group">
                                        <label class="control-label">Notes</label>
                                        <textarea 
                                            name="description" 
                                            class="form-control form-control-sm summernote textarea" 
                                            data-readonly="<?= ($readonly || $readonlyDispatch || $readonlyPersonality) ? 'true' : 'false'; ?>"
                                        ><?php echo htmlspecialchars_decode($description ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="col-md-6 pl-3">
                            <!-- Transport Section -->
                             <?php if (isset($_GET['id']) || $radio_staff){ ?>
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h4 class="mb-0"><i class="fas fa-car mr-2"></i>Transport <?= ($radio_staff) ? '/ Permit' : '' ?></h4>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label class="form-label">Transport Option</label>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="drop_option" id="dropOffOnly" value="dropOffOnly" <?php echo isset($drop_option) && $drop_option == 'dropOffOnly' ? 'checked' : '' ?> <?= $disabled.$disabledPersonality ?>>
                                                <label class="form-check-label" for="dropOffOnly">
                                                    Drop Off Only
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="drop_option" id="dropOffReturn" value="dropOffReturn" <?php echo isset($drop_option) && $drop_option == 'dropOffReturn' ? 'checked' : '' ?> <?= $disabled.$disabledPersonality ?>>
                                                <label class="form-check-label" for="dropOffReturn">
                                                    Drop Off/Return
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="drop_option" id="pickupOnly" value="pickupOnly" <?php echo isset($drop_option) && $drop_option == 'pickupOnly' ? 'checked' : '' ?> <?= $disabled.$disabledPersonality ?>>
                                                <label class="form-check-label" for="pickupOnly">
                                                    Pick Up
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="drop_option" id="noTransport" value="" <?php echo !isset($drop_option) || $drop_option == '' || (isset($drop_option) && $drop_option == NULL) ? 'checked' : '' ?> <?= $disabled.$disabledPersonality?>>
                                                <label class="form-check-label" for="noTransport">
                                                    No Transport Required
                                                </label>
                                            </div>
                                             <?php if($disabled.$disabledPersonality){ ?>
                                            <input type="hidden" name="drop_option" value="<?= $drop_option ?>" />
                                            <?php } ?>
                                    </div>
                                    <?php if (isset($_GET['id']) && (in_array($user_role, $editor_roles) || in_array($user_role, $broadcast_roles))){ ?>
                                    <div class="form-group">
                                        <div class="custom-control custom-switch my-2">
                                            <input type="checkbox" class="custom-control-input" id="transport_confirmed" name="transport_confirmed" <?php echo isset($transport_confirmed) && $transport_confirmed == 1 ? 'checked' : '' ?><?= $readonlyPersonality ?>>
                                            <label class="custom-control-label font-weight-light" for="transport_confirmed">
                                            <?php echo (isset($transport_confirmed) && $transport_confirmed == 1) ? 'Transportation confirmed' : 'Confirm transportation'; ?>
                                            </label>
                                        </div>
                                    </div>
                                    <?php } ?>
                                    <?php if ($radio_staff){ ?>
                                    <div class="form-group">
                                        <div class="custom-control custom-switch my-2">
                                            <input type="checkbox" class="custom-control-input" id="request_permit" name="request_permit" <?php echo isset($request_permit) && $request_permit == 1 ? 'checked' : '' ?><?= $readonlyPersonality ?>>
                                            <label class="custom-control-label font-weight-light" for="request_permit">
                                            <?php echo (isset($request_permit) && $request_permit == 1) ? 'Permit Requested' : 'Request Permit'; ?>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="custom-control custom-switch my-2">
                                            <input type="checkbox" class="custom-control-input" id="toll_required" name="toll_required" <?php echo isset($toll_required) && $toll_required == 1 ? 'checked' : '' ?><?= $readonlyPersonality ?>>
                                            <label class="custom-control-label font-weight-light" for="toll_required">
                                            <?php echo (isset($toll_required) && $toll_required == 1) ? 'Toll Requested' : 'Request Toll'; ?>
                                            </label>
                                        </div>
                                    </div>
                                    <?php } ?>
                                    <?php if ($radio_staff && !empty($readonlyPersonality)){ ?>
                                        <input type="hidden" name="request_permit" value="<?= $request_permit?>">
                                        <input type="hidden" name="transport_confirmed" value="<?= $transport_confirmed?>">
                                    <?php } ?>
                                </div>
                            </div>
                            <?php } ?>
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($id ?? ''); ?>">
                            <input type="hidden" name="assigned_by" value="<?php echo htmlspecialchars($assigned_by ?? ''); ?>">
                            <!-- Team Assignment Section -->
                            <?php
                                $all_members = [];
                                $salesreps = [];
                                $personalities = [];
                                $engineers = [];
                                $producers = [];
                                $reporters = [];
                                $photographers = [];
                                $videographers = [];
                                $socials = [];
                                $drivers = [];
                                $djs = [];
                                $teamRem = "";
                                $station = isset($station_show) ? explode(' : ', $station_show)[0] : null;
                                
                                if (isset($team_members)) { // Check if $team_members is set
                                    // Split the team members string into an array
                                    $all_members = explode(',', $team_members);
                                    $salesreps = explode(',', $team_members);
                                    $personalities = explode(',', $team_members);
                                    $engineers = explode(',', $team_members);
                                    $producers = explode(',', $team_members);
                                    $reporters = explode(',', $team_members);
                                    $photographers = explode(',', $team_members);
                                    $videographers = explode(',', $team_members);
                                    $socials = explode(',', $team_members);
                                    $drivers = explode(',', $team_members);
                                    $djs = explode(',', $team_members);
                                }

                                // Vehicles                                                             
                                $vehicle_qry = $conn->query("SELECT id, plate_number, make_model FROM transport_vehicles");
                            ?>
                            <!-- Team Assignment Section -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h4 class="mb-0"><i class="fas fa-users mr-2"></i>Team Assignment</h4>
                                </div>
                                <div class="card-body">
                                    <div class="form-group"> 
                                        <?php if($radio_staff || !is_null($station_show)){?>
                                        <!-- Sales Rep -->
                                        <div class="role-group">
                                            <label>Sales Rep</label>
                                            <div class="assignee-wrapper">
                                                <?php 
                                                $salesrep_qry = $admin->get_users_roles_station($conn, 'Sales Rep', $station); ?>
                                                <select id="salesrep-select" name="assignee[salesrep][]" class="custom-select custom-select-sm" multiple="multiple" <?= $disabled.$disabledPersonality.$required.$disabledDispatch ?>>
                                                    <!-- <option value="">Select a reporter</option> -->

                                                <?php if($salesrep_qry):
                                                    foreach ($salesrep_qry as $salesrep): 
                                                            if(in_array($salesrep['empid'], $salesreps))
                                                                if(!empty($disabledBroadcast) || in_array($user_role, $manager_roles))
                                                                    $all_members = array_diff($all_members, [$salesrep['empid']]);
                                                        ?>
                                                        <option value="<?= htmlspecialchars($salesrep['empid']) ?>" <?php  echo isset($salesreps) && in_array($salesrep['empid'], $salesreps) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($salesrep['display_name']) ?> (<?= htmlspecialchars($salesrep['role_name']) ?>)
                                                        </option>
                                                    <?php endforeach; else: ?>
                                                        <option>No salesreps available</option>
                                                    <?php endif; ?>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Producer -->
                                        <div class="role-group">
                                            <label>Producer</label>
                                            <div class="assignee-wrapper">
                                                <?php 
                                                $producer_qry = $admin->get_users_roles_station($conn, ['Producer', 'Broadcast Coordinator'], $station); ?>
                                                <select id="producer-select" name="assignee[producer][]" class="custom-select custom-select-sm" multiple="multiple" <?= $disabled.$disabledDispatch?>>
                                                <?php if($producer_qry):
                                                    foreach ($producer_qry as $producer): 
                                                            if(in_array($producer['empid'], $producers))
                                                                if(!empty($disabledBroadcast)|| in_array($user_role, $manager_roles))
                                                                    $all_members = array_diff($all_members, [$producer['empid']]);
                                                        ?>
                                                        <option value="<?= htmlspecialchars($producer['empid']) ?>" <?php  echo isset($producers) && in_array($producer['empid'], $producers) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($producer['display_name']) ?> (<?= htmlspecialchars($producer['role_name']) ?>)
                                                        </option>
                                                    <?php endforeach; else: ?>
                                                        <option>No producers available</option>
                                                    <?php endif; ?>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Personality -->
                                        <div class="role-group">
                                            <label>Personality</label>
                                            <div class="assignee-wrapper">
                                               
                                                <?php 
                                                $personality_qry = $admin->get_users_roles_station($conn, ['Personality', 'Programme Director'], $station); ?>
                                                <select id="personality-select" name="assignee[personality][]" class="custom-select custom-select-sm" multiple="multiple" <?= $disabled ?>>
                                                <?php if($personality_qry):
                                                    foreach ($personality_qry as $personality): 
                                                            if(in_array($personality['empid'], $personalities))
                                                                if(!empty($disabledBroadcast) || in_array($user_role, $manager_roles) || !empty($disabledPersonality))
                                                                    $all_members = array_diff($all_members, [$personality['empid']]);
                                                        ?>
                                                        <option value="<?= htmlspecialchars($personality['empid']) ?>" <?php  echo isset($personalities) && in_array($personality['empid'], $personalities) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($personality['display_name']) ?> (<?= htmlspecialchars($personality['role_name']) ?>)
                                                        </option>
                                                    <?php endforeach; else: ?>
                                                        <option>No personalities available</option>
                                                    <?php endif; ?>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Engineer (OB) -->
                                        <div class="role-group">
                                            <label>Engineer (OB)</label>
                                            <div class="assignee-wrapper">
                                                <?php 
                                                $engineer_qry = $admin->get_users_roles_station($conn, ['Engineer', 'Tech Op'], $station); ?>
                                                <select id="engineer-select-out" name="assignee[engineer][]" class="custom-select custom-select-sm" multiple="multiple" <?= $disabled.$disabledPersonality.$disabledDispatch ?>>
                                                <?php if($engineer_qry):
                                                    foreach ($engineer_qry as $engineer): 
                                                            if(in_array($engineer['empid'], $engineers))
                                                                if(!empty($disabledBroadcast) || in_array($user_role, $manager_roles))
                                                                    $all_members = array_diff($all_members, [$engineer['empid']]);
                                                        ?>
                                                        <option value="<?= htmlspecialchars($engineer['empid']) ?>" <?php  echo isset($engineers) && in_array($engineer['empid'], $engineers) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($engineer['display_name']) ?> (<?= htmlspecialchars($engineer['role_name']) ?>)
                                                        </option>
                                                    <?php endforeach; else: ?>
                                                        <option>No engineers available</option>
                                                    <?php endif; ?>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Engineer (In-House) -->
                                        <div class="role-group">
                                            <label>Engineer (Studio)</label>
                                            <div class="assignee-wrapper">
                                                <?php 
                                                $engineer_qry = $admin->get_users_roles_station($conn, ['Engineer', 'Tech Op'], $station); ?>
                                                <select id="engineer-select-in" name="studio_engineer" class="custom-select custom-select-sm" multiple="multiple" <?= $disabled.$disabledPersonality.$disabledDispatch ?>>
                                                <?php if($engineer_qry):
                                                    foreach ($engineer_qry as $engineer):
                                                        if(in_array($studio_engineer, $engineers))
                                                                if(!empty($disabledBroadcast) || in_array($user_role, $manager_roles))
                                                                    $all_members = array_diff($all_members, [$studio_engineer]); 
                                                        ?>
                                                        <option value="<?= htmlspecialchars($engineer['empid']) ?>" <?php  echo isset($engineers) && ($engineer['empid'] == $studio_engineer) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($engineer['display_name']) ?> (<?= htmlspecialchars($engineer['role_name']) ?>)
                                                        </option>
                                                    <?php endforeach; else: ?>
                                                        <option>No engineers available</option>
                                                    <?php endif; ?>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- DJ -->
                                        <div class="role-group">
                                            <label>DJ</label>
                                            <div class="assignee-wrapper">
                                                <?php
                                                $dj_qry = $admin->get_users_roles_station($conn, 'DJ', $station); ?>
                                                <select id="dj-select" name="assignee[dj][]" class="custom-select custom-select-sm" multiple="multiple" <?= $disabled.$disabledBroadcast.$requiredPersonality.$disabledDispatch ?>>
                                                <?php if($dj_qry):
                                                    foreach ($dj_qry as $dj): 
                                                            if(in_array($dj['empid'], $djs))
                                                                if(!empty($disabledPersonality) || in_array($user_role, $manager_roles))
                                                                    $all_members = array_diff($all_members, [$dj['empid']]);
                                                                
                                                            
                                                            if(!empty($disabledPersonality) || in_array($user_role, $manager_roles))    
                                                                $all_members = array_diff($all_members, ['NODJ']);

                                                        ?>
                                                        <option value="<?= htmlspecialchars($dj['empid']) ?>" <?php  echo isset($djs) && in_array($dj['empid'], $djs) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($dj['display_name']) ?> (<?= htmlspecialchars($dj['role_name']) ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                        <option value="NODJ" <?php  echo isset($djs) && in_array('NODJ', $djs) ? 'selected' : '' ?>>No DJ Required</option>
                                                    <?php endif; ?>
                                                </select>
                                            </div>

                                            <!-- Checkbox and Dropdown for Request -->
                                            <?php //if (isset($disabledPersonality)): ?>
                                            <div class="request-wrapper">
                                                <label>
                                                    <input type="checkbox" name="request[dj]" class="request-checkbox" <?= $disabled.$disabledPersonality.$disabledDispatch ?> <?php echo isset($dj_requested) && $dj_requested == 1 ? 'checked' : '' ?>><span class="font-weight-light small"> 
                                                    <?php echo (isset($dj_requested) && $dj_requested == 1) ? 'Requested' : 'Request DJ'; ?>
                                                    </span>
                                                </label>
                                                <select name="request_amount[dj]" class="request-amount form-control form-control-sm d-none">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                            <?php //endif; ?>
                                            <?php if(!empty($disabled.$disabledDispatch.$disabledPersonality) && (isset($dj_requested)) && $dj_requested == 1): ?>
                                                <input type="hidden" name="request[dj]" value="<?= $dj_requested?>">
                                            <?php endif; ?>
                                        </div>

                                        <!-- Social S&B -->
                                        <div class="role-group">
                                            <label>Social Media</label>
                                            <div class="assignee-wrapper">
                                                
                                                <?php 
                                                $social_qry = $admin->get_users_roles_station($conn, ['Multimedia','Social Media'], $station); ?>
                                                <select name="assignee[social][]" class="custom-select custom-select-sm" multiple="multiple" <?= $disabledBroadcast.$disabledPersonality.$disabledDispatch ?>>
                                                <?php if($social_qry):
                                                    foreach ($social_qry as $social): 
                                                            if(in_array($social['empid'], $socials))
                                                                if(!empty($disabledMedia)  || in_array($user_role, $manager_roles))
                                                                    $all_members = array_diff($all_members, [$social['empid']]);
                                                            
                                                            if(!empty($disabledMedia) || in_array($user_role, $manager_roles))
                                                                $all_members = array_diff($all_members, ['NOSOCIAL']);
                                                                    
                                                        ?>
                                                        <option value="<?= htmlspecialchars($social['empid']) ?>" <?php  echo isset($socials) && in_array($social['empid'], $socials) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($social['display_name']) ?> 
                                                        </option>
                                                    <?php endforeach; ?>
                                                        <option value="NOSOCIAL" <?php  echo isset($socials) && in_array('NOSOCIAL', $socials) ? 'selected' : '' ?>>No social media available</option>
                                                    <?php endif; ?>
                                                </select>
                                                
                                            </div>
                                            <!-- Checkbox and Dropdown for Request -->
                                            <?php if (isset($disabledBroadcast)): ?>
                                            <div class="request-wrapper">
                                                <label>
                                                    <input type="checkbox" name="request[social]" class="request-checkbox" <?= $disabled.$disabledDispatch ?> <?php echo isset($social_requested) && $social_requested == 1 ? 'checked' : '' ?>><span class="font-weight-light small"> 
                                                    <?php echo (isset($social_requested) && $social_requested == 1) ? 'Requested' : 'Request Social Media'; ?>
                                                        
                                                    </span>
                                                </label>
                                                <select name="request_amount[social]" class="request-amount form-control form-control-sm d-none">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                            <?php endif; ?>
                                            <?php if(!empty($disabledBroadcast || $disabledPersonality || $disabledDispatch) && (isset($social_requested)) && $social_requested == 1): ?>
                                                <input type="hidden" name="request[social]" value="<?= $social_requested?>">
                                            <?php endif; ?>
                                        </div>

                                        <?php } else { ?>
                                        <!-- Reporter -->
                                        <div class="role-group">
                                            <label>Reporters</label>
                                            <div class="assignee-wrapper">
                                                
                                                <?php 
                                                $reporter_qry = $admin->get_users_roles_station($conn, ['Reporter', 'Editor', 'Freelancer']); ?>
                                                <select name="assignee[reporter][]" class="custom-select custom-select-sm" multiple="multiple" <?= $disabled.$disabledDispatch ?>>
                                                <?php if($reporter_qry):
                                                    foreach ($reporter_qry as $reporter): 
                                                            if(in_array($reporter['empid'], $reporters))
                                                                if(!empty($disabledEditors))
                                                                    $all_members = array_diff($all_members, [$reporter['empid']]);
                                                        ?>
                                                        <option value="<?= htmlspecialchars($reporter['empid']) ?>" <?php  echo isset($reporters) && in_array($reporter['empid'], $reporters) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($reporter['display_name']) ?> (<?= htmlspecialchars($reporter['role_name']) ?>)
                                                        </option>
                                                    <?php endforeach; else: ?>
                                                        <option>No reporters available</option>
                                                    <?php endif; ?>
                                                </select>
                                            </div>
                                        </div>
                                                
                                        <!-- Photographer -->
                                        <div class="role-group">
                                            <label>Photographers</label>
                                            <div class="assignee-wrapper">
                                                
                                                <?php 
                                                $photographer_qry = $admin->get_users_roles_station($conn, ['Photographer','Photo Editor']); ?>
                                                <select name="assignee[photographer][]" class="custom-select custom-select-sm" multiple="multiple" <?= $disabledDispatch.$disabledEditors.$disabledMedia ?><?= $required?>>
                                                <?php if($photographer_qry):
                                                    foreach ($photographer_qry as $photographer): 
                                                            if(in_array($photographer['empid'], $photographers))
                                                                if(!empty($disabledDigital))
                                                                    $all_members = array_diff($all_members, [$photographer['empid']]);

                                                            if(!empty($disabledDigital))
                                                                $all_members = array_diff($all_members, ['NOPHOTO']);
                                                                    
                                                        ?>
                                                        <option value="<?= htmlspecialchars($photographer['empid']) ?>" <?php  echo isset($photographers) && in_array($photographer['empid'], $photographers) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($photographer['display_name']) ?> 
                                                        </option>
                                                    <?php endforeach; ?>
                                                        <option value="NOPHOTO" <?php  echo isset($photographers) && in_array('NOPHOTO', $photographers) ? 'selected' : '' ?>>No photographers available</option>
                                                    <?php endif; ?>
                                                </select>
                                            </div>
                                            <!-- Checkbox and Dropdown for Request -->
                                            <?php if (isset($disabledEditors)): ?>
                                            <div class="request-wrapper">
                                                <label>
                                                    <input type="checkbox" name="request[photographer]" class="request-checkbox" <?= $disabled.$disabledDispatch ?> <?php echo isset($photo_requested) && $photo_requested == 1 ? 'checked' : '' ?>><span class="font-weight-light small"> 
                                                    <?php echo (isset($photo_requested) && $photo_requested == 1) ? 'Requested' : 'Request Photographer'; ?>
                                                    </span>
                                                </label>
                                                <select name="request_amount[photographer]" class="request-amount form-control form-control-sm d-none">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                            <?php endif; ?>
                                            <?php if(!empty($disabledMedia.$disabledDispatch) && (isset($photo_requested)) && $photo_requested == 1): ?>
                                                <input type="hidden" name="request[photographer]" value="<?= $photo_requested?>">
                                            <?php endif; ?>
                                        </div>

                                        <!-- Videographer -->
                                        <div class="role-group">
                                            <label>Videographers</label>
                                            <div class="assignee-wrapper">
    
                                                <?php 
                                                $videographer_qry = $admin->get_users_roles_station($conn, ['Videographer']); ?>
                                                <select name="assignee[videographer][]" class="custom-select custom-select-sm" multiple="multiple" <?= $disabledDispatch.$disabledEditors.$disabledDigital ?>>
                                                <?php if($videographer_qry):
                                                    foreach ($videographer_qry as $videographer): 
                                                            if(in_array($videographer['empid'], $videographers))
                                                                if(!empty($disabledMedia))
                                                                    $all_members = array_diff($all_members, [$videographer['empid']]);

                                                             if(!empty($disabledMedia))
                                                                    $all_members = array_diff($all_members, ['NOVIDEO']);
                                                        ?>
                                                        <option value="<?= htmlspecialchars($videographer['empid']) ?>" <?php  echo isset($videographers) && in_array($videographer['empid'], $videographers) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($videographer['display_name']) ?> 
                                                        </option>
                                                    <?php endforeach; ?>
                                                        <option value="NOVIDEO" <?php  echo isset($videographers) && in_array('NOVIDEO', $videographers) ? 'selected' : '' ?>>No videographers available</option>
                                                    <?php endif; ?>
                                                </select>
                                            </div>
                                            <!-- Checkbox and Dropdown for Request -->
                                            <?php if (isset($disabledEditors)): ?>
                                            <div class="request-wrapper">
                                                <label>
                                                    <input type="checkbox" name="request[videographer]" class="request-checkbox" <?= $disabled.$disabledDispatch ?> <?php echo isset($video_requested) && $video_requested == 1 ? 'checked' : '' ?>><span class="font-weight-light small"> 
                                                    <?php echo (isset($video_requested) && $video_requested == 1) ? 'Requested' : 'Request Videographer'; ?>
                                                    </span>
                                                </label>
                                                <select name="request_amount[videographer]" class="request-amount form-control form-control-sm d-none">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                            <?php endif; ?>
                                            <?php if(!empty($disabledDigital.$disabledDispatch) && (isset($video_requested)) && $video_requested == 1): ?>
                                                <input type="hidden" name="request[videographer]" value="<?= $video_requested?>">
                                            <?php endif; ?>
                                        </div>

                                        <!-- Social -->
                                        <div class="role-group">
                                            <label>Social Media</label>
                                            <div class="assignee-wrapper">
                                                
                                                <?php 
                                                $social_qry = $admin->get_users_roles_station($conn, ['Multimedia', 'Social Media']); ?>
                                                <select name="assignee[social][]" class="custom-select custom-select-sm" multiple="multiple" <?= $disabledDispatch.$disabledEditors.$disabledDigital?>>
                                                <?php if($social_qry):
                                                    foreach ($social_qry as $social): 
                                                            if(in_array($social['empid'], $socials))
                                                                if(!empty($disabledMedia))
                                                                    $all_members = array_diff($all_members, [$social['empid']]);
                                                                
                                                            if(!empty($disabledMedia) || in_array($user_role, $manager_roles))
                                                                $all_members = array_diff($all_members, ['NOSOCIAL']);
                                                                    
                                                        ?>
                                                        <option value="<?= htmlspecialchars($social['empid']) ?>" <?php  echo isset($socials) && in_array($social['empid'], $socials) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($social['display_name']) ?> 
                                                        </option>
                                                    <?php endforeach; ?>
                                                        <option value="NOSOCIAL" <?php  echo isset($socials) && in_array('NOSOCIAL', $socials) ? 'selected' : '' ?>>No social media available</option>
                                                    <?php endif; ?>
                                                </select>
                                            </div>
                                            <!-- Checkbox and Dropdown for Request -->
                                            <?php if (isset($disabledEditors)): ?>
                                            <div class="request-wrapper">
                                                <label>
                                                    <input type="checkbox" name="request[social]" class="request-checkbox" <?= $disabled.$disabledDispatch ?> <?php echo isset($social_requested) && $social_requested == 1 ? 'checked' : '' ?>><span class="font-weight-light small"> 
                                                    <?php echo (isset($social_requested) && $social_requested == 1) ? 'Requested' : 'Request Social Media'; ?>
                                                        
                                                    </span>
                                                </label>
                                                <select name="request_amount[social]" class="request-amount form-control form-control-sm d-none">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                            <?php endif; ?>
                                            <?php if(!empty($disabledDigital.$disabledDispatch) && (isset($social_requested)) && $social_requested == 1): ?>
                                                <input type="hidden" name="request[social]" value="<?= $social_requested?>">
                                            <?php endif; ?>
                                        </div>
                                        <?php } ?>
                                        <!-- Drivers -->
                                        <div class="role-group <?= ($user_role == 'Dispatcher') ? '' : 'd-none' ?>" >
                                            <label>Drivers</label>
                                            <div class="assignee-wrapper">
                                                
                                                <?php 
                                                $driver_qry = $admin->get_users_roles_station($conn, 'Driver'); ?>
                                                <select name="assignee[driver][]" class="custom-select custom-select-sm" multiple="multiple" <?= $disabledEditors.$disabledDigital ?><?= $requiredDispatch?>>
                                                <?php if($driver_qry):
                                                    foreach ($driver_qry as $driver): 
                                                        if(!empty($disabledDispatch) || !empty($disabledMedia))
                                                                if(!empty($disabledMedia))
                                                                    $all_members = array_diff($all_members, [$driver['empid']]);
                                                        ?>
                                                        <option value="<?= htmlspecialchars($driver['empid']) ?>" <?php  echo isset($drivers) && in_array($driver['empid'], $drivers) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($driver['display_name']) ?> 
                                                        </option>
                                                    <?php endforeach; else: ?>
                                                        <option>No driver media available</option>
                                                    <?php endif; ?>
                                                </select>
                                            </div>
                                            <!-- Checkbox and Dropdown for Request -->
                                            <?php if (isset($disabledEditors)): ?>
                                            <div class="request-wrapper d-none">
                                                <label>
                                                    <input type="checkbox" name="request[driver]" class="request-checkbox" disabled <?php echo isset($driver_requested) && $driver_requested == 1 ? 'checked' : '' ?>><span class="font-weight-light small"> 
                                                    <?php echo (isset($driver_requested) && $driver_requested == 1) ? 'Requested' : 'Request Social Media'; ?>
                                                        
                                                    </span>
                                                </label>
                                                <select name="request_amount[driver]" class="request-amount form-control form-control-sm d-none">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                            <?php endif; ?>
                                            <?php if(!empty($disabledDigital)): ?>
                                                <input type="hidden" name="request[driver]" value="<?= $driver_requested?>" disabled>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Vehicles -->
                                        <div class="role-group <?= ($user_role == 'Dispatcher') ? '' : 'd-none' ?>" >
                                            <label>Assigned Vehicle</label>
                                            <div class="assignee-wrapper">
                                                <select name="transport_id" class="custom-select custom-select-sm" <?= $requiredDispatch?> >
                                                    <option value="">Assign a vehicle
                                                    <?php
                                                    
                                                    
                                                    if ($vehicle_qry) {
                                                        while ($user_row = $vehicle_qry->fetch_assoc()):
                                                    ?>
                                                    <option value="<?php echo htmlspecialchars($user_row['id']); ?>" <?php echo isset($transport_id) && $transport_id == $user_row['id'] ? 'selected' : '' ?>>
                                                        <?php echo htmlspecialchars($user_row['make_model']) ; ?>
                                                    </option>
                                                    <?php 
                                                        endwhile;
                                                    } else {
                                                        echo "<option>No vehicles available</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Get team members if boxes disabled -->
                            <?php if(!empty($disabled.$disabledDispatch.$disabledEditors.$disabledBroadcast.$disabledPersonality)){
                                $teamRem = implode(',', $all_members);   
                                //  print_r($teamRem); // Debug team_members going to the db                            ?>
                            <input type="hidden" name="team" value="<?= $teamRem ?>" />
                            <?php } ?>
                            <?php 

                                //$currentStatus = (in_array($user_role,['Dispatcher', 'Op Manager'])) ? 'Approved': 'Pending';
                                $currentStatus = ($user_role === 'Op Manager') ? 'Endorsed' : (in_array($user_role, ['Dispatcher']) ? 'Approved' : 'Pending');
                                $currentStatus = (isset($status) && in_array($status,['Approved'])) ? $status : $currentStatus;
                            ?>
                            <!-- Stub out Status -->
                            <input name="status" type="hidden" value="<?php echo htmlspecialchars($currentStatus); ?>">
                            <input name="uid" type="hidden" value="<?php echo (isset($uid)? $uid : ''); ?>">
                            <!-- <input name="transport_confirmed" type="hidden" value="<?php //echo htmlspecialchars($transport_confirmed ?? ''); ?>"> -->
                            <?php if($login_role_id < 5){ ?>
                            <div class="form-group d-none">
                                <label for="">Status</label>
                                <?php //echo $status; 
                                ?>
                                <select name="status" id="status" class="custom-select custom-select-sm">
                                    <option value="Pending" <?php echo isset($status) && $status == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="Approved" <?php echo isset($status) && $status == 'Approved' ? 'selected' : '' ?>>Approved</option>
                                    <option value="Not Approved" <?php echo isset($status) && $status == '' ? 'selected' : '' ?>>Not Approved</option>
                                </select>
                            </div>
                            <?php } ?>
                            <?php if (intval($id) > 0){?>
                            <div class="text-start small d-none">
                            <!-- Equipment Request Checkbox -->
                                <div class="form-check mt-3">
                                    <input class="form-check-input" type="checkbox" id="equipment_requested" <?php echo (!empty($equipment_requested)) ? 'checked=checked' : ''; ?>>
                                    <label class="form-check-label" for="equipment_requested"> <?php echo !empty($equipment_requested) ? 'Equipment Requested' : 'Request Equipment'; ?> </label>
                                </div>    
                            </div>
                            <?php } ?>
                        </div>
                        
                    </div>
                    <!-- <hr /> -->
                  
                    <!-- Form Footer -->
                    <div class="card mt-1">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <div class="custom-control custom-switch mb-2 <?= (in_array($user_role, ['Broadcast Coordinator'])) ? 'd-none' : '' ?>">
                                    <input type="checkbox" class="custom-control-input" id="send_notification" name="send_notification" checked>
                                    <label class="custom-control-label" for="send_notification">
                                        <?= (isset($send_notification) && $send_notification == 1) ? 
                                            'Notification Sent (uncheck if you do not wish to send another)' : 'Send Notification' ?>
                                    </label>
                                </div>
                                
                                <?php if (isset($_GET['id']) && (!in_array($user_role, ['Broadcast Coordinator', 'Photo Editor', 'Multimedia', 'Programme Director']))): ?>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input text-danger" id="is_cancelled" name="is_cancelled"  
                                           <?= (isset($is_cancelled) && $is_cancelled == 1) ? 'checked' : '' ?>>
                                    <label class="custom-control-label text-danger" for="is_cancelled">
                                        <?= (isset($is_cancelled) && $is_cancelled == 1) ? 'Cancelled' : 'Cancel Assignment' ?>
                                    </label>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (in_array($user_role, ['Broadcast Coordinator', 'Producer' , 'Programme Director', 'ITAdmin'])): ?> <!-- && (isset($status) && $status == 'Pending') -->
                                <div class="custom-control custom-switch mt-2">
                                    <input type="checkbox" class="custom-control-input text-primary" id="alert_manager" name="alert_manager" value="1" checked 
                                           <?= (isset($alert_manager) && $alert_manager == 1) ? 'checked' : '' ?>>
                                    <label class="custom-control-label text-primary" for="alert_manager">
                                        Alert Manager for Approval
                                    </label>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="ml-auto">
                                <a class="btn btn-outline-secondary mr-2" href="index.php?page=home">
                                    <i class="fas fa-times mr-1"></i> Cancel
                                </a>
                                <button class="btn btn-primary" id="save_assignment_button">
                                    <i class="fas fa-save mr-1"></i> <?= empty($id) ? 'Save' : 'Update' ?> Assignment
                                </button>
                            </div>
                        </div>
                    </div>
                
                    </form>
            </div>
        </div>
    </div>
</div>

<!-- Equipment Request Modal -->
<?php include('modal_equipment_request.php'); ?>

<script>

function convertTo24Hour(time) {
    const [hoursMinutes, period] = time.split(" ");
    let [hours, minutes] = hoursMinutes.split(":");
    hours = parseInt(hours, 10);

    if (period === "PM" && hours !== 12) {
        hours += 12;
    }
    if (period === "AM" && hours === 12) {
        hours = 0;
    }
    return `${hours.toString().padStart(2, "0")}:${minutes}`;
}

function loadUsersForStation(roles, station, targetSelectId) {
    const rolesParam = Array.isArray(roles) ? roles.join(',') : roles;
    
    $.ajax({
        url: 'get_users.php',
        method: 'GET',
        data: {
            roles: rolesParam,
            station: station
        },
        dataType: 'json',
        success: function(users) {
            const $select = $('#' + targetSelectId);
            $select.empty();
            
            $.each(users, function(index, user) {
                $select.append(
                    $('<option></option>')
                        .val(user.empid)
                        .text(user.display_name + ' (' + user.role_name + ')')
                );
            });
        },
        error: function(xhr, status, error) {
            console.error('Error loading users:', error);
        }
    });
}

$(document).ready(function(){

    $('#station_show').on('change', function() {
        const station = $(this).find('option:selected').data('station');
        
        // Load selects based on the selected station
        loadUsersForStation('Sales Rep', station, 'salesrep-select');
        loadUsersForStation(['Personality', 'Programme Director'], station, 'personality-select');
        loadUsersForStation(['Engineer', 'Tech Op'], station, 'engineer-select-in');
        loadUsersForStation(['Engineer', 'Tech Op'], station, 'engineer-select-out');
        loadUsersForStation(['Producer', 'Broadcast Coordinator'], station, 'producer-select');
        loadUsersForStation('DJ', station, 'dj-select');

    });
    
    var isReadonly = $('.summernote').data('readonly');
    // Initialize Summernote editor
    $('.summernote').each(function() {
        
        $(this).summernote({
            height: 100,
            toolbar: [
            ['style', ['bold', 'italic', 'underline']],
            ['para', ['ul', 'ol', 'paragraph']],
            ],
            disableDragAndDrop: true,
        });
        // Set the editor to readonly if isReadonly is true
        if (isReadonly == true) {
            $(this).summernote('disable');
        }
    });

    $('.summernote').on('summernote.paste', function(e, ne) {
        // Prevent both Summernote's default handling and browser's default
        e.preventDefault();
        ne.preventDefault();
        
        // Get plain text from clipboard
        let bufferText = ((e.originalEvent || ne.originalEvent).clipboardData || window.clipboardData).getData('text/plain');
        
        // Insert plain text at the cursor position
        $(this).summernote('pasteHTML', $('<div>').text(bufferText).html());
        
        return false; // Prevent further propagation
    });

    // Show modal when checkbox is checked
    $('#equipment_requested').change(function() {
        if (this.checked) {
            $('#equipmentModal').modal('show');
        }
    });

    $('#submitEquipmentRequest').click(function() {
            $('#submitEquipmentRequest').prop('disabled', true);

            let assignmentId = "<?= $id ?>"; // Get assignment ID
            let assignmentDate = $('#assignment_date').val().trim(); //
            let startTime = $('#start_time').val().trim(); //
            let endTime = $('#end_time').val().trim(); //
            let departTime = $('#depart_time').val().trim(); //
            let assignment = $('#title').val().trim(); //
            let equipmentDetails = $('#equipment_details').val().trim(); //
            let request = $('#equipment_requested').is(':checked') ? 1 : 0;

            if (equipmentDetails === "") {
                $('#submitEquipmentRequest').prop('disabled', false);
                alert_toast('Please enter equipment details.', 'error');
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

                    } else {
                        $('#submitEquipmentRequest').prop('disabled', false);
                        alert_toast('Failed to submit request.', 'error');
                    }
                },
                error: function() {
                    $('#submitEquipmentRequest').prop('disabled', false);
                    alert_toast('Something went wrong. Please try again later.', 'error');
                }
            });
        });
    //for multiselect dropdowns
    $('.custom-select-sm').select2();
    

    // Event listener for changes in start_time or end_time
    $("#start_time, #end_time").on("change", function () {
        const startTime = $("#start_time").val();
        const endTime = $("#end_time").val();
        // Ensure both times are selected
        if (!startTime || !endTime) {
            return;
        }

        // Convert times to Date objects for comparison
        const startDateTime = new Date("2024-01-01T" + convertTo24Hour(startTime) + ":00");
        const endDateTime = new Date("2024-01-01T" + convertTo24Hour(endTime) + ":00");

        // Handle end time being on the next day
        // if (endDateTime <= startDateTime) {
        //     endDateTime.setDate(endDateTime.getDate() + 1); // Move end time to the next day
        // }

        if (startDateTime >= endDateTime) {
            // Alert the user and reset the end time if invalid
            alert_toast('Start Time must be earlier than End Time. Please correct your selection', 'error');
            $("#end_time").val("");
        }
    });

    $('#equipment_requested').change(function() {
        $('#equipment_field').toggleClass('d-none', !this.checked);
    });

    $('#manage_assignment').submit(function(e){
      e.preventDefault();
      start_load(); 
      $('#save_assignment_button').prop('disabled', true);
        // Check if any radio button is selected
        // if (!$("input[name='drop_option']:checked").val()) {
        //     // Prevent form submission
        //     e.preventDefault();
        //     end_load(); 
        //      alert_toast('Please select a transport option before submitting the form', 'error');
        //     return;
        // }
      
        $.ajax({
            url: 'ajax.php?action=save_assignment',
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
            try {
                let response = JSON.parse(resp);
                if (response.status === 'success') {
                    alert_toast('The assignment has been updated', 'success');
                    setTimeout(() => {
                        location.href = 'index.php?page=view_assignment&id=' + response.message; // Redirect after success
                    }, 3000);
                } else if (response.status === 'error') {
                    alert_toast(response.message, 'error');
                    $('#save_assignment_button').prop('disabled', false);
                } else {
                    alert_toast('Unexpected response: ' + resp, 'error');
                    $('#save_assignment_button').prop('disabled', false);
                }
            } catch (e) {
                console.error('Error parsing JSON response:', e);
                alert_toast('An error occurred while processing the response.', 'error');
                $('#save_assignment_button').prop('disabled', false);
            }
            end_load(); 

        
            }
        });
    });

    // Initialize: Hide all dropdowns initially
    $('.request-amount').hide();

    // Toggle dropdown visibility when checkbox is clicked
    $('.request-checkbox').on('change', function() {
        const dropdown = $(this).closest('.request-wrapper').find('.request-amount');
        if (this.checked) {
            dropdown.show();
        } else {
            dropdown.hide();
        }
    });
  });                                        
</script>