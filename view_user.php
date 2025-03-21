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
    // Prepare and execute the query to fetch employee details
    $stmt = $conn->prepare("SELECT 
        u.empid,
        u.firstname,
        u.lastname,
        u.email,
        u.address,
        u.contact_number,
        r.role_name,
        u.preferred_channel
    FROM 
        users u
    LEFT JOIN roles r
        ON u.role_id = r.role_id
    WHERE 
        u.id = ?");
    
    if ($stmt === false) {
        die('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $employee = $result->fetch_assoc();
            foreach ($employee as $k => $v) {
                $$k = $v;
            }
        } else {
            die('Employee not found.');
        }
    } else {
        die('Query failed: ' . $stmt->error);
    }

    $stmt->close();
} else {
    die('Invalid employee ID.');
}

$conn->close();
?>
<style>
    .employee-card {
        max-width: 600px; /* Adjust card width */
        margin: 0 auto; /* Center horizontally */
    }
    .widget-employee-header {
        padding: 1rem; /* Adjust header padding */
    }
    .widget-employee-header h3 {
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

<div class="container mt-4">
    <div class="employee-card card card-widget widget-employee shadow">
        <!-- Header -->
        <div class="widget-employee-header bg-dark text-white text-center p-4">
            <h3 class="widget-employee-title"><?php echo htmlspecialchars($firstname . ' ' . $lastname ?? 'No Name'); ?></h3>
        </div>

        <!-- Card Body -->
        <div class="card-body">

            <!-- Employee Details Section -->
            <div class="mb-4">
                <h5>Employee Details</h5>
                <div class="row mb-3">
                    <div class="col-4"><strong>Employee ID:</strong></div>
                    <div class="col-8"><?php echo htmlspecialchars($empid ?? 'N/A'); ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-4"><strong>Name:</strong></div>
                    <div class="col-8"><?php echo htmlspecialchars($firstname . ' ' . $lastname ?? 'N/A'); ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-4"><strong>Email:</strong></div>
                    <div class="col-8"><?php echo htmlspecialchars($email ?? 'N/A'); ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-4"><strong>Address:</strong></div>
                    <div class="col-8"><?php echo htmlspecialchars($address ?? 'N/A'); ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-4"><strong>Contact Number:</strong></div>
                    <div class="col-8"><?php echo htmlspecialchars($contact_number ?? 'N/A'); ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-4"><strong>Role:</strong></div>
                    <div class="col-8"><?php echo htmlspecialchars($role_name ?? 'N/A'); ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-4"><strong>Preferred Channel:</strong></div>
                    <div class="col-8"><?php echo htmlspecialchars($preferred_channel ?? 'N/A'); ?></div>
                </div>
            </div>
        </div>

        <!-- Footer (only visible to users with role_id < 5) -->
        <?php if ($login_role_id < 5) { ?>
        <div class="card-footer text-center">
            <a href="index.php?page=user&id=<?php echo $id; ?>" class="mx-5">Edit Employee</a>
            <!-- Cancel Button to Return to User List -->
            <a href="index.php?page=user_list" class="mx-5">Cancel</a>
        </div>
        <?php } ?>
    </div>
</div>
