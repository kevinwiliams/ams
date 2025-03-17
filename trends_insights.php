<?php
include 'db_connect.php'; // Include database connection

// Fetch the number of assignments created per month based on the `date_created` column
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

// Initialize empty arrays to hold the chart data
$months = [];
$assignments = [];

while ($row = $query->fetch_assoc()) {
    // Convert month number to month name 
    $month_name = date('M', mktime(0, 0, 0, $row['month'], 10));
    $year_month = $month_name . ' ' . $row['year']; // Format like "Jan 2025"
    
    $months[] = $year_month;
    $assignments[] = $row['total_assignments'];
}

// Convert arrays to JSON for use in JavaScript
$months_json = json_encode($months);
$assignments_json = json_encode($assignments);
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
    <div class="container mt-5">
        <!-- Centered Trends and Insights Section -->
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <h2 class="text-center">Trends and Insights</h2>

                <!-- Print Button on the Right -->
                <button class="btn btn-success mb-3 float-right" onclick="window.print()">Print</button>

                <!-- Trends Chart -->
                <h3>Trends: Assignment List Created</h3>
                <canvas id="trendsChart"></canvas>
                <script>
                    var ctx = document.getElementById('trendsChart').getContext('2d');
                    var trendsChart = new Chart(ctx, {
                        type: 'bar',  
                        data: {
                            labels: <?php echo $months_json; ?>, // Dynamic months
                            datasets: [{
                                label: 'Assignments Created',
                                data: <?php echo $assignments_json; ?>, // Dynamic data
                                backgroundColor: 'rgba(153, 102, 255, 0.6)', // Bar color
                                borderColor: 'rgba(153, 102, 255, 1)', // Bar border color
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true, 
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            },
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top',
                                },
                            },
                        }
                    });
                </script>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
