<?php
    // Include the database connection
    require_once 'db_connect.php';

    // Handle CRUD operations
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $id = $_POST['id'] ?? '';
        $plate_number = $_POST['plate_number'] ?? '';
        $make_model = $_POST['make_model'] ?? '';

        if ($action === 'add') {
            // Add new role
            $stmt = $conn->prepare("INSERT INTO transport_vehicles (plate_number, make_model) VALUES (?, ?)");
            $stmt->bind_param("ss", $plate_number, $make_model);
            if ($stmt->execute()) {
                echo '<script>alert_toast("Vehicle added successfully!", "success");</script>';
            } else {
                echo '<script>alert_toast("Failed to add vehicle.", "error");</script>';
            }
            $stmt->close();
        } elseif ($action === 'update') {
            // Update role
            $stmt = $conn->prepare("UPDATE transport_vehicles SET plate_number = ?, make_model = ? WHERE id = ?");
            $stmt->bind_param("ssi", $plate_number, $make_model, $id);
            if ($stmt->execute()) {
                echo '<script>alert("Vehicle updated successfully!");</script>';
            } else {
                echo '<script>alert_toast("Failed to update ehicle.", "error");</script>';
            }
            $stmt->close();
        } elseif ($action === 'delete') {
            // Delete role
            $stmt = $conn->prepare("UPDATE transport_vehicles SET is_deleted = 1 WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                echo '<script>alert_toast("Vehicle deleted successfully!", "success");</script>';
            } else {
                echo '<script>alert_toast("Failed to delete vehicle.", "error");</script>';
            }
            $stmt->close();
        }
    }

    // Fetch all roles for DataTable
    $vehicles = [];
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $result = $conn->query("SELECT * FROM transport_vehicles WHERE is_deleted = 0");
        while ($row = $result->fetch_assoc()) {
            $vehicles[] = $row;
        }
    }
    ?>
<div class="container">
    <div class="card card-outline card-primary">
        <div class="card-header d-flex">
            <h4 class="my-0 font-weight-normal flex-grow-1">Manage Vehicles</h4>
            <div class="card-tools">
                <?php if ($login_role_id < 5): ?>
                    <button class="btn btn-sm btn-danger ml-2" data-toggle="modal" data-target="#addVehicleModal"><i class="fa fa-plus"></i> Add Vehicle</button>

                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
        <table id="vehiclesTable" class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Plate Number</th>
                    <th>Make & Model</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vehicles as $vehicle): ?>
                    <tr>
                        <td><?= $vehicle['id'] ?></td>
                        <td><?= $vehicle['plate_number'] ?></td>
                        <td><?= $vehicle['make_model'] ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning editVehicle" data-id="<?= $vehicle['id'] ?>" data-plate="<?= $vehicle['plate_number'] ?>" data-model="<?= $vehicle['make_model'] ?>">Edit</button>
                            <button class="btn btn-sm btn-danger deleteVehicle" data-id="<?= $vehicle['id'] ?>">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>    
    

    <!-- Add Vehicle Modal -->
    <div class="modal fade" id="addVehicleModal" tabindex="-1" aria-labelledby="addVehicleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addVehicleModalLabel">Add Vehicle</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="addVehicleForm" method="POST" action="">
                        <input type="hidden" name="action" value="add">
                        <div class="form-group">
                            <label for="plateNumber">Plate Number</label>
                            <input type="text" class="form-control" id="plateNumber" name="plate_number" required>
                        </div>
                        <div class="form-group">
                            <label for="makeModel">Make & Model</label>
                            <input type="text" class="form-control" id="makeModel" name="make_model" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Vehicle Modal -->
    <div class="modal fade" id="editVehicleModal" tabindex="-1" aria-labelledby="editVehicleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editVehicleModalLabel">Edit Vehicle</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="editVehicleForm" method="POST" action="">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" id="editVehicleId" name="id">
                        <div class="form-group">
                            <label for="editPlateNumber">Plate Number</label>
                            <input type="text" class="form-control" id="editPlateNumber" name="plate_number" required>
                        </div>
                        <div class="form-group">
                            <label for="editMakeModel">Make & Model</label>
                            <input type="text" class="form-control" id="editMakeModel" name="make_model" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </form>
                </div>
            </div>
        </div>
    </div>



<!-- Custom Script -->
<script>
        $(document).ready(function () {
            // Initialize DataTable
            $('#vehiclesTable').DataTable();

            // Add Vehicle
            $('#addVehicleForm').on('submit', function (e) {
                e.preventDefault();
                const formData = $(this).serialize();
                $.post('', formData, function (response) {
                    console.log(response);
                    alert_toast("Vehicle added successfully!", "success");

                    setTimeout(() => {
                        location.reload();
                    }, 2500);
                });
            });

            // Edit Vehicle
            $(document).on('click', '.editVehicle', function () {
                const vehicleId = $(this).data('id');
                const plateNumber = $(this).data('plate');
                const makeModel = $(this).data('model');
                $('#editVehicleId').val(vehicleId);
                $('#editPlateNumber').val(plateNumber);
                $('#editMakeModel').val(makeModel);
                $('#editVehicleModal').modal('show');
            });

            $('#editVehicleForm').on('submit', function (e) {
                e.preventDefault();
                const formData = $(this).serialize();
                $.post('', formData, function (response) {
                        $('#editVehicleModal').modal('hide');
                        alert_toast("Vehicle updated successfully!", "success");

                        setTimeout(() => {
                            location.reload();
                    }, 2500);
                });
            });

            // Delete Vehicle
            $(document).on('click', '.deleteVehicle', function () {
                const vehicleId = $(this).data('id');
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'You will not be able to recover this vehicle!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post('', { action: 'delete', id: vehicleId }, function (response) {
                                location.reload(); // Reload the page to reflect changes
                        });
                    }
                });
            });
        });
    </script>