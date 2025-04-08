<?php
require_once 'db_connect.php';
require_once 'admin_class.php';
$admin = new Action($conn);

header('Content-Type: application/json');

$roles = isset($_GET['roles']) ? explode(',', $_GET['roles']) : ['Sales Rep']; // Default to DJ if not specified
$station = $_GET['station'] ?? null;

$users = $admin->get_users_roles_station($conn, $roles, $station);

echo json_encode($users);