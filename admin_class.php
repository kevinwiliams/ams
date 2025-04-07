<?php

if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

ini_set('display_errors', 1);

Class Action {
	private $db;
	private $adhocDb;
	private $env;

   
	public function __construct() {
		ob_start();
		
		include('db_connect.php');
	
		$this->db = $conn;
		$this->adhocDb = $mssqlconn;
		$this->env = new EnvLoader('.env');
	}
	function __destruct() {
	    if ($this->db) {
			$this->db->close(); // Close MySQLi connection
		}
	
		if ($this->adhocDb) {
			sqlsrv_close($this->adhocDb); // Close SQLSRV connection
		}
	    ob_end_flush();
	}
	// Function to load env file
	public function getEnv() {
        return $this->env;
    }

	// System login
	function login(){
		extract($_POST);
			$qry = $this->db->query("SELECT *,concat(firstname,' ',lastname) as name FROM users u JOIN roles r ON r.role_id = u.role_id where u.email = '".$email."' and u.password = '".md5($password)."'  ");
		if($qry->num_rows > 0){
			foreach ($qry->fetch_array() as $key => $value) {
				if($key != 'password' && !is_numeric($key))
					$_SESSION['login_'.$key] = $value;

				if($key == 'login_id')
					$_SESSION['login_id'] = $value;
				
				if($key == 'role_id')
					$_SESSION['role_id'] = $value;
				
				if($key == 'empid')
					$_SESSION['empid'] = $value;
				
				if($key == 'role_name')
					$_SESSION['role_name'] = $value;
			}

			$timestamp = date("Y-m-d H:i:s"); // Get current date and time
			$query = "UPDATE users SET last_login = ? WHERE email = ?";
            $stmt = $this->db->prepare($query);
			$stmt->bind_param("ss", $timestamp, $email);
            $stmt->execute();
			$stmt->close();

			return 1;
		}else{

			return 2;
		}
	}
	// Log Out
	function logout(){
		session_destroy();
		foreach ($_SESSION as $key => $value) {
			unset($_SESSION[$key]);
		}
		header("location:login.php");
	}
	// Forget password 
	public function forgot_password() {
		$env = $this->getEnv();
		$site_url = $env->get('SITE_URL');
		$userEmail = $_POST['email'];
		if (!$this->db) {
			throw new Exception("Main database connection is not valid.");
		}
	
		// Check if the email exists in the main database
		$sql = "SELECT id FROM users WHERE email = ?";
		$stmt = $this->db->prepare($sql);
		$stmt->bind_param("s", $userEmail);
		$stmt->execute();
		$result = $stmt->get_result();
		$row = $result->fetch_assoc();
	
		if (!$row) {
			return "Email not found.";
		}
	
		// Generate a secure token
		$token_hash_hash = bin2hex(random_bytes(32)); // Secure 64-character token
		$expires = date("Y-m-d H:i:s", strtotime("+1 hour")); // Token expires in 1 hour
	
		// Store token in the **adhocDb** for temporary authentication
		$storeToken = $this->store_reset_token($userEmail, $token_hash_hash, $expires);
	
		// Generate reset link
		$resetLink = $site_url."/reset_password.php?token=" . urlencode($token_hash_hash);
	
		// Email message
		$message = "Click the link below to reset your password:<br><br>$resetLink<br><br>This link will expire in 1 hour.";
		
		// Send the email
		return $this->send_mail($userEmail, ["subject" => "Password Reset", "body" => $message]);
	}
	//Reset password
	public function reset_password($userId, $pass) {

        // Verify current password
        $query = "SELECT password FROM users WHERE id = ".intval($userId);
        $stmt = $this->db->query($query);
        // $stmt->execute([$userId]);
        $user = $stmt->fetch_assoc();

        if (!empty($pass)) {
            // Update password
            $hashedPassword = md5($pass);
            $query = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $this->db->prepare($query);
			$stmt->bind_param("si", $hashedPassword, $userId);
            // $stmt->execute();

            if ($stmt->execute()) {
                return json_encode(['status' => 'success']);
            } else {
                return json_encode(['status' => 'error', 'message' => 'Failed to update password']);
            }
        } else {
            return json_encode(['status' => 'error', 'message' => 'Current password is incorrect']);
        }
    
	}
	// Update password in the database
	public function update_password() {
		$currentPassword = $_POST['currentPassword'] ?? '';
        $newPassword = $_POST['newPassword'];
        $userId = $_SESSION['login_id'];

        // Verify current password
        $query = "SELECT password FROM users WHERE id = ".intval($userId);
        $stmt = $this->db->query($query);
        // $stmt->execute([$userId]);
        $user = $stmt->fetch_assoc();

        if (md5($currentPassword) == $user['password'] || empty($currentPassword)) {
            // Update password
            $hashedPassword = md5($newPassword);
            $query = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $this->db->prepare($query);
			$stmt->bind_param("si", $hashedPassword, $userId);
            // $stmt->execute();

            if ($stmt->execute()) {
                return json_encode(['status' => 'success']);
            } else {
                return json_encode(['status' => 'error', 'message' => 'Failed to update password']);
            }
        } else {
            return json_encode(['status' => 'error', 'message' => 'Current password is incorrect']);
        }
    
	}
	// Check if the email exists in the database
	public function check_email_exists($email) {
		$stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
		$stmt->bind_param("s", $email);
		$stmt->execute();
		$stmt->store_result();

		if ($stmt->num_rows > 0) {
			$stmt->bind_result($id);
			$stmt->fetch();
			return $id; // Return user ID if exists
		}
		return false;
	}
	// Store reset token in the database
	public function store_reset_token($email, $token_hash, $expires) {
		$stmt = $this->db->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
		$stmt->bind_param("sss", $token_hash, $expires, $email);
		return $stmt->execute();
	}
	// Verify token and return user ID
	public function verify_token($token_hash) {
		$stmt = $this->db->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
		$stmt->bind_param("s", $token_hash);
		$stmt->execute();
		$stmt->store_result();

		if ($stmt->num_rows > 0) {
			$stmt->bind_result($id);
			$stmt->fetch();
			return $id;
		}
		return false;
	}
	public function get_user_by_email($email) {
		$stmt = $this->db->prepare("SELECT id, email FROM users WHERE email = ?");
		$stmt->bind_param("s", $email);
		$stmt->execute();
		return $stmt->get_result()->fetch_assoc();
		
	}
	public function get_user_by_id($id) {
		$stmt = $this->db->prepare("SELECT id, firstname, lastname, email FROM users WHERE id = ?");
		$stmt->bind_param("i", $id);
		$stmt->execute();
		return $stmt->get_result()->fetch_assoc();
		
	}
	function get_team_members($team_member_ids) {
		// Split the team member IDs into an array and trim whitespace
		$ids = explode(',', $team_member_ids);
		$ids = array_map('trim', $ids);
		$ids = array_unique($ids); // Remove duplicates
		$ids = array_filter($ids); // Remove empty values
	
		// Sanitize the IDs to prevent SQL injection
		$sanitized_ids = array_map(function($id) {
			return "'" . $this->db->real_escape_string($id) . "'";
		}, $ids);
	
		// Create a comma-separated list of sanitized IDs
		$ids_list = implode(',', $sanitized_ids);

		if (empty($ids_list)) {
			return '';
		}
	
		$query = "SELECT empid, CONCAT(firstname, ' ', lastname) AS name FROM users WHERE empid IN ($ids_list)";
		$result = $this->db->query($query);
	
		if (!$result) {
			return 'Error fetching names.';
		}
	
		$names = [];
		while ($row = $result->fetch_assoc()) {
			$names[$row['empid']] = $row['name'];
		}
	
		$team_member_names = [];
		foreach ($ids as $id) {
			if (isset($names[$id])) {
				$team_member_names[] = $names[$id];
			}
		}
	
		// Join the names into a single string
		return implode(', ', $team_member_names);
	}
	// Save / Update system users
	function save_user(){
		extract($_POST);
		$data = "";
		$newEmpid = "";
		$sb_staff = isset($_POST['sb_staff']) ? 1 : 0;

		if(empty($id)){
			// Fetch the last empid from the database
			$sql = "SELECT empid FROM users where empid like 'E%' ORDER BY id DESC LIMIT 1";
			$result = $this->db->query($sql);

			if ($result->num_rows > 0) {
				$row = $result->fetch_assoc();
				$lastEmpid = $row['empid'];
				
				// Extract the numeric part of empid and increment
				$lastNumber = intval(substr($lastEmpid, 1)); // Remove the 'E' and convert to an integer
				$newNumber = $lastNumber + 1; // Increment by 1
			} else {
				// If no empid exists, start from 1
				$newNumber = 1;
			}
			// Format the new empid with leading zeros
			$newEmpid = 'E' . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
		}
		

		foreach($_POST as $k => $v){
			if(!in_array($k, array('id','cpass','password', 'channels', 'contact_number','sb_staff', 'station')) && !is_numeric($k)){
				if(empty($data)){
					// Set new employee ID
					if ($k == 'empid')
						if (empty($id))
							$v = $newEmpid;

					$data .= " $k='$v' ";
				}
				else{
					$data .= ", $k='$v' ";
				}
			}
		}
		$data .= ", sb_staff=$sb_staff ";
		if (isset($_POST['contact_number'])) {
			$phone = $this->sanitize_number($_POST['contact_number']); 
			$data .= ", contact_number='$phone' ";
		}

		$stationList = [];
		if (isset($_POST['station'])) {
			foreach ($_POST['station'] as $key => $value) {
				$stationList = array_merge($stationList, [$value]);
			}
			$chosen_stations = implode(',', $stationList);
			$data .= ", station='$chosen_stations' ";
		}

		$channelList = [];
		if (isset($_POST['channels'])) {
			foreach ($_POST['channels'] as $key => $value) {
				$channelList = array_merge($channelList, [$value]);
			}
			$chosen_channels = implode(',', $channelList);
			$data .= ", preferred_channel='$chosen_channels' ";
		}

		if(!empty($password)){
					$data .= ", password=md5('$password') ";
		}
		
		$check = $this->db->query("SELECT * FROM users where email ='$email' ".(!empty($id) ? " and id != {$id} " : ''))->num_rows;
		if($check > 0){
			return $email;
			exit;
		}
		if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
			$move = move_uploaded_file($_FILES['img']['tmp_name'],'assets/uploads/'. $fname);
			$data .= ", avatar = '$fname' ";

		}

		if(empty($id)){
			$save = $this->db->query("INSERT INTO users set $data");
		}else{
			$save = $this->db->query("UPDATE users set $data where id = $id");
		}

		if($save){
			return 1;
		}
	}
	function signup(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id','cpass')) && !is_numeric($k)){
				if($k =='password'){
					if(empty($v))
						continue;
					$v = md5($v);

				}
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}

		$check = $this->db->query("SELECT * FROM users where email ='$email' ".(!empty($id) ? " and id != {$id} " : ''))->num_rows;
		if($check > 0){
			return 2;
			exit;
		}
		if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
			$move = move_uploaded_file($_FILES['img']['tmp_name'],'assets/uploads/'. $fname);
			$data .= ", avatar = '$fname' ";

		}
		if(empty($id)){
			$save = $this->db->query("INSERT INTO users set $data");

		}else{
			$save = $this->db->query("UPDATE users set $data where id = $id");
		}

		if($save){
			if(empty($id))
				$id = $this->db->insert_id;
			foreach ($_POST as $key => $value) {
				if(!in_array($key, array('id','cpass','password')) && !is_numeric($key))
					$_SESSION['login_'.$key] = $value;
			}
					$_SESSION['login_id'] = $id;
				if(isset($_FILES['img']) && !empty($_FILES['img']['tmp_name']))
					$_SESSION['login_avatar'] = $fname;
			return 1;
		}
	}
	function update_user(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id','cpass','table','password')) && !is_numeric($k)){
				
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		$check = $this->db->query("SELECT * FROM users where email ='$email' ".(!empty($id) ? " and id != {$id} " : ''))->num_rows;
		if($check > 0){
			return 2;
			exit;
		}
		if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
			$move = move_uploaded_file($_FILES['img']['tmp_name'],'assets/uploads/'. $fname);
			$data .= ", avatar = '$fname' ";

		}
		if(!empty($password))
			$data .= " ,password=md5('$password') ";
		if(empty($id)){
			$save = $this->db->query("INSERT INTO users set $data");
		}else{
			$save = $this->db->query("UPDATE users set $data where id = $id");
		}

		if($save){
			foreach ($_POST as $key => $value) {
				if($key != 'password' && !is_numeric($key))
					$_SESSION['login_'.$key] = $value;
			}
			if(isset($_FILES['img']) && !empty($_FILES['img']['tmp_name']))
					$_SESSION['login_avatar'] = $fname;
			return 1;
		}
	}
	function delete_user(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM users where id = ".$id);
		if($delete)
			return 1;
	}
	function save_system_settings(){
		extract($_POST);
		$data = '';
		foreach($_POST as $k => $v){
			if(!is_numeric($k)){
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		if($_FILES['cover']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['cover']['name'];
			$move = move_uploaded_file($_FILES['cover']['tmp_name'],'../assets/uploads/'. $fname);
			$data .= ", cover_img = '$fname' ";

		}
		$chk = $this->db->query("SELECT * FROM system_settings");
		if($chk->num_rows > 0){
			$save = $this->db->query("UPDATE system_settings set $data where id =".$chk->fetch_array()['id']);
		}else{
			$save = $this->db->query("INSERT INTO system_settings set $data");
		}
		if($save){
			foreach($_POST as $k => $v){
				if(!is_numeric($k)){
					$_SESSION['system'][$k] = $v;
				}
			}
			if($_FILES['cover']['tmp_name'] != ''){
				$_SESSION['system']['cover_img'] = $fname;
			}
			return 1;
		}
	}
	function save_image(){
		extract($_FILES['file']);
		if(!empty($tmp_name)){
			$fname = strtotime(date("Y-m-d H:i"))."_".(str_replace(" ","-",$name));
			$move = move_uploaded_file($tmp_name,'assets/uploads/'. $fname);
			$protocol = strtolower(substr($_SERVER["SERVER_PROTOCOL"],0,5))=='https'?'https':'http';
			$hostName = $_SERVER['HTTP_HOST'];
			$path =explode('/',$_SERVER['PHP_SELF']);
			$currentPath = '/'.$path[1]; 
			if($move){
				return $protocol.'://'.$hostName.$currentPath.'/assets/uploads/'.$fname;
			}
		}
	}
	function get_report(){
		extract($_POST);
		$data = array();
		$get = $this->db->query("SELECT t.*,p.name as ticket_for FROM ticket_list t inner join pricing p on p.id = t.pricing_id where date(t.date_created) between '$date_from' and '$date_to' order by unix_timestamp(t.date_created) desc ");
		while($row= $get->fetch_assoc()){
			$row['date_created'] = date("M d, Y",strtotime($row['date_created']));
			$row['name'] = ucwords($row['name']);
			$row['adult_price'] = number_format($row['adult_price'],2);
			$row['child_price'] = number_format($row['child_price'],2);
			$row['amount'] = number_format($row['amount'],2);
			$data[]=$row;
		}
		return json_encode($data);

	}
	// Save / Update assignment
	function save_assignment() {
		
		extract($_POST);
	
		// Initialize variables
		$env = $this->getEnv();
		$ob_approval_email = $env->get('APPROVE_ASSIGNMENT_CC');
		$data = [];
		$log = [];
		$status = '';
		$timestamp = (new DateTime())->format('Y-m-d H:i:s');
		$assignmentDate = $_POST['assignment_date'] ?? '';
		$alert_op = isset($alert_manager) ? 1 : 0;
		$notify = isset($send_notification) ? 1 : 0;
		$cancelled = isset($is_cancelled) ? 1 : 0;
		$exclusive = isset($is_exclusive) ? 1 : 0;
		$toll = isset($toll_required) ? 1 : 0;
		$permit_requested = isset($request_permit) ? 1 : 0;
		$confirmed_transport = isset($transport_confirmed) ? 1 : 0;
		$uuid = !empty($uid) ? $uid : uniqid('event_', true);
		$user_role = $_SESSION['role_name'];
		$admin_roles = ['Manager', 'ITAdmin', 'Editor', 'Dept Admin', 'Op Manager', 'Broadcast Coordinator'];
		$team_members_str = "";

		// Set default values
		$data['transport_confirmed'] = $confirmed_transport;
		$data['send_notification'] = $notify;
		$data['is_cancelled'] = $cancelled;
		$data['request_permit'] = $permit_requested;
		
		// Process POST data
		foreach ($_POST as $key => $value) {
			if (!empty($value) && !in_array($key, ['id', 'assignee', 'team', 'request', 'request_amount', 'assigned_by', 'alert_manager']) && !is_numeric($key)) {
				// Escape specific fields for HTML entities and SQL
				if (in_array($key, ['description', 'title', 'equipment', 'location', 'contact_information'])) {
					$value = $this->db->real_escape_string(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
				}
	
				// Handle special cases
				switch ($key) {
					case 'status':
						$status = $value;
						break;
					case 'send_notification':
						$value = $notify;
						break;
					case 'is_cancelled':
						$value = $cancelled;
						break;
					case 'transport_confirmed':
						$value = $confirmed_transport;
						break;
					case 'is_exclusive':
						$value = $exclusive;
						break;
					case 'toll_required':
						$value = $toll;
						break;
					case 'request_permit':
						$value = $permit_requested;
						break;
					case 'uid':
						$value = $uuid;
						break;
				}
	
				// Append to $data array
				$data[$key] = is_int($value) ? $value : "'$value'";
			}
		}
	
		// Combine assignees into a comma-separated string
		$team_members = $this->get_team_members_from_post($_POST);
		if (!empty($team_members) && count($team_members) > 0) {
			$team_members_str = implode(',', $team_members);
			$data['team_members'] = "'$team_members_str'";
		}
	
		// Check for double booking
		if (empty($id)) {
			$alreadyAssigned = $this->check_double_booking($team_members, $assignmentDate, $_POST['start_time']);
			if ($alreadyAssigned) {
				// return $alreadyAssigned . ' is already assigned!';
				return json_encode([
					"status" => "error",
					"message" => $alreadyAssigned . ' is already assigned!'
				]);
			}
		}
	
		// Log dispatch details
		if (isset($_POST['transport_id']) && $status == 'Approved') {
			$log['assignment_id'] = $id;
			$log['transport_id'] = $_POST['transport_id'] ?? null;
			$log['created_by'] = $_SESSION['login_id'];
		}
	
		// Update assigned_by if user is an admin
		if (in_array($user_role, $admin_roles)) {
			$data['assigned_by'] = "'" . $_SESSION['login_id'] . "'";
		}
	
		// Always capture last user to edit assignment
		$data['edited_by'] = "'" . $_SESSION['login_id'] . "'";
	
		// Update Approved User field
		if (isset($_POST['status']) && $_POST['status'] == 'Pending') {
			$data['approved_by'] = "'" . $_SESSION['login_id'] . "'";
			$data['approval_date'] = "'$timestamp'";
		}
	
		// Flag resource requests
		$data['photo_requested'] = isset($_POST['request']['photographer']) ? 1 : 0;
		$data['video_requested'] = isset($_POST['request']['videographer']) ? 1 : 0;
		$data['social_requested'] = isset($_POST['request']['social']) ? 1 : 0;
		$data['dj_requested'] = isset($_POST['request']['dj']) ? 1 : 0;
	
		// Handle transport log
		if (!empty($_POST['transport_id'])) {
			$data['transport_confirmed'] = 1;
			$this->update_transport_logs($id, $log);
		}
	
		// Check if notification was already sent
		$notifyAlreadySent = $this->check_notification_sent($id);
		$postponed = $this->isPostponement($id, $assignmentDate, $_POST['start_time']);
	
		// SAVE ASSIGNMENT
		if (empty($id)) {
			// Add create date & UID
			$data['date_created'] = "'$timestamp'";
			$data['uid'] = "'$uuid'";
			$query = "INSERT INTO assignment_list SET " . $this->build_query($data);
		} else {
			$query = "UPDATE assignment_list SET " . $this->build_query($data) . " WHERE id=$id";
		}
	
		// Execute the query
		// echo $query;
		$save = $this->db->query($query);
		if ($save) {
			$lastInsertId = $this->db->insert_id;
			$assigned_id = $id;
			// If no ID is provided, use the last inserted ID
			$id = !empty($id) ? $id : $lastInsertId;
			
			// Prepare assignment info for notifications
			$assignmentInfo = $this->prepare_assignment_info($id, $uuid, $assignmentDate, $team_members_str, $cancelled, $postponed, $confirmed_transport);
			$data_json = json_encode($assignmentInfo);
	
			// Send resource request emails
			if (isset($_POST['request'])) {
				$this->send_resource_request($_POST, $data_json);
			}
			// Send permit request emails
			if (isset($_POST['request_permit'])) {
				$this->send_resource_request($_POST, $data_json, $permit_requested);
			}
			// Send a single email to all recipients
			if ($alert_op) {
				$subjectTxt = $this->build_email_subject($assignmentDate, $notifyAlreadySent, $cancelled, $postponed);
				$this->send_ams_mail($ob_approval_email, $data_json, $subjectTxt);
			}
			// Send notifications
			if ($notify) {
				$subjectTxt = $this->build_email_subject($assignmentDate, $notifyAlreadySent, $cancelled, $postponed);
				$this->send_notifications($team_members, $data_json, $subjectTxt);
			}

			// Log the assignment confirmation for the current user if they are part of the team
			if (strpos($team_members_str, $_SESSION['login_empid']) !== false && empty($assigned_id)) {
				$this->log_confirmed($id, $_SESSION['login_empid'], intval($_SESSION['login_id']));
			}
			
			return json_encode([
				"status" => "success",
				"message" => $id
			]);
		} else {
			return json_encode([
				"status" => "error",
				"message" => "Error saving record: " . $this->db->error
			]);
		}
	}
	
	// Get team members from POST data
	function get_team_members_from_post($postData) {
		$team_members = [];
		if (isset($postData['assignee'])) {
			foreach ($postData['assignee'] as $role => $members) {
				$team_members = array_merge($team_members, $members);
			}
		}
		if (isset($postData['team'])) {
			$team_members = array_merge($team_members, array_filter(explode(',', $postData['team'])));
		}
		return $team_members;
	}
	// Check for double booking
	function check_double_booking($team_members, $assignmentDate, $startTime) {
		$assignedMembers = [];
		foreach ($team_members as $member) {
			if ($member) {
				$memberDetails = $this->check_booking($member, $assignmentDate, $startTime);
				if ($memberDetails) {
					$assignedMembers[] = $memberDetails['firstname'] . " " . $memberDetails['lastname'];
				}
			}
		}
		return count($assignedMembers) > 0 ? implode(", ", $assignedMembers) : false;
		
	}
	// Update transport log
	function update_transport_logs($assignmentId, $logData) {
		$logged = $this->check_transport_log($assignmentId);
		$logQuery = $logged ? "UPDATE transport_log SET " . $this->build_query($logData) . " WHERE assignment_id=$assignmentId"
							: "INSERT INTO transport_log SET " . $this->build_query($logData);
		$this->db->query($logQuery);
	}
	// Check if notification was alreadu sent out
	function check_notification_sent($assignmentId) {
		if (!empty($assignmentId)) {
			$checkQuery = "SELECT send_notification FROM assignment_list WHERE id = $assignmentId";
			$checkResult = $this->db->query($checkQuery);
			return $checkResult ? $checkResult->fetch_assoc()['send_notification'] == 1 : false;
		}
		return false;
	}
	// Build query string for SQL
	function build_query($data) {
		return implode(', ', array_map(fn($k, $v) => "$k=$v", array_keys($data), $data));
	}
	// Prepare assignment info (ARRAY) for email
	function prepare_assignment_info($id, $uuid, $assignmentDate, $team_members_str, $cancelled, $postponed, $transport_confirmed) {
		$env = $this->getEnv();
		$site_url = $env->get('SITE_URL');
		$assigned_id = intval($_POST['assigned_by']);
		$assignedBy = $this->get_user_by_id($assigned_id);
		$assigned_by = (!empty($_POST['id'])) ? $assignedBy['firstname']. ' '. $assignedBy['lastname'] : $_SESSION['login_firstname'].' '.$_SESSION['login_lastname'];

		return [
			'assignment_date' => $assignmentDate,
			'start_time' => $_POST['start_time'] ?? 'N/A',
			'end_time' => $_POST['end_time'] ?? 'N/A',
			'depart_time' => $_POST['depart_time'] ?? 'N/A',
			'toll_required' => isset($_POST['toll_required']) ? 'Yes' : 'No',
			'assignment' => $_POST['title'] ?? '',
			'show' => $_POST['station_show'] ?? '',
			'contact_information' => $_POST['contact_information'] ?? '',
			'details' => $_POST['description'] ?? '',
			'venue' => $_POST['location'] ?? '',
			'transport_confirmed' => ($transport_confirmed == 1) ? 'Yes' :  'No',
			'team' => $this->get_team_members($team_members_str),
			'assigned_by' => $assigned_by,
			'assigned_by_email' => $assignedBy['email'] ?? $_SESSION['login_email'],
			'updated_by' => $_SESSION['login_firstname'] . ' ' . $_SESSION['login_lastname'],
			'url' => $site_url . '/index.php?page=view_assignment&id=' . $id . '&confirm=true',
			'uid' => $uuid,
			'is_cancelled' => $cancelled,
			'sb_staff'=> $_SESSION['login_sb_staff'] ?? '',
			
		];
	}
	// Build email subject line
	function build_email_subject($assignmentDate, $notifyAlreadySent, $cancelled, $postponed) {
		$subjectTxt = date("D, M d, Y", strtotime($assignmentDate));
		if ($notifyAlreadySent) {
			if ($cancelled) {
				$subjectTxt .= " (Cancelled)";
			} else {
				$subjectTxt .= $postponed ? " (Postponed)" : " (Updated)";
			}
		}
		return $subjectTxt;
	}
	// Flag assignment as deleted for archival/reporting purposes
	function delete_assignment() {
		// Validate and sanitize input
		if (!isset($_POST['id'], $_POST['deleted_by'])) {
			return "Invalid input data";
		}
	
		$id = intval($_POST['id']);
		$deleted_by = intval($_POST['deleted_by']);
	
		try {
			// Use a prepared statement to avoid SQL injection
			$stmt = $this->db->prepare("UPDATE assignment_list SET is_deleted = 1, deleted_by = ? WHERE id = ?");
			$result = $stmt->execute([$deleted_by, $id]);
	
			if ($result) {
				return 1; // Success
			} else {
				return "Failed to update record";
			}
		} catch (Exception $e) {
			// Handle exception
			return "Error: " . $e->getMessage();
		}
	}
	// WhatsApp Message Sender
    function send_whatsapp($phoneNumber, $message) {
		$id = '471629832710047'; // Test
		// $id = '493150340556087'; // 187602766056
		$url = 'https://graph.facebook.com/v21.0/'.$id.'/messages';
        $token_hash_hash = 'EAAdLGeX9ZAJgBO4YFZAYAbzEcwf7Hx1UGC0R2MZAqMM8ZARwWmehVC1JDjGe9HDBO4BDafo7PfvtSCd9y3To41zdiNJpQWDnNfZCvoFtVDcUgPgg5Mj6hBuWXilLHfgW1u0O0ZABAOAmr2dAS1guoGomWqr5Ad3XBalWEJ2UOZAmZAhuLtTwHIaLERVI9jPuSZAPuS8OJ9xOrmJeRWyDrm1TJR0SAUFquhPLfgNLVXW3w';
		$assignDetails = json_decode($message, true); // Convert back to array

		$txtMessage = "
		Details:
		Date: {$assignDetails['assignment_date']}
		Start Time: {$assignDetails['start_time']}
		Assignment: {$assignDetails['assignment']}
		Venue: {$assignDetails['venue']}
		Notes: {$assignDetails['details']}
		Assigned By: {$assignDetails['assigned_by']}
		View Assignment: {$assignDetails['url']}";
		/*
		$data = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $phoneNumber, 
            'type' => 'text',
            'text' => [
				'preview_url' => false, 
				'body' => $txtMessage
			]
        ];*/
		
		$data = [
            'messaging_product' => 'whatsapp',
			'recipient_type' => 'individual',
            'to' => $phoneNumber, 
            'type' => 'template',
            'template' => [
				'name' => 'assignment_details', 
				'language' => [
					'code' => 'en'
				],
				'components' => [
					[
						'type' => 'header',
						'parameters' => [
							['type' => 'text', 'text' => $assignDetails['assignment_date'], 'parameter_name' => 'assignment_date'],
						]
					],
					[
						'type' => 'body',
						'parameters' => [
							//['type' => 'text', 'text' => $assignDetails['assignment_date']], // Maps to "assignment_date"
							['type' => 'text', 'text' => $assignDetails['start_time'], 'parameter_name' => 'start_time'],      // Maps to "start_time"
							['type' => 'text', 'text' => $assignDetails['assignment'], 'parameter_name' => 'assignment'],      // Maps to "assignment"
							['type' => 'text', 'text' => $assignDetails['venue'], 'parameter_name' => 'venue'],           // Maps to "venue"
							['type' => 'text', 'text' => $assignDetails['details'], 'parameter_name' => 'details'],         // Maps to "details"
							['type' => 'text', 'text' => $assignDetails['assigned_by'], 'parameter_name' => 'assigned_by'],     // Maps to "assigned_by"
							// ['type' => 'text', 'text' => $assignDetails['url']]            // Maps to "url"
						]
					],
					[
						'type' => 'button',
						'sub_type' => 'url',
						'index' => '0',
						'parameters' => [
							['type' => 'text', 'text' => $assignDetails['url']],
						]
					]
				]
			]
				];

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer $token_hash_hash",
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($data)
        ];

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode !== 200) {
            throw new Exception("WhatsApp API Error: $response");
        }

        curl_close($ch);
        return 1;
    }
    // Custom SMS Sender
    function send_sms($phoneNumber, $message) {
		try {
			
			$textMsg = $this->gen_short_msg($message);
			$stmt = $this->db->prepare("INSERT INTO sms_messages (recipient, text, status) VALUES (?, ?, ?)");
			$result = $stmt->execute([$phoneNumber, $textMsg, 'pending']);
			$stmt->close();
			
			if ($result) {
				return 1;
			} else {
				return "Error saving SMS to database.";
			}
		} catch (Exception $e) {
			// Return the exception message in case of an error
			return "Error sending SMS: " . $e->getMessage();
		}
	} 
	 // Email Sender
	function send_ams_mail($email, $message, $subject, $bcc = "") {
		
		if (!$this->adhocDb) {
			throw new Exception("SQL Server connection is not valid.");
		}
		
		try {
			//Get ENV Variables
			$env = $this->getEnv();
			$emailFrom = $env->get('EMAIL_FROM');
			$copyAssignEmail = $env->get('EMAIL_ASSIGNMENT_CC');
			$emailTable = $env->get('MSSQL_TABLE_NAME');
			$radio_staff = $_SESSION['login_sb_staff'] == 1 ? true : false;
			$mailtype = ($radio_staff) ? "Outside Broadcast" : "Assignment";
			$subject = $mailtype . " - " . $subject;
			
			$email = trim($email); 
			$bccEmails = $bcc;      
			$ccEmails = str_replace(',', ';', $copyAssignEmail);			       
			$fromEmail = $emailFrom;   
			$assignDetails = json_decode($message, true); // Convert back to array
			// Generate meeting request
			//$cStatus = $assignDetails['is_cancelled'];

			$icsFilePath = $this->generate_ics_file($assignDetails);
			$subjectTxt = urlencode($subject);
			
			if (isset($assignDetails['transport_option']) && $assignDetails['transport_option'] !="") {
				$ccEmails .= "";
			}
			// Create HTML structure
			$htmlContent = '<h3>'.$mailtype.' Details</h3>';
			$htmlContent .= '<table style="width: 100%; border-collapse: collapse;">';
			foreach ($assignDetails as $key => $value) {
				if($value && !in_array($key, ['uid', 'is_cancelled', 'sb_staff'] )){
				
					switch ($key) {
						case 'assignment_date':
							$value = date("l, M j, Y", strtotime($value));
							break;
						case 'url':
							$value = '<a href="'.urlencode($value).'">Confirm Assignment</a>'; // urlencode for python mailer
							$key = urlencode('&nbsp;'); // urlencode for python mailer
							break;
						case 'updated_by':
							$value = (str_contains($subject, "Updated")) ? $value : '';
							break;
						case 'show':
							$value = ($radio_staff) ? $value : '';
							break;
						case 'toll_required':
							$value = ($radio_staff) ? $value : '';
							break;
						default:
							//$value = htmlspecialchars($value);
							break;
					}
					if(!in_array($key, ['assigned_by_email']) && !empty($value))
						$htmlContent .= '<tr>
											<td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>' . ucfirst(str_replace('_', ' ', $key)) . '</strong></td>
											<td style="padding: 8px; border-bottom: 1px solid #ddd;">' . $value . '</td>
										</tr>';
				}
			}

			$htmlContent .= '<tr>
								<td style="padding: 8px; border-bottom: 1px solid #ddd;" colspan="2">
									<!-- <a href="'.$icsFilePath.'">Add to Calendar</a> -->
									* Open the attachment to add a reminder to your calendar
								</td>
							</tr>';
			$htmlContent .= "</table>";

			$emailBody = urlencode($htmlContent);
			$attachment = urlencode($icsFilePath);
			$mail = "encoding=UTF-8&to=$email&bcc=$bccEmails&cc=$ccEmails&from=$fromEmail&subject=$subjectTxt&msgbody=$emailBody&attachment=$attachment";

			
			$sql = "INSERT INTO ".$emailTable." (mess) VALUES (?)";
			$stmt = sqlsrv_prepare($this->adhocDb, $sql, [$mail]);
			
			if (!$stmt) {
				throw new Exception("SQLSRV Prepare Error: " . print_r(sqlsrv_errors(), true));
			}

			if (sqlsrv_execute($stmt)) {
				// $icsFolder = $_SERVER['DOCUMENT_ROOT'].'\calendar_attachments'; // Local storage folder
				// // Extract the file name from the URL
				// $icsFileName = basename($icsFilePath);

				// // Convert URL to local file path
				// $localFilePath = rtrim($icsFolder, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $icsFileName;

				// // Check if the file exists and delete it
				// if (file_exists($localFilePath)) {
				// 	if (unlink($localFilePath)) {
				// 		echo "File deleted: $localFilePath";
				// 	} else {
				// 		echo "Error deleting file: $localFilePath";
				// 	}
				// } else {
				// 	echo "File not found: $localFilePath";
				// }
				return "Mail sent successfully.";
			} else {
				throw new Exception("SQLSRV Execution Error: " . print_r(sqlsrv_errors(), true));
			}
		} catch (Exception $e) {
			return "Error sending Mail: " . $e->getMessage();
		}
    }
	// Send resource request to assigned personnel
	function send_resource_request($postData, $data_json, $permit = null) {
		$radio_staff = $_SESSION['login_sb_staff'] == 1 ? true : false;

		$roles = ['photographer', 'videographer', 'social', 'driver', 'dj'];
	
		// Initialize request data
		$requestData = [];
		$requestedRoles = []; // Define this before use
	
		// Ensure request arrays exist
		$postData['request'] = $postData['request'] ?? [];
		$postData['request_amount'] = $postData['request_amount'] ?? [];
	
		// Populate request data dynamically
		foreach ($roles as $role) {
			$isRequested = (bool) ($postData['request'][$role] ?? false);
			$amount = $postData['request_amount'][$role] ?? 0;
	
			if ($isRequested) {
				$requestData[$role] = ['requested' => true, 'amount' => $amount];
				$requestedRoles[] = $role; // Track requested roles
			}
		}
	
		// If no roles were requested, return early
		if (empty($requestedRoles)) {
			return json_encode(['status' => 'error', 'message' => 'No roles requested.']);
		}
		// Decode the JSON data
		$assignmentInfo = json_decode($data_json, true);
	
		// Define role-specific recipients
		$env = $this->getEnv();
        $recip_photo = $env->get('EMAIL_PHOTO_REQUEST');
        $recip_video = $env->get('EMAIL_VIDEO_REQUEST');
        $recip_social = $env->get('EMAIL_SOCIAL_REQUEST');
        $recip_driver = $env->get('EMAIL_DRIVER_REQUEST');
        $recip_dj = '';
		if (str_contains($assignmentInfo['show'], 'FYAH')) {
			$recip_dj = $env->get('EMAIL_DJ_REQUEST_FYAH');
		} else {
			$recip_dj = $env->get('EMAIL_DJ_REQUEST_EDGE');
		}
		//Split in case there are multiple recipients for each
		$roleRecipients = [
			'photographer' => str_replace(',', ';', $recip_photo), 
			'videographer' => str_replace(',', ';', $recip_video),
			'social' => str_replace(',', ';', $recip_social),
			'driver' => str_replace(',', ';', $recip_driver),
			'dj' => str_replace(',', ';', $recip_dj)
		];
		
        $permit_email = $env->get('PERMIT_REQUEST');
		$mailType = ($permit) ? "Permit" : "Resource";

		// Prepare email subject and body
		$subject = $mailType.' Request - '.date("D, M d, Y", strtotime($assignmentInfo['assignment_date']));
		$body = '<h3>'.$mailType.' Request Details</h3>';
		$body .= '<table style="width: 100%; border-collapse: collapse;">';
	
		// Add requested roles and amounts
		if(empty($permit)){
			foreach ($requestData as $role => $data) {
				$body .= '<tr><td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>' . ucfirst($role) . '</strong></td>
						  <td style="padding: 8px; border-bottom: 1px solid #ddd;">' . $data['amount'] . ' requested</td></tr>';
			}
		}
		
		// Add assignment details
		foreach ($assignmentInfo as $key => $value) {
			if($value){
				if($key == 'assignment_date')
					$value = date("l, M d, Y", strtotime($value));

				if($key == 'show')
					$value = ($radio_staff) ? $value : '';
				
				if(!in_array($key, ['assigned_by_email', 'url', 'updated_by', 'uid', 'is_cancelled', 'sb_staff']) && !empty($value))
					$body .= '<tr>
							<td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>' . ucfirst(str_replace('_', ' ', $key)) . '</strong></td>
							<td style="padding: 8px; border-bottom: 1px solid #ddd;">' . ($value) . '</td>
						</tr>';
			}
		}
		
		if(empty($permit)){ // Add URL for resource requests only
		$body .= '<tr>
				  <td colspan="2" style="padding: 8px; border-bottom: 1px solid #ddd;">
				  	<a href="' . urlencode($assignmentInfo["url"]) . '">Add Resource to Assignment</a>
				  </td>
				  </tr>';
		}
		$body .= '</table>'; 
	
		// Determine recipients based on requested roles
		$recipients = [];
		foreach ($requestedRoles as $role) {
			if (isset($roleRecipients[$role])) {
				$recipients[] = $roleRecipients[$role];
			}
		}
	
		// Remove duplicate recipients
		$recipients = array_unique($recipients);
	
		// Send email to each recipient
		foreach ($recipients as $recipient) {
			$this->send_mail($recipient, [
				'subject' => $subject,
				'body' => $body
			]);
		}
	
		// Return success response
		return json_encode(['status' => 'success', 'message' => 'Request sent successfully!']);
	}
	// Generic send mail function
	function send_mail($email, $message) {
		
		if (!$this->adhocDb) {
			throw new Exception("SQL Server connection is not valid.");
		}
		
		try {
			$env = $this->getEnv();
			$emailFrom = $env->get('EMAIL_FROM');
			$emailTable = $env->get('MSSQL_TABLE_NAME');

			$bccEmails = "";      
			$fromEmail = $emailFrom;   
			$bodyDetails = $message; // Convert back to array
			$subjectTxt = urlencode($bodyDetails['subject']);
			$copyEmail = (str_contains($subjectTxt, "Requisition Form")) ? $_SESSION['login_email'] : $env->get('EMAIL_CC');
			$ccEmails = str_replace(',', ';', $copyEmail);        


			
			// Create HTML structure
			// $htmlContent = "<h3>Reset Details</h3>";
			$htmlContent = "<table border='0' cellpadding='10' cellspacing='0'>";
				$htmlContent .= "<tr>
									<td>" . $bodyDetails['body'] . "</td>
								</tr>";
			$htmlContent .= "</table>";
			$emailBody = urlencode($htmlContent);

			$mail = "encoding=UTF-8&to=$email&bcc=$bccEmails&cc=$ccEmails&from=$fromEmail&subject=$subjectTxt&msgbody=$emailBody";
			
			$sql = "INSERT INTO ".$emailTable." (mess) VALUES (?)";
			$stmt = sqlsrv_prepare($this->adhocDb, $sql, [$mail]);
			
			if (!$stmt) {
				throw new Exception("SQLSRV Prepare Error: " . print_r(sqlsrv_errors(), true));
			}

			if (sqlsrv_execute($stmt)) {
				return 1;
			} else {
				throw new Exception("SQLSRV Execution Error: " . print_r(sqlsrv_errors(), true));
			}
		} catch (Exception $e) {
			return "Error sending Mail: " . $e->getMessage();
		}
    }
	// General Messaging Function
	function send_notifications($empIds, $message, $subjectTxt) {
		// Fetch contact details for all employees
		$contacts = [];
		$placeholders = implode(',', array_fill(0, count($empIds), '?'));
		$stmt = $this->db->prepare("SELECT u.email, u.contact_number, u.preferred_channel, r.role_name FROM users u JOIN roles r ON u.role_id = r.role_id WHERE empid IN ($placeholders)");
		$stmt->execute($empIds);
		$stmt->bind_result($email, $phoneNumber, $channel, $role_name);

		// Collect all contacts
		while ($stmt->fetch()) {
			$contacts[] = [
				'email' => $email,
				'phoneNumber' => $phoneNumber,
				'channel' => $channel,
				'role' => $role_name
			];
		}
		$stmt->close();

		if (empty($contacts)) {
			throw new Exception("No contacts found.");
		}
		// Prepare email recipients
		$emailRecipients = [];
		$hiddenRecipients = [];
		foreach ($contacts as $contact) {
			if (!empty($contact['email'])) {
				if(in_array($contact['role'], ['Freelancer'])){ //Hide freelancer email addresses
					$hiddenRecipients[] = $contact['email'];
				}else{
					$emailRecipients[] = $contact['email'];
				}
			}
		}
		// Remove duplicate recipients
		$bccRecipients = array_unique($hiddenRecipients);
		$recipients = array_unique($emailRecipients);
		// Convert the array to a semicolon-separated string
		$emailRecipientsString = implode(';', $recipients);
		$hiddenEmails = implode(';', $bccRecipients);

		// Send a single email to all recipients
		if (!empty($emailRecipients)) {
			$this->send_ams_mail($emailRecipientsString, $message, $subjectTxt, $hiddenEmails);
		}
		// Send SMS and WhatsApp messages individually
		foreach ($contacts as $contact) {
			$channels = explode(',', $contact['channel']);

			if (in_array('whatsapp', $channels)) {
				// $this->send_whatsapp($contact['phoneNumber'], $message);
			}
			if (in_array('sms', $channels)) {
				$this->send_sms($contact['phoneNumber'], $message);
			}
		}

		// return 1;
	}
	public function isPostponement($assignment_id, $assignment_date, $start_time) {
        $query = "SELECT assignment_date, start_time FROM assignment_list 
                  WHERE id = ?";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $assignment_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($result) {
            $existing_date = $result['assignment_date'];
            $existing_time = $result['start_time'];

            // Check if either the date or time has changed
            return ($existing_date !== $assignment_date || $existing_time !== $start_time);
        }
        return false; // No previous record found, so it's not a postponement
    }
	// Check for assignee double bookings
	function check_booking($empID, $assignDate, $time) {
		try {
			// Query to check if the employee is in the assignment list
			$assignmentQuery = "
				SELECT id 
				FROM assignment_list 
				WHERE FIND_IN_SET(?, team_members) > 0 AND assignment_date = ? AND start_time = ?
			";
			$stmt = $this->db->prepare($assignmentQuery);
			$stmt->bind_param('sss',$empID, $assignDate, $time);
			$stmt->execute();

			$result = $stmt->get_result();
	
			// Check if an assignment exists
			if ($result->num_rows > 0) {
				// Query to get team member details
				$userQuery = "
					SELECT u.firstname, u.lastname 
					FROM users u
					JOIN roles r ON u.role_id = r.role_id
					WHERE r.role_name NOT IN ('Sales Rep','Producer', 'Broadcast Coordinator') AND u.empid = ? 
				";
				$userStmt = $this->db->prepare($userQuery);
				$userStmt->bind_param('s', $empID); // Assuming empid is a string
				$userStmt->execute();
				$userResult = $userStmt->get_result();
				
				// Fetch and return the result
				if ($userResult->num_rows > 0) {
					return $userResult->fetch_assoc(); // Return user details if found
				} else {
					return false; // No user found with that empID
				}
			} else {
				return false; // No assignment found for that date
			}
		} catch (PDOException $e) {
			// Catch and return any errors related to the database
			return "Error: " . $e->getMessage();
		} catch (Exception $e) {
			// Catch any other unexpected errors
			return "Unexpected Error: " . $e->getMessage();
		}
	}
	// Check transport log if assignement active
	function check_transport_log($assignmentID) {
		try {
			// Query to check if the assignment ID exists in the transport_log table
			$logQuery = "
				SELECT id 
				FROM transport_log 
				WHERE assignment_id = ?
			";
	
			// Prepare the statement
			$stmt = $this->db->prepare($logQuery);
			$stmt->bind_param('i', $assignmentID);
			$stmt->execute();
			$result = $stmt->get_result();
	
			if ($result->num_rows > 0) {
				return true; // Assignment ID exists in the log
			} else {
				return false; // Assignment ID does not exist in the log
			}
		} catch (Exception $e) {
			// Handle any exceptions
			return false;
		}
	}
	// Update Transport log with assigned vehicle details / Mark assignment as 'Complete'
    public function update_transport_log() {
		$assignment_id = $_POST['assignment_id'];
		$mileage = $_POST['mileage'];
		$gas_level = $_POST['gas_level'];

        if (empty($assignment_id) || !is_numeric($mileage)) {
            return false;
        }
        // Prepare the SQL statement
        $sql = "UPDATE transport_log 
                SET mileage = ?, gas_level = ?, updated_at = NOW() 
                WHERE assignment_id = ?";
        $stmt = $this->db->prepare($sql);

        // Bind parameters
        $stmt->bind_param("isi", $mileage, $gas_level, $assignment_id);

        // Execute the query
        if ($stmt->execute()) {

			$qry = "UPDATE assignment_list SET status = 'Complete' WHERE id = ?";
			$stmt = $this->db->prepare($qry);
			$stmt->bind_param("i", $assignment_id);
			if ($stmt->execute()) {
				return 1;
			}


        } else {
            return 2;
        }
    }
	// Log confirm seen recipts
	function log_confirmed($assignment_id = null, $empid = null, $user_id = null) {
		$data = [];
		if (!empty($_POST) && isset($_POST['assignment_id'])) {
			foreach ($_POST as $k => $v) {
				if (!is_numeric($k)) {
					$data[$k] = $this->db->real_escape_string($v);
				}
			}
		}
		
		// Add optional parameters to the data array if provided
		if ($assignment_id !== null) {
			$data['assignment_id'] = intval($assignment_id);
		}
		if ($empid !== null) {
			$data['empid'] = $this->db->real_escape_string($empid);
		}
		if ($user_id !== null) {
			$data['user_id'] = intval($user_id);
		}

		// Build the query dynamically
		$columns = implode(", ", array_keys($data));
		$values = implode(", ", array_map(fn($v) => is_int($v) ? $v : "'$v'", $data));
		$query = "INSERT INTO confirmed_logs ($columns) VALUES ($values)";

		$save = $this->db->query($query);

		if ($save) {
			return 1;
		} else {
			return "Error saving record: " . $this->db->error;
		}
	}
	// Send mail request for eqipmentment
	function equipment_request() {
		try {
			$id = intval($_POST['assignment_id']) ?? null;
			$request = intval($_POST['equipment_requested']) ?? 0;
			$details = $_POST['equipment_details'] ?? '';
			$env = $this->getEnv();
			$radio_staff = $_SESSION['login_sb_staff'] == 1 ? true : false;
			$requestEmailTo = ($radio_staff) ? $env->get('EMAIL_EQUIPMENT_REQUEST_SB'): $env->get('EMAIL_EQUIPMENT_REQUEST');

			
			$emailDetails = [
				'assignment_date' => date("D, M d, Y", strtotime($_POST['assignment_date'])) ?? '',
				'start_time' => $_POST['start_time'] ?? 'N/A',
				'end_time' => $_POST['end_time'] ?? 'N/A',
				'depart_time' => $_POST['depart_time'] ?? 'N/A',
				'assignment' => $_POST['title'] ?? '',
				'details' => urlencode($details) ?? '',
				'requested_by' => $_SESSION['login_firstname'].' '.$_SESSION['login_lastname'],
			];

		
			$stmt = $this->db->prepare("UPDATE assignment_list SET equipment_requested = ?, equipment = ? WHERE id = ?");
			$stmt->bind_param("isi", $request, $details, $id);
			$update_success = $stmt->execute();
			$stmt->close();

			$message = json_encode($emailDetails);
			if($update_success){
				$mail = $this->send_equip_req_mail($requestEmailTo, $message);
			}
		
			return json_encode($update_success 
				? ["status" => "success", "message" => $mail] 
				: ["status" => "error", "message" => "Database update failed"]
			);
		} catch (Exception $e) {
			return "Error";
		}
		
	}
	// Equipment Reqest Emailer
	function send_equip_req_mail($email, $message) {
		
		if (!$this->adhocDb) {
			throw new Exception("SQL Server connection is not valid.");
		}
		
		try {
			//Get ENV Variables
			$env = $this->getEnv();
			$emailFrom = $env->get('EMAIL_FROM');
			$emailTable = $env->get('MSSQL_TABLE_NAME');
			$user_role = $_SESSION['role_name'];
			$radio_staff = $_SESSION['login_sb_staff'] == 1 ? true : false;
			$req_status = ($user_role === 'Engineer') ? " (Confirmed) " : (($user_role === 'Op Manager') ? " (Approved) " : " (Pending) ");

			$email = trim($email);
			$bccEmails = "";      
			//$ccEmails = "redi";      
			$ccEmails = trim($_SESSION['login_email']);      
			$fromEmail = $emailFrom;   
			$requestDetails = json_decode($message, true); // Convert back to array
			$requestType = isset($requestDetails['items']) ? 'Outside Broadcast Form' : 'Equipment Request';
			$subjectTxt = urlencode($requestType . " - " . date("D, M d, Y", strtotime($requestDetails['assignment_date'])) . ($radio_staff ? $req_status : ""));
			
			// Create HTML structure
			$htmlContent = '<h3>'.$requestType.'</h3>';
			$htmlContent .= '<table style="width: 100%; border-collapse: collapse;">';
			foreach ($requestDetails as $key => $value) {
				if($value){
					if($key == 'assignment_date')
						$value = date("D, M d, Y", strtotime($value));

					if($key == 'items'){
						$htmlContent .= '<tr>
							<td colspan="2" style="padding: 8px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($value) . '</td>
						</tr>';
					}else{
						$htmlContent .= '<tr>
							<td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>' . ucfirst(str_replace('_', ' ', $key)) . '</strong></td>
							<td style="padding: 8px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($value) . '</td>
						</tr>';
					}
					
					
				}
				
			}
			$htmlContent .= '</table>';
			$emailBody = urlencode($htmlContent);

			$mail = "encoding=UTF-8&to=$email&bcc=$bccEmails&cc=$ccEmails&from=$fromEmail&subject=$subjectTxt&msgbody=$emailBody";

			$sql = "INSERT INTO ".$emailTable." (mess) VALUES (?)";
			$stmt = sqlsrv_prepare($this->adhocDb, $sql, [$mail]);
			
			if (!$stmt) {
				throw new Exception("SQLSRV Prepare Error: " . print_r(sqlsrv_errors(), true));
			}

			if (sqlsrv_execute($stmt)) {
				return "Mail sent successfully.";
			} else {
				throw new Exception("SQLSRV Execution Error: " . print_r(sqlsrv_errors(), true));
			}
		} catch (Exception $e) {
			return "Error sending Mail: " . $e->getMessage();
		}
    }
	// Generate a short message to send SMS text
	function gen_short_msg($assignmentInfo) {
		$assignDetails = json_decode($assignmentInfo, true);

		// Extract fields from the assignment info array
		// date("D, M d, Y", strtotime($assignDetails['assignment_date']))
		$assignmentDate = date("D, M d", strtotime($assignDetails['assignment_date']));
		$startTime = $assignDetails['start_time'];
		$endTime = $assignDetails['end_time'];
		$departTime = $assignDetails['depart_time'];
		$assignment = $assignDetails['assignment'];
		$station_show = $assignDetails['show'];
		$details = $assignDetails['details'];
		$venue = $assignDetails['venue'];
		$assignedBy = $assignDetails['assigned_by'];
		$url = $assignDetails['url'];
		
		// Shorten URL to save characters
		$apiResponse = file_get_contents("https://tinyurl.com/api-create.php?url=" . urlencode($url));
		$shortUrl =  $apiResponse;
		$departure = (!empty($departTime)) ? $departTime.'.' : '';
		// Base message structure
		$message = "AMS - $assignmentDate, $startTime-$endTime. $assignment, $venue: $departure Assigned by $assignedBy. Info: $shortUrl";
	
		// If the message exceeds 160 characters, start truncating
		if (strlen($message) > 160) {
			//  Remove "details" completely to save space
			$message = "AMS - $assignmentDate, $startTime - $assignment, $venue: $departure Assigned by $assignedBy. Info: $shortUrl";
	
			//  Truncate fields like "venue" if still too long
			if (strlen($message) > 160) {
				$venue = strlen($venue) > 20 ? substr($venue, 0, 17) . "..." : $venue;
				// $assignment = strlen($assignment) > 20 ? substr($assignment, 0, 17) . "..." : $assignment;
				$message = "AMS - $assignmentDate, $startTime - $assignment, $venue. Info: $shortUrl";
			}
		}
	
		return $message;
	}
	// Sanitize phone number (remove non-numeric characters)
    private function sanitize_number($phone) {
        return preg_replace('/[^0-9+]/', '', $phone); // Keeps only numbers and '+'
    }
	// Generate ICS file with assignment details to attach to email
	public function generate_ics_file($info)  {
		$env = $this->getEnv();
		$site_url = $env->get('SITE_URL');
		$folder_loc = $env->get('ICS_FOLDER');
		$status = ($info['is_cancelled']) ? 'CANCELLED' : 'CONFIRMED';

		$uid = isset($info['uid']) ? $info['uid'] : uniqid('event_', true); // Generate a unique ID if not provided
		
		// Convert times from 12-hour format (00:00 AM/PM) to 24-hour format for .ics
		$startDateTime = date('Ymd\THis', strtotime($info['assignment_date'] . ' ' . $info['start_time']));
	
		// If end time is not provided, add 3 hours to the start time
		if (empty($info['end_time'])) {
			$endDateTime = date('Ymd\THis', strtotime($info['assignment_date'] . ' ' . $info['start_time']) + 3 * 3600);
		} else {
			$endDateTime = date('Ymd\THis', strtotime($info['assignment_date'] . ' ' . $info['end_time']));
		}
	
		// If depart time is not provided, set it to 'N/A'
		$departTime = empty($info['depart_time']) ? 'N/A' : $info['depart_time'];
		$departDateTime = date('Ymd\THis', strtotime($info['assignment_date'] . ' ' . $departTime));
		$radio_staff = $_SESSION['login_sb_staff'] == 1 ? true : false;
		$ob_station = ($radio_staff) ? "\\nShow: ". $info['show'] : '';
	
		// Generate the .ics content
		$icsContent = "BEGIN:VCALENDAR\r\n";
		$icsContent .= "VERSION:2.0\r\n";
		$icsContent .= "PRODID:-//Jamaica Observer//AMS//EN\r\n";
		$icsContent .= "BEGIN:VEVENT\r\n";
		$icsContent .= "UID:" . $uid . "\r\n";
		$icsContent .= "DTSTAMP:" . date('Ymd\THis') . "\r\n";
		$icsContent .= "DTSTART:" . $startDateTime . "\r\n";
		$icsContent .= "DTEND:" . $endDateTime . "\r\n";
		$icsContent .= "STATUS:" . $status . "\r\n";
		$icsContent .= "SUMMARY: Assignment | " . $info['assignment'] . "\r\n";
		$icsContent .= "DESCRIPTION:" . $info['details'] . $ob_station ."\\n\\nTeam: " . $info['team'] . "\\nAssigned by: " . $info['assigned_by'] . "\\nDeparture Time: " . $departTime . "\r\n";
		$icsContent .= "LOCATION:" . $info['venue'] . "\r\n";
		$icsContent .= "URL:" . $info['url'] . "\r\n";
		$icsContent .= "ORGANIZER;CN=" . $info['assigned_by'] . ":mailto:" . $info['assigned_by_email'] . "\r\n";
		$icsContent .= "END:VEVENT\r\n";
		$icsContent .= "END:VCALENDAR\r\n";
	
		// Define the folder to store .ics files
		$icsFolder = $folder_loc . 'calendar_attachments'; 
		if (!is_dir($icsFolder)) {
			mkdir($icsFolder, 0777, true); // Create the folder if it doesn't exist
		}
	
		// Save the .ics file temporarily
		$icsFileName = $uid . '.ics';
		$icsFilePath = $icsFolder . '\\' . $icsFileName;
		file_put_contents($icsFilePath, $icsContent);
	
		// Generate the HTTPS URL
		$icsFileUrl = $site_url.'/calendar_attachments/' . $icsFileName; // Update with your domain
	
		return $icsFileUrl;
	}
	// Save inspection details and send email
	public function save_inspection() {
		extract($_POST);
		$data = [];
		$inspection_id = isset($id) ? $this->db->real_escape_string($id) : '';
		$assignment_id = isset($assignment_id) ? $this->db->real_escape_string($assignment_id) : '';
		$notify = isset($items_requested) ? 1 : 0;
		
		// Build the data array with proper escaping
		foreach ($_POST as $k => $v) {
			if (!empty($v) && !in_array($k, array('id', 'permits', 'permit_notes', 'inventory', 'assignment_id', 'assignment_title', 'assignment_date', 'assignment_time', 'items_requested'))) {
				if (in_array($k, array('general_notes', 'layout_notes', 'tent_location', 'banner_location'))) {
					$data[$k] = "'" . $this->db->real_escape_string(htmlspecialchars($v, ENT_QUOTES, 'UTF-8')) . "'";
				} else {
					// Escape and sanitize other fields
					$data[$k] = is_numeric($v) ? $v : "'" . $this->db->real_escape_string($v) . "'";
				}
			}
		}

		$data['updated_at'] = 'NOW()';
	
		// Handle permits separately
		$permit_notes = isset($_POST['permit_notes']) ? $this->db->real_escape_string($_POST['permit_notes']) : '';
		$permits = isset($_POST['permits']) ? $_POST['permits'] : array();
		$inventory = isset($_POST['inventory']) ? $_POST['inventory'] : array();
		
		if ($notify) {
			$data['items_requested'] = $notify;
			$data['report_status'] = "'Pending'";

			try {
				$this->equipment_ob_request($_POST);
			} catch (Exception $e) {
				return json_encode(['status' => 'error', 'message' => 'Failed to send equipment request: ' . $e->getMessage()]);
			}
		}
		// Start transaction
		$this->db->begin_transaction();
	
		try {
			// Prepare SET clause for SQL
			$set_clause = implode(', ', array_map(
				function ($v, $k) { return "$k = $v"; },
				$data,
				array_keys($data)
			));
	
			if(empty($inspection_id)) {
				// Insert new inspection
				$sql = "INSERT INTO venue_inspections SET $set_clause";
				$save = $this->db->query($sql);
				$inspection_id = $this->db->insert_id;
			} else {
				// Update existing inspection
				$sql = "UPDATE venue_inspections SET $set_clause WHERE id = $inspection_id";
				// Debug: Output the SQL query
				//echo("SQL Query: " . $sql);
				$save = $this->db->query($sql);
			}
	
			if($save) {
				// Handle permits
				try {
					$this->db->query("DELETE FROM venue_permits WHERE inspection_id = $inspection_id");
					if(!empty($permits)) {
						foreach($permits as $permit_type) {
							$permit_type = $this->db->real_escape_string($permit_type);
							$this->db->query("INSERT INTO venue_permits (inspection_id, permit_type, notes) VALUES ($inspection_id, '$permit_type', '$permit_notes')");
						}
					}
				} catch (Exception $e) {
					throw new Exception('Failed to handle permits: ' . $e->getMessage());
				}
	
				// Handle inventory
				try {
					if(!empty($inventory)) {
						foreach($inventory as $item) {
							$item_id = $this->db->real_escape_string($item['item_id']);
							$status = isset($item['status']) ? 1 : 0;
							$quantity = isset($item['quantity']) ? intval($item['quantity']) : 0;
							$notes = isset($item['notes']) ? $this->db->real_escape_string($item['notes']) : '';
							
							// Check if inventory record already exists
							$check = $this->db->query("SELECT * FROM ob_inventory 
													   WHERE assignment_id = $assignment_id 
													   AND item_id = '$item_id'");
							
							if($check->num_rows > 0) {
								// Update existing record
								$this->db->query("UPDATE ob_inventory SET 
												  status = $status,
												  quantity = $quantity,
												  notes = '$notes',
												  updated_at = NOW()
												  WHERE assignment_id = $assignment_id 
												  AND item_id = '$item_id'");
							} else {
								// Insert new record
								$this->db->query("INSERT INTO ob_inventory 
												  (assignment_id, item_id, status, quantity, notes) 
												  VALUES ($assignment_id, '$item_id', $status, $quantity, '$notes')");
							}
						}
					}
				} catch (Exception $e) {
					throw new Exception('Failed to handle inventory: ' . $e->getMessage());
				}
	
				$this->db->commit();
				return json_encode(['status' => 'success', 'inspection_id' => $inspection_id]);
			}
		} catch(Exception $e) {
			$this->db->rollback();
			return json_encode(['status' => 'error', 'message' => $e->getMessage()]);
		}
	
		return json_encode(['status' => 'error', 'message' => 'Unknown error occurred']);
	}
	// Send email request for equipment
	function equipment_ob_request($postData) {
		try {
			$user_role = $_SESSION['role_name'] ?? null;
			if ($user_role === null) {
				return json_encode(["status" => "error", "message" => "User role not found"]);
			}

			$env = $this->getEnv();
			$requestEmailTo = ($user_role === 'Engineer') ? $env->get('EMAIL_ITEMS_REQUEST_SB') : 
								($user_role === 'Op Manager' ? $env->get('EMAIL_ITEMS_REQUEST_BC') : $env->get('EMAIL_ITEMS_REQUEST_EN'));
			$id = intval($postData['assignment_id']) ?? null;
			$request = intval($postData['items_requested']) ?? 0;
			$inventory = isset($postData['inventory']) ? $postData['inventory'] : array();
	
			// First check if items have already been requested
			$checkStmt = $this->db->prepare("SELECT items_requested FROM venue_inspections WHERE id = ?");
			$checkStmt->bind_param("i", $id);
			$checkStmt->execute();
			$checkStmt->bind_result($db_requested);
			$checkStmt->fetch();
			$checkStmt->close();
	
			// If items were already requested, return without doing anything
			if ($db_requested == 1) {
				return json_encode(["status" => "info", "message" => "Items were already requested"]);
			}
	
			if (!empty($inventory)) {
				// Start building the HTML table
				$details = '<table style="width: 100%; border-collapse: collapse;">';
				$details .= '<tr style="background-color: #f2f2f2;">';
				$details .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Equipment</th>';
				$details .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Quantity Out</th>';
				$details .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Quantity In</th>';
				$details .= '</tr>';
				
				foreach ($inventory as $item) {
					$status = isset($item['status']) ? (int)$item['status'] : 0;
					
					// Only include items with status = 1
					if ($status === 1) {
						$quantity = isset($item['quantity']) ? intval($item['quantity']) : 0;
						$item_name = isset($item['name']) ? htmlspecialchars($item['name']) : '';
						
						$details .= '<tr>';
						$details .= '<td style="border: 1px solid #ddd; padding: 8px;">' . $item_name . '</td>';
						$details .= '<td style="border: 1px solid #ddd; padding: 8px;">' . $quantity . '</td>';
						$details .= '<td style="border: 1px solid #ddd; padding: 8px;">&nbsp;</td>';
						$details .= '</tr>';
					}
				}
				
				$details .= '</table>';
			} else {
				$details = 'No inventory items';
			}
	
			$emailDetails = [
				'assignment_date' => date("D, M d, Y", strtotime($postData['assignment_date'])) ?? '',
				'duration' => $postData['assignment_time'] ?? 'N/A',
				'assignment' => $postData['assignment_title'] ?? '',
				'items' => urlencode($details ?? ''),
				// 'broadcast_technician' => ':',
				// 'security' => ':',
				'confirm_form' => $env->get('SITE_URL').'/index.php?view_site_report&id='.$id,
			];
	
			$stmt = $this->db->prepare("UPDATE venue_inspections SET items_requested = ? WHERE id = ?");
			$stmt->bind_param("ii", $request, $id);
			$update_success = $stmt->execute();
			$stmt->close();
	
			$message = json_encode($emailDetails);
			if($update_success){
				$mail = $this->send_equip_req_mail($requestEmailTo, $message);
			}
		
			return json_encode($update_success 
				? ["status" => "success", "message" => $mail] 
				: ["status" => "error", "message" => "Database update failed"]
			);
		} catch (Exception $e) {
			return json_encode(["status" => "error", "message" => $e->getMessage()]);
		}
	}
	// Update report status and send email
	function update_report_status() {
		extract($_POST);

		if (!isset($assignment_id) || !isset($report_status)) {
			return json_encode(['status' => 'error', 'message' => 'Invalid input data']);
		}

		$env = $this->getEnv();
		$assignment_id = intval($assignment_id);
		$report_status = $this->db->real_escape_string($report_status);
		$current_datetime = date("Y-m-d H:i:s");

		$role_name = $_SESSION['role_name'] ?? null;
		$login_id = $_SESSION['login_id'] ?? null;

		try {
			// Update the report status based on the role
			if (in_array($role_name,['Broadcast Coordinator', 'Producer'])) {
				$stmt = $this->db->prepare("UPDATE venue_inspections SET report_status = ?, updated_at = ? WHERE assignment_id = ?");
				$stmt->bind_param("ssi", $report_status, $current_datetime, $assignment_id);
			} elseif ($role_name === 'Engineer') {
				$stmt = $this->db->prepare("UPDATE venue_inspections SET report_status = ?, confirmed_at = ? WHERE assignment_id = ?");
				$stmt->bind_param("ssi", $report_status, $current_datetime, $assignment_id);
			} elseif ($role_name === 'Op Manager') {
				$stmt = $this->db->prepare("UPDATE venue_inspections SET report_status = ?, approved_at = ?, approved_by = ? WHERE assignment_id = ?");
				$stmt->bind_param("ssii", $report_status, $current_datetime, $login_id, $assignment_id);
			} else {
				return json_encode(['status' => 'error', 'message' => 'Unauthorized role']);
			}

			if ($stmt->execute()) {
				// Fetch assignment details for the email
				$query = "SELECT a.title AS assignment_title, a.assignment_date, a.start_time, a.end_time, v.items_requested 
						  FROM assignment_list a 
						  JOIN venue_inspections v ON a.id = v.assignment_id 
						  WHERE a.id = ?";
				$detailsStmt = $this->db->prepare($query);
				$detailsStmt->bind_param("i", $assignment_id);
				$detailsStmt->execute();
				$result = $detailsStmt->get_result()->fetch_assoc();
				$detailsStmt->close();

				if ($result) {
					// Prepare email details
					$emailDetails = [
						'assignment_date' => date("D, M d, Y", strtotime($result['assignment_date'])),
						'duration' => $result['start_time'] . ' - ' . $result['end_time'],
						'assignment' => $result['assignment_title'],
						'status' => $report_status,
						'url' => $env->get('SITE_URL') . '/index.php?page=view_site_report&id=' . $assignment_id,
					];

					// Determine recipient email based on role
					$requestEmailTo = ($role_name === 'Engineer') ? $env->get('EMAIL_ITEMS_REQUEST_SB') : 
									  ($role_name === 'Op Manager' ? $env->get('EMAIL_ITEMS_REQUEST_BC') : $env->get('EMAIL_ITEMS_REQUEST_EN'));

					// Send the email
					$subject = 'Requisition Form - ' . $emailDetails['assignment'].' ('.$emailDetails['status'].')';
					$body = '<h3>Requisition for OB</h3>';
					$body .= '<p><strong>Assignment:</strong> ' . $emailDetails['assignment'] . '</p>' ;
					$body .= '<p><strong>Date:</strong> ' . $emailDetails['assignment_date'] . '</p>' ;
					$body .= '<p><strong>Duration:</strong> ' . $emailDetails['duration'] . '</p>' ;
					$body .= '<p><strong>Status:</strong> ' . $emailDetails['status'] . '</p>' ;
					$body .= '<p><strong>Submitted By:</strong> ' . $_SESSION['login_firstname'].' '.$_SESSION['login_lastname'] . '</p>' ;
					$body .= '<p><strong>View Form:</strong> <a href="' . urlencode($emailDetails['url']) . '">Requisition Form</a></p>';


					$this->send_mail($requestEmailTo, [
						'subject' => $subject,
						'body' => $body
					]);
					
				}

				return json_encode(['status' => 'success', 'message' => 'Report status updated and email sent successfully']);
			} else {
				return json_encode(['status' => 'error', 'message' => 'Failed to update report status']);
			}
		} catch (Exception $e) {
			return json_encode(['status' => 'error', 'message' => $e->getMessage()]);
		}
	}
	// Get users with specific roles and optional station filter
	public function get_users_roles_station($conn, $roles, $station = null) {
		// Convert single role to array for consistency
		if (!is_array($roles)) {
			$roles = [$roles];
		}
		
		// Prepare the role placeholders
		$role_placeholders = implode(',', array_fill(0, count($roles), '?'));
		
		// Base query
		$query = "SELECT u.empid, 
						CASE 
							WHEN CHAR_LENGTH(u.alias) > 0 THEN u.alias 
							ELSE CONCAT(u.firstname, ' ', u.lastname) 
						END AS display_name, 
						r.role_name 
				FROM users u 
				JOIN roles r ON r.role_id = u.role_id
				WHERE r.role_name IN ($role_placeholders)";
		
		// Add station filter if provided
		$params = $roles;
		if (!empty($station)) {
			$query .= " AND (FIND_IN_SET(?, u.station) > 0 OR u.station = '' OR u.station IS NULL)";
			$params[] = $station;
		}
		
		$query .= " ORDER BY r.role_name, u.firstname";
		
		// Prepare and execute the query
		$stmt = $conn->prepare($query);
		
		// Bind parameters dynamically
		$types = str_repeat('s', count($params));
		$stmt->bind_param($types, ...$params);
		
		$stmt->execute();
		$result = $stmt->get_result();
		
		$users = [];
		while ($row = $result->fetch_assoc()) {
			$users[] = $row;
		}
		
		return $users;
	}
	
}

	
		

	
	
	

		
