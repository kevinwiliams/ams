<?php
include 'db_connect.php';

// Fetch driver list from users table using role name matching Driver
$driverQuery = "SELECT u.id, CONCAT(u.firstname, ' ', u.lastname) AS name
    FROM users u
    LEFT JOIN roles r ON u.role_id = r.role_id
    WHERE u.is_deleted = 0
      AND (LOWER(r.role_name) = 'driver' OR LOWER(r.role_name) LIKE '%driver%')
    ORDER BY u.firstname, u.lastname";
$drivers = $conn->query($driverQuery);

// Fetch all active assignments
$assignmentQuery = "SELECT id, title, assignment_date, start_time, end_time, location, status, COALESCE(team_members, '') AS team_members, assignment_type
    FROM assignment_list
    WHERE is_cancelled <> 1 and drop_option IS NOT NULL
    ORDER BY assignment_date DESC";
$assignments = $conn->query($assignmentQuery);
if (!$assignments) {
    die('Error fetching assignments: ' . $conn->error);
}

// Fetch user map for team member names
$userMap = [];
$userResult = $conn->query("SELECT id, CONCAT(firstname, ' ', lastname) AS name FROM users WHERE is_deleted = 0");
if ($userResult) {
    while ($userRow = $userResult->fetch_assoc()) {
        $userMap[$userRow['id']] = $userRow['name'];
    }
}
?>

<div class="col-lg-12">
    <div class="card card-outline card-success">
        <div class="card-header">
            <h3 class="card-title">Driver Assignments</h3>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="driver-select">Filter by Driver</label>
                        <select id="driver-select" class="form-control form-control-sm">
                            <option value="">-- Select a driver --</option>
                            <?php while ($driver = $drivers->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($driver['id']); ?>"><?php echo htmlspecialchars($driver['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6 d-flex align-items-end justify-content-end">
                    <div class="text-right">
                        <p class="mb-0 text-muted">Assignments visible: <strong id="driver-assignment-count"><?php echo $assignments->num_rows; ?></strong></p>
                        <p class="mb-0"><small>Select a driver to filter the list.</small></p>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-striped table-sm" id="driver-assignments-table">
                    <thead class="bg-light">
                        <tr>
                            <th>Assignment Date</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Assignment</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Team</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $assignments->fetch_assoc()):
                            $teamMemberIds = array_filter(array_map('trim', explode(',', $row['team_members'])));
                            $teamNames = [];
                            foreach ($teamMemberIds as $memberId) {
                                if (!empty($memberId) && isset($userMap[$memberId])) {
                                    $teamNames[] = $userMap[$memberId];
                                }
                            }
                            $teamNameString = !empty($teamNames) ? implode(', ', $teamNames) : '—';
                        ?>
                        <tr data-team-members="<?php echo htmlspecialchars(implode(',', $teamMemberIds)); ?>">
                            <td><?php echo htmlspecialchars(date('M d, Y', strtotime($row['assignment_date']))); ?></td>
                            <td><?php echo htmlspecialchars($row['start_time']); ?></td>
                            <td><?php echo htmlspecialchars($row['end_time'] ?? '—'); ?></td>
                            <td><a href="index.php?page=view_assignment&id=<?php echo $row['id']; ?>"><?php echo htmlspecialchars_decode($row['title']); ?></a></td>
                            <td><?php echo htmlspecialchars($row['location']); ?></td>
                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                            <!-- <td><?php //echo htmlspecialchars($teamNameString); ?></td> -->
                            <td><?php echo htmlspecialchars($row['assignment_type']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var table = $('#driver-assignments-table').DataTable({
        dom: 'Bfrtip',
        buttons: ['copy', 'csv', 'excel', 'print'],
        columnDefs: [
                { type: 'date', targets: 0 }
            ],
        order: [[0, 'desc']],
        // columnDefs: [
        //     { targets: [6], orderable: false }
        // ]
    });

    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (settings.nTable.id !== 'driver-assignments-table') {
            return true;
        }
        var selectedDriver = $('#driver-select').val();
        if (!selectedDriver) {
            return true;
        }
        var row = table.row(dataIndex).node();
        var members = $(row).data('team-members') ? $(row).data('team-members').toString().split(',').map(function(value) {
            return value.trim();
        }) : [];
        return members.indexOf(selectedDriver) !== -1;
    });

    $('#driver-select').on('change', function() {
        table.draw();
        $('#driver-assignment-count').text(table.rows({ filter: 'applied' }).count());
    });
});
</script>
