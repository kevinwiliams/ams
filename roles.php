<?php
    // Include the database connection
    require_once 'db_connect.php';

    // Handle CRUD operations
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $role_id = $_POST['role_id'] ?? '';
        $role_name = $_POST['role_name'] ?? '';

        if ($action === 'add') {
            // Add new role
            $stmt = $conn->prepare("INSERT INTO roles (role_name) VALUES (?)");
            $stmt->bind_param("s", $role_name);
            if ($stmt->execute()) {
                echo '<script>alert_toast("Role added successfully!", "success");</script>';
            } else {
                echo '<script>alert_toast("Failed to add role.", "error");</script>';
            }
            $stmt->close();
        } elseif ($action === 'update') {
            // Update role
            $stmt = $conn->prepare("UPDATE roles SET role_name = ? WHERE role_id = ?");
            $stmt->bind_param("si", $role_name, $role_id);
            if ($stmt->execute()) {
                echo '<script>alert_toast("Role updated successfully!", "success");</script>';
            } else {
                echo '<script>alert_toast("Failed to update role.", "error");</script>';
            }
            $stmt->close();
        } elseif ($action === 'delete') {
            // Delete role
            $stmt = $conn->prepare("DELETE FROM roles WHERE role_id = ?");
            $stmt->bind_param("i", $role_id);
            if ($stmt->execute()) {
                echo '<script>alert_toast("Role deleted successfully!", "success");</script>';
            } else {
                echo '<script>alert_toast("Failed to delete role.", "error");</script>';
            }
            $stmt->close();
        }
    }

    // Fetch all roles for DataTable
    $roles = [];
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $result = $conn->query("SELECT * FROM roles");
        while ($row = $result->fetch_assoc()) {
            $roles[] = $row;
        }
    }
    ?>
<div class="container">
    <div class="card card-outline card-primary">
        <div class="card-header d-flex">
            <h4 class="my-0 font-weight-normal flex-grow-1">Manage Roles</h4>
            <div class="card-tools">
                <?php if ($login_role_id < 5): ?>
                    <button class="btn btn-sm btn-danger ml-2" data-toggle="modal" data-target="#addRoleModal"><i class="fa fa-plus"></i> Add Role</button>

                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <table id="rolesTable" class="table table-bordered small">
                <thead>
                    <tr>
                    <th>Actions</th>
                        <th>Role Name</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($roles as $role): ?>
                        <tr>
                            <td>
                                <button class="btn btn-sm btn-warning editRole" data-id="<?= $role['role_id'] ?>" data-name="<?= $role['role_name'] ?>"><i class="fas fa-edit"></i></button>
                                <!-- <button class="btn btn-sm btn-danger deleteRole" data-id="<?= $role['role_id'] ?>"><i class="fas fa-trash"></i></button> -->
                            </td>
                            <td><?= $role['role_name'] ?></td>
                            
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>    
    

    <!-- Add Role Modal -->
    <div class="modal fade" id="addRoleModal" tabindex="-1" aria-labelledby="addRoleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addRoleModalLabel">Add Role</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="addRoleForm" method="POST" action="">
                        <input type="hidden" name="action" value="add">
                        <div class="form-group">
                            <label for="roleName">Role Name</label>
                            <input type="text" class="form-control" id="roleName" name="role_name" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Role Modal -->
    <div class="modal fade" id="editRoleModal" tabindex="-1" aria-labelledby="editRoleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editRoleModalLabel">Edit Role</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="editRoleForm" method="POST" action="">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" id="editRoleId" name="role_id">
                        <div class="form-group">
                            <label for="editRoleName">Role Name</label>
                            <input type="text" class="form-control" id="editRoleName" name="role_name" required>
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
            $('#rolesTable').DataTable();

            // Add Role
            $('#addRoleForm').on('submit', function (e) {
                e.preventDefault();
                const formData = $(this).serialize();
                $.post('', formData, function (response) {
                    location.reload(); // Reload the page to reflect changes
                });
            });

            // Edit Role
            $(document).on('click', '.editRole', function () {
                const roleId = $(this).data('id');
                const roleName = $(this).data('name');
                $('#editRoleId').val(roleId);
                $('#editRoleName').val(roleName);
                $('#editRoleModal').modal('show');
            });

            $('#editRoleForm').on('submit', function (e) {
                e.preventDefault();
                const formData = $(this).serialize();
                $.post('', formData, function (response) {
                    location.reload(); // Reload the page to reflect changes
                });
            });

            // Delete Role
            $(document).on('click', '.deleteRole', function () {
                const roleId = $(this).data('id');
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'You will not be able to recover this role!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post('', { action: 'delete', role_id: roleId }, function (response) {
                            location.reload(); // Reload the page to reflect changes
                        });
                    }
                });
            });
        });
    </script>