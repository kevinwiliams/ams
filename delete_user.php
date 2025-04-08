<?php
include('db_connect.php');

// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_role = $_SESSION['role_name'];
$create_roles = ['Manager', 'ITAdmin', 'Editor', 'Dept Admin','Op Manager'];

// Check if user is authorized
if (!in_array($user_role, $create_roles)) {
    // User is not authorized to delete
    echo 'Permission denied';
    exit;
}

if (isset($_POST['id'])) {
    $user_id = intval($_POST['id']);

    // Prepare the delete query
    $delete_query = $conn->prepare("UPDATE users SET is_deleted = 1 WHERE id = ?");
    $delete_query->bind_param("i", $user_id);

    if ($delete_query->execute()) {
        echo 'success';
    } else {
        echo 'error';
    }
} else {
    echo 'No ID provided';
}
?>
