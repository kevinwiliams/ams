<?php
include 'db_connect.php'; // Include database connection

// Fetch the number of assignments per user based on empid in team_members
$reporter_query = $conn->query("
    SELECT 
        u.empid,
        u.firstname,
        u.lastname,
        u.role_id,
        COUNT(a.id) AS assignments_per_user
    FROM 
        assignment_list a
    JOIN 
        users u ON FIND_IN_SET(u.empid, a.team_members) > 0
    GROUP BY 
        u.empid 
");

// Initialize arrays for the user chart data
$user_names = [];
$user_assignments = [];

while ($row = $reporter_query->fetch_assoc()) {
    $user_names[] = $row['empid'] . ' - ' . $row['firstname'] . ' ' . $row['lastname']; 
    $user_assignments[] = $row['assignments_per_user'];
}

// Fetch the number of assignments created per month based on the date_created column
$query = $conn->query("
    SELECT 
        YEAR(date_created) AS year,
        MONTH(date_created) AS month,
        COUNT(id) AS total_assignments
    FROM 
        assignment_list
    GROUP BY 
        YEAR(date_created), MONTH(date_created)
    ORDER BY 
        YEAR(date_created) ASC, MONTH(date_created) ASC
");

// Initialize arrays to hold the chart data
$months = [];
$assignments = [];

while ($row = $query->fetch_assoc()) {
    $month_name = date('M', mktime(0, 0, 0, $row['month'], 10));
    $year_month = $month_name . ' ' . $row['year']; 
    
    $months[] = $year_month;
    $assignments[] = $row['total_assignments'];
}

// Convert arrays to JSON for JavaScript
$months_json = json_encode($months);
$assignments_json = json_encode($assignments);
$user_names_json = json_encode($user_names);
$user_assignments_json = json_encode($user_assignments);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trends and Insights</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

    <!-- First Container: Trends - Assignment List Created -->
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <h2 class="text-center">Assignment List Created</h2>
                <!-- <button class="btn btn-success mb-3" onclick="printSection('trendsChartContainer')">Print Trends</button> -->
                <div id="trendsChartContainer">
                    <canvas id="trendsChart"></canvas>
                </div>
                <script>
                    var ctx = document.getElementById('trendsChart').getContext('2d');
                    var trendsChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: <?php echo $months_json; ?>,
                            datasets: [{
                                label: 'Assignments Created',
                                data: <?php echo $assignments_json; ?>,
                                backgroundColor: 'rgba(153, 102, 255, 0.6)',
                                borderColor: 'rgba(153, 102, 255, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: { beginAtZero: true }
                            },
                            plugins: {
                                legend: { display: true, position: 'top' }
                            }
                        }
                    });
                </script>
            </div>
        </div>
    </div>

    <!-- Horizontal Line to Separate Sections -->
    <hr class="mt-5 mb-5" style="border-top: 2px solid #000;">

    <!-- Second Container: Assignments Per User -->
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <h2 class="text-center">Assignments Per User</h2>
                <!-- <button class="btn btn-success mb-3" onclick="printSection('userAssignmentsChartContainer')">Print Assignments Per User</button> -->
                <div id="userAssignmentsChartContainer">
                    <canvas id="userAssignmentsChart"></canvas>
                </div>
                <script>
                    var ctx2 = document.getElementById('userAssignmentsChart').getContext('2d');
                    var userAssignmentsChart = new Chart(ctx2, {
                        type: 'bar',
                        data: {
                            labels: <?php echo $user_names_json; ?>,
                            datasets: [{
                                label: 'Assignments per User',
                                data: <?php echo $user_assignments_json; ?>,
                                backgroundColor: 'rgba(75, 192, 192, 0.6)',
                                borderColor: 'rgba(75, 192, 192, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: { beginAtZero: true }
                            },
                            plugins: {
                                legend: { display: true, position: 'top' }
                            }
                        }
                    });
                </script>
            </div>
        </div>
    </div>

    <script>
        function printSection(id) {
            var printContent = document.getElementById(id).innerHTML;
            var originalContent = document.body.innerHTML;
            document.body.innerHTML = printContent;
            window.print();
            document.body.innerHTML = originalContent;
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
