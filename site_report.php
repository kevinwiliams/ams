<?php
include 'db_connect.php'; // Include database connection

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$assignment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$assignment_id) die('Assignment ID is required');

// Check if assignment exists
$stmt = $conn->prepare("SELECT * FROM assignment_list WHERE id = ?");
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$assignment = $stmt->get_result()->fetch_assoc();

if (!$assignment) {
    die("Assignment not found");
}

// Try to find existing inspection
$stmt = $conn->prepare("SELECT * FROM venue_inspections WHERE assignment_id = ?");
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$inspection = $stmt->get_result()->fetch_assoc();

// If no inspection exists, create a new one
if (!$inspection) {
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO venue_inspections (assignment_id, site_visit_date, updated_by) VALUES (?, CURDATE(), ?)");
        $stmt->bind_param("ii", $assignment_id, $_SESSION['login_id']);
        $stmt->execute();
        $inspection_id = $conn->insert_id;
        
        // Fetch the newly created inspection
        $stmt = $conn->prepare("SELECT * FROM venue_inspections WHERE id = ?");
        $stmt->bind_param("i", $inspection_id);
        $stmt->execute();
        $inspection = $stmt->get_result()->fetch_assoc();
        
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        die("Error creating inspection: " . $e->getMessage());
    }
}

// Initialize data arrays
$inspection = array_merge([
    'id' => '',
    'assignment_id' => $assignment_id,
    'parking_available' => 0,
    'bathrooms_available' => 0,
    'setup_time' => '',
    'layout_notes' => '',
    'tent_location' => '',
    'banner_location' => '',
    'general_notes' => '',
    'permit_notes' => '',
    'site_visit_date' => date('Y-m-d')
], $inspection);



// Fetch permits
$permits = [];
$result = $conn->query("SELECT permit_type FROM venue_permits WHERE inspection_id = " . ($inspection['id'] ?: '0'));
while ($row = $result->fetch_assoc()) {
    $permits[] = $row['permit_type'];
}

// Get inventory items
$inventory_items = [];
$stmt = $conn->query("SELECT * FROM ob_items ORDER BY item_name");
$all_items = $stmt->fetch_all(MYSQLI_ASSOC);

$stmt = $conn->prepare("SELECT * FROM ob_inventory WHERE assignment_id = ?");
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$existing_inventory = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Prepare inventory data for display
$inventory_data = [];
foreach ($all_items as $item) {
    $found = false;
    foreach ($existing_inventory as $inv) {
        if ($inv['item_id'] == $item['item_id']) {
            $inventory_data[] = [
                'item_id' => $item['item_id'],
                'item_name' => $item['item_name'],
                'status' => $inv['status'],
                'quantity' => $inv['quantity'],
                'notes' => $inv['notes']
            ];
            $found = true;
            break;
        }
    }
    if (!$found) {
        $inventory_data[] = [
            'item_id' => $item['item_id'],
            'item_name' => $item['item_name'],
            'status' => 0,
            'quantity' => 0,
            'notes' => ''
        ];
    }
}

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
                                    <span class="text-muted">Report:</span> <?= htmlspecialchars($assignment['title']) ?>
                                </p>
                                <p class="text-muted mb-1">
                                
                                    <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($assignment['location']) ?>
                                    &nbsp; <i class="fas fa-calendar-alt"></i> <?= date('D, M j, Y', strtotime($assignment['assignment_date'])) ?> &nbsp;  <i class="fas fa-clock"></i><?= htmlspecialchars($assignment['start_time']) ?> - <?= htmlspecialchars($assignment['end_time']) ?>
</p><p class="text-muted p-0">
                                <?php 
                                $station_show_parts = explode(':', $assignment['station_show']);
                                $station_show = isset($station_show_parts[1]) ? $station_show_parts[1] : $assignment['station_show'];
                                ?>
                                <i class="fas fa-broadcast-tower"></i> <?= htmlspecialchars($station_show) ?>
                                </p>
                            </div>
                            <div class="ms-3"> <!-- ms-3 adds margin-left -->
                                <img src="assets/uploads/<?= (str_contains($assignment['station_show'], 'FYAH')) ? 'fyah':'edge'?>_logo.png" alt="Report Image" class="img-fluid rounded" style="max-height: 80px;">
                            </div>
                        </div>
                        <hr>
                    </div>
                </div>
                <form id="inspectionForm">
                    <input type="hidden" name="id" value="<?= $inspection['id'] ?>">
                    <input type="hidden" name="assignment_id" value="<?= $assignment_id ?>">
                    
                    <!-- Basic Information -->
                    <div class="form-row mb-3">
                        <div class="col-md-6">
                            <label for="site_visit_date">Site Visit Date</label>
                            <input type="date" class="form-control" name="site_visit_date" 
                                   value="<?= $inspection['site_visit_date'] ?>" required>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="setup_time" class="control-label">Setup Time</label>
                                <select name="setup_time" id="setup_time" class="custom-select custom-select-sm">
                                    <option value="">Select Setup Time</option>
                                    <?php 
                                    $times = [];
                                    for ($i = 0; $i < 24; $i++) {
                                        for ($j = 0; $j < 60; $j += 15) {
                                            $time = sprintf('%02d:%02d', $i, $j);
                                            $display_time = date('h:i A', strtotime($time));
                                            if ($time == '00:00') $display_time = 'Midnight';
                                            $selected = ($inspection['setup_time'] == $display_time) ? 'selected' : '';
                                            echo "<option value='$display_time' $selected>$display_time</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Inventory Section -->
                        <div class="card mb-3 col-lg-6 col-sm-12">
                            <div class="card-header text-bold">Execution Requirements</div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm" id="inventoryTable">
                                        <thead>
                                            <tr>
                                                <th>Item</th>
                                                <th>Used</th>
                                                <th>Quantity</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($inventory_data as $item): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($item['item_name']) ?></td>
                                                <td>
                                                    <input type="hidden" name="inventory[<?= $item['item_id'] ?>][item_id]" value="<?= $item['item_id'] ?>">
                                                    <div class="custom-control custom-switch">
                                                        <input type="checkbox" class="custom-control-input" 
                                                               id="item_status_<?= $item['item_id'] ?>" 
                                                               name="inventory[<?= $item['item_id'] ?>][status]" 
                                                               value="1" <?= $item['status'] ? 'checked' : '' ?>>
                                                        <label class="custom-control-label" for="item_status_<?= $item['item_id'] ?>"></label>
                                                    </div>
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control form-control-sm" 
                                                           name="inventory[<?= $item['item_id'] ?>][quantity]" 
                                                           value="<?= $item['quantity'] ?>" min="0">
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <!-- General Notes & Amenities -->
                        <div class="form-group col-lg-6 col-sm-12 px-lg-4">
                            <label for="general_notes">Additional Notes</label>
                            <textarea class="form-control form-control-sm summernote textarea" 
                                      name="general_notes" id="general_notes" rows="2"><?= htmlspecialchars_decode($inspection['general_notes'] ?? '') ?></textarea>
                              
                            <!-- Amenities -->
                            <div class="form-row my-4">
                                <div class="col-md-6">
                                    <label>Is parking available?</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="parking_available" 
                                               id="parking_yes" value="1" <?= $inspection['parking_available'] ? 'checked' : '' ?> required>
                                        <label class="form-check-label" for="parking_yes">Yes</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="parking_available" 
                                               id="parking_no" value="0" <?= !$inspection['parking_available'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="parking_no">No</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label>Are bathrooms available?</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="bathrooms_available" 
                                               id="bathrooms_yes" value="1" <?= $inspection['bathrooms_available'] ? 'checked' : '' ?> required>
                                        <label class="form-check-label" for="bathrooms_yes">Yes</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="bathrooms_available" 
                                               id="bathrooms_no" value="0" <?= !$inspection['bathrooms_available'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="bathrooms_no">No</label>
                                    </div>
                                    
                                    
                                </div>
                            </div>

                            <!-- Layout Details -->
                            <div class="card mb-3">
                                <div class="card-header">Venue Layout</div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="layout_notes">Layout Notes</label>
                                            <textarea class="form-control" name="layout_notes" id="layout_notes" rows="3"><?= htmlspecialchars($inspection['layout_notes'] ?? '') ?></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="tent_location">Tent Location</label>
                                            <textarea class="form-control" name="tent_location" id="tent_location" rows="2"><?= htmlspecialchars($inspection['tent_location'] ?? '') ?></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="banner_location">Banner Location</label>
                                            <textarea class="form-control" name="banner_location" id="banner_location" rows="2"><?= htmlspecialchars($inspection['banner_location'] ?? '') ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    
                    <hr>
                    
                    <div class="row">
                        <!-- Permits Section -->
                        <div class="col-lg-6 col-sm-12">
                            <div class="card mb-3">
                                <div class="card-header">Permits Obtained</div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="permits[]" 
                                                   id="permit_municipal" value="municipal" <?= in_array('municipal', $permits) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="permit_municipal">Municipal</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="permits[]" 
                                                   id="permit_jcf" value="jcf" <?= in_array('jcf', $permits) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="permit_jcf">JCF (Police)</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="permits[]" 
                                                   id="permit_jfb" value="jfb" <?= in_array('jfb', $permits) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="permit_jfb">JFB (Fire Brigade)</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="permits[]" 
                                                   id="permit_ems" value="ems" <?= in_array('ems', $permits) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="permit_ems">EMS (Emergency Medical Services)</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="permits[]" 
                                                   id="permit_jamms" value="jamms" <?= in_array('jamms', $permits) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="permit_jamms">JAMMS/JACAP</label>
                                        </div>
                                        <textarea class="form-control form-control-sm mt-2 textarea" 
                                                  name="permit_notes" placeholder="Permit details"><?= htmlspecialchars($inspection['permit_notes']) ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col">
                            <button type="submit" class="btn btn-primary mr-2" id="saveBtn">
                                <i class="fas fa-save"></i> Save Inspection
                            </button>
                            <a href="index.php?page=view_site_report&id=<?= $assignment_id ?>" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
        $(document).ready(function() {
            
            $('.custom-select-sm').select2();
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

            // Form submission handler
            $('#inspectionForm').on('submit', function(e) {
                e.preventDefault();
                
                // Disable save button during submission
                $('#saveBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
                
                // Serialize form data including unchecked checkboxes
                var formData = new FormData(this);
                
                // Manually add permit checkboxes if unchecked
                $('input[name="permits[]"]').each(function() {
                    if (!$(this).is(':checked')) {
                        formData.append($(this).attr('name'), '');
                    }
                });
                
                // Add AJAX request
                $.ajax({
                    url: 'ajax.php?action=save_inspection',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            alert_toast('Inspection saved successfully!', 'success');
                            
                            // Update hidden ID field if this was a new inspection
                            if (response.inspection_id) {
                                $('input[name="id"]').val(response.inspection_id);
                            }
                            let id = $('input[name="assignment_id"]').val();
                            setTimeout(() => {
                                    location.href = 'index.php?page=view_site_report&id=' + id; // Redirect after success
                                }, 2500);

                        } else {
                            alert_toast('Error: ' + response.message, 'danger');
                        }
                    },
                    error: function(xhr, status, error) {
                        alert_toast('AJAX Error: ' + error, 'danger');
                        console.error(xhr.responseText);
                    },
                    complete: function() {
                        $('#saveBtn').prop('disabled', false).html('<i class="fas fa-save"></i> Save Inspection');
                    }
                });
            });
            
                       
            // Toggle quantity field based on status
            $('input[name^="inventory["][name$="[status]"]').change(function() {
                var quantityField = $(this).closest('tr').find('input[type="number"]');
                quantityField.prop('disabled', !this.checked);
            }).trigger('change');
        });
    </script>