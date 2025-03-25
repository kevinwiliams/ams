<?php
include 'db_connect.php'; // Include database connection

// Define threshold for low activity alert
$low_activity_threshold = 5;

// Fetch weekly assignment data for the current month, last month, and last year
$query = $conn->query(" 
    SELECT YEARWEEK(date_created, 1) AS week, 
           COUNT(id) AS total_assignments, 
           YEAR(date_created) AS year, 
           MONTH(date_created) AS month 
    FROM assignment_list 
    GROUP BY YEARWEEK(date_created, 1), YEAR(date_created), MONTH(date_created) 
    ORDER BY YEAR(date_created) ASC, MONTH(date_created) ASC
");

$weeks = [];
$assignments = [];
$last_month_assignments = [];
$last_year_assignments = [];

while ($row = $query->fetch_assoc()) {
    $weeks[] = "Week " . substr($row['week'], 4) . ' ' . $row['year'];
    $assignments[] = $row['total_assignments'];
    
    // Determine if it's last month or last year for comparison
    if ($row['month'] == date('m') - 1 && $row['year'] == date('Y')) {
        $last_month_assignments[] = $row['total_assignments'];
    } elseif ($row['year'] == date('Y') - 1) {
        $last_year_assignments[] = $row['total_assignments'];
    }
}

// Calculate moving average (3-week period)
$moving_avg = [];
for ($i = 0; $i < count($assignments); $i++) {
    $sum = 0;
    $count = 0;
    for ($j = max(0, $i - 2); $j <= $i; $j++) {
        $sum += $assignments[$j];
        $count++;
    }
    $moving_avg[] = round($sum / $count, 2);
}

// Find peak activity (max assignments in a week)
$peak_activity = max($assignments);
$peak_week = $weeks[array_search($peak_activity, $assignments)];

// Convert data to JSON
$weeks_json = json_encode($weeks);
$assignments_json = json_encode($assignments);
$moving_avg_json = json_encode($moving_avg);
$last_month_json = json_encode($last_month_assignments);
$last_year_json = json_encode($last_year_assignments);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignment Trends</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Assignment Trends</h2>
        
        <!-- Low Activity Alert -->
        <?php if (min($assignments) < $low_activity_threshold): ?>
            <div class="alert alert-danger">Warning: Low activity detected in some weeks!</div>
        <?php endif; ?>

        <h3>Peak Activity: <?php echo $peak_week; ?> (<?php echo $peak_activity; ?> assignments)</h3>

        <!-- Print Button for Weekly Breakdown -->
        <button class="btn btn-success mb-3 float-right" onclick="printGraph('weeklyChart')">Print</button>
        <canvas id="weeklyChart"></canvas>

        <!-- Print Button for Comparison Chart -->
        <button class="btn btn-success mb-3 float-right" onclick="printGraph('comparisonChart')">Print</button>
        <canvas id="comparisonChart"></canvas>
    </div>

    <script>
        function printGraph(chartId) {
            var canvas = document.getElementById(chartId);
            var dataUrl = canvas.toDataURL();
            var newWindow = window.open();
            newWindow.document.write('<img src="' + dataUrl + '"/>');
            newWindow.print();
        }

        var ctx1 = document.getElementById('weeklyChart').getContext('2d');
        var weeklyChart = new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: <?php echo $weeks_json; ?>,
                datasets: [{
                    label: 'Assignments Created',
                    data: <?php echo $assignments_json; ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }, {
                    label: 'Moving Average',
                    data: <?php echo $moving_avg_json; ?>,
                    type: 'line',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    fill: false
                }]
            },
            options: { responsive: true, scales: { y: { beginAtZero: true } } }
        });

        var ctx2 = document.getElementById('comparisonChart').getContext('2d');
        var comparisonChart = new Chart(ctx2, {
            type: 'line',
            data: {
                labels: <?php echo $weeks_json; ?>,
                datasets: [{
                    label: 'Current Month',
                    data: <?php echo $assignments_json; ?>,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    fill: false
                }, {
                    label: 'Last Month',
                    data: <?php echo $last_month_json; ?>,
                    borderColor: 'rgba(255, 206, 86, 1)',
                    fill: false
                }, {
                    label: 'Last Year',
                    data: <?php echo $last_year_json; ?>,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    fill: false
                }]
            },
            options: { responsive: true, scales: { y: { beginAtZero: true } } }
        });
    </script>
</body>
</html>
