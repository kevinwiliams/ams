<?php
include 'db_connect.php';

// Turn on error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if ID is provided and valid
$id = isset($_GET['id']) ? intval($_GET['id']) : 0; // Ensure ID is an integer

if ($id > 0) {
    // Prepare and execute the query to fetch assignment details
    $stmt = $conn->prepare("SELECT 
                            a.*,
                            -- Get comma-separated team member names
                            (SELECT GROUP_CONCAT(CONCAT(u.firstname, ' ', u.lastname, '(', r.role_name ,')') SEPARATOR ', ')
                            FROM users u
                            LEFT JOIN roles r
                            ON u.role_id = r.role_id
                            WHERE FIND_IN_SET(u.empid, REPLACE(a.team_members, ' ', '')) > 0
                            ) AS team_member_names,
                            
                            -- Get assigned_by user name
                            CONCAT(assigned_by_user.firstname, ' ', assigned_by_user.lastname) AS assigned_by_name,
                            -- Get edited_by user name
                            CONCAT(edited_by_user.firstname, ' ', edited_by_user.lastname) AS edited_by_name,
                            -- Get approved_by user name
                            CONCAT(approved_by_user.firstname, ' ', approved_by_user.lastname) AS approved_by_name
                            FROM 
                                assignment_list a
                            -- Join to get assigned_by user details
                            LEFT JOIN users assigned_by_user 
                                ON a.assigned_by = assigned_by_user.id
                            -- Join to get edited_by user details
                            LEFT JOIN users edited_by_user 
                                ON a.edited_by = edited_by_user.id
                            -- Join to get approved_by user details
                            LEFT JOIN users approved_by_user 
                                ON a.approved_by = approved_by_user.id
                             WHERE a.id = ?");
    if ($stmt === false) {
        die('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $assignment = $result->fetch_assoc();
            foreach ($assignment as $k => $v) {
                $$k = $v;
            }
        } else {
            die('Assignment not found.');
        }
    } else {
        die('Query failed: ' . $stmt->error);
    }

    $stmt->close();
} else {
    die('Invalid assignment ID.');
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignment Details</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header text-center bg-dark text-white">
                <h3>Assignment Details</h3>
            </div>
            <div class="card-body">
                <!-- Two columns for the assignment details -->
                <div class="row">
                    <!-- Column 1 -->
                    <div class="col-md-6">
                        <div class="row mb-3">
                            <div class="col-4"><strong>Destination:</strong></div>
                            <div class="col-8"><?php echo htmlspecialchars($location ?? 'N/A'); ?></div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-4"><strong>Assignment Date:</strong></div>
                            <div class="col-8"><?php echo htmlspecialchars($assignment_date ?? 'N/A'); ?></div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-4"><strong>Purpose:</strong></div>
                            <div class="col-8"><?php echo htmlspecialchars($title ?? 'N/A'); ?></div>
                        </div>

                        <?php if ($depart_time) { ?>
                            <div class="row mb-3">
                                <div class="col-4"><strong>Expected Depart:</strong></div>
                                <div class="col-8"><?php echo htmlspecialchars($depart_time ?? 'N/A'); ?></div>
                            </div>
                        <?php } ?>

                        <?php if ($end_time) { ?>
                            <div class="row mb-3">
                                <div class="col-4"><strong>Expected Return:</strong></div>
                                <div class="col-8"><?php echo htmlspecialchars($end_time ?? 'N/A'); ?></div>
                            </div>
                        <?php } ?>
                    </div>

                    <!-- Column 2 -->
                    <div class="col-md-6">
                        <div class="row mb-3">
                            <div class="col-4"><strong>Requested By:</strong></div>
                            <div class="col-8"><?php echo htmlspecialchars($team_member_names ?? 'N/A'); ?></div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-4"><strong>Date:</strong></div>
                            <div class="col-8"><?php echo date('Y-m-d');  ?></div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-4"><strong>Approved by:</strong></div>
                            <div class="col-8"><?php echo htmlspecialchars($approved_by ?? 'N/A'); ?></div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-4"><strong>Driver:</strong></div>
                            <div class="col-8"><?php echo htmlspecialchars($options[$drop_option] ?? 'No driver assigned'); ?></div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-4"><strong>Vehicle Licence:</strong></div>
                            <div class="col-8"><?php echo htmlspecialchars($options[$drop_option] ?? 'N/A'); ?></div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-4"><strong>Dispatcher:</strong></div>
                            <div class="col-8"><?php echo htmlspecialchars($options[$drop_option] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Button to print or show the details -->
                <div class="text-start small">
                    <!-- Print Button on the Right -->
                    <button class="btn btn-success mb-3 float-right" onclick="window.print()">Print Transport Form</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
