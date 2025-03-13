<?php
include '../db_connect.php'; // Include the database connection file

header('Content-Type: application/json');

$requestMethod = $_SERVER['REQUEST_METHOD'];

if ($requestMethod === 'GET') {
    // Fetch messages from the database where status is 'pending'
    $sql = "SELECT id, recipient, text FROM sms_messages WHERE status = 'pending'";
    $result = $conn->query($sql);

    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }

    echo json_encode($messages);

} elseif ($requestMethod === 'POST') {
    // Update message status (sent/failed)
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['id'], $data['status'])) {
        $id = $data['id'];
        $status = $data['status'];

        // Update the status in the database
        $stmt = $conn->prepare("UPDATE sms_messages SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Status updated.']);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid request data.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
}
?>