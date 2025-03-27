<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['login_id'])) {
    header("Location: login.php?returnUrl=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}


include 'db_connect.php'; // Removed sidebar and topbar includes

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$radio_staff = $_SESSION['login_sb_staff'] == 1 ? true : false;
$login_role_id = $_SESSION['role_id']? $_SESSION['role_id'] : 0;
$user_role = $_SESSION['role_name'] ?? '';

//Declare user roles for filtering
$editor_roles = ['Editor', 'Manager', 'Dept Admin', 'Op Manager'];
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

    .remove-assignee,
    .add-more-assignee {
        font-size: 1.2rem;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        padding: 0 8px;
    }

    .remove-assignee {
        color: #dc3545;
        margin-left: 10px;
    }

    .remove-assignee:hover {
        color: #c82333;
    }

    .add-more-assignee {
        color: #28a745;
        font-size: 1.5rem;
        margin-left: 10px;
    }

    .add-more-assignee:hover {
        color: #218838;
    }

    select {
        width: 250px;
    }

    .button-container {
        display: flex;
        align-items: center;
        position: relative;
    }

    .role-group {
        margin-bottom: 15px;
    }

    .info-text {
        font-size: 0.9rem;
        color: #888;
        margin-top: 5px;
        display: none;
        position: absolute;
        top: -20px;
        left: 0;
    }

    .button-container:hover .info-text {
        display: block;
    }

    .button-container > .info-text {
        margin-top: 2px;
    }
</style>

<div class="container">
    <?php // 'SB STaff:'.$_SESSION['login_sb_staff'] ?>
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <form action="" id="manage_assignment" method="POST">
                    <div class="row">
                        <div class="col-md-6 border-right">
                            <div class="form-group">
                                <label for="assignment_date" class="control-label">Assignment Date</label>
                                <input type="date" name="assignment_date" id="assignment_date" class="form-control form-control-sm" required value="<?php echo htmlspecialchars($assignment_date ?? ''); ?>" <?= $readonly.$readonlyDispatch.$readonlyPersonality ?>>
                            </div>
                            <div class="form-group row">
                                <!-- Start Time -->
                                <?php if($disabled.$disabledDispatch){ ?>
                                <input type="hidden" name="start_time" value="<?= $start_time ?>" />
                                <?php } ?>
                                <div class="col-md-6">
                                    <label for="start_time" class="control-label">Start Time</label>
                                    <select name="start_time" id="start_time" class="custom-select custom-select-sm" required <?= $disabled.$disabledDispatch ?>>
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
                                <?php if($disabled.$disabledDispatch){ ?>
                                <input type="hidden" name="end_time" value="<?= $end_time ?>" />
                                <?php } ?>
                                <div class="col-md-6">
                                    <label for="end_time" class="control-label">End Time</label>
                                    <select name="end_time" id="end_time" class="custom-select custom-select-sm" <?= $disabled.$disabledDispatch ?>>
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
                                <?php if($disabled.$disabledDispatch){ ?>
                                <input type="hidden" name="depart_time" value="<?= $depart_time ?>" />
                                <?php } ?>
                                <label for="depart_time" class="control-label">Depart Time (optional)</label>
                                <select name="depart_time" id="depart_time" class="custom-select custom-select-sm" <?= $disabled.$disabledDispatch.$disabledPersonality ?>>
                                    <option value="" selected="selected">Select Depart Time</option>
                                    <?php 
                                    // Generate time options
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
                                            (isset($depart_time) && $depart_time == $t[1] ? ' selected' : '') .
                                            '>' . htmlspecialchars($t[1]) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <?php if($radio_staff){?>
                            <div class="form-group">
                                <label for="station_show" class="control-label">Show</label>
                                <select name="station_show" id="station_show" class="custom-select custom-select-sm" <?= $disabledPersonality.$required ?>>
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
                                        echo '<option value="' . $display_text . '"'. $selected .'>' . $display_text . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        <?php } ?>
                            <div class="form-group">
                                <label for="title" class="control-label">Assignment</label>
                                <input type="text" name="title" id="title" class="form-control form-control-sm" required value="<?php echo htmlspecialchars_decode($title ?? ''); ?>" <?= $readonly.$readonlyDispatch.$readonlyPersonality ?>>
                            </div>
                            <div class="form-group">
                                <label for="location" class="control-label">Venue</label>
                                <input type="text" name="location" id="location" class="form-control form-control-sm" required value="<?php echo htmlspecialchars($location ?? ''); ?>" <?= $readonly.$readonlyDispatch.$readonlyPersonality ?>>
                            </div>
                            <div class="form-group">
                                <label class="control-label">Notes</label>
                                <textarea name="description" class="form-control form-control-sm summernote textarea" <?= $readonly.$readonlyDispatch.$readonlyPersonality ?>><?php echo htmlspecialchars_decode($description ?? ''); ?></textarea>
                            </div>
                            <?php if (isset($_GET['id']) && (in_array($user_role, $editor_roles))){ ?>
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
                            <?php } ?>
                            <?php if ($radio_staff && !empty($readonlyPersonality)){ ?>
                                <input type="hidden" name="request_permit" value="<?= $request_permit?>">
                                <input type="hidden" name="transport_confirmed" value="<?= $transport_confirmed?>">
                            <?php } ?>
                           
                        </div>

                        <div class="col-md-6">
                            <div class="form-group d-none">
                                <label class="form-label">Transport Option</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="drop_option" id="dropOffOnly" value="dropOffOnly" <?php echo isset($drop_option) && $drop_option == 'dropOffOnly' ? 'checked' : '' ?> <?= $disabled.$disabledDispatch ?>>
                                        <label class="form-check-label" for="dropOffOnly">
                                            Drop Off Only
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="drop_option" id="dropOffReturn" value="dropOffReturn" <?php echo isset($drop_option) && $drop_option == 'dropOffReturn' ? 'checked' : '' ?> <?= $disabled.$disabledDispatch ?>>
                                        <label class="form-check-label" for="dropOffReturn">
                                            Drop Off/Return
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="drop_option" id="pickupOnly" value="pickupOnly" <?php echo isset($drop_option) && $drop_option == 'pickupOnly' ? 'checked' : '' ?> <?= $disabled.$disabledDispatch ?>>
                                        <label class="form-check-label" for="pickupOnly">
                                            Pick Up
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="drop_option" id="noTransport" value="noTransport" <?php echo isset($drop_option) && $drop_option == '' || (isset($drop_option) && $drop_option == NULL) ? 'checked' : '' ?> <?= $disabled.$disabledDispatch ?>>
                                        <label class="form-check-label" for="noTransport">
                                            No Transport Required
                                        </label>
                                    </div>
                            </div>
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($id ?? ''); ?>">
                            <input type="hidden" name="assigned_by" value="<?php echo htmlspecialchars($assigned_by ?? ''); ?>">
                            <?php
                                $all_members = [];
                                $teamRem = "";

                            ?>
                            <div class="form-group"> 
                                
                            <?php if($radio_staff){?>
                                <!-- Sales Rep -->
                                <div class="role-group">
                                    <label>Sales Rep</label>
                                    <div class="assignee-wrapper">
                                        <select name="assignee[salesrep][]" class="custom-select custom-select-sm" multiple="multiple" <?= $disabledPersonality.$required ?>>
                                            <!-- <option value="">Select a reporter</option> -->
                                            <?php
                                                $salesreps = [];

                                                if(isset($team_members))
                                                    $all_members = explode(',', $team_members);

                                                // Fetch users with role_id = 5 (for salesreps)
                                                if(isset($team_members))
                                                    $salesreps = explode(',', $team_members);
                                            
                                                $user_qry = $conn->query("SELECT u.empid, u.firstname, u.lastname, r.role_name 
                                                FROM users u 
                                                JOIN roles r ON r.role_id = u.role_id
                                                WHERE r.role_name in ('Sales Rep')
                                                ORDER BY 
                                                    r.role_name, u.firstname;
                                                                                             ");
                                            if ($user_qry) {
                                                while ($user_row = $user_qry->fetch_assoc()):
                                                    if(in_array($user_row['empid'], $salesreps))
                                                        if(!empty($disabledPersonality))
                                                            $all_members = array_diff($all_members, [$user_row['empid']]);
                                                   
                                            ?>
                                            <option value="<?php echo htmlspecialchars($user_row['empid']); ?>" <?php  echo isset($salesreps) && in_array($user_row['empid'], $salesreps) ? 'selected' : '' ?>>
                                                <?php echo trim($user_row['firstname']) . ' ' . trim($user_row['lastname']).' ('.$user_row['role_name'].')'; ?>
                                            </option>
                                            <?php 
                                                endwhile;
                                            } else {
                                                echo "<option>No salesreps available</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Personality -->
                                <div class="role-group">
                                    <label>Personality</label>
                                    <div class="assignee-wrapper">
                                        <select name="assignee[personality][]" class="custom-select custom-select-sm" multiple="multiple" <?= $disabledPersonality ?>>
                                            <!-- <option value="">Select a reporter</option> -->
                                            <?php
                                                $personalities = [];

                                                if(isset($team_members))
                                                    $all_members = explode(',', $team_members);

                                                // Fetch users with role_id = 5 (for personalities)
                                                if(isset($team_members))
                                                    $personalities = explode(',', $team_members);
                                            
                                                $user_qry = $conn->query("SELECT u.empid, u.firstname, u.lastname, r.role_name 
                                                FROM users u 
                                                JOIN roles r ON r.role_id = u.role_id
                                                WHERE r.role_name in ('Personality', 'Programme Director')
                                                ORDER BY 
                                                    r.role_name, u.firstname;
                                                                                             ");
                                            if ($user_qry) {
                                                while ($user_row = $user_qry->fetch_assoc()):
                                                    if(in_array($user_row['empid'], $personalities))
                                                        if(!empty($disabledPersonality))
                                                            $all_members = array_diff($all_members, [$user_row['empid']]);
                                                   
                                            ?>
                                            <option value="<?php echo htmlspecialchars($user_row['empid']); ?>" <?php  echo isset($personalities) && in_array($user_row['empid'], $personalities) ? 'selected' : '' ?>>
                                                <?php echo trim($user_row['firstname']) . ' ' . trim($user_row['lastname']).' ('.$user_row['role_name'].')'; ?>
                                            </option>
                                            <?php 
                                                endwhile;
                                            } else {
                                                echo "<option>No personalities available</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- DJ -->
                                <div class="role-group">
                                    <label>DJ</label>
                                    <div class="assignee-wrapper">
                                        <select name="assignee[dj][]" class="custom-select custom-select-sm" multiple="multiple" <?= $disabledBroadcast.$requiredPersonality ?>>
                                            <!-- <option value="">Select a reporter</option> -->
                                            <?php
                                                $djs = [];

                                                if(isset($team_members))
                                                    $all_members = explode(',', $team_members);

                                                // Fetch users with role_id = 5 (for djs)
                                                if(isset($team_members))
                                                    $djs = explode(',', $team_members);
                                            
                                                $user_qry = $conn->query("SELECT u.empid, u.firstname, u.lastname, r.role_name 
                                                FROM users u 
                                                JOIN roles r ON r.role_id = u.role_id
                                                WHERE r.role_name in ('DJ')
                                                ORDER BY 
                                                    r.role_name, u.firstname;
                                                                                             ");
                                            if ($user_qry) {
                                                while ($user_row = $user_qry->fetch_assoc()):
                                                    if(in_array($user_row['empid'], $djs))
                                                        if(!empty($disabledBroadcast))
                                                            $all_members = array_diff($all_members, [$user_row['empid']]);
                                                   
                                            ?>
                                            <option value="<?php echo htmlspecialchars($user_row['empid']); ?>" <?php  echo isset($djs) && in_array($user_row['empid'], $djs) ? 'selected' : '' ?>>
                                                <?php echo trim($user_row['firstname']) . ' ' . trim($user_row['lastname']).' ('.$user_row['role_name'].')'; ?>
                                            </option>
                                            <?php 
                                                endwhile;
                                            } else {
                                                echo "<option>No djs available</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                     <!-- Checkbox and Dropdown for Request -->
                                     <?php //if (isset($disabledPersonality)): ?>
                                    <div class="request-wrapper">
                                        <label>
                                            <input type="checkbox" name="request[dj]" class="request-checkbox" <?= $disabledPersonality.''.$requiredPersonality ?> <?php echo isset($dj_requested) && $dj_requested == 1 ? 'checked' : '' ?>><span class="font-weight-light small"> 
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
                                    <?php if(!empty($disabledMedia) && (isset($dj_requested)) && $dj_requested == 1): ?>
                                        <input type="hidden" name="request[dj]" value="<?= $dj_requested?>">
                                    <?php endif; ?>
                                </div>

                                <!-- Engineer -->
                                <div class="role-group">
                                    <label>Engineer</label>
                                    <div class="assignee-wrapper">
                                        <select name="assignee[engineer][]" class="custom-select custom-select-sm" multiple="multiple" <?= $disabledPersonality ?>>
                                            <!-- <option value="">Select a reporter</option> -->
                                            <?php
                                                $engineers = [];

                                                if(isset($team_members))
                                                    $all_members = explode(',', $team_members);

                                                // Fetch users with role_id = 5 (for engineers)
                                                if(isset($team_members))
                                                    $engineers = explode(',', $team_members);
                                            
                                                $user_qry = $conn->query("SELECT u.empid, u.firstname, u.lastname, r.role_name 
                                                FROM users u 
                                                JOIN roles r ON r.role_id = u.role_id
                                                WHERE r.role_name in ('Engineer')
                                                ORDER BY 
                                                    r.role_name, u.firstname;
                                                                                             ");
                                            if ($user_qry) {
                                                while ($user_row = $user_qry->fetch_assoc()):
                                                    if(in_array($user_row['empid'], $engineers))
                                                        if(!empty($disabledPersonality))
                                                            $all_members = array_diff($all_members, [$user_row['empid']]);
                                                   
                                            ?>
                                            <option value="<?php echo htmlspecialchars($user_row['empid']); ?>" <?php  echo isset($engineers) && in_array($user_row['empid'], $engineers) ? 'selected' : '' ?>>
                                                <?php echo trim($user_row['firstname']) . ' ' . trim($user_row['lastname']).' ('.$user_row['role_name'].')'; ?>
                                            </option>
                                            <?php 
                                                endwhile;
                                            } else {
                                                echo "<option>No engineers available</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Producer -->
                                <div class="role-group">
                                    <label>Producer</label>
                                    <div class="assignee-wrapper">
                                        <select name="assignee[producer][]" class="custom-select custom-select-sm" multiple="multiple" <?= $disabledPersonality ?>>
                                            <!-- <option value="">Select a reporter</option> -->
                                            <?php
                                                $producers = [];

                                                if(isset($team_members))
                                                    $all_members = explode(',', $team_members);

                                                // Fetch users with role_id = 5 (for producers)
                                                if(isset($team_members))
                                                    $producers = explode(',', $team_members);
                                            
                                                $user_qry = $conn->query("SELECT u.empid, u.firstname, u.lastname, r.role_name 
                                                FROM users u 
                                                JOIN roles r ON r.role_id = u.role_id
                                                WHERE r.role_name in ('Producer', 'Broadcast Coordinator')
                                                ORDER BY 
                                                    r.role_name, u.firstname;
                                                                                             ");
                                            if ($user_qry) {
                                                while ($user_row = $user_qry->fetch_assoc()):
                                                    if(in_array($user_row['empid'], $producers))
                                                        if(!empty($disabledPersonality))
                                                            $all_members = array_diff($all_members, [$user_row['empid']]);
                                                   
                                            ?>
                                            <option value="<?php echo htmlspecialchars($user_row['empid']); ?>" <?php  echo isset($producers) && in_array($user_row['empid'], $producers) ? 'selected' : '' ?>>
                                                <?php echo trim($user_row['firstname']) . ' ' . trim($user_row['lastname']).' ('.$user_row['role_name'].')'; ?>
                                            </option>
                                            <?php 
                                                endwhile;
                                            } else {
                                                echo "<option>No producers available</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <?php } ?>
                                <?php if(!$radio_staff){?>
                                <!-- Reporter -->
                                <div class="role-group">
                                    <label>Reporters</label>
                                    <div class="assignee-wrapper">
                                        <select name="assignee[reporter][]" class="custom-select custom-select-sm" multiple="multiple" <?= $disabled.$disabledDispatch?> required>
                                            <!-- <option value="">Select a reporter</option> -->
                                            <?php
                                                $reporters = [];

                                                if(isset($team_members))
                                                    $all_members = explode(',', $team_members);

                                                // Fetch users with role_id = 5 (for reporters)
                                                if(isset($team_members))
                                                    $reporters = explode(',', $team_members);
                                            
                                                $user_qry = $conn->query("SELECT u.empid, u.firstname, u.lastname, r.role_name 
                                                FROM users u 
                                                JOIN roles r ON r.role_id = u.role_id
                                                WHERE r.role_name in ('Reporter', 'Editor', 'Freelancer')
                                                ORDER BY 
                                                    r.role_name, u.firstname;
                                                                                             ");
                                            if ($user_qry) {
                                                while ($user_row = $user_qry->fetch_assoc()):
                                                    if(in_array($user_row['empid'], $reporters))
                                                        if(!empty($disabledPersonality))
                                                            $all_members = array_diff($all_members, [$user_row['empid']]);
                                                   
                                            ?>
                                            <option value="<?php echo htmlspecialchars($user_row['empid']); ?>" <?php  echo isset($reporters) && in_array($user_row['empid'], $reporters) ? 'selected' : '' ?>>
                                                <?php echo trim($user_row['firstname']) . ' ' . trim($user_row['lastname']).' ('.$user_row['role_name'].')'; ?>
                                            </option>
                                            <?php 
                                                endwhile;
                                            } else {
                                                echo "<option>No reporters available</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                           
                                <!-- Photographer -->
                                <div class="role-group">
                                    <label>Photographers</label>
                                    <div class="assignee-wrapper">
                                        <select name="assignee[photographer][]" class="custom-select custom-select-sm" multiple="multiple" <?= $disabledDispatch.$disabledPersonality.$disabledMedia ?><?= $required?>>
                                        <!-- <option value="">Select a photographer</option> -->
                                            <?php
                                            // Fetch users with role_id = 6 (for photographers)
                                            $photographers = [];
                                            if(isset($team_members))
                                                $photographers = explode(',', $team_members);
                                            
                                            $user_qry = $conn->query("SELECT u.empid, u.firstname, u.lastname, r.role_name 
                                            FROM users u 
                                            JOIN roles r ON r.role_id = u.role_id
                                            WHERE u.role_id in (7, 11)
                                            ORDER BY 
                                                r.role_name, u.firstname;");
                                            if ($user_qry) {
                                                while ($user_row = $user_qry->fetch_assoc()):
                                                    if(in_array($user_row['empid'], $photographers))
                                                        if(!empty($disabledDigital))
                                                            $all_members = array_diff($all_members, [$user_row['empid']]);
                                                        
                                                    // print_r($all_members);
                                                    echo 'disabb'.$disabledDigital;
                                            ?>
                                            <option value="<?php echo htmlspecialchars($user_row['empid']); ?>" <?php echo isset($photographers) && in_array($user_row['empid'], $photographers) ? 'selected' : '' ?>>
                                                <?php echo htmlspecialchars($user_row['firstname']) . ' ' . htmlspecialchars($user_row['lastname']); ?>
                                            </option>
                                            <?php 
                                                endwhile;
                                            } else {
                                                echo "<option>No photographers available</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <!-- Checkbox and Dropdown for Request -->
                                    <?php if (isset($disabledPersonality)): ?>
                                    <div class="request-wrapper">
                                        <label>
                                            <input type="checkbox" name="request[photographer]" class="request-checkbox" <?= $disabled ?> <?php echo isset($photo_requested) && $photo_requested == 1 ? 'checked' : '' ?>><span class="font-weight-light small"> 
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
                                    <?php if(!empty($disabledMedia) && (isset($photo_requested)) && $photo_requested == 1): ?>
                                        <input type="hidden" name="request[photographer]" value="<?= $photo_requested?>">
                                    <?php endif; ?>
                                </div>

                                <!-- Videographer -->
                                <div class="role-group">
                                    <label>Videographers</label>
                                    <div class="assignee-wrapper">
                                        <select name="assignee[videographer][]" class="custom-select custom-select-sm" multiple="multiple" <?= $disabledDispatch.$disabledPersonality.$disabledDigital ?>>
                                        <!-- <option value="">Select a videographer</option> -->
                                            <?php
                                            $videographers = [];
                                            // Fetch users with role_id = 7 (for videographers)
                                            if(isset($team_members))
                                                $videographers = explode(',', $team_members);
                                            
                                            $user_qry = $conn->query("SELECT u.empid, u.firstname, u.lastname, r.role_name 
                                            FROM users u 
                                            JOIN roles r ON r.role_id = u.role_id
                                            WHERE u.role_id in (8)
                                            ORDER BY 
                                                r.role_name, u.firstname;");
                                            if ($user_qry) {
                                                while ($user_row = $user_qry->fetch_assoc()):
                                                    if(in_array($user_row['empid'], $videographers))
                                                        if(!empty($disabledMedia))
                                                            $all_members = array_diff($all_members, [$user_row['empid']]);
                                            ?>
                                            <option value="<?php echo htmlspecialchars($user_row['empid']); ?>" <?php echo isset($videographers) && in_array($user_row['empid'], $videographers) ? 'selected' : '' ?>>
                                                <?php echo htmlspecialchars($user_row['firstname']) . ' ' . htmlspecialchars($user_row['lastname']); ?>
                                            </option>
                                            <?php 
                                                endwhile;
                                            } else {
                                                echo "<option>No videographers available</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                     <!-- Checkbox and Dropdown for Request -->
                                     <?php if (isset($disabledPersonality)): ?>
                                    <div class="request-wrapper">
                                        <label>
                                            <input type="checkbox" name="request[videographer]" class="request-checkbox" <?= $disabled ?> <?php echo isset($video_requested) && $video_requested == 1 ? 'checked' : '' ?>><span class="font-weight-light small"> 
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
                                    <?php if(!empty($disabledDigital) && (isset($video_requested)) && $video_requested == 1): ?>
                                        <input type="hidden" name="request[videographer]" value="<?= $video_requested?>">
                                    <?php endif; ?>
                                </div>

                                <!-- Social -->
                                <div class="role-group">
                                    <label>Social Media</label>
                                    <div class="assignee-wrapper">
                                        <select name="assignee[social][]" class="custom-select custom-select-sm" multiple="multiple" <?= $disabledDispatch.$disabledPersonality.$disabledDigital ?>>
                                            <?php
                                            $socials = [];
                                            // Fetch users with role_id = 7 (for videographers)
                                            if(isset($team_members))
                                                $socials = explode(',', $team_members);
                                            
                                            $user_qry = $conn->query("SELECT u.empid, u.firstname, u.lastname, r.role_name 
                                            FROM users u 
                                            JOIN roles r ON r.role_id = u.role_id
                                            WHERE u.role_id in (14, 16)
                                            ORDER BY 
                                                r.role_name, u.firstname;");
                                            if ($user_qry) {
                                                while ($user_row = $user_qry->fetch_assoc()):
                                                    if(in_array($user_row['empid'], $socials))
                                                        if(!empty($disabledMedia))
                                                            $all_members = array_diff($all_members, [$user_row['empid']]);
                                            ?>
                                            <option value="<?php echo htmlspecialchars($user_row['empid']); ?>" <?php echo isset($socials) && in_array($user_row['empid'], $socials) ? 'selected' : '' ?>>
                                                <?php echo htmlspecialchars($user_row['firstname']) . ' ' . htmlspecialchars($user_row['lastname']); ?>
                                            </option>
                                            <?php 
                                                endwhile;
                                            } else {
                                                echo "<option>No social media available</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                     <!-- Checkbox and Dropdown for Request -->
                                     <?php if (isset($disabledPersonality)): ?>
                                    <div class="request-wrapper">
                                        <label>
                                            <input type="checkbox" name="request[social]" class="request-checkbox" <?= $disabled ?> <?php echo isset($social_requested) && $social_requested == 1 ? 'checked' : '' ?>><span class="font-weight-light small"> 
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
                                    <?php if(!empty($disabledDigital) && (isset($social_requested)) && $social_requested == 1): ?>
                                        <input type="hidden" name="request[social]" value="<?= $social_requested?>">
                                    <?php endif; ?>
                                </div>
                                <?php } ?>
                                 <!-- Drivers -->
                                 <div class="role-group <?= ($user_role == 'Dispatcher') ? '' : 'd-none' ?>" >
                                    <label>Drivers</label>
                                    <div class="assignee-wrapper">
                                        <select name="assignee[driver][]" class="custom-select custom-select-sm" multiple="multiple" <?= $disabledPersonality.$disabledDigital ?><?= $requiredDispatch?>>
                                            <?php
                                            $drivers = [];
                                            // Fetch users with role_id = 9 (for drivers)
                                            if(isset($team_members))
                                                $drivers = explode(',', $team_members);
                                            
                                            $user_qry = $conn->query("SELECT empid, firstname, lastname FROM users WHERE role_id = 9");
                                            if ($user_qry) {
                                                while ($user_row = $user_qry->fetch_assoc()):
                                                    if(in_array($user_row['empid'], $drivers))
                                                        if(!empty($disabledDispatch) || !empty($disabledMedia))
                                                            $all_members = array_diff($all_members, [$user_row['empid']]);
                                            ?>
                                            <option value="<?php echo htmlspecialchars($user_row['empid']); ?>" <?php echo isset($drivers) && in_array($user_row['empid'], $drivers) ? 'selected' : '' ?>>
                                                <?php echo htmlspecialchars($user_row['firstname']) . ' ' . htmlspecialchars($user_row['lastname']) ; ?>
                                            </option>
                                            <?php 
                                                endwhile;
                                            } else {
                                                echo "<option>No drivers available</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                      <!-- Checkbox and Dropdown for Request -->
                                      <?php if (isset($disabledPersonality)): ?>
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
                                            
                                            $user_qry = $conn->query("SELECT id, plate_number FROM transport_vehicles");
                                            if ($user_qry) {
                                                while ($user_row = $user_qry->fetch_assoc()):
                                            ?>
                                            <option value="<?php echo htmlspecialchars($user_row['id']); ?>" <?php echo isset($transport_id) && $transport_id == $user_row['id'] ? 'selected' : '' ?>>
                                                <?php echo htmlspecialchars($user_row['plate_number']) ; ?>
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
                            
                            <!-- Get team members if boxes disabled -->
                            <?php if(!empty($disabled.$disabledDispatch.$disabledEditors.$disabledBroadcast.$disabledPersonality)){
                                $teamRem = implode(',', $all_members);   
                                // print_r($teamRem);                             ?>
                            <input type="hidden" name="team" value="<?= $teamRem ?>" />
                            <?php } ?>
                            <?php 

                                $currentStatus = ($user_role == 'Dispatcher') ? 'Approved': 'Pending';
                                $currentStatus = (isset($status) && $status == 'Approved') ? $status : $currentStatus;
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
                    <hr />
                    <div class="row">
                            <div class="col d-flex justify-content-sm-between">
                                <div id="checkboxes">
                                    <div class="custom-control custom-switch my-2 <?php echo (in_array($user_role, ['Broadcast Coordinator'])) ? 'd-none' : ''; ?>">
                                        <input type="checkbox" class="custom-control-input" id="send_notification" name="send_notification" <?php echo (!($radio_staff)) && (!in_array($user_role, ['Broadcast Coordinator'])) ? 'checked' : ''; ?> <?php //echo isset($send_notification) && $send_notification == 1 ? 'checked' : '' ?>>
                                        <label class="custom-control-label font-weight-light" for="send_notification">
                                        <?php echo (isset($send_notification) && $send_notification == 1) ? 'Notification Sent (uncheck if you do not wish to send another)' : 'Send Notification'; ?>
                                        </label>
                                    </div>
                                    <?php if (isset($_GET['id'])){ ?>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input text-danger" id="is_cancelled" name="is_cancelled"  <?php echo isset($is_cancelled) && $is_cancelled == 1 ? 'checked' : '' ?>>
                                        <label class="custom-control-label text-danger" for="is_cancelled">
                                        <?php echo (isset($is_cancelled) && $is_cancelled == 1) ? 'Cancelled' : 'Cancel Assignment'; ?>
                                        </label>
                                    </div>
                                    <?php } ?>  
                                    <?php if (!isset($_GET['id'])  && (in_array($user_role, ['Broadcast Coordinator', 'ITAdmin']))){ ?>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input text-primary" id="alert_manager" name="alert_manager">
                                        <label class="custom-control-label text-primary" for="alert_manager">
                                        Alert Manager for Approval
                                        </label>
                                    </div>
                                    <?php } ?>  
                                </div>
                            
                            <!-- <label>
                                <input type="checkbox" name="send_notification" class="checkbox"  checked <?php //echo isset($send_notification) && $send_notification == 1 ? 'checked' : '' ?>>
                                <span class="font-weight-light "> 
                                    <?php echo (isset($send_notification) && $send_notification == 1) ? 'Notification Sent (uncheck if you do not wish to send another)' : 'Send Notification'; ?>
                                </span>
                            </label> -->
                                <div class="form-group">
                                    <a class="btn btn-secondary mx-5" href="index.php?page=home">Cancel</a>

                                    <button class="btn btn-danger" id="save_assignment_button"><i class="fa fa-save"></i> <?php echo empty($id) ? 'Save' : 'Update' ?> Assignment</button>
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
$(document).ready(function(){

    $('.summernote').summernote({
        height: 150,
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'underline', 'clear']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['insert', ['link']],
            // ['view', ['codeview']]
        ]
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
            end_load(); 

            if (resp == 1) {
                alert_toast('The assignment has been updated', 'success');
                setTimeout(() => {
                    location.href = 'index.php?page=home'; // Redirect after success
                }, 3000);
            
            }else{
                alert_toast(resp, 'error');
                $('#save_assignment_button').prop('disabled', false);
            }
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