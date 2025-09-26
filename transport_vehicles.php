<?php
require_once 'db_connect.php';

class TransportVehicle {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getAllVehicles() {
        $result = $this->conn->query("SELECT * FROM transport_vehicles WHERE is_deleted = 0 ORDER BY plate_number ASC");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function addVehicle($plate, $make_model, $year, $registration, $fitness, $insurance, $location, $last_maintenance, $category) {
        // Use NULLIF(..., '') so empty strings are converted to NULL for DATE columns
        $stmt = $this->conn->prepare("INSERT INTO transport_vehicles 
            (plate_number, make_model, model_year, registration_expiry, fitness_expiry, insurance_expiry, vehicle_location, last_maintenance, vehicle_category) 
            VALUES (?, ?, ?, NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''), ?, NULLIF(?, ''), ?)");
        $stmt->bind_param("ssissssss", $plate, $make_model, $year, $registration, $fitness, $insurance, $location, $last_maintenance, $category);
        return $stmt->execute();
    }
    public function updateVehicle($id, $plate, $make_model, $year, $registration, $fitness, $insurance, $location, $last_maintenance, $category) {
        // Use NULLIF(..., '') so empty strings are converted to NULL for DATE columns
        $stmt = $this->conn->prepare("UPDATE transport_vehicles 
            SET plate_number=?, make_model=?, model_year=?, registration_expiry=NULLIF(?, ''), fitness_expiry=NULLIF(?, ''), insurance_expiry=NULLIF(?, ''), vehicle_location=?, last_maintenance=NULLIF(?, ''), vehicle_category=? 
            WHERE id=?");
        $stmt->bind_param("ssissssssi", $plate, $make_model, $year, $registration, $fitness, $insurance, $location, $last_maintenance, $category, $id);
        return $stmt->execute();
    
    }

    public function deleteVehicle($id) {
        // soft delete
        $stmt = $this->conn->prepare("UPDATE transport_vehicles SET is_deleted = 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}

$vehicleObj = new TransportVehicle($conn);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? '';
    $plate = $_POST['plate_number'] ?? '';
    $make_model = $_POST['make_model'] ?? '';
    $year = $_POST['model_year'] ?? null;
    $registration = $_POST['registration_expiry'] ?? null;
    $fitness = $_POST['fitness_expiry'] ?? null;
    $insurance = $_POST['insurance_expiry'] ?? null;
    $location = $_POST['vehicle_location'] ?? '';
    $last_maintenance = $_POST['last_maintenance'] ?? null;
    $category = $_POST['vehicle_category'] ?? '';

    if ($action === 'add') {
        $ok = $vehicleObj->addVehicle($plate, $make_model, $year, $registration, $fitness, $insurance, $location, $last_maintenance, $category);
        echo json_encode(['status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Vehicle added!' : 'Failed to add.']);
    } elseif ($action === 'update') {
        $ok = $vehicleObj->updateVehicle($id, $plate, $make_model, $year, $registration, $fitness, $insurance, $location, $last_maintenance, $category);
        echo json_encode(['status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Vehicle updated!' : 'Failed to update.']);
    } elseif ($action === 'delete') {
        $ok = $vehicleObj->deleteVehicle($id);
        echo json_encode(['status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Vehicle deleted!' : 'Failed to delete.']);
    }
    exit;
}

// Fetch data for display
$vehicles = $vehicleObj->getAllVehicles();

// Helper: calculate "days ago"
function daysAgo($date) {
    if (!$date) return '';
    $d1 = new DateTime($date);
    $d2 = new DateTime();
    $diff = $d2->diff($d1)->days;
    return $d1->format("Y-m-d") . " (" . $diff . " days ago)";
}
?>


<div class="container">
    <div class="card card-outline card-primary">
        <div class="card-header d-flex">
            <h4 class="my-0 font-weight-normal flex-grow-1">Manage Transport Vehicles</h4>
            <div class="card-tools">
                <button class="btn btn-sm btn-danger ml-2" data-toggle="modal" data-target="#addVehicleModal">
                    <i class="fa fa-plus"></i> Add Vehicle
                </button>
            </div>
        </div>
        <div class="card-body">
            <table id="vehiclesTable" class="table table-hover small">
                <thead class="bg-light">
                    <tr>
                        <th>Plate</th>
                        <th>Make/Model</th>
                        <th>Year</th>
                        <th>Registration Expiry</th>
                        <th>Fitness Expiry</th>
                        <th>Insurance Expiry</th>
                        <th>Location</th>
                        <th>Last Maintenance</th>
                        <th>Category</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vehicles as $v): ?>
                        <?php
                            // compute days until each expiry (signed days: negative = past due)
                            $now = new DateTime();
                            $threshold = 30; // days to consider "expiring soon"

                            $regDays = $v['registration_expiry'] ? (int)((new DateTime($v['registration_expiry']))->diff($now)->format('%r%a')) : null;
                            $fitDays = $v['fitness_expiry'] ? (int)((new DateTime($v['fitness_expiry']))->diff($now)->format('%r%a')) : null;
                            $insDays = $v['insurance_expiry'] ? (int)((new DateTime($v['insurance_expiry']))->diff($now)->format('%r%a')) : null;

                            // determine highest severity among expiry fields: danger > warning > normal
                            $severity = '';
                            foreach ([$regDays, $fitDays, $insDays] as $d) {
                                if ($d === null) continue;
                                if ($d < 0) { $severity = 'danger'; break; }
                                if ($d <= $threshold && $severity !== 'danger') $severity = 'warning';
                            }

                            // map to a row class (Bootstrap + gentle custom accents)
                            $rowClass = $severity === 'danger' ? 'row-expiry-danger' : ($severity === 'warning' ? 'row-expiry-warning' : '');
                            // Print shared styles once to keep presentation professional and subtle
                            if (!isset($GLOBALS['expiry_styles_printed'])) {
                                echo '<style>
                                    /* Row background accents */
                                    .row-expiry-warning { background-color: #fff7e6 !important; } /* soft amber */
                                    .row-expiry-danger  { background-color: #fff0f0 !important; } /* soft red/pink */

                                    /* Emphasize expiry columns (registration / fitness / insurance are cols 4/5/6) */
                                    .row-expiry-warning td:nth-child(4),
                                    .row-expiry-warning td:nth-child(5),
                                    .row-expiry-warning td:nth-child(6) {
                                        color: #8a6d3b;
                                        font-weight: 600;
                                    }
                                    .row-expiry-danger td:nth-child(4),
                                    .row-expiry-danger td:nth-child(5),
                                    .row-expiry-danger td:nth-child(6) {
                                        color: #7a1f1f;
                                        font-weight: 700;
                                    }

                                    /* Subtle left border to call attention */
                                    .row-expiry-warning { border-left: 4px solid #ffd166; }
                                    .row-expiry-danger  { border-left: 4px solid #ff6b6b; }
                                    </style>';
                                $GLOBALS['expiry_styles_printed'] = true;
                            }
                        ?>
                        <tr class="<?= $rowClass ?>">
                            <td><?= $v['plate_number'] ?></td>
                            <td><?= htmlspecialchars($v['make_model']) ?></td>
                            <td><?= $v['model_year'] ?></td>
                            <td><?= $v['registration_expiry'] ? date('Y-m-d', strtotime($v['registration_expiry'])) : '' ?></td>
                            <td><?= $v['fitness_expiry'] ? date('Y-m-d', strtotime($v['fitness_expiry'])) : '' ?></td>
                            <td><?= $v['insurance_expiry'] ? date('Y-m-d', strtotime($v['insurance_expiry'])) : '' ?></td>
                            <td><?= $v['vehicle_location'] ?></td>
                            <td><?= daysAgo($v['last_maintenance']) ?></td>
                            <td><?= $v['vehicle_category'] ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning editVehicle"
                                    data-id="<?= $v['id'] ?>"
                                    data-plate="<?= $v['plate_number'] ?>"
                                    data-make="<?= htmlspecialchars($v['make_model']) ?>"
                                    data-year="<?= $v['model_year'] ?>"
                                    data-reg="<?= $v['registration_expiry'] ? date('Y-m-d', strtotime($v['registration_expiry'])) : '' ?>"
                                    data-fit="<?= $v['fitness_expiry'] ? date('Y-m-d', strtotime($v['fitness_expiry'])) : '' ?>"
                                    data-ins="<?= $v['insurance_expiry'] ? date('Y-m-d', strtotime($v['insurance_expiry'])) : '' ?>"
                                    data-loc="<?= $v['vehicle_location'] ?>"
                                    data-maint="<?= $v['last_maintenance'] ? date('Y-m-d', strtotime($v['last_maintenance'])) : '' ?>"
                                    data-cat="<?= $v['vehicle_category'] ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger deleteVehicle" data-id="<?= $v['id'] ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Vehicle Modal -->
<div class="modal fade" id="addVehicleModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="addVehicleForm">
        <div class="modal-header"><h5>Add Vehicle</h5></div>
        <div class="modal-body">
            <input type="hidden" name="action" value="add">
            <div class="form-group"><label>Plate</label><input class="form-control" name="plate_number" required></div>
            <div class="form-group"><label>Make/Model</label><input class="form-control" name="make_model" required></div>
            <div class="form-group"><label>Year</label><input type="number" class="form-control" name="model_year"></div>
            <div class="form-group"><label>Registration Expiry</label><input type="date" class="form-control" name="registration_expiry"></div>
            <div class="form-group"><label>Fitness Expiry</label><input type="date" class="form-control" name="fitness_expiry"></div>
            <div class="form-group"><label>Insurance Expiry</label><input type="date" class="form-control" name="insurance_expiry"></div>
            <div class="form-group"><label>Location</label><input class="form-control" name="vehicle_location"></div>
            <div class="form-group"><label>Last Maintenance</label><input type="date" class="form-control" name="last_maintenance"></div>
            <div class="form-group"><label>Category</label><input class="form-control" name="vehicle_category"></div>
        </div>
        <div class="modal-footer"><button class="btn btn-primary">Save</button></div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Vehicle Modal -->
<div class="modal fade" id="editVehicleModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="editVehicleForm">
        <div class="modal-header"><h5>Edit Vehicle</h5></div>
        <div class="modal-body">
            <input type="hidden" name="action" value="update">
            <input type="hidden" id="editVehicleId" name="id">
            <div class="form-group"><label>Plate</label><input class="form-control" id="editPlate" name="plate_number"></div>
            <div class="form-group"><label>Make/Model</label><input class="form-control" id="editMake" name="make_model"></div>
            <div class="form-group"><label>Year</label><input type="number" class="form-control" id="editYear" name="model_year"></div>
            <div class="form-group"><label>Registration Expiry</label><input type="date" class="form-control" id="editReg" name="registration_expiry"></div>
            <div class="form-group"><label>Fitness Expiry</label><input type="date" class="form-control" id="editFit" name="fitness_expiry"></div>
            <div class="form-group"><label>Insurance Expiry</label><input type="date" class="form-control" id="editIns" name="insurance_expiry"></div>
            <div class="form-group"><label>Location</label><input class="form-control" id="editLoc" name="vehicle_location"></div>
            <div class="form-group"><label>Last Maintenance</label><input type="date" class="form-control" id="editMaint" name="last_maintenance"></div>
            <div class="form-group"><label>Category</label><input class="form-control" id="editCat" name="vehicle_category"></div>
        </div>
        <div class="modal-footer"><button class="btn btn-primary">Update</button></div>
      </form>
    </div>
  </div>
</div>

<script>
$(function(){
    $('#vehiclesTable').DataTable();

    // Add
    $('#addVehicleForm').on('submit', function(e){
        e.preventDefault();
        $.post('transport_vehicles.php', $(this).serialize(), function(resp){
            Swal.fire(resp.status, resp.message, resp.status);
            if(resp.status === 'success') location.reload();
        }, 'json');
    });

    // Edit
    $(document).on('click','.editVehicle',function(){
        $('#editVehicleId').val($(this).data('id'));
        $('#editPlate').val($(this).data('plate'));
        $('#editMake').val($(this).data('make'));
        $('#editYear').val($(this).data('year'));
        $('#editReg').val($(this).data('reg'));
        $('#editFit').val($(this).data('fit'));
        $('#editIns').val($(this).data('ins'));
        $('#editLoc').val($(this).data('loc'));
        $('#editMaint').val($(this).data('maint'));
        $('#editCat').val($(this).data('cat'));
        $('#editVehicleModal').modal('show');
    });
    $('#editVehicleForm').on('submit', function(e){
        e.preventDefault();
        $.post('transport_vehicles.php', $(this).serialize(), function(resp){
            Swal.fire(resp.status, resp.message, resp.status);
            if(resp.status === 'success') location.reload();
        }, 'json');
    });

    // Delete
    $(document).on('click','.deleteVehicle',function(){
        let id = $(this).data('id');
        Swal.fire({title:'Delete vehicle?',icon:'warning',showCancelButton:true})
        .then((res)=>{
            if(res.isConfirmed){
                $.post('transport_vehicles.php',{action:'delete',id:id},function(resp){
                    Swal.fire(resp.status, resp.message, resp.status);
                    if(resp.status === 'success') location.reload();
                },'json');
            }
        });
    });
});
</script>
