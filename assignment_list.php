<style>
    .dataTables_filter {
        /* display: none; */
        float: right !important;
    }
    .strike-through {
        text-decoration: line-through;
    }
    </style>

<?php 
include('db_connect.php');

// Initialize session variables
$login_role_id = $_SESSION['role_id'] ?? 5; // Default to 5 (Reporter)
$login_empid = $_SESSION['login_id'] ?? 0;
$login_empid = intval($login_empid);
$sessionempid = $_SESSION['login_empid'];
$user_role = $_SESSION['role_name'];
$radio_staff = $_SESSION['login_sb_staff'] == 1 ? true : false;

// Initialize $assignment_list
$assignment_list = null;

// Fetch assignment data based on conditions
$where = "WHERE (a.is_deleted = 0 OR a.is_deleted IS NULL)"; 
$create_roles = ['Manager', 'ITAdmin', 'Editor', 'Dept Admin', 'Security','Op Manager', 'Broadcast Coordinator' ];
$edit_roles = ['Manager', 'ITAdmin', 'Editor', 'Multimedia', 'Dispatcher', 'Photo Editor', 'Dept Admin', 'Op Manager', 'Programme Director'];
$delete_roles = ['Manager', 'ITAdmin', 'Editor', 'Dept Admin', 'Op Manager'];
$digital_roles = ['Photo Editor'];
$multimedia_roles = ['Multimedia'];
$freelance_roles = ['Freelancer'];

if(in_array($user_role,  $freelance_roles)){
    $where .= " AND FIND_IN_SET('" . $sessionempid . "', a.team_members)";
}

$sbQry = ($radio_staff) ? " AND a.station_show <> '' " : " AND a.station_show IS NULL ";


// if(!in_array($user_role,  $edit_roles)){
//     $where .= " AND FIND_IN_SET('" . $sessionempid . "', a.team_members)";
// }
// if(in_array($user_role,  ['Multimedia']))
//     $where .= " AND (FIND_IN_SET('".$sessionempid."', a.team_members) OR (a.video_requested = 1 OR a.social_requested = 1)
//                     OR EXISTS (SELECT 1 FROM users u LEFT JOIN roles r ON u.role_id = r.role_id WHERE FIND_IN_SET(u.empid, a.team_members) AND r.role_name in ('Multimedia', 'Social Media', 'Videographer') ))";
//     //$where .= " AND FIND_IN_SET('".$sessionempid."', a.team_members)  OR (a.video_requested = 1 OR a.social_requested = 1)";

// if(in_array($user_role,  ['Photo Editor']))
//     $where .= " AND (FIND_IN_SET('".$sessionempid."', a.team_members)  OR (a.photo_requested = 1)
//     OR EXISTS (SELECT 1 FROM users u LEFT JOIN roles r ON u.role_id = r.role_id WHERE FIND_IN_SET(u.empid, a.team_members) AND r.role_name in ('Photo Editor', 'Photographer') ))";


$query = "SELECT a.*, 
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
                 WHERE FIND_IN_SET(u.empid, a.team_members)) AS team_members_names_with_roles,
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
            LEFT JOIN users studio_engineer_user ON a.studio_engineer = studio_engineer_user.empid
            $where $sbQry
            ORDER BY a.assignment_date DESC";
$assignment_list = $conn->query($query);

// Handle potential errors
if (!$assignment_list) {
    die("Error executing query: " . $conn->error);
}

?>

<div class="row mb-3">
    <!-- Start Date Filter -->
    <div class="col-md-3">
        <label for="startDate">Start Date:</label>
        <input type="text" id="startDate" class="form-control form-control-sm" placeholder="Start Date">
    </div>

    <!-- End Date Filter -->
    <div class="col-md-3">
        <label for="endDate">End Date:</label>
        <input type="text" id="endDate" class="form-control form-control-sm" placeholder="End Date">
    </div>

    <!-- Team Member Filter -->
    <?php if (in_array($user_role, $edit_roles)){?>
        <div class="col-md-3">
            <label for="teamMemberFilter">Filter by Team Member:</label>
            <select id="teamMemberFilter" class="form-control form-control-sm custom-select-sm">
                <option value="">All Team Members</option>
                <?php
                $disAllowedRoles = "'ITAdmin', 'Dispatcher', 'Dept Admin', 'Driver'"; // Define allowed role names
                $sbQry = ($radio_staff) ? " AND u.sb_staff = 1 " : " ";
                $teamMembersList = $conn->query("
                    SELECT 
                        CASE 
                            WHEN u.alias IS NOT NULL AND CHAR_LENGTH(u.alias) > 0 THEN u.alias 
                            ELSE CONCAT(u.firstname, ' ', u.lastname) 
                        END AS name 
                    FROM users u 
                    LEFT JOIN roles r ON u.role_id = r.role_id 
                    WHERE r.role_name NOT IN ($disAllowedRoles)
                    AND u.is_deleted = 0 $sbQry
                    ORDER BY u.firstname
                ");
                while ($member = $teamMembersList->fetch_assoc()) {
                    echo "<option value='" . $member['name'] . "'>" . $member['name'] . "</option>";
                }
                ?>
            </select>
        </div>
    <?php } ?>
    <!-- Clear Filters Button -->
    <div class="col-md-3 d-flex align-items-end">
        <button id="clearFilters" class="btn btn-warning btn-sm">Clear Filters</button>
    </div>
</div>
<!-- HTML for displaying the assignment_list -->
<div class="col-lg-12">
    <input type="hidden" name="deleted_by" value="<?php echo $login_empid ?>" />
    <div class="card card-outline card-primary">
        <div class="card-header d-flex">
        <!-- <h4 class="my-0 font-weight-normal flex-grow-1">All Assignments</h4> -->
        <a href="index.php?page=calendar" class="py-2 flex-grow-1">
                <i class="fa fa-calendar" aria-hidden="true"></i> Calendar View
            </a>

            <div class="card-tools">
                <?php if (in_array($user_role, $create_roles)): ?>
                    <a href="index.php?page=assignment" class="btn btn-danger btn-sm ml-2"><i class="fa fa-plus"></i> Add New Assignment</a>

                <?php endif; ?>
            </div>
        </div>
        
        
        <!-- Assignment List -->
        <div class="card-body">
            <table class="table table-hover small" id="list">
                <thead class="thead-dark">
                    <tr>
                        <th>Assignment Date</th>
                        <th>Duration</th>
                        <!-- <th>End Time</th> -->
                        <th>Assignment</th>
                        <!-- <th>Description</th> -->
                        <th>Venue</th>
                        <th>Assigned to</th>
                        <th>Assigned by</th>
                        <!-- <th>Approved by</th> -->
                        <th>Created</th>
                        <!-- <th>Status</th> -->
                        <!-- <th>Action</th> -->
                        <!-- <th>Progress</th> -->
                        <?php if (in_array($user_role,  $edit_roles)) { ?>
                        <th>Actions</th>
                    <?php } ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    while ($row = $assignment_list->fetch_assoc()): 
                        $hightlight = false;
                        if(in_array($user_role, $digital_roles) && $row['photo_requested'] == 1){
                            $hightlight = true;
                        }
                        if(in_array($user_role, $multimedia_roles) && ($row['video_requested'] == 1 ||  $row['social_requested'] == 1)){
                            $hightlight = true;
                        }
                    ?>
                    <tr class="<?= ($hightlight) ?'table-warning': '' ?>">
                 
                    <td style="width: 100px;">
                        <a href="index.php?page=view_assignment&id=<?php echo $row['id']; ?>" class="text-decoration-none">    
                        <?= date("D, M j, Y", strtotime($row['assignment_date'])); ?>
                        
                        </a>
                        <?php
                            if (date("Y-m-d", strtotime($row['date_created'])) > $row['assignment_date']){
                                echo ' <i class="fas fa-history"></i>';
                            }
                        ?>
                    </td>
                    <td style="width: 110px;"> <span class="flex-nowrap <?php echo ($row['is_cancelled'] == 1) ? 'strike-through' : ''; ?>"><?php echo $row['start_time'] .' - '.$row['end_time'] ?? 'N/A'; ?></span>  </td>
                    <!-- <td> <span class="<?php echo ($row['is_cancelled'] == 1) ? 'strike-through' : ''; ?>"><?php echo $row['end_time'] ?? 'N/A'; ?></span>  </td> -->

                    <td>
                        <?php echo ($row['is_cancelled'] == 1) ? '<b>CANCELLED:</b> ' : ''; ?>
                        <span class="text-wrap <?php echo ($row['is_cancelled'] == 1) ? 'strike-through' : ''; ?>">
                            <?php echo ucwords(htmlspecialchars_decode($row['title']) ?? 'No Title'); ?>
                        </span>
                    </td>
                        <!-- <td><?php echo isset($row['description']) ? htmlspecialchars(substr($row['description'], 0, 15)) . " ..." : 'No Description'; ?></td> -->
                        <td>
                            <span class="text-wrap <?php echo ($row['is_cancelled'] == 1) ? 'strike-through' : ''; ?>">
                                <?php echo htmlspecialchars_decode($row['location'] ?? 'N/A'); ?>
                            </span>
                        </td>
                        <td>
                            <span class="<?php echo ($row['is_cancelled'] == 1) ? 'strike-through' : ''; ?>">
                            <?php //echo ($row['team_members_names_with_roles']); 
                                 $requestedTypes = [];
                                 if ($row['dj_requested'] == 1) $requestedTypes[] = 'DJ';
                                 if ($row['photo_requested'] == 1) $requestedTypes[] = 'Photo';
                                 if ($row['video_requested'] == 1) $requestedTypes[] = 'Video';
                                 if ($row['social_requested'] == 1) $requestedTypes[] = 'Social';
                                 if ($row['driver_requested'] == 1) $requestedTypes[] = 'Driver';
                                 
                                if(!empty($row['team_members_names_with_roles'])){
                                    $teamMembers = explode(', ', $row['team_members_names_with_roles']);
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
                                } else { echo 'No Reporter Assigned<br>'; }

                                if (!empty($row['studio_engineer_name'])) {
                                    $statusClass = strpos($row['studio_engineer_name'], '/') !== false ? 'text-success fw-bold' : 'text-danger';
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
                                        if (stripos($row['team_members'], 'NODJ') !== false) {
                                            $notAvailable[] = 'DJ Not Required';
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
                        <td>
                            <?php echo ($row['assigned_by_name']); ?>
                        </td>
                        <!-- <td>
                            <?php echo ($row['approved_by_name']); ?>
                        </td> -->
                        <td> <?php echo $row['date_created'] ?? 'N/A'; ?> </td>
                        <td>
                        <?php if (in_array($user_role,  $edit_roles)) { ?>

                            <a data-id="<?php echo $row['id']; ?>" href="#" class="btn text-info edit-assignment">    
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php } ?>
                        <?php if (in_array($user_role,  $delete_roles)) { ?>

                            <!-- <a data-id="<?php echo $row['id']; ?>" href="#" class="text-danger del-assignment">    
                                <i class="fas fa-trash-alt"></i>
                            </a> -->
                            <?php } ?>

                            

                        </td>

					</tr>	
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<script>
    $(document).ready(function(){
        // $('#list').dataTable({
        //     order: [[0, 'desc']] 
        // });
        //for multiselect dropdowns
        $('.custom-select-sm').select2();

        var table = $('#list').DataTable({
            dom: "<'row'<'col-md-6'B><'col-md-6'f>>" + 
                "<'row'<'col-sm-12'tr>>" + 
                "<'row'<'col-sm-5'i><'col-sm-7'p>>",
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ],
            pageLength: 10,
            columnDefs: [
                { type: 'date', targets: 0 }
            ],
            order: [[0, 'desc']],
            searching: true
        });

        // Initialize DateTimePickers with constraints
        $('#startDate').datetimepicker({
            timepicker: false,
            format: 'Y-m-d',
            onShow: function (ct) {
                this.setOptions({
                    maxDate: $('#endDate').val() ? $('#endDate').val() : false
                });
            }
        });

        $('#endDate').datetimepicker({
            timepicker: false,
            format: 'Y-m-d',
            onShow: function (ct) {
                this.setOptions({
                    minDate: $('#startDate').val() ? $('#startDate').val() : false
                });
            }
        });

        // Date Range Filtering Function
        function filterByDate() {
            var startDate = $('#startDate').val();
            var endDate = $('#endDate').val();

            $.fn.dataTable.ext.search.push(function (settings, data) {
                var assignmentDate = "";
                assignmentDate = data[0]; // Adjust index based on table columns
               
                var formattedDate = moment(assignmentDate, 'ddd, MMM D, YYYY').format('YYYY-MM-DD');

                if (startDate && formattedDate < startDate) return false;
                if (endDate && formattedDate > endDate) return false;
                return true;
            });

            table.draw();
        }

        // Apply Date Filters
        $('#startDate, #endDate').on('change', function () {
            $.fn.dataTable.ext.search = []; // Reset filters
            filterByDate();
            $('#teamMemberFilter').trigger('change');
        });

        // Team Member Filter
        $('#teamMemberFilter').on('change', function () {
            table.column(4).search(this.value).draw(); // Adjust index based on table columns
        });
        // Clear Filters Button
        $('#clearFilters').on('click', function () {
            $('#startDate, #endDate').val('');
            $('#teamMemberFilter').val('');
            $.fn.dataTable.ext.search = []; // Reset all filters
            table.search('').columns().search('').draw(); // Reset table
        });

        // $('.edit-assignment').on('click', function () {
        $('#list').on('click', '.edit-assignment', function () {
            var Id = $(this).data('id'); // Get the ID from the clicked button
            // alert(Id);
            location.href = 'index.php?page=assignment&id=' + Id; // Redirect after success
        });

        // $('.del-assignment').on('click', function () {
        $('#list').on('click', '.del-assignment', function () {
            var assignId = $(this).data('id'); // Get the ID from the clicked button
            var loggedUser = $('[name=deleted_by]').val(); // Get the ID of the logged-in user

            // Show confirmation dialog using alert_toast
            alert_toast('', 'warning', '', {
                isConfirmation: true, // Enable confirmation dialog
                title: 'Are you sure?',
                text: 'This action cannot be undone!',
                confirmText: 'Yes, delete it!',
                cancelText: 'Cancel',
                confirmCallback: function () {
                    // User confirmed deletion
                    $.ajax({
                        url: 'ajax.php?action=delete_assignment',
                        method: 'POST',
                        data: { id: assignId, deleted_by: loggedUser },
                        success: function (resp) {
                            if (resp == 1) {
                                console.log(resp);
                                // Show success alert
                                alert_toast('The record has been deleted.', 'success');
                                setTimeout(() => {
                                    location.href = 'index.php?page=assignment_list'; // Redirect after success
                                }, 3000);
                            } else {
                                // Show failure alert
                                alert_toast('Failed to delete the record. Please try again later.', 'error');
                            }
                        },
                        error: function (err) {
                            // Show error alert
                            alert_toast('Something went wrong. Please try again later.', 'error');
                            console.log(err);
                        },
                    });
                },
            });
        });
    });


</script>
