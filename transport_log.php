<?php
include 'db_connect.php';

// Get filter parameters
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$vehicle_id = isset($_GET['vehicle_id']) ? intval($_GET['vehicle_id']) : '';
$driver_id = isset($_GET['driver_id']) ? intval($_GET['driver_id']) : '';
$fuel_level = isset($_GET['fuel_level']) ? $conn->real_escape_string($_GET['fuel_level']) : '';

// Build WHERE conditions
$conditions = [];

if ($search) {
    $conditions[] = "(tv.plate_number LIKE '%$search%' OR a.title LIKE '%$search%' OR CONCAT(u.firstname, ' ', u.lastname) LIKE '%$search%')";
}

if ($date_from) {
    $conditions[] = "DATE(tl.created_at) >= '$date_from'";
}

if ($date_to) {
    $conditions[] = "DATE(tl.created_at) <= '$date_to'";
}

if ($vehicle_id) {
    $conditions[] = "tl.transport_id = $vehicle_id";
}

if ($driver_id) {
    $conditions[] = "tl.created_by = $driver_id";
}

if ($fuel_level) {
    $conditions[] = "tl.gas_level = '$fuel_level'";
}

$where_clause = count($conditions) > 0 ? "WHERE " . implode(" AND ", $conditions) : "";

$query = "SELECT tl.*, 
    a.title AS assignment_title,
    a.location,
    tv.plate_number,
    tv.make_model,
    tv.id AS vehicle_id,
    CONCAT(u.firstname, ' ', u.lastname) AS created_by_name,
    u.id AS created_by_id
    FROM transport_log tl
    LEFT JOIN assignment_list a ON tl.assignment_id = a.id
    LEFT JOIN transport_vehicles tv ON tl.transport_id = tv.id
    LEFT JOIN users u ON tl.created_by = u.id
    $where_clause
    ORDER BY tl.created_at DESC";

$result = $conn->query($query);

// Fetch vehicles for filter dropdown
$vehicles_query = "SELECT DISTINCT tv.id, tv.plate_number, tv.make_model FROM transport_log tl 
                   LEFT JOIN transport_vehicles tv ON tl.transport_id = tv.id 
                   WHERE tv.id IS NOT NULL 
                   ORDER BY tv.plate_number";
$vehicles = $conn->query($vehicles_query);

// Fetch drivers for filter dropdown
$drivers_query = "SELECT DISTINCT u.id, u.firstname, u.lastname, CONCAT(u.firstname, ' ', u.lastname) AS driver_name FROM transport_log tl 
                  LEFT JOIN users u ON tl.created_by = u.id 
                  WHERE u.id IS NOT NULL 
                  ORDER BY u.firstname, u.lastname";
$drivers = $conn->query($drivers_query);

// Helper function to format fuel level
function getFuelBadge($gasLevel) {
    $levels = [
        'empty' => ['Empty', 'danger'],
        'qtr' => ['1/4', 'warning'],
        'half' => ['1/2', 'info'],
        '3/4' => ['3/4', 'success'],
        'full' => ['Full', 'success']
    ];
    
    if (isset($levels[$gasLevel])) {
        list($label, $class) = $levels[$gasLevel];
        return "<span class=\"badge badge-$class\">$label</span>";
    }
    return "<span class=\"badge badge-secondary\">N/A</span>";
}

// Helper function to format date
function formatDate($date) {
    if (!$date) return 'N/A';
    return date('M j, Y g:i A', strtotime($date));
}
?>

<div class="col-lg-12">
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Transport Logs</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        </div>

        <!-- Filter Panel -->
        <div class="card-body" id="filterPanel">
            <div class="row mb-3">
                <div class="col-md-12">
                    <button class="btn btn-sm btn-outline-primary" type="button" data-toggle="collapse" data-target="#advancedFilters" aria-expanded="false">
                        <i class="fas fa-filter"></i> Show Filters
                    </button>
                </div>
            </div>

            <div class="collapse" id="advancedFilters">
                <form method="GET" action="" class="border p-3 rounded mb-3">
                    <input type="hidden" name="page" value="transport_log">
                    
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="search">Search</label>
                                <input type="text" class="form-control form-control-sm" id="search" name="search" 
                                       placeholder="Plate, assignment, driver..." value="<?= htmlspecialchars($search) ?>">
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="date_from">Date From</label>
                                <input type="date" class="form-control form-control-sm" id="date_from" name="date_from" 
                                       value="<?= htmlspecialchars($date_from) ?>">
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="date_to">Date To</label>
                                <input type="date" class="form-control form-control-sm" id="date_to" name="date_to" 
                                       value="<?= htmlspecialchars($date_to) ?>">
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="vehicle_id">Vehicle</label>
                                <select class="form-control form-control-sm" id="vehicle_id" name="vehicle_id">
                                    <option value="">All Vehicles</option>
                                    <?php while($vehicle = $vehicles->fetch_assoc()): ?>
                                        <option value="<?= $vehicle['id'] ?>" <?= $vehicle_id == $vehicle['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($vehicle['plate_number']) ?> - <?= htmlspecialchars($vehicle['make_model']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="driver_id">Driver</label>
                                <select class="form-control form-control-sm" id="driver_id" name="driver_id">
                                    <option value="">All Drivers</option>
                                    <?php while($driver = $drivers->fetch_assoc()): ?>
                                        <option value="<?= $driver['id'] ?>" <?= $driver_id == $driver['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($driver['driver_name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="fuel_level">Fuel Level</label>
                                <select class="form-control form-control-sm" id="fuel_level" name="fuel_level">
                                    <option value="">All Levels</option>
                                    <option value="empty" <?= $fuel_level == 'empty' ? 'selected' : '' ?>>Empty</option>
                                    <option value="qtr" <?= $fuel_level == 'qtr' ? 'selected' : '' ?>>1/4</option>
                                    <option value="half" <?= $fuel_level == 'half' ? 'selected' : '' ?>>1/2</option>
                                    <option value="3/4" <?= $fuel_level == '3/4' ? 'selected' : '' ?>>3/4</option>
                                    <option value="full" <?= $fuel_level == 'full' ? 'selected' : '' ?>>Full</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fas fa-search"></i> Apply Filters
                            </button>
                            <a href="index.php?page=transport_log" class="btn btn-sm btn-secondary">
                                <i class="fas fa-redo"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Transport Log Table -->
        <div class="card-body">
            <div class="table-responsive">
                <table id="transportLogTable" class="table table-hover small">
                    <thead class="bg-light">
                        <tr>
                            <th>Vehicle</th>
                            <th>Assignment</th>
                            <th>Driver</th>
                            <th>Date</th>
                            <th>Mileage</th>
                            <th>Fuel Level</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($row['plate_number'] ?? 'N/A') ?></strong>
                                    <br><small class="text-muted"><?= htmlspecialchars($row['make_model'] ?? '') ?></small>
                                </td>
                                <td>
                                    <?php if (!empty($row['assignment_id'])): ?>
                                        <a href="index.php?page=view_assignment&id=<?= $row['assignment_id'] ?>">
                                            <?= htmlspecialchars($row['assignment_title'] ?? 'N/A') ?>
                                        </a>
                                        <br><small class="text-muted"><?= htmlspecialchars($row['location'] ?? '') ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($row['created_by_name'] ?? 'N/A') ?></td>
                                <td><?= formatDate($row['created_at']) ?></td>
                                <td>
                                    <?php if ($row['mileage']): ?>
                                        <strong><?= number_format($row['mileage']) ?></strong> km
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?= getFuelBadge($row['gas_level']) ?>
                                </td>
                                <td>
                                    <small><?= formatDate($row['updated_at']) ?></small>
                                </td>
                                <td class="text-center">
                                    <a href="index.php?page=view_assignment&id=<?= $row['assignment_id'] ?>" 
                                       class="btn btn-sm btn-outline-info" title="View Assignment">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button class="btn btn-sm btn-outline-warning edit-log" 
                                            data-id="<?= $row['id'] ?>"
                                            data-assignment-id="<?= $row['assignment_id'] ?>"
                                            data-mileage="<?= htmlspecialchars($row['mileage'] ?? '') ?>"
                                            data-gas-level="<?= htmlspecialchars($row['gas_level'] ?? '') ?>"
                                            title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Edit Transport Log Modal -->
<div class="modal fade" id="editTransportLogModal" tabindex="-1" role="dialog" aria-labelledby="editTransportLogModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editTransportLogModalLabel">Edit Transport Log</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editTransportLogForm">
                    <input type="hidden" id="edit_log_id" name="log_id">
                    <input type="hidden" id="edit_assignment_id" name="assignment_id">
                    <div class="form-group">
                        <label for="edit_mileage">Mileage (km)</label>
                        <input type="number" class="form-control" id="edit_mileage" name="mileage" required>
                    </div>
                    <div class="form-group">
                        <label>Fuel Level</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="gas_level" id="edit_gas_empty" value="empty">
                                <label class="form-check-label" for="edit_gas_empty">Empty</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="gas_level" id="edit_gas_qtr" value="qtr">
                                <label class="form-check-label" for="edit_gas_qtr">1/4</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="gas_level" id="edit_gas_half" value="half">
                                <label class="form-check-label" for="edit_gas_half">1/2</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="gas_level" id="edit_gas_3qtr" value="3/4">
                                <label class="form-check-label" for="edit_gas_3qtr">3/4</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="gas_level" id="edit_gas_full" value="full">
                                <label class="form-check-label" for="edit_gas_full">Full</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveEditTransportLogBtn">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable with enhanced export options
    $('#transportLogTable').DataTable({
        dom: '<"row"<"col-md-6"l><"col-md-6"f>><"row"<"col-md-12"B>><"row"<"col-md-12"tr>><"row"<"col-md-6"i><"col-md-6"p>>',
        buttons: [
            {
                extend: 'copy',
                text: '<i class="fas fa-copy"></i> Copy',
                className: 'btn btn-sm btn-outline-secondary',
                title: 'Transport Logs - ' + new Date().toLocaleDateString(),
                exportOptions: {
                    columns: ':not(:last-child)'
                }
            },
            {
                extend: 'csv',
                text: '<i class="fas fa-file-csv"></i> CSV',
                className: 'btn btn-sm btn-outline-secondary',
                filename: 'transport_logs_' + new Date().toISOString().slice(0, 10),
                exportOptions: {
                    columns: ':not(:last-child)'
                }
            },
            {
                extend: 'excel',
                text: '<i class="fas fa-file-excel"></i> Excel',
                className: 'btn btn-sm btn-outline-secondary',
                filename: 'transport_logs_' + new Date().toISOString().slice(0, 10),
                title: 'Transport Logs',
                exportOptions: {
                    columns: ':not(:last-child)'
                }
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf"></i> PDF',
                className: 'btn btn-sm btn-outline-secondary',
                filename: 'transport_logs_' + new Date().toISOString().slice(0, 10),
                title: 'Transport Logs - ' + new Date().toLocaleDateString(),
                orientation: 'landscape',
                pageSize: 'A4',
                exportOptions: {
                    columns: ':not(:last-child)'
                }
            },
            {
                extend: 'print',
                text: '<i class="fas fa-print"></i> Print',
                className: 'btn btn-sm btn-outline-secondary',
                title: 'Transport Logs',
                exportOptions: {
                    columns: ':not(:last-child)'
                }
            }
        ],
        order: [[3, 'desc']], // Sort by Date descending
        responsive: true,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        columnDefs: [
            {
                targets: -1,
                searchable: false,
                sortable: false
            }
        ]
    });

    // Edit log button click handler
    $(document).on('click', '.edit-log', function() {
        const logId = $(this).data('id');
        const assignmentId = $(this).data('assignment-id');
        const mileage = $(this).data('mileage');
        const gasLevel = $(this).data('gas-level');

        // Populate modal form
        $('#edit_log_id').val(logId);
        $('#edit_assignment_id').val(assignmentId);
        $('#edit_mileage').val(mileage);

        // Clear previous selections
        $('input[name="gas_level"]').prop('checked', false);

        // Set fuel level radio button
        if (gasLevel) {
            $('input[name="gas_level"][value="' + gasLevel + '"]').prop('checked', true);
        }

        // Show modal
        $('#editTransportLogModal').modal('show');
    });

    // Save edited log
    $('#saveEditTransportLogBtn').on('click', function() {
        const assignmentId = $('#edit_assignment_id').val();
        const mileage = $('#edit_mileage').val();
        const gasLevel = $('input[name="gas_level"]:checked').val();

        if (!mileage) {
            alert('Please enter mileage');
            return;
        }

        if (!gasLevel) {
            alert('Please select a fuel level');
            return;
        }

        $.ajax({
            url: 'ajax.php?action=update_transport_log',
            type: 'POST',
            data: {
                assignment_id: assignmentId,
                mileage: mileage,
                gas_level: gasLevel
            },
            success: function(response) {
                if (response == 1) {
                    alert('Transport log updated successfully');
                    location.reload();
                } else {
                    alert('Failed to update transport log');
                }
            },
            error: function() {
                alert('Error updating transport log');
            }
        });

        $('#editTransportLogModal').modal('hide');
    });

    // Toggle filter panel on load
    var hasActiveFilters = '<?= ($search || $date_from || $date_to || $vehicle_id || $driver_id || $fuel_level) ? 'true' : 'false' ?>';
    if (hasActiveFilters === 'true') {
        $('#advancedFilters').addClass('show');
    }
});
</script>
