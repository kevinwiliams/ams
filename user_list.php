<?php 
include('db_connect.php');
// session_start();  // Make sure session is started

// Initialize session variables
$login_role_id = $_SESSION['role_id'] ?? 5; // Default to 5 (Reporter)
$login_empid = $_SESSION['login_id'] ?? 0;
$login_empid = intval($login_empid);
$sessionempid = $_SESSION['login_empid'] ?? 0; // Ensure proper initialization
$radio_staff = $_SESSION['login_sb_staff'] == 1 ? true : false;

$user_role = $_SESSION['role_name'];
$create_roles = ['Manager', 'ITAdmin', 'Editor', 'Dept Admin','Op Manager' ];


// Fetch user details
$user_details_query = $conn->prepare("SELECT firstname, lastname FROM users WHERE id = ?");
$user_details_query->bind_param("i", $login_empid);
$user_details_query->execute();
$user_details_result = $user_details_query->get_result();

if ($user_details_result->num_rows > 0) {
    $user_details = $user_details_result->fetch_assoc();
    $firstname = $user_details['firstname'];
    $lastname = $user_details['lastname'];
} else {
    $firstname = 'Admin';
    $lastname = '';
}

"";

$sbQry = ($radio_staff) ? " WHERE u.sb_staff = 1 " : "";

// Fetch user data (without the `is_deleted` condition)
$query = "SELECT u.id, u.empid, CONCAT(u.firstname, ' ', u.lastname) AS name, u.alias, u.email, u.address, u.contact_number, r.role_name, u.preferred_channel
          FROM users u 
          LEFT JOIN roles r ON u.role_id = r.role_id
          $sbQry
          ORDER BY u.firstname, u.lastname";

$user_list = $conn->query($query);

// Handle potential errors
if (!$user_list) {
    die("Error executing query: " . $conn->error);
}
?>

<!-- HTML for displaying the user list -->
<div class="col-lg-12">
<input type="hidden" name="deleted_by" value="<?php echo htmlspecialchars($login_empid); ?>" />
    <div class="card card-outline card-success">
        <div class="card-header">
            <h3 class="card-title">User List</h3>
            <div class="card-tools">
                <?php if (in_array($user_role, $create_roles)): // Only allow users with role less than 5 to add new users ?>
                    <a class="btn btn-block btn-sm btn-danger" href="./index.php?page=user">
                        <i class="fa fa-plus"></i> Add New User
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- User List -->
        <div class="card-body">
            <table class="table table-hover small" id="user-list">
                <thead class="bg-light">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <!-- <th>Address</th> -->
                        <th>Contact Number</th>
                        <th>Role</th>
                        <th>Preferred Channel</th>
                        <?php if (in_array($user_role, $create_roles)) { ?><th>Actions</th> <?php } ?>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $user_list->fetch_assoc()): ?>
                    <tr>
                 
                    <td>
                        <a href="index.php?page=view_user&id=<?php echo htmlspecialchars($row['id']); ?>" class="text-decoration-none">  
                            <?php 
                                echo ucwords(htmlspecialchars($row['name'])); 
                                if (!empty($row['alias'])) {
                                    echo " (" . htmlspecialchars($row['alias']) . ")";
                                }
                            ?>
                        </a>
                    </td>
                    <td><?php echo htmlspecialchars($row['email'] ?? 'N/A'); ?> </td>
                    <!-- <td><?php echo htmlspecialchars($row['address'] ?? 'N/A'); ?></td> -->
                    <td><?php echo htmlspecialchars($row['contact_number'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($row['role_name'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($row['preferred_channel'] ?? 'N/A'); ?> </td>
                    <?php if (in_array($user_role, $create_roles)) { ?>
                    <td>
                        <a data-id="<?php echo htmlspecialchars($row['id']); ?>" href="#" class="btn text-info edit-user">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a data-id="<?php echo htmlspecialchars($row['id']); ?>" href="#" class="btn text-danger del-user">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                    </td>
                    <?php } ?>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    $(document).ready(function(){
        $('#user-list').DataTable({
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ]
        }); // Initialize DataTables

        // Edit user functionality
        $('#user-list').on('click', '.edit-user', function () {
        // $('.edit-user').on('click', function() {
            var userId = $(this).data('id');
            location.href = "index.php?page=user&id=" + userId; // Redirect to edit user page
        });

        // Delete user functionality
        $('#user-list').on('click', '.del-user', function (e) {
        // $('.del-user').on('click', function(e) {
            e.preventDefault();
            var userId = $(this).data('id');
            if (confirm("Are you sure you want to delete this user?")) {
                $.ajax({
                    url: 'delete_user.php',
                    type: 'POST',
                    data: { id: userId },
                    success: function(response) {
                        if (response === 'success') {
                            alert('User deleted successfully');
                            location.reload(); // Reload the page after deletion
                        } else {
                            alert('Error deleting user');
                        }
                    }
                });
            }
        });
    });
</script>

