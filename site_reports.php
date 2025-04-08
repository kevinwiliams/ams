<?php
include 'db_connect.php';

// Fetch all data without pagination
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$search_condition = $search ? "WHERE a.title LIKE '%$search%' OR a.location LIKE '%$search%'" : '';

$query = "SELECT vi.*, a.title, a.location, a.assignment_date, a.station_show 
          FROM venue_inspections vi
          JOIN assignment_list a ON vi.assignment_id = a.id
          $search_condition
          ORDER BY vi.site_visit_date DESC";

$result = $conn->query($query);
?>
<style>
    .badge {
        font-size: 0.7rem;
    }
</style>
<div class="container-fluid">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title">Requistion Forms</h4>
                <form method="GET" action="" class="form-inline">
                    <input type="hidden" name="page" value="site_reports">
                    <div class="input-group d-none">
                        <input type="text" class="form-control" name="search" placeholder="Search assignments..." 
                               value="<?= htmlspecialchars($search) ?>">
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="card-body">
                <!-- Inspection List Table -->
                <div class="table-responsive">
                    <table id="inspectionsTable" class="table table-hover small">
                        <thead class="bg-light">
                            <tr>
                                <th>Assignment</th>
                                <th>Location</th>
                                <th>Date</th>
                                <th>Show</th>
                                <th>Visit Date</th>
                                <th>Parking</th>
                                <th>Bathrooms</th>
                                <th>Status</th>
                                <!-- <th>Actions</th> -->
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <?php 
                                $show_name = $row['station_show'];
                                if (!empty($show_name) && strpos($show_name, ':') !== false) {
                                    $parts = explode(':', $show_name, 2);
                                    $show_name = trim($parts[1]);
                                }
                                ?>
                                <tr>
                                    <td>
                                        <a href="index.php?page=view_site_report&id=<?= $row['assignment_id'] ?>">
                                            <?= htmlspecialchars($row['title']) ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($row['location']) ?></td>
                                    <td><?= date('M j, Y', strtotime($row['assignment_date'])) ?></td>
                                    <td><?= htmlspecialchars($show_name ?? '') ?></td>
                                    <td><?= date('M j, Y', strtotime($row['site_visit_date'])) ?></td>
                                    <td class="text-center">
                                        <?= $row['parking_available'] ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-danger">No</span>' ?>
                                    </td>
                                    <td class="text-center">
                                        <?= $row['bathrooms_available'] ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-danger">No</span>' ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!empty($row['report_status'])): ?>
                                            <?php if ($row['report_status'] == 'Pending'): ?>
                                                <span class="badge badge-warning">Pending</span>
                                            <?php elseif ($row['report_status'] == 'Confirmed'): ?>
                                                <span class="badge badge-primary">Confirmed</span>
                                            <?php elseif ($row['report_status'] == 'Approved'): ?>
                                                <span class="badge badge-success">Approved</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Unknown</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">No Status</span>
                                        <?php endif; ?>
                                    </td>
                                    <!-- <td class="text-center">
                                        <a href="index.php?page=site_report&id=<?= $row['assignment_id'] ?>" 
                                           class="btn btn-sm btn-outline-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td> -->
                                </tr>
                            <?php endwhile; ?>
                            <?php if($result->num_rows == 0): ?>
                                <tr>
                                    <td colspan="9" class="text-center">No inspections found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include DataTables and Export Buttons -->

<script>
$(document).ready(function() {
    $('#inspectionsTable').DataTable({
        dom: "<'row'<'col-md-6'B><'col-md-6'f>>" + 
                "<'row'<'col-sm-12'tr>>" + 
                "<'row'<'col-sm-5'i><'col-sm-7'p>>",
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ],
            order: [[4, 'asc']],
    });
});
</script>
