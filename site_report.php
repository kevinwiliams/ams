<?php
include 'db_connect.php'; // Include database connection

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_role = $_SESSION['role_name'] ?? '';

//Declare user roles for filtering
$editor_roles = ['Op Manager'];

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
                                    <span class="text-muted">Form :</span> <?= htmlspecialchars($assignment['title']) ?>
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
                    <input type="hidden" name="assignment_title" value="<?= $assignment['title']?>">
                    <input type="hidden" name="assignment_date" value="<?= $assignment['assignment_date'] ?>">
                    <input type="hidden" name="assignment_time" value="<?= htmlspecialchars($assignment['start_time']).' - '.htmlspecialchars($assignment['end_time'])?>">

                    
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
                        <div class="card-header text-bold d-flex justify-content-between align-items-center">
                            <span>Execution Requirements</span>
                            <?php if (in_array($user_role, $editor_roles)): ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary ml-auto" onclick="printEquipmentForm()">
                                <i class="fas fa-print"></i> Print Gate Pass
                            </button>
                            <?php endif; ?>
                        </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm" id="inventoryTable">
                                        <thead>
                                            <tr>
                                                <th>Item</th>
                                                <th>Used</th>
                                                <th>Quantity</th>
                                                <?php if (in_array($user_role, $editor_roles)): ?>
                                                    <th>Notes</th>
                                                <?php endif; ?>    
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($inventory_data as $item): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($item['item_name']) ?></td>
                                                <td>
                                                    <input type="hidden" name="inventory[<?= $item['item_id'] ?>][item_id]" value="<?= $item['item_id'] ?>">
                                                    <input type="hidden" name="inventory[<?= $item['item_id'] ?>][name]" value="<?= $item['item_name'] ?>">
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
                                                <?php if (in_array($user_role, $editor_roles)): ?>
                                                <td>
                                                    <input type="text" class="form-control form-control-sm" 
                                                           name="inventory[<?= $item['item_id'] ?>][notes]" 
                                                           value="<?= $item['notes'] ?>" placeholder="Notes">
                                                </td>
                                                <?php endif; ?>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input text-primary" id="items_requested" name="items_requested" <?php //= isset($inspection['items_requested']) && $inspection['items_requested'] == 1 ? 'checked' : '' ?>>
                                        <label class="custom-control-label text-primary font-weight-light" for="items_requested">
                                        <?= isset($inspection['items_requested']) && $inspection['items_requested'] == 1 ? 'Form Sent' : 'Send Equipment Form' ?>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- General Notes & Amenities -->
                        <div class="form-group col-lg-6 col-sm-12 px-lg-4">
                            <div class="form-group">
                                <div class="custom-control custom-switch my-2">
                                    <input type="checkbox" class="custom-control-input" id="toll_required" name="toll_required" value="1" <?php echo isset($inspection['toll_required']) && $inspection['toll_required'] == 1 ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="toll_required">
                                    <?php echo (isset($inspection['toll_required']) && $inspection['toll_required'] == 1) ? 'Toll Fee Requested' : 'Request Toll Fee'; ?>
                                    </label>
                                </div>
                            </div>                           
                            
                              
                            <!-- Amenities -->
                            

                            <!-- Layout Details -->
                            <div class="card mb-3">
                                <div class="card-header">Venue Layout</div>
                                    <div class="card-body">
                                        <div class="form-row mb-3">
                                            <div class="col-md-6">
                                                <label>Is parking available?</label>
                                                <div class="custom-control custom-switch">
                                                    <input class="custom-control-input" type="checkbox" name="parking_available" 
                                                           id="parking_available" value="1" <?= $inspection['parking_available'] ? 'checked' : '' ?>>
                                                    <label class="custom-control-label" for="parking_available">Yes</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label>Are bathrooms available?</label>
                                                <div class="custom-control custom-switch">
                                                    <input class="custom-control-input" type="checkbox" name="bathrooms_available" 
                                                           id="bathrooms_available" value="1" <?= $inspection['bathrooms_available'] ? 'checked' : '' ?>>
                                                    <label class="custom-control-label" for="bathrooms_available">Yes</label>
                                                </div>
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input class="custom-control-input" type="checkbox" name="bring_your_own" id="bring_your_own" value="1" <?= isset($inspection['bring_your_own']) && $inspection['bring_your_own'] ? 'checked' : '' ?>>
                                                <label class="custom-control-label font-weight-light" for="bring_your_own">Bring Your Own Power</label>
                                            </div>
                                        </div>
                            
                                        <div class="form-group" id="nearest_power_group" class="<?= isset($inspection['bring_your_own']) && $inspection['bring_your_own'] ? 'd-none' : '' ?>" style="">
                                            <label for="nearest_power_source">Nearest Power Source (feet)</label>
                                            <input type="number" class="form-control" name="nearest_power_source" id="nearest_power_source" value="<?= htmlspecialchars($inspection['nearest_power_source'] ?? '') ?>">
                                        </div>
                                        <hr>
                                        <div class="form-group">
                                            <label for="network_available">Network Available</label>
                                            <select class="form-control custom-select-sm" name="network_available" id="network_available">
                                                <option value="">-- Select --</option>
                                                <option value="DIGICEL" <?= isset($inspection['network_available']) && $inspection['network_available'] == 'DIGICEL' ? 'selected' : '' ?>>DIGICEL</option>
                                                <option value="FLOW" <?= isset($inspection['network_available']) && $inspection['network_available'] == 'FLOW' ? 'selected' : '' ?>>FLOW</option>
                                                <option value="OTHER" <?= isset($inspection['network_available']) && $inspection['network_available'] == 'OTHER' ? 'selected' : '' ?>>OTHER</option>
                                                <option value="NONE" <?= isset($inspection['network_available']) && $inspection['network_available'] == 'NONE' ? 'selected' : '' ?>>NONE</option>
                                            </select>
                                        </div>
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
                                <label for="general_notes">Additional Notes</label>
                                <textarea class="form-control form-control-sm summernote textarea" 
                                        name="general_notes" id="general_notes" rows="2"><?= htmlspecialchars_decode($inspection['general_notes'] ?? '') ?>
                                </textarea>
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
                                        <textarea class="form-control form-control-sm mt-2 textarea d-none" 
                                                  name="permit_notes" placeholder="Permit details"><?= htmlspecialchars($inspection['permit_notes']) ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Footer -->
                    <div class="card mt-1">
                        <div class="card-body d-flex justify-content-between align-items-center">
                         
                            
                            <div class="ml-auto">
                             
                                <a href="index.php?page=view_site_report&id=<?= $assignment_id ?>" class="btn btn-secondary mr-2">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                                <button type="submit" class="btn btn-primary " id="saveBtn">
                                <i class="fas fa-save"></i> Save Form
                            </button>
                            
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include('gate_pass.php'); ?>

<script>
        $(document).ready(function() {

             // Update Yes/No text on toggle
            $('#parking_available').change(function() {
                $(this).next('label').text(this.checked ? 'Yes' : 'No');
            }).trigger('change');

            $('#bathrooms_available').change(function() {
                $(this).next('label').text(this.checked ? 'Yes' : 'No');
            }).trigger('change');

            $('#bring_your_own').change(function() {
                $('#nearest_power_group').toggle(!this.checked);
            });
            
            // Trigger the change event on page load in case checkbox is already checked
            $('#bring_your_own').trigger('change');
            
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
                            alert_toast('Error: ' + response.message, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        alert_toast('AJAX Error: ' + error, 'error');
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

        function printEquipmentForm() {
            // Get current date for the form
            const now = new Date();
            document.getElementById('print-current-date').textContent = now.toLocaleDateString('en-US', {
                year: 'numeric', month: 'long', day: 'numeric'
            }) + ' at ' + now.toLocaleTimeString('en-US', {
                hour: '2-digit', minute: '2-digit'
            });

            // Populate the print form with current data
            document.getElementById('print-assignment-title').textContent = document.querySelector('input[name="assignment_title"]').value;
            document.getElementById('print-assignment-date').textContent = document.querySelector('input[name="assignment_date"]').value;
            document.getElementById('print-assignment-time').textContent = document.querySelector('input[name="assignment_time"]').value;
            document.getElementById('print-site-visit-date').textContent = document.querySelector('input[name="site_visit_date"]').value;
            document.getElementById('print-setup-time').textContent = document.querySelector('select[name="setup_time"]').value;
            
            // Populate equipment table
            const inventoryRows = document.querySelectorAll('#inventoryTable tbody tr');
            const printBody = document.getElementById('print-equipment-body');
            printBody.innerHTML = '';
            
            inventoryRows.forEach(row => {
                const itemName = row.querySelector('td:first-child').textContent;
                const quantityInput = row.querySelector('input[type="number"]');
                const quantity = quantityInput ? quantityInput.value : '0';
                const notesInput = row.querySelector('input[type="text"][name*="notes"]');
                const notes = notesInput ? notesInput.value : '';
                
                const newRow = document.createElement('tr');
                newRow.style.borderBottom = '1px solid #eee';
                if(quantity > 0){
                    newRow.innerHTML = `
                    <td style="padding: 10px 15px; border-bottom: 1px solid #eee;">${itemName}</td>
                    <td style="padding: 10px 15px; text-align: center; border-bottom: 1px solid #eee;">${quantity}</td>
                    <td style="padding: 10px 15px; text-align: center; border-bottom: 1px solid #eee;"></td>
                    <td style="padding: 10px 15px; border-bottom: 1px solid #eee;">${notes}</td>
                `;
                }
                
                printBody.appendChild(newRow);
            });
            
            // Create a new window for printing
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                    <head>
                        <title>Equipment Checkout Form</title>
                        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
                        <style>
                            @media print {
                                body {
                                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                                    margin: 0;
                                    padding: 20px;
                                    color: #333;
                                }
                                .no-print {
                                    display: none !important;
                                }
                            }
                            @page {
                                size: A4;
                                margin: 15mm;
                            }
                        </style>
                    </head>
                    <body>
                        ${document.getElementById('printEquipmentForm').innerHTML}
                        <div class="no-print" style="text-align: center; margin-top: 20px;">
                            <button onclick="window.print()" style="padding: 10px 20px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer;">
                                <i class="fas fa-print"></i> Print Now
                            </button>
                            <button onclick="window.close()" style="padding: 10px 20px; background: #e74c3c; color: white; border: none; border-radius: 4px; cursor: pointer; margin-left: 10px;">
                                <i class="fas fa-times"></i> Close
                            </button>
                        </div>
                        <script>
                            // Auto-print when the window loads
                            setTimeout(function() {
                                window.print();
                            }, 500);
                        <\/script>
                    </body>
                </html>
            `);
            printWindow.document.close();
        }
    </script>