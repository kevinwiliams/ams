<?php
include '../db_connect.php'; // Include the database connection file

header('Content-Type: application/json');

$requestMethod = $_SERVER['REQUEST_METHOD'];

if ($requestMethod === 'GET') {
    // Fetch messages from the database where status is 'pending'
    $sql = "SELECT id, recipient, text, updated_at, status FROM sms_messages order by updated_at desc";
    $result = $conn->query($sql);

    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }

    echo json_encode($messages);

} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
}
?>