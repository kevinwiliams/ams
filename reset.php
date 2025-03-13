<?php
include 'db_connect.php';
include 'admin_class.php';

$admin = new Action($conn);

// Check if token is provided
if (!isset($_GET['token'])) {
    die("Invalid request.");
}

$token = $_GET['token'];

// Verify the token and get user ID
$user_id = $admin->verify_token($token);
if (!$user_id) {
    die("Invalid or expired token.");
}

// If form is submitted, update password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['password'])) {
    $new_password = $_POST['password'];

    if ($admin->reset_password($user_id, $new_password)) {
        $_SESSION['success'] = "Your password has been reset successfully. You can now log in.";
        header("Location: login.php");
        exit();
    } else {
        $_SESSION['error'] = "Failed to reset password. Try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <title>Reset Password</title>
</head>
<body>
  <h2>Reset Your Password</h2>
  <?php if (isset($_SESSION['error'])) { echo "<p style='color: red;'>" . $_SESSION['error'] . "</p>"; unset($_SESSION['error']); } ?>
  <form action="reset.php?token=<?php echo htmlspecialchars($token); ?>" method="POST">
    <input type="password" name="password" required placeholder="Enter new password">
    <button type="submit">Reset Password</button>
  </form>
</body>
</html>
