<?php
include 'db_connect.php'; // Include database connection

// Fetch user performance metrics
$user_performance = $conn->query("
    SELECT 
        u.empid,
        u.firstname,
        u.lastname,
        COUNT(a.id) AS total_assignment_list, -- Total overall assignments
        SUM(CASE WHEN YEAR(a.assignment_date) = YEAR(CURDATE()) AND MONTH(a.assignment_date) = MONTH(CURDATE()) THEN 1 ELSE 0 END) AS assignments_this_month, -- Assignments this month
        SUM(CASE WHEN YEAR(a.assignment_date) = YEAR(CURDATE() - INTERVAL 1 MONTH) AND MONTH(a.assignment_date) = MONTH(CURDATE() - INTERVAL 1 MONTH) THEN 1 ELSE 0 END) AS assignments_last_month, -- Assignments last month
        SUM(CASE WHEN YEAR(a.assignment_date) = YEAR(CURDATE()) THEN 1 ELSE 0 END) AS assignments_this_year -- Assignments this year
    FROM 
        users u
    LEFT JOIN 
        assignment_list a ON FIND_IN_SET(u.empid, a.team_members) > 0
    GROUP BY 
        u.empid, u.firstname, u.lastname
");

?>

<!-- Centered User Performance Metrics Section -->
<div class="row justify-content-center">
    <div class="col-lg-12">
        <!-- User Performance Chart -->
        <script>
            var ctx = document.getElementById('userPerformanceChart').getContext('2d');
            var userPerformanceChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: [
                        <?php 
                        while ($row = $user_performance->fetch_assoc()) {
                            echo "'" . $row['firstname'] . " " . $row['lastname'] . "',";
                        } 
                        ?>
                    ],
                    datasets: [{
                        label: 'Completed Assignments This Month',
                        data: [
                            <?php 
                            $user_performance->data_seek(0); // Reset result set pointer
                            while ($row = $user_performance->fetch_assoc()) {
                                echo $row['assignments_this_month'] . ",";
                            } 
                            ?>
                        ],
                        backgroundColor: 'rgba(153, 102, 255, 0.2)',
                        borderColor: 'rgba(153, 102, 255, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        </script> 

        <!-- User Performance Data (Raw) -->
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h4 class="">User Performance Data</h4>
            </div>
            <div class="card-body">
                <table class="table table-bordered" id="tblmetrics">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>This Month</th>
                            <th>Last Month</th>
                            <th>This Year</th>
                            <th>Overall</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $user_performance->data_seek(0); // Reset result set pointer
                        while ($user = $user_performance->fetch_assoc()): 
                        ?>
                        <tr>
                            <!-- Link to the new page with user_id parameter using index.php?page= -->
                            <!-- <td><a href="index.php?page=user_assignment_report.php?user_id=<?php echo $user['empid']; ?>"><?php echo $user['firstname']. ' '. $user['lastname']; ?></a></td> -->
                            <td><a href="index.php?page=user_assignment_report&user_id=<?php echo $user['empid']; ?>"><?php echo $user['firstname']. ' '. $user['lastname']; ?></a></td>
                            <td><?php echo intval($user['assignments_this_month']); ?></td>
                            <td><?php echo $user['assignments_last_month']; ?></td>
                            <td><?php echo $user['assignments_this_year']; ?></td>
                            <td><?php echo $user['total_assignment_list']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section to display assignments -->
        <!-- <div id="userAssignmentsSection" class="mt-5">
            <h4>Assignments:</h4>
            <div id="assignmentsList"></div>
        </div> -->

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

        // Commented-out AJAX code for assignments loading (since we are redirecting to another page now)
        /*
        $(document).on('click', '.view-assignments', function(e) {
            e.preventDefault();
            var userId = $(this).data('user-id');

            // Send AJAX request to fetch assignments
            $.ajax({
                url: 'get_user_assignments.php', // This is a new PHP script to fetch assignments
                method: 'GET',
                data: { user_id: userId },
                success: function(response) {
                    $('#assignmentsList').html(response);
                },
                error: function(err) {
                    $('#assignmentsList').html('<p class="text-danger">Error loading assignments.</p>');
                }
            });
        });
        */
    });
</script>
</body>
</html>
