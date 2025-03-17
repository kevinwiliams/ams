<?php
include 'db_connect.php'; // Include database connection

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Retrieve the user ID from the URL if available
$id = $_GET['name'] ?? ''; 
$drivers = [];
$supervisors = [];

// Fetch user details if name is provided
if ($id) {
    // Fetch the user details (optional based on your requirements)
    $qry = $conn->query("SELECT * FROM users WHERE name = " . intval($id));
    if ($qry->num_rows === 0) {
        die('User not found.');
    }
    $user = $qry->fetch_assoc();
    foreach ($user as $k => $v) {
        $$k = $v; // Create variables for each field
    }

    // Fetch the status from the assignment table
    $assignment_qry = $conn->query("SELECT status FROM assignment WHERE name = " . intval($id));
    $status = '';
    if ($assignment_qry && $assignment_qry->num_rows > 0) {
        $assignment = $assignment_qry->fetch_assoc();
        $status = $assignment['status'];  // Fetch the status
    }
}

// Fetch the available drivers and dispatchers from the users table
$driver_qry = $conn->query("SELECT firstname, lastname FROM users WHERE role_id = 'Driver'");
if ($driver_qry) {
    while ($row = $driver_qry->fetch_assoc()) {
        $drivers[] = $row;
    }
}

$supervisor_qry = $conn->query("SELECT firstname, lastname FROM users WHERE role_id = 'dispatcher'");
if ($supervisor_qry) {
    while ($row = $supervisor_qry->fetch_assoc()) {
        $supervisors[] = $row;
    }
}

?>

<style>
    img#cimg {
        height: 15vh;
        width: 15vh;
        object-fit: cover;
        border-radius: 100%;
    }
</style>

<div class="container">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <form action="" id="vehicle_request_form" method="POST">
                    <div class="row">
                        <!-- First Column: Requester Info -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="dept_requesting" class="control-label">Dept. Requesting</label>
                                <input type="text" name="dept_requesting" id="dept_requesting" class="form-control form-control-sm" required>
                            </div>

                            <div class="form-group">
                                <label for="date" class="control-label">Date</label>
                                <input type="date" name="date" id="date" class="form-control form-control-sm" required>
                            </div>

                            <div class="form-group">
                                <label for="destination" class="control-label">Destination</label>
                                <input type="text" name="destination" id="destination" class="form-control form-control-sm" required>
                            </div>

                            <div class="form-group">
                                <label for="purpose" class="control-label">Purpose</label>
                                <textarea name="purpose" id="purpose" class="form-control form-control-sm" rows="3" required></textarea>
                            </div>

                            <div class="form-group">
                                <label for="expected_departure" class="control-label">Expected Departure (Date & Time)</label>
                                <input type="datetime-local" name="expected_departure" id="expected_departure" class="form-control form-control-sm" required>
                            </div>

                            <div class="form-group">
                                <label for="expected_return" class="control-label">Expected Return (Date & Time)</label>
                                <input type="datetime-local" name="expected_return" id="expected_return" class="form-control form-control-sm" required>
                            </div>

                            <div class="form-group">
                                <label for="requested_by_name" class="control-label">Requested By (Name)</label>
                                <input type="text" name="requested_by_name" id="requested_by_name" class="form-control form-control-sm" required>
                            </div>

                            <div class="form-group">
                                <label for="requested_by_date" class="control-label">Requested By (Date)</label>
                                <input type="date" name="requested_by_date" id="requested_by_date" class="form-control form-control-sm" required>
                            </div>
                        </div>

                        <!-- Second Column: Approvals and Driver Info -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="approved_by" class="control-label">Approved By</label>
                                <!-- Pre-fill the field with the status value from the assignment table -->
                                <input type="text" name="approved_by" id="approved_by" class="form-control form-control-sm" value="<?php echo htmlspecialchars($status); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="drivers_name" class="control-label">Driver</label>
                                <select name="drivers_name" id="drivers_name" class="form-control form-control-sm" required>
                                    <option value="">Select a Driver</option>
                                    <?php foreach ($drivers as $driver): ?>
                                        <option value="<?php echo htmlspecialchars($driver['name']); ?>"><?php echo htmlspecialchars($driver['firstname'] . ' ' . $driver['lastname']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="dispatcher_name" class="control-label">Dispatcher</label>
                                <select name="dispatcher_name" id="dispatcher_name" class="form-control form-control-sm" required>
                                    <option value="">Select a Dispatcher</option>
                                    <?php foreach ($supervisors as $supervisor): ?>
                                        <option value="<?php echo htmlspecialchars($supervisor['name']); ?>"><?php echo htmlspecialchars($supervisor['firstname'] . ' ' . $supervisor['lastname']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="vehicle_plate" class="control-label">Vehicle License Plate</label>
                                <input type="text" name="vehicle_plate" id="vehicle_plate" class="form-control form-control-sm" required>
                            </div>
                        </div>
                    </div>

                    <!-- Submit and Print Buttons -->
                    <div class="form-group text-center">
                        <button type="submit" class="btn btn-primary">Submit Request</button>
                        <button type="button" class="btn btn-secondary" onclick="printForm()">Print Form</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    
// Print form functionality
function printForm() {
    var printContents = document.getElementById('vehicle_request_form').outerHTML;
    var originalContents = document.body.innerHTML;

    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents;
}

$(document).ready(function() {
    // Handle form submission with AJAX
    $('#vehicle_request_form').submit(function(e) {
        e.preventDefault();
        start_load();

        $.ajax({
            url: 'ajax.php?action=save_vehicle_request',
            method: 'POST',
            data: $(this).serialize(),
            success: function(resp) {
                end_load();

                if (resp == 1) {
                    Swal.fire({
                        title: 'Success!',
                        text: 'The vehicle request has been saved.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false,
                    }).then(() => {
                        location.href = 'index.php?page=request_list'; // Redirect after success
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: resp,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function(err) {
                console.log(err);
                end_load();
                Swal.fire({
                    title: 'Error!',
                    text: 'Something went wrong. Please try again later.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    });
});

 src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js">
</script>
