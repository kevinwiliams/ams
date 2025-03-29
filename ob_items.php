<?php
require_once 'db_connect.php';

class OBItem {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getAllItems() {
        $result = $this->conn->query("SELECT * FROM ob_items ORDER BY item_name ASC");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function addItem($item_name, $description) {
        $stmt = $this->conn->prepare("INSERT INTO ob_items (item_name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $item_name, $description);
        return $stmt->execute();
    }

    public function updateItem($item_id, $item_name, $description) {
        $stmt = $this->conn->prepare("UPDATE ob_items SET item_name = ?, description = ? WHERE item_id = ?");
        $stmt->bind_param("ssi", $item_name, $description, $item_id);
        return $stmt->execute();
    }

    public function deleteItem($item_id) {
        $stmt = $this->conn->prepare("DELETE FROM ob_items WHERE item_id = ?");
        $stmt->bind_param("i", $item_id);
        return $stmt->execute();
    }
}

$obItem = new OBItem($conn);

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $item_id = $_POST['item_id'] ?? '';
    $item_name = $_POST['item_name'] ?? '';
    $description = $_POST['description'] ?? '';

    if ($action === 'add') {
        if ($obItem->addItem($item_name, $description)) {
            echo json_encode(['status' => 'success', 'message' => 'Item added successfully!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add item.']);
        }
    } elseif ($action === 'update') {
        if ($obItem->updateItem($item_id, $item_name, $description)) {
            echo json_encode(['status' => 'success', 'message' => 'Item updated successfully!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update item.']);
        }
    } elseif ($action === 'delete') {
        if ($obItem->deleteItem($item_id)) {
            echo json_encode(['status' => 'success', 'message' => 'Item deleted successfully!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete item.']);
        }
    }
    exit;
}

$items = $obItem->getAllItems();
?>


    <div class="container">
    <div class="card card-outline card-primary">
        <div class="card-header d-flex">
            <h4 class="my-0 font-weight-normal flex-grow-1">Manage OB Items</h4>
            <div class="card-tools">
                <?php if ($login_role_id < 5): ?>
                    <button class="btn btn-sm btn-danger ml-2" data-toggle="modal" data-target="#addItemModal"><i class="fa fa-plus"></i> Add Item</button>

                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <table id="itemsTable" class="table table-hover small">
                <thead class="bg-light">
                    <tr>
                        <th>Item Name</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= $item['item_name'] ?></td>
                            <td><?= $item['description'] ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning editItem" 
                                        data-id="<?= $item['item_id'] ?>" 
                                        data-name="<?= htmlspecialchars($item['item_name']) ?>" 
                                        data-desc="<?= htmlspecialchars($item['description']) ?>">
                                        <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger deleteItem" data-id="<?= $item['item_id'] ?>"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    </div>

    <!-- Add Item Modal -->
    <div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addItemModalLabel">Add OB Item</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="addItemForm">
                        <input type="hidden" name="action" value="add">
                        <div class="form-group">
                            <label for="itemName">Item Name</label>
                            <input type="text" class="form-control" id="itemName" name="item_name" required>
                        </div>
                        <div class="form-group">
                            <label for="itemDescription">Description</label>
                            <textarea class="form-control" id="itemDescription" name="description" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Item Modal -->
    <div class="modal fade" id="editItemModal" tabindex="-1" aria-labelledby="editItemModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editItemModalLabel">Edit OB Item</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="editItemForm">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" id="editItemId" name="item_id">
                        <div class="form-group">
                            <label for="editItemName">Item Name</label>
                            <input type="text" class="form-control" id="editItemName" name="item_name" required>
                        </div>
                        <div class="form-group">
                            <label for="editItemDescription">Description</label>
                            <textarea class="form-control" id="editItemDescription" name="description" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            $('#itemsTable').DataTable();

            // Add Item
            $('#addItemForm').on('submit', function (e) {
                e.preventDefault();
                const formData = $(this).serialize();
                $.post('ob_items.php', formData, function (response) {
                    if (response.status === 'success') {
                        Swal.fire('Success', response.message, 'success');
                        $('#addItemModal').modal('hide');
                        location.reload();
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                }, 'json');
            });

            // Edit Item
            $(document).on('click', '.editItem', function () {
                const itemId = $(this).data('id');
                const itemName = $(this).data('name');
                const itemDesc = $(this).data('desc');
                $('#editItemId').val(itemId);
                $('#editItemName').val(itemName);
                $('#editItemDescription').val(itemDesc);
                $('#editItemModal').modal('show');
            });

            $('#editItemForm').on('submit', function (e) {
                e.preventDefault();
                const formData = $(this).serialize();
                $.post('ob_items.php', formData, function (response) {
                    if (response.status === 'success') {
                        Swal.fire('Success', response.message, 'success');
                        $('#editItemModal').modal('hide');
                        location.reload();
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                }, 'json');
            });

            // Delete Item
            $(document).on('click', '.deleteItem', function () {
                const itemId = $(this).data('id');
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'You will not be able to recover this item!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post('ob_items.php', { action: 'delete', item_id: itemId }, function (response) {
                            if (response.status === 'success') {
                                Swal.fire('Deleted!', response.message, 'success');
                                location.reload();
                            } else {
                                Swal.fire('Error', response.message, 'error');
                            }
                        }, 'json');
                    }
                });
            });
        });
    </script>
