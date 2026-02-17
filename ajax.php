<?php
ob_start();
date_default_timezone_set("America/Jamaica");

include 'admin_class.php';  
// include 'db_connect.php'; 

$action = $_GET['action'] ?? '';

$crud = new Action();  

if ($action == 'login') {
    $save = $crud->login();
    if ($save) echo $save;
} elseif ($action == 'logout') {
    $save = $crud->logout();
    if ($save) echo $save;
} elseif ($action == 'signup') {
    $save = $crud->signup();
    if ($save) echo $save;
} elseif ($action == 'save_user') {
    $save = $crud->save_user();
    if ($save) echo $save;
} elseif ($action == 'update_user') {
    $save = $crud->update_user();
    if ($save) echo $save;
} elseif ($action == 'delete_user') {
    $save = $crud->delete_user();
    if ($save) echo $save;
} elseif ($action == 'save_project') {
    $save = $crud->save_project();
    if ($save) echo $save;
} elseif ($action == 'delete_project') {
    $save = $crud->delete_project();
    if ($save) echo $save;
} elseif ($action == 'save_assignment') {
    $save = $crud->save_assignment();
    if ($save) echo $save;
} elseif ($action == 'delete_assignment') {
    $save = $crud->delete_assignment();
    if ($save) echo $save;
} elseif ($action == 'log_confirmed') {
    $save = $crud->log_confirmed();
    if ($save) echo $save;
} elseif ($action == 'equipment_request') {
    $save = $crud->equipment_request();
    if ($save) echo $save;
} elseif ($action == 'update_report_status') {
    $save = $crud->update_report_status();
    if ($save) echo $save;
}elseif ($action == 'save_inspection') {
    $save = $crud->save_inspection();
    if ($save) echo $save;
} elseif ($action == 'delete_progress') {
    $save = $crud->delete_progress();
    if ($save) echo $save;
} elseif ($action == 'get_report') {
    $get = $crud->get_report();
    if ($get) echo $get;
} elseif ($action == 'forgot_password') {
    $get = $crud ->forgot_password();
    if ($get) echo $get; 
} elseif ($action == 'update_password') {
    $get = $crud ->update_password();
    if ($get) echo $get; 
}elseif ($action == 'update_transport_log') {
    $get = $crud ->update_transport_log();
    if ($get) echo $get; 
}elseif ($action == 'save_gate_pass_log') {
    $get = $crud ->save_gate_pass_log();
    if ($get) echo $get; 
}elseif ($action == 'save_closing_remark') {
    $get = $crud ->save_closing_remark();
    if ($get) echo $get; 
}elseif ($action == 'get_closing_remarks') {
    $assignment_id = intval($_POST['assignment_id']);
    $get = $crud ->get_closing_remarks($assignment_id);
    if ($get) echo $get; 
} elseif ($action == 'get_transport_log') {
    $get = $crud ->get_transport_log();
    if ($get) echo $get; 
} elseif ($action == 'get_gate_pass_logs') {
    $get = $crud ->get_gate_pass_logs();
    if ($get) echo $get; 
}
// $conn->close(); 
ob_end_flush();
