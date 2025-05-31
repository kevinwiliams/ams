<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('db_connect.php');

$login_role_id = $_SESSION['role_id'] ?? 5; 
$login_empid = $_SESSION['login_id'] ?? 0;
$db_empid = $_SESSION['empid'] ?? '';
$user_role = $_SESSION['role_name'] ?? '';

$login_empid = intval($login_empid);

$firstname = $_SESSION['login_firstname'];
$lastname = $_SESSION['login_lastname'];

$radio_staff = $_SESSION['login_sb_staff'] == 1 ? true : false;


//-------------------------------------------------------------------------------//
$editorQry = "";
$dispatchQry = "";
$securityQry = "";
$sbQry = "";

$view_roles = ['Manager', 'ITAdmin', 'Editor', 'Multimedia', 'Dispatcher', 'Photo Editor', 'Dept Admin', 'Security', 'Op Manager', 'Broadcast Coordinator' ];
$digital_roles = ['Photo Editor'];
$multimedia_roles = ['Multimedia'];
$dj_roles = ['Programme Director'];
$create_roles = ['Manager', 'ITAdmin', 'Editor', 'Dept Admin', 'Security','Op Manager', 'Broadcast Coordinator' ];

$sbQry .= ($radio_staff) ? " AND a.station_show <> '' " : " AND a.station_show IS NULL ";

if(!in_array($user_role,  $view_roles))
    $editorQry = " AND FIND_IN_SET('".$db_empid."', REPLACE(a.team_members, ' ', '')) > 0 OR studio_engineer = '".$db_empid."' ";

if(in_array($user_role,  $digital_roles))
    $editorQry = " AND a.photo_requested = 1 ";

if(in_array($user_role,  $multimedia_roles))
    $editorQry = " AND (a.video_requested = 1 OR a.social_requested = 1) ";

if(in_array($user_role,  $multimedia_roles) && $radio_staff)
    $editorQry .= " AND station_show IS NOT NULL ";


if($user_role =='Dispatcher')
    $dispatchQry = " AND a.drop_option <> 'noTransport' AND a.status = 'Pending'";

if($user_role =='Security')
    $dispatchQry = " AND a.status = 'Approved' ";

if(in_array($user_role,  ['Photo Editor', 'Multimedia', 'Manager']))
    $editorQry .= " OR FIND_IN_SET('".$db_empid."', REPLACE(a.team_members, ' ', '')) > 0 ";


$recentQry = "SELECT a.*,t.*,v.*,
    a.id AS assignment_id,
    a.title AS assignment_title,
    t.id AS transport_log_id,
    t.transport_id,
    v.id AS vehicle_id,
                (SELECT GROUP_CONCAT(
                    CONCAT(
                        CASE 
                            WHEN u.alias IS NOT NULL AND u.alias <> '' THEN u.alias
                            ELSE CONCAT(u.firstname, ' ', u.lastname)
                        END, 
                        ' (', r.role_name, ')', 
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
                LEFT JOIN roles r ON u.role_id = r.role_id
                WHERE FIND_IN_SET(u.empid, a.team_members)) AS team_members_names,
                (SELECT CONCAT(u.firstname, ' ', u.lastname) 
                FROM users u 
                WHERE u.id = a.assigned_by) AS assigned_by_name,
                (SELECT CONCAT(u.firstname, ' ', u.lastname) 
                FROM users u 
                WHERE u.id = a.approved_by) AS approved_by_name,
                 (SELECT CONCAT(
                    u.firstname, ' ', u.lastname, 
                    CASE 
                        WHEN EXISTS (
                            SELECT 1 
                            FROM confirmed_logs cl 
                            WHERE cl.assignment_id = a.id AND cl.empid = studio_engineer_user.empid
                        ) THEN ' /' 
                        ELSE ' |' 
                    END
                ) 
                FROM users u 
                WHERE u.empid = a.studio_engineer) AS studio_engineer_name
               
                FROM assignment_list a
                LEFT JOIN transport_log t ON a.id = t.assignment_id
                LEFT JOIN transport_vehicles v ON t.transport_id = v.id
                  LEFT JOIN users studio_engineer_user ON a.studio_engineer = studio_engineer_user.empid
                WHERE  (a.is_deleted = 0 OR a.is_deleted IS NULL) 
                $editorQry $dispatchQry $sbQry
                ORDER BY a.assignment_date DESC 
                LIMIT 20";
$recentAssignments = $conn->query($recentQry);

$options = [
    'dropOffOnly' => 'Drop Off Only',
    'dropOffReturn' => 'Drop Off/Return',
    'pickupOnly' => 'Pick Up Only',
    '' => 'N/A' //If empty or null
];
?>
<style>
  
    .strike-through {
        text-decoration: line-through;
    }
    </style>
<div class="content"><?php //echo $db_empid.' '.$login_role_id.' '.$user_role ?>
    
    <?php

    function trimString($string, $limit = 55) {
        if (strlen($string) > $limit) {
            return substr($string, 0, $limit) . '...';
        }
        return $string; 
    }

    $current_date = date('Y-m-d H:i:s');
    $next_assignment = null;
    $last_assignment = null;
    $future_assignments = [];
    $editorQ = "";

    if(in_array($user_role,  ['Editor', 'Multimedia', 'Photo Editor']))
        $editorQ .= " AND FIND_IN_SET('".$db_empid."', REPLACE(team_members, ' ', '')) > 0 ";
    
    if(!in_array($user_role, $view_roles))
        $editorQ .= " AND FIND_IN_SET('".$db_empid."', REPLACE(team_members, ' ', '')) > 0 ";

    $query = "SELECT * FROM assignment_list WHERE is_cancelled <> 1 $editorQ ORDER BY assignment_date ";
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()){
        
        $assignment_datetime = date('Y-m-d H:i:s', strtotime($row['assignment_date'] . ' ' . $row['start_time']));

        if ($assignment_datetime > $current_date) {
            if (!$next_assignment) {
                $next_assignment = $row; // Closest future assignment becomes "Next Assignment"
            } else {
                $future_assignments[] = $row; // Other future assignments
            }
        } else {
            $last_assignment = $row; // Assign the most recent past assignment
        }
        
    }

    ?>
    <div class="row">
    <!-- Next Assignment -->
    <?php if ($next_assignment): ?>
        <div class="col-md-4 col-sm-6 col-12">
            <div class="info-box bg-info" >
                <span class="info-box-icon" style="width:35px"><i class="far fa-bookmark"></i></span>
                <div class="info-box-content" style="line-height:1.45em">
                    <span class="info-box-text small">NEXT ASSIGNMENT</span>
                    <span class="info-box-number"><?= date('D M d, Y', strtotime($next_assignment['assignment_date'])) ?> - <?= htmlspecialchars($next_assignment['start_time']) ?></span>
                    <span class="info-box-number"><a href="index.php?page=view_assignment&id=<?= $next_assignment['id']; ?>" class="text-white"><?= trimString(htmlspecialchars_decode($next_assignment['title'])) ?></a></span>
                    <span class="progress-description">
                    <i class="fas fa-map-marker-alt mr-1"></i> <?= htmlspecialchars_decode($next_assignment['location']) ?>
                    </span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Last Assignment -->
    <?php if ($last_assignment): ?>
        <div class="col-md-4 col-sm-6 col-12">
            <div class="info-box bg-secondary">
                <span class="info-box-icon" style="width:35px"><i class="fas fa-history"></i></span>
                <div class="info-box-content" style="line-height:1.45em">
                    <span class="info-box-text small">LAST ASSIGNMENT</span>
                    <span class="info-box-number"><?= date('D M d, Y', strtotime($last_assignment['assignment_date'])) ?> - <?= htmlspecialchars($last_assignment['start_time']) ?></span>
                    <span class="info-box-number"><a href="index.php?page=view_assignment&id=<?= $last_assignment['id']; ?>" class="text-white"><?= trimString(htmlspecialchars_decode($last_assignment['title'])) ?></a></span>
                    <span class="progress-description">
                    <i class="fas fa-map-marker-alt mr-1"></i> <?= htmlspecialchars_decode($last_assignment['location']) ?>
                    </span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Future Assignments -->
    <?php if (!empty($future_assignments)): ?>
        <div class="col-md-4 col-sm-6 col-12">
            <div class="info-box bg-warning">
                <span class="info-box-icon" style="width:35px"><i class="far fa-calendar"></i></span>
                <div class="info-box-content" style="line-height:1.45em">
                    <span class="info-box-text small">FUTURE ASSIGNMENT</span>
                    <span class="info-box-number"><?= date('D M d, Y', strtotime($future_assignments[0]['assignment_date'])) ?> - <?= htmlspecialchars($future_assignments[0]['start_time']) ?></span>
                    <span class="info-box-number"><a href="index.php?page=view_assignment&id=<?= $future_assignments[0]['id']; ?>" class="text-dark"><?= trimString(htmlspecialchars_decode($future_assignments[0]['title'])) ?></a></span>
                    <span class="progress-description">
                    <i class="fas fa-map-marker-alt mr-1"></i> <?= htmlspecialchars_decode($future_assignments[0]['location']) ?>
                    </span>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

    <!-- Last 50 Assignments -->
    <div class="last-50-assignments">

        <div class="card card-outline card-primary">
            <div class="card-header d-flex">
            <h4 class="my-0 font-weight-normal flex-grow-1">Recent Assignments</h4>
            <div class="card-tools">
                <?php if (in_array($user_role, $create_roles)): ?>
                    <a href="index.php?page=assignment" class="btn btn-danger btn-sm"><i class="fa fa-plus"></i> Add New Assignment</a>

                <?php endif; ?>
            </div>
            </div>
            <div class="card-body">
            <?php if ($recentAssignments && $recentAssignments->num_rows > 0): ?>
            <table class="table table-striped table-hover small" id="recentTable">
                <thead class="thead-dark">
                    <tr>
                        <!-- <th>ID</th> -->
                        <th class="flex-nowrap">Assignment Date</th>
                        
                        <?php if(in_array($user_role,  ['Dispatcher', 'Security'])) :?>
                            <th>License</th>
                        <?php endif ?>
                        <th>Duration</th>
                        <!-- <th>End Time</th> -->
                        <th>Assignment</th>
                        <!-- <th>Description</th> -->
                        <th>Venue</th>
                        <th>Assignee</th>
                        <th>Assigned By</th>
                        <?php if(in_array($user_role,  ['Dispatcher', 'Security'])) :?>
                        <th>Status</th>
                        <th>Transport</th>
                        <?php endif ?>
                        <!-- <th>Date Created</th> -->
                        <!-- <th>Approved By</th> -->
                        <!-- <th>Approved Date</th> -->
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $recentAssignments->fetch_assoc()): 
                            $hightlight = false;
                            if(in_array($user_role, $digital_roles) && $row['photo_requested'] == 1){
                                $hightlight = true;
                            }
                            if(in_array($user_role, $multimedia_roles) && ($row['video_requested'] == 1 ||  $row['social_requested'] == 1)){
                                $hightlight = true;
                            }
                            if(in_array($user_role, $dj_roles) && $row['dj_requested'] == 1){
                                $hightlight = true;
                            }
                        
                        ?>
                        <tr class="<?= ($hightlight) ?'table-warning': '' ?>">
                            <!-- <td><?php echo htmlspecialchars($row['assignment_id']); ?></td> -->
                            <td style="width: 100px;">
                                <span class="<?php echo ($row['is_cancelled'] == 1) ? 'strike-through' : ''; ?>">
                                <a href="index.php?page=view_assignment&id=<?php echo $row['assignment_id']; ?>" class="text-decoration-none">
                                    <?php echo date("D, M j, Y", strtotime($row['assignment_date'])); ?>
                                </a>
                                </span>
                                <?php
                                    if (date("Y-m-d", strtotime($row['date_created'])) > $row['assignment_date']){
                                        echo ' <i class="fas fa-history"></i>';
                                    }
                                ?>
                            </td>
                            <?php if(in_array($user_role,  ['Dispatcher', 'Security'])) :?>
                            <td><span class="<?php echo ($row['is_cancelled'] == 1) ? 'strike-through' : ''; ?>"><?php echo htmlspecialchars($row['plate_number']).' '.htmlspecialchars($row['make_model']); ?></span></td>
                            <?php endif ?>
                            <td style="width: 110px;"><span class="flex-nowrap <?php echo ($row['is_cancelled'] == 1) ? 'strike-through' : ''; ?>"><?= htmlspecialchars($row['start_time']).' - '.htmlspecialchars($row['end_time'] ?? 'N/A'); ?></span></td>
                            <!-- <td><span class="<?php echo ($row['is_cancelled'] == 1) ? 'strike-through' : ''; ?>"><?php echo htmlspecialchars($row['end_time']); ?></span></td> -->
                            
                            <td>
                                <!-- <a href="index.php?page=view_assignment&id=<?php echo $row['id']; ?>" class="text-decoration-none"> -->
                                <?php echo ($row['is_cancelled'] == 1) ? '<b>CANCELLED:</b> ' : ''; ?>
                                <span class="text-wrap <?php echo ($row['is_cancelled'] == 1) ? 'strike-through' : ''; ?>"><?php echo htmlspecialchars_decode($row['title']); ?> </span>
                                <!-- </a> -->
                            </td>
                            <!-- <td><?php echo htmlspecialchars(substr($row['description'], 0, 15)) . " ... " . htmlspecialchars(substr($row['description'], -5)); ?></td> -->
                            <td><span class="text-wrap <?php echo ($row['is_cancelled'] == 1) ? 'strike-through' : ''; ?>"><?php echo htmlspecialchars_decode($row['location']); ?></span></td>
                            <!-- <td><?php echo htmlspecialchars(($row['team_members_names'])); ?></td> -->
                            <td>
                                <span class="<?php echo ($row['is_cancelled'] == 1) ? 'strike-through' : ''; ?>">
                                <?php 
                                    $charactersToRemove = ["/", "|"];
                                    $requestedTypes = array_filter([
                                        $row['dj_requested'] == 1 ? 'DJ' : null,
                                        $row['photo_requested'] == 1 ? 'Photo' : null,
                                        $row['video_requested'] == 1 ? 'Video' : null,
                                        $row['social_requested'] == 1 ? 'Social' : null,
                                        $row['driver_requested'] == 1 ? 'Driver' : null
                                    ]);

                                    if (!empty($row['team_members_names'])) {
                                        foreach (explode(', ', $row['team_members_names']) as $member) {
                                            $statusClass = strpos($member, '/') !== false ? 'text-success fw-bold' : 'text-secondary';
                                            echo "<span class='$statusClass'>" . str_replace($charactersToRemove, "", $member) . "</span><br>";
                                        }
                                    } else {
                                        echo 'No Reporter Assigned<br>';
                                    }

                                    if (!empty($row['studio_engineer_name'])) {
                                        $statusClass = strpos($row['studio_engineer_name'], '/') !== false ? 'text-success fw-bold' : 'text-secondary';
                                        echo "<span class='$statusClass'>" . str_replace($charactersToRemove, "", $row['studio_engineer_name']) . " (Studio Engineer)</span>";
                                    }

                                    if (!empty($requestedTypes)) {
                                        echo '<span class="text-info">' . implode(', ', $requestedTypes) . ' Requested</span>';
                                    }

                                    $notAvailable = [];
                                    if (!empty($row['team_members'])) {
                                        if (stripos($row['team_members'], 'NOSOCIAL') !== false) {
                                            $notAvailable[] = 'Social Not Available';
                                        }
                                        if (stripos($row['team_members'], 'NOPHOTO') !== false) {
                                            $notAvailable[] = 'Photo Not Available';
                                        }
                                        if (stripos($row['team_members'], 'NOVIDEO') !== false) {
                                            $notAvailable[] = 'Video Not Available';
                                        }
                                    }
                                    if (!empty($notAvailable)) {
                                        foreach ($notAvailable as $na) {
                                            echo '<br><span class="text-danger">' . htmlspecialchars($na) . '</span>';
                                        }
                                    }
                                ?>
                                </span>
                        
                            </td>
                            <td><?php echo htmlspecialchars($row['assigned_by_name']); ?></td>
                            <?php if(in_array($user_role,  ['Dispatcher', 'Security'])) :?>
                            <td><span class="<?php echo ($row['is_cancelled'] == 1) ? 'strike-through' : ''; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                            <td><span class="<?php echo ($row['is_cancelled'] == 1) ? 'strike-through' : ''; ?>"><?php echo htmlspecialchars($options[$row['drop_option']]) ?? 'N/A'; ?></span></td>
                            <?php endif ?>
                            <!-- <td><?php echo htmlspecialchars($row['date_created']); ?></td> -->
                            <!-- <td><?php echo htmlspecialchars($row['approved_by_name']); ?></td> -->
                            <!-- <td><?php echo htmlspecialchars($row['approval_date']); ?></td> -->
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No assignments found.</p>
        <?php endif; ?>
            </div>
        </div>
        
    </div>

    
</div>

<script>
    
    $(document).ready(function(){
        $('#recentTable').dataTable({
            columnDefs: [
                { type: 'date', targets: 0 } 
            ],
            order: [[0, 'desc']] 
        });
       
    });
</script>