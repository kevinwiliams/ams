<?php
include 'db_connect.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$assignment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_role = $_SESSION['role_name'] ?? '';
$radio_staff = $_SESSION['login_sb_staff'] == 1 ? true : false;
$edit_roles = ['Broadcast Coordinator', 'Op Manager', 'ITAdmin'];


if (!$assignment_id) die('Assignment ID is required');

// Get assignment details
$stmt = $conn->prepare("SELECT * FROM assignment_list WHERE id = ?");
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$assignment = $stmt->get_result()->fetch_assoc();

if (!$assignment) {
    die("Assignment not found");
}

// Get inspection details
$stmt = $conn->prepare("SELECT vi.*, u.firstname, u.lastname FROM venue_inspections vi LEFT JOIN users u ON vi.updated_by = u.id WHERE vi.assignment_id = ?");
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$inspection = $stmt->get_result()->fetch_assoc();

if (!$inspection) {
    echo '<a href="index.php?page=site_report&id='. $assignment_id.'" class="mx-5"> <i class="fas fa-clipboard-check"></i> Create new inspection report.</a>';

    die("No inspection found for this assignment");
}

// Get permits
$permits = [];
$result = $conn->query("SELECT permit_type FROM venue_permits WHERE inspection_id = " . $inspection['id']);
while ($row = $result->fetch_assoc()) {
    $permits[] = $row['permit_type'];
}

// Get inventory items
$inventory_items = [];
$stmt = $conn->prepare("
    SELECT i.item_name, inv.status, inv.quantity, inv.notes 
    FROM ob_inventory inv
    JOIN ob_items i ON inv.item_id = i.item_id
    WHERE inv.assignment_id = ? AND inv.status = 1
    ORDER BY i.item_name
");
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$required_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Format date and time
$site_visit_date = $inspection['site_visit_date'] ? date('F j, Y', strtotime($inspection['site_visit_date'])) : 'Not specified';
$setup_time = $inspection['setup_time'] ?: 'Not specified';
?>

<div class="container">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="h5">
                                    <i class="fas fa-clipboard-check"></i>
                                    <span class="text-muted">Form :</span> <?= htmlspecialchars_decode($assignment['title']) ?>
                                </p>
                                <p class="text-muted">
                                    <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($assignment['location']) ?>
                                    &nbsp; <i class="fas fa-calendar-alt"></i> <?= date('D, M j, Y', strtotime($assignment['assignment_date'])) ?>
                                    &nbsp; <i class="fas fa-clock"></i> <?= htmlspecialchars($assignment['start_time']) ?> - <?= htmlspecialchars($assignment['end_time']) ?>
                                </p>
                            </div>
                            <div class="ms-3">
                                <img src="assets/uploads/<?= (str_contains($assignment['station_show'], 'FYAH')) ? 'fyah':'edge'?>_logo.png" 
                                     alt="Station Logo" class="img-fluid rounded" style="max-height: 80px;">
                            </div>
                        </div>
                        <hr>
                    </div>
                </div>
                <form id="inspection-form">
                    <input type="hidden" name="assignment_id" value="<?= $assignment_id ?>">
                    <?php
                        $report_status = '';
                        if (in_array($user_role, ['Producer', 'Broadcast Coordinator'])) {
                            $report_status = 'Pending';
                        } elseif ($user_role === 'Engineer') {
                            $report_status = 'Confirmed';
                        } elseif ($user_role === 'Op Manager') {
                            $report_status = 'Approved';
                        }
                    ?>
                    <input type="hidden" name="report_status" value="<?= $report_status ?>">
                </form>
                <!-- Basic Information -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <i class="fas fa-info-circle"></i> Inspection Details
                            </div>
                            <div class="card-body">
                                <p><strong>Site Visit Date:</strong> <?= $site_visit_date ?></p>
                                <p><strong>Toll Required:</strong> <?= $inspection['toll_required'] ? 'Yes' : 'No' ?></p>
                                <p><strong>Setup Time:</strong> <?= $setup_time ?></p>
                                <p><strong>Parking Available:</strong> <?= $inspection['parking_available'] ? 'Yes' : 'No' ?></p>
                                <p><strong>Bathrooms Available:</strong> <?= $inspection['bathrooms_available'] ? 'Yes' : 'No' ?></p>
                                <p><strong>Network:</strong> <?= $inspection['network_available'] ?? 'Not Assigned' ?></p>
                                <p><strong>Power Source:</strong> 
                                    <?php if (!empty($inspection['bring_your_own'])): ?>
                                        Bring own power source
                                    <?php else: ?>
                                        <?= isset($inspection['nearest_power_source']) ? $inspection['nearest_power_source'] . ' Feet' : 'Not Assigned' ?>
                                    <?php endif; ?>
                                </p>

                            </div>
                        </div>
                        <!-- Show & Personnel Card -->
                        <div class="card mb-3">
                        <div class="card-header bg-light">
                        <i class="fas fa-users"></i> Show & Personnel
                        </div>
                        <div class="card-body">
                        <?php
                        // Extract show name (handle "STATION: SHOW" format)
                        $show_name = $assignment['station_show'];
                        if (strpos($show_name, ':') !== false) {
                            $show_parts = explode(':', $show_name, 2);
                            $show_name = trim($show_parts[1]);
                        }

                        // Get team member IDs
                        $team_member_ids = !empty($assignment['team_members']) ? explode(',', $assignment['team_members']) : [];

                        // Fetch team members with their roles
                        $personnel = [
                            'Host' => null,
                            'Producer' => null,
                            'Engineer' => null
                        ];

                        if (!empty($team_member_ids)) {
                            // Prepare the list for SQL query
                            $placeholders = implode(',', array_fill(0, count($team_member_ids), '?'));
                            $stmt = $conn->prepare("
                                SELECT u.empid, u.firstname, u.lastname, r.role_name 
                                FROM users u
                                JOIN roles r ON u.role_id = r.role_id
                                WHERE u.empid IN ($placeholders)
                            ");
                            
                            // Bind parameters dynamically
                            $types = str_repeat('s', count($team_member_ids));
                            $stmt->bind_param($types, ...$team_member_ids);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            while ($member = $result->fetch_assoc()) {
                                switch ($member['role_name']) {
                                    case 'Personality':
                                    case 'Programme Director':
                                        $personnel['Host'] = $member;
                                        break;
                                    case 'Producer':
                                    case 'Broadcast Coordinator':
                                        $personnel['Producer'] = $member;
                                        break;
                                    case 'Engineer':
                                        $personnel['Engineer'] = $member;
                                        break;
                                }
                            }
                        }
                        ?>

                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Show:</strong> <?= htmlspecialchars($show_name) ?></p>
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col-md-4">
                                <p><strong>Host:</strong><br>
                                <?php if ($personnel['Host']): ?>
                                    <?= htmlspecialchars($personnel['Host']['firstname'] . ' ' . $personnel['Host']['lastname']) ?>
                                <?php else: ?>
                                    <span class="text-muted">Not assigned</span>
                                <?php endif; ?>
                                </p>
                            </div>
                            
                            <div class="col-md-4">
                                <p><strong>Producer:</strong><br>
                                <?php if ($personnel['Producer']): ?>
                                    <?= htmlspecialchars($personnel['Producer']['firstname'] . ' ' . $personnel['Producer']['lastname']) ?>
                                <?php else: ?>
                                    <span class="text-muted">Not assigned</span>
                                <?php endif; ?>
                                </p>
                            </div>
                            
                            <div class="col-md-4">
                                <p><strong>Engineer:</strong><br>
                                <?php if ($personnel['Engineer']): ?>
                                    <?= htmlspecialchars($personnel['Engineer']['firstname'] . ' ' . $personnel['Engineer']['lastname']) ?>
                                <?php else: ?>
                                    <span class="text-muted">Not assigned</span>
                                <?php endif; ?>
                                </p>
                            </div>
                        </div>

                        </div>
                        </div>
                    </div>

                    <!-- Required Items -->
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <i class="fas fa-boxes"></i> Required Equipment
                            </div>
                            <div class="card-body">
                                <?php if (!empty($required_items)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Item</th>
                                                    <th>Qty</th>
                                                    <th>Notes</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($required_items as $item): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                                                        <td><?= $item['quantity'] ?></td>
                                                        <td><?= htmlspecialchars($item['notes']) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">No equipment marked as required</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Layout Details -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <i class="fas fa-map-marked-alt"></i> Venue Layout
                            </div>
                            <div class="card-body">
                                <?php if ($inspection['layout_notes']): ?>
                                    <h6>Layout Notes:</h6>
                                    <div class="border p-2 mb-2 bg-white">
                                        <?= nl2br(htmlspecialchars($inspection['layout_notes'])) ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($inspection['tent_location']): ?>
                                    <h6>Tent Location:</h6>
                                    <div class="border p-2 mb-2 bg-white">
                                        <?= nl2br(htmlspecialchars($inspection['tent_location'])) ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($inspection['banner_location']): ?>
                                    <h6>Banner Location:</h6>
                                    <div class="border p-2 bg-white">
                                        <?= nl2br(htmlspecialchars($inspection['banner_location'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="text-muted ">
                            Created At: <?= date('M. j, Y g:iA', strtotime($inspection['created_at'])) ?>
                            <?php if ($inspection['updated_at']): ?>
                                <br>Updated: <?= date('M. j, Y g:iA', strtotime($inspection['updated_at'])) ?>
                            <?php endif; ?>
                            <?php if ($inspection['updated_by']): ?>
                                <br>Last updated by: <?= htmlspecialchars($inspection['firstname']) . ' ' . htmlspecialchars($inspection['lastname']) ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Permits and General Notes -->
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <i class="fas fa-file-alt"></i> Permits & Notes
                            </div>
                            <div class="card-body">
                                <?php if (!empty($permits) && array_filter($permits)): ?>
                                    <h6>Permits Obtained:</h6>
                                    <ul>
                                        <?php foreach ($permits as $permit): ?>
                                            <?php if (empty($permit)) continue; ?>
                                            <li><?= strtoupper($permit) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p class="text-muted">No permits recorded</p>
                                <?php endif; ?>

                                <!-- General Notes -->
                                <?php if (!empty(trim($inspection['general_notes']))): ?>
                                    <h6>Additional Notes:</h6>
                                    <div class="border p-2 bg-white">
                                        <?= nl2br(htmlspecialchars_decode($inspection['general_notes'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row no-print">
                    <div class="col">
                        <a href="index.php?page=view_assignment&id=<?= $assignment_id ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Assignment
                        </a>
                        <?php if (in_array($user_role, $edit_roles)): ?>
                            <?php if ($user_role === 'Engineer' && $inspection['report_status'] === 'Pending'): ?>
                                <button id="confirm-report-btn" class="update-status-btn btn btn-success float-right mx-2">
                                    <i class="fas fa-check-circle"></i> Confirm Report
                                </button>
                            <?php elseif ($user_role === 'Op Manager' && $inspection['report_status'] === 'Confirmed'): ?>
                                <button id="approve-report-btn" class="update-status-btn btn btn-success float-right mx-2">
                                    <i class="fas fa-check-circle"></i> Approve Report
                                </button>
                            <?php endif; ?>

                            <?php if ($inspection['report_status'] !== 'Approved'): ?>
                                <a href="index.php?page=site_report&id=<?= $assignment_id ?>" class="btn btn-primary float-right">
                                    <i class="fas fa-edit"></i> Edit Form
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if ($inspection['report_status'] === 'Approved'): ?>
                            <button onclick="window.print()" class="btn btn-primary no-print">
                                <i class="fas fa-print"></i> Print Form
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .card-header {
        font-weight: 600;
    }
    h6 {
        font-size: 0.9rem;
        font-weight: 600;
        margin-top: 0.5rem;
    }

    @media print {
    .no-print {
        display: none !important;
    }

    @media print {
        .col-md-6 {
            width: 50%; /* Ensure two-column layout */
        }

        .col-md-4 {
            width: 33.33%; /* Ensure three-column layout */
        }

        .col-lg-12 {
            width: 100%;
        }
    }
}

</style>

<script>
    function printCard() {
        var card = document.querySelector('.card'); // Select the card
        var printWindow = window.open('', '', 'width=800,height=600');
        printWindow.document.write('<html><head><title>Print Report</title>');
        printWindow.document.write('<link rel="stylesheet" href="assets/dist/css/adminlte.min.css">'); // Link your CSS file
        printWindow.document.write('<link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">'); // Link your CSS file
        printWindow.document.write('</head><body>');
        printWindow.document.write(card.outerHTML); // Only print the card
        printWindow.document.write('</body></html>');
        printWindow.document.close();

        // printWindow.print();
    }//

    $(document).ready(function() {
        $(document).on('click', '.update-status-btn', function(e) {
            e.preventDefault();
            
            // Get form data
            var formData = {
                assignment_id: $('#inspection-form').find('input[name="assignment_id"]').val(),
                report_status: $('#inspection-form').find('input[name="report_status"]').val()
            };

            // Validate required fields
            if (!formData.assignment_id || !formData.report_status) {
                alert_toast('Please make sure all required fields are filled', 'error');
                return false;
            }

            // Show confirmation dialog
            alert_toast('', 'warning', '', {
                isConfirmation: true,
                title: 'Update Report Status',
                text: 'Are you sure you want to update this report status?',
                confirmText: 'Yes, update it',
                confirmCallback: function() {
                    // Show loading indicator
                    alert_toast('Updating report status...', 'info', '', {
                        showSuccessButton: false,
                        timer: null // No auto-close
                    });

                    // Make AJAX request
                    $.ajax({
                        url: 'ajax.php?action=update_report_status', // Update with your endpoint
                        type: 'POST',
                        dataType: 'json',
                        data: formData,
                        success: function(response) {
                            Swal.close(); // Close loading dialog
                            if (response.status === 'success') {
                                window.alert_toast(response.message, 'success', '', {
                                    timer: 2000,
                                    showSuccessButton: false
                                });
                                // Optional: Reload page or update UI after delay
                                setTimeout(function() {
                                    location.reload();
                                }, 2000);
                            } else {
                                window.alert_toast(response.message, 'error');
                            }
                        },
                        error: function(xhr, status, error) {
                            Swal.close(); // Close loading dialog
                            window.alert_toast('An error occurred: ' + error, 'error');
                        }
                    });
                }
            });
        });
    
    });
</script>