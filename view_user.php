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
$user_role = $_SESSION['role_name'];
$create_roles = ['Manager', 'ITAdmin', 'Editor', 'Dept Admin','Op Manager' ];

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
    .badge {
        font-size: 0.875rem;
    }
    .employee-profile {
        border: none;
        border-radius: 0.5rem;
        overflow: hidden;
    }
    
    .employee-detail {
        display: flex;
        flex-direction: column;
        margin-bottom: 1.25rem;
    }
    
    .detail-label {
        font-weight: 600;
        color: #6c757d;
        font-size: 0.875rem;
        margin-bottom: 0.25rem;
    }
    
    .detail-value {
        font-size: 1rem;
        word-break: break-word;
    }
    
    @media (max-width: 767.98px) {
        .employee-detail {
            margin-bottom: 1rem;
        }
    }
</style>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card employee-profile shadow">
                <!-- Header with Employee Name -->
                <div class="card-header bg-primary text-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">
                            <i class="fas fa-user-tie me-2"></i>
                            <?= htmlspecialchars($firstname . ' ' . $lastname ?? 'Employee Profile') ?>
                        </h3>
                        <span class="badge bg-light text-dark">
                            ID: <?= htmlspecialchars($empid ?? 'N/A') ?>
                        </span>
                    </div>
                </div>

                <!-- Card Body -->
                <div class="card-body">
                    <!-- Employee Details Section -->
                    <div class="row g-3 mb-4">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <div class="employee-detail">
                                <span class="detail-label">Full Name</span>
                                <span class="detail-value"><?= htmlspecialchars($firstname . ' ' . $lastname ?? 'N/A') ?></span>
                            </div>
                            
                            <div class="employee-detail">
                                <span class="detail-label">Email</span>
                                <span class="detail-value">
                                    <a href="mailto:<?= htmlspecialchars($email ?? '') ?>">
                                        <?= htmlspecialchars($email ?? 'N/A') ?>
                                    </a>
                                </span>
                            </div>
                            
                            <div class="employee-detail">
                                <span class="detail-label">Contact Number</span>
                                <span class="detail-value">
                                    <a href="tel:<?= htmlspecialchars($contact_number ?? '') ?>">
                                        <?= htmlspecialchars($contact_number ?? 'N/A') ?>
                                    </a>
                                </span>
                            </div>
                        </div>
                        
                        <!-- Right Column -->
                        <div class="col-md-6">
                            <div class="employee-detail">
                                <span class="detail-label">Address</span>
                                <span class="detail-value"><?= htmlspecialchars($address ?? 'N/A') ?></span>
                            </div>
                            
                            <div class="employee-detail">
                                <span class="detail-label">Role</span>
                                <span class="detail-value">
                                    <span class="badge bg-info text-dark">
                                        <?= htmlspecialchars($role_name ?? 'N/A') ?>
                                    </span>
                                </span>
                            </div>
                            
                            <div class="employee-detail">
                                <span class="detail-label">Notification Preferences</span>
                                <span class="detail-value">
                                    <?php 
                                    if (!empty($preferred_channel)) {
                                        $channels = explode(',', $preferred_channel);
                                        foreach ($channels as $channel):
                                    ?>
                                        <span class="badge bg-light text-dark me-1">
                                            <?= htmlspecialchars(strtoupper($channel)) ?>
                                        </span>
                                    <?php 
                                        endforeach;
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Additional Sections Can Be Added Here -->
                </div>

                <!-- Footer with Action Buttons -->
                <?php if (in_array($user_role, $create_roles)): ?>
                <div class="card-footer bg-light py-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-center">
                        <a href="index.php?page=user&id=<?= $id ?>" class="btn btn-primary">
                            <i class="fas fa-edit me-2"></i> Edit Employee
                        </a>
                        <a href="index.php?page=user_list" class="btn btn-outline-secondary">
                            <i class="fas fa-list me-2"></i> Back to List
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
