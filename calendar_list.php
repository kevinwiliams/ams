<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db_connect.php';


$db_empid = $_SESSION['empid'] ?? '';
$sessionempid = $_SESSION['login_empid'];
$user_role = $_SESSION['role_name'];
$freelance_roles = ['Freelancer'];
$radio_staff = $_SESSION['login_sb_staff'] == 1 ? true : false;

$where = " WHERE (a.is_deleted = 0 OR a.is_deleted IS NULL)"; 

$sbQry = ($radio_staff) ? " AND a.station_show <> '' " : "";

if(in_array($user_role,  $freelance_roles)){
    $where .= "  AND FIND_IN_SET('".$db_empid."', REPLACE(a.team_members, ' ', '')) > 0 ";
}

$query = "SELECT a.*,  
(SELECT GROUP_CONCAT(
        CONCAT('<b>',u.firstname, ' ', u.lastname, '</b> (', r.role_name, ')'
            
        ) SEPARATOR ', ')  
     FROM users u 
     LEFT JOIN roles r ON u.role_id = r.role_id
     WHERE FIND_IN_SET(u.empid, a.team_members)) AS team_members_names_with_roles,
    (SELECT CONCAT(u.firstname, ' ', u.lastname) 
     FROM users u 
     WHERE u.id = a.assigned_by) AS assigned_by_name
   
FROM assignment_list a $where $sbQry";
$result = $conn->query($query);

$events = [];
while ($row = $result->fetch_assoc()) {
    // Combine date & time and convert to 24-hour ISO format for FullCalendar
    $full_datetime = date("Y-m-d H:i:s", strtotime("{$row['assignment_date']} {$row['start_time']}"));
    $events[] = [
        'id' => $row['id'],
        'title' => ($row['is_cancelled'] == 1 ? 'CANCELLED: ' : '') . htmlspecialchars_decode($row['title']),
        'start' => $full_datetime,
        'description' => '<b>Venue:</b> '. htmlspecialchars_decode($row['location']) .'<br><b>Start Time:</b> '.$row['start_time'].((!empty($row['end_time'])) ? ' - <b>End Time:</b> '. $row['end_time'] : '').'<br><b>Assigned By:</b> '.$row['assigned_by_name'].'<br><br><small><b>Team:</b> '.$row['team_members_names_with_roles'].'</small><br>',
        'textColor' => ($row['is_cancelled'] == 1 ? 'red' : 'black'),
        'backgroundColor' => ($row['is_cancelled'] == 1 ? 'red' : 'green'),
    ];
}

echo json_encode($events);
?>