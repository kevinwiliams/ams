<?php
include('db_connect.php');

// Ensure proper session
session_start();

// Check if user is authorized
if ($_SESSION['role_id'] >= 5) {
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
