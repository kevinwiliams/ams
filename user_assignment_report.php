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

// Check if the user ID is set in the URL
if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];

    // Query to fetch assignments for the user
    $query = "
        SELECT 
            a.id, 
            a.title, 
            a.assignment_date, 
            a.start_time,
            a.end_time
        FROM 
            assignment_list a 
        WHERE 
            FIND_IN_SET('$user_id', a.team_members) > 0";

    // Ensure the query executes properly
    $result = $conn->query($query);

    // Check for query execution error
    if (!$result) {
        die("Error executing query: " . $conn->error);
    }
} else {
    echo "User ID not provided.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"> <!-- Corrected charset to UTF-8 -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .assignment-card {
            max-width: 600px; /* Adjust card width */
            margin: 0 auto; /* Center horizontally */
        }
        .widget-assignment-header {
            padding: 1rem; /* Adjust header padding */
        }
        .widget-assignment-header h3 {
            margin-bottom: 0;
        }
        .card-footer dl dt {
            font-weight: bold;
        }
        .card-footer dl dd {
            margin-bottom: 0.5rem;
        }
        .modal-footer {
            border-top: none; /* Remove border for a cleaner look */
        }
    </style>
</head>
<body>

    <!-- Back Button -->
    <button onclick="window.history.back();" class="btn btn-secondary mb-3">Back</button>

    <div class="card card-outline card-warning">
        <div class="card-header">
        <table class="table table-bordered" id="tblmetrics">
            <thead>
            <tr>
                <th>Assignment Date</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Assignment Name</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['assignment_date']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['start_time']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['end_time']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='4'>No assignments found.</td></tr>";
            }
            ?>
        </tbody>
    </table>
    </div>
        </div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    $(document).ready(function(){
        $('#tblmetrics').dataTable({
            dom: 'Bfrtip', // Place buttons above the table
            buttons: [
                'copy', 'csv', 'excel', 'print' // Add buttons for Copy, CSV, Excel, and Print
            ]
        });
    });
</script>
</body>
</html>
