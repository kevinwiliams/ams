<?php
// Include the database connection
require_once 'db_connect.php';

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? '';
    $show_name = $_POST['show_name'] ?? '';
    $station = $_POST['station'] ?? '';
    $is_exclusive = isset($_POST['is_exclusive']) ? 1 : 0;

    if ($action === 'add') {
        // Add new show
        $stmt = $conn->prepare("INSERT INTO station_shows (show_name, station, is_exclusive) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $show_name, $station, $is_exclusive);
        if ($stmt->execute()) {
            echo '<script>Swal.fire("Success", "Show added successfully!", "success");</script>';
        } else {
            echo '<script>Swal.fire("Error", "Failed to add show.", "error");</script>';
        }
        $stmt->close();
    } elseif ($action === 'update') {
        // Update show
        $stmt = $conn->prepare("UPDATE station_shows SET show_name = ?, station = ?, is_exclusive = ? WHERE id = ?");
        $stmt->bind_param("ssii", $show_name, $station, $is_exclusive, $id);
        if ($stmt->execute()) {
            echo '<script>Swal.fire("Success", "Show updated successfully!", "success");</script>';
        } else {
            echo '<script>Swal.fire("Error", "Failed to update show.", "error");</script>';
        }
        $stmt->close();
    } elseif ($action === 'delete') {
        // Delete show
        $stmt = $conn->prepare("DELETE FROM station_shows WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo '<script>Swal.fire("Success", "Show deleted successfully!", "success");</script>';
        } else {
            echo '<script>Swal.fire("Error", "Failed to delete show.", "error");</script>';
        }
        $stmt->close();
    }
}

// Fetch all shows for display
$result = $conn->query("SELECT * FROM station_shows");
$shows = [];
while ($row = $result->fetch_assoc()) {
    $shows[] = $row;
}
?>

    <div class="container">
        <div class="card card-outline card-primary">
            <div class="card-header d-flex">
                <h4 class="my-0 font-weight-normal flex-grow-1">Manage Station Shows</h4>
                <div class="card-tools">
                    <?php if ($login_role_id < 5): ?>
                        <button class="btn btn-sm btn-danger ml-2" data-toggle="modal" data-target="#addShowModal"><i class="fa fa-plus"></i> Add Show</button>

                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <table id="showsTable" class="table table-bordered small">
                    <thead>
                        <tr>
                            <!-- <th>ID</th> -->
                            <th>Show Name</th>
                            <th>Station</th>
                            <th>Exclusive</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($shows as $show): ?>
                            <tr>
                                <!-- <td><?= $show['id'] ?></td> -->
                                <td><?= $show['show_name'] ?></td>
                                <td><?= $show['station'] ?></td>
                                <td><?= $show['is_exclusive'] ? 'Yes' : 'No' ?></td>
                                <td>
                                    <button class="btn btn-sm btn-warning editShow" data-id="<?= $show['id'] ?>" data-name="<?= $show['show_name'] ?>" data-station="<?= $show['station'] ?>" data-exclusive="<?= $show['is_exclusive'] ?>">Edit</button>
                                    <!-- <button class="btn btn-sm btn-danger deleteShow" data-id="<?= $show['id'] ?>">Delete</button> -->
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Add Show Modal -->
    <div class="modal fade" id="addShowModal" tabindex="-1" aria-labelledby="addShowModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addShowModalLabel">Add Show</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="addShowForm" method="POST" action="">
                        <input type="hidden" name="action" value="add">
                        <div class="form-group">
                            <label for="showName">Show Name</label>
                            <input type="text" class="form-control" id="showName" name="show_name" required>
                        </div>
                        <div class="form-group">
                            <label for="station">Station</label>
                            <select class="form-control" id="station" name="station" required>
                                <option value="EDGE">EDGE</option>
                                <option value="FYAH">FYAH</option>
                            </select>
                        </div>
                        <div class="form-group form-check">
                            <input type="checkbox" class="form-check-input" id="isExclusive" name="is_exclusive">
                            <label class="form-check-label" for="isExclusive">Exclusive</label>
                        </div>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Show Modal -->
    <div class="modal fade" id="editShowModal" tabindex="-1" aria-labelledby="editShowModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editShowModalLabel">Edit Show</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="editShowForm" method="POST" action="">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" id="editShowId" name="id">
                        <div class="form-group">
                            <label for="editShowName">Show Name</label>
                            <input type="text" class="form-control" id="editShowName" name="show_name" required>
                        </div>
                        <div class="form-group">
                            <label for="editStation">Station</label>
                            <select class="form-control" id="editStation" name="station" required>
                                <option value="EDGE">EDGE</option>
                                <option value="FYAH">FYAH</option>
                            </select>
                        </div>
                        <div class="form-group form-check">
                            <input type="checkbox" class="form-check-input" id="editIsExclusive" name="is_exclusive">
                            <label class="form-check-label" for="editIsExclusive">Exclusive</label>
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
            $('#showsTable').DataTable();

            // Edit Show
            $(document).on('click', '.editShow', function () {
                const showId = $(this).data('id');
                const showName = $(this).data('name');
                const station = $(this).data('station');
                const isExclusive = $(this).data('exclusive');
                $('#editShowId').val(showId);
                $('#editShowName').val(showName);
                $('#editStation').val(station);
                $('#editIsExclusive').prop('checked', isExclusive === 1);
                $('#editShowModal').modal('show');
            });

            // Delete Show
            $(document).on('click', '.deleteShow', function () {
                const showId = $(this).data('id');
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'You will not be able to recover this show!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Submit delete form
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '';
                        const actionInput = document.createElement('input');
                        actionInput.type = 'hidden';
                        actionInput.name = 'action';
                        actionInput.value = 'delete';
                        const idInput = document.createElement('input');
                        idInput.type = 'hidden';
                        idInput.name = 'id';
                        idInput.value = showId;
                        form.appendChild(actionInput);
                        form.appendChild(idInput);
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        });
    </script>
