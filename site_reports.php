<?php
include 'db_connect.php';

// Pagination setup
$per_page = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1; // Ensure page is at least 1
$start = max(0, ($page - 1) * $per_page); // Ensure start is not negative

// Search functionality
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$search_condition = $search ? "WHERE a.title LIKE '%$search%' OR a.location LIKE '%$search%'" : '';

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM venue_inspections vi 
                JOIN assignment_list a ON vi.assignment_id = a.id $search_condition";
$total_result = $conn->query($count_query);
$total_row = $total_result->fetch_assoc();
$total = $total_row['total'];
$pages = ceil($total / $per_page);

// Main query with pagination
$query = "SELECT vi.*, a.title, a.location, a.assignment_date, a.station_show 
          FROM venue_inspections vi
          JOIN assignment_list a ON vi.assignment_id = a.id
          $search_condition
          ORDER BY vi.site_visit_date DESC
          LIMIT $start, $per_page";

$result = $conn->query($query);
?>

<div class="container-fluid">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title">Venue Inspections</h4>
            </div>
            <div class="card-body">
                <!-- Search Box -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <form method="GET" action="">
                            <input type="hidden" name="page" value="site_reports">
                            <div class="input-group">
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
                </div>

                <!-- Inspection List Table -->
                <div class="table-responsive">
                    <table class="table table-hover small">
                        <thead class="bg-light">
                            <tr>
                                <th>Assignment</th>
                                <th>Location</th>
                                <th>Date</th>
                                <th>Show</th>
                                <th>Visit Date</th>
                                <th>Parking</th>
                                <th>Bathrooms</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <?php 
                                // Extract show name if in "STATION: SHOW" format
                                $show_name = $row['station_show'];
                                if (!empty($show_name) && strpos($show_name, ':') !== false) {
                                    $parts = explode(':', $show_name, 2);
                                    $show_name = trim($parts[1]);
                                }
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['title']) ?></td>
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
                                        <a href="index.php?page=view_site_report&id=<?= $row['assignment_id'] ?>" 
                                           class="btn btn-sm btn-outline-primary" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="index.php?page=site_report&id=<?= $row['assignment_id'] ?>" 
                                           class="btn btn-sm btn-outline-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <!-- <button class="btn btn-sm btn-outline-danger delete-inspection" 
                                                data-id="<?= $row['id'] ?>" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button> -->
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            <?php if($result->num_rows == 0): ?>
                                <tr>
                                    <td colspan="8" class="text-center">No inspections found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if($pages > 1): ?>
                <nav>
                    <ul class="pagination justify-content-center">
                        <?php if($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=site_reports&page=<?= $page-1 ?>&search=<?= urlencode($search) ?>">
                                    Previous
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for($i = 1; $i <= $pages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=site_reports&page=<?= $i ?>&search=<?= urlencode($search) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if($page < $pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=site_reports&page=<?= $page+1 ?>&search=<?= urlencode($search) ?>">
                                    Next
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Delete inspection confirmation
    $('.delete-inspection').click(function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'ajax.php?action=delete_inspection',
                    method: 'POST',
                    data: { id: id },
                    dataType: 'json',
                    success: function(resp) {
                        if(resp.status == 'success') {
                            Swal.fire(
                                'Deleted!',
                                'Inspection has been deleted.',
                                'success'
                            ).then(() => location.reload())
                        } else {
                            Swal.fire(
                                'Error!',
                                resp.message || 'Failed to delete inspection',
                                'error'
                            )
                        }
                    }
                })
            }
        })
    });
});
</script>