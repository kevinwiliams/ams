<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'db_connect.php';


$login_role_id = $_SESSION['role_id'] ?? 5; 
$login_empid = $_SESSION['login_id'] ?? 0;
$db_empid = $_SESSION['empid'] ?? '';
$user_role = $_SESSION['role_name'] ?? '';

$login_empid = intval($login_empid);

$firstname = $_SESSION['login_firstname'];
$lastname = $_SESSION['login_lastname'];



//-------------------------------------------------------------------------------//
$editorQry = "";
$dispatchQry = "";
$securityQry = "";

$view_roles = ['Manager', 'ITAdmin', 'Editor', 'Multimedia', 'Dispatcher', 'Photo Editor', 'Dept Admin', 'Security' ];
$digital_roles = ['Photo Editor'];
$multimedia_roles = ['Multimedia'];

if(!in_array($user_role,  $view_roles))
    $editorQry = " AND FIND_IN_SET('".$db_empid."', REPLACE(a.team_members, ' ', '')) > 0 ";

if(in_array($user_role,  $digital_roles))
    $editorQry = " AND a.photo_requested = 1 ";

if(in_array($user_role,  $multimedia_roles))
    $editorQry = " AND (a.video_requested = 1 OR a.social_requested = 1) ";


if($user_role =='Dispatcher')
    $dispatchQry = " AND a.drop_option <> 'noTransport' AND a.status = 'Pending'";

if($user_role =='Security')
    $dispatchQry = " AND a.status = 'Approved' ";

if(in_array($user_role,  ['Photo Editor', 'Multimedia', 'Manager']))
    $editorQry .= " OR FIND_IN_SET('".$db_empid."', REPLACE(a.team_members, ' ', '')) > 0 ";


$query = "SELECT a.*,  
(SELECT GROUP_CONCAT(
        CONCAT(u.firstname, ' ', u.lastname, ' (', r.role_name, ')'
            
        ) SEPARATOR ', ')  
     FROM users u 
     LEFT JOIN roles r ON u.role_id = r.role_id
     WHERE FIND_IN_SET(u.empid, a.team_members)) AS team_members_names_with_roles,
    (SELECT CONCAT(u.firstname, ' ', u.lastname) 
     FROM users u 
     WHERE u.id = a.assigned_by) AS assigned_by_name
   
FROM assignment_list a WHERE 1=1 $editorQry $dispatchQry";
$result = $conn->query($query);

$events = [];
while ($row = $result->fetch_assoc()) {
    // Combine date & time and convert to 24-hour ISO format for FullCalendar
    $full_datetime = date("Y-m-d H:i:s", strtotime("{$row['assignment_date']} {$row['start_time']}"));

    $events[] = [
        'id' => $row['id'],
        'title' => htmlspecialchars_decode($row['title']),
        'start' => $full_datetime,
        'description' => '<b>Venue:</b> '.$row['location'].'<br><b>Start Time:</b> '.$row['start_time'].((!empty($row['end_time'])) ? ' - <b>End Time:</b> '. $row['end_time'] : '').'<br><b>Assigned By:</b> '.$row['assigned_by_name'].'<br><br><small><b>Team:</b> '.$row['team_members_names_with_roles'].'</small><br>'
    ];
}

echo json_encode($events);
?>