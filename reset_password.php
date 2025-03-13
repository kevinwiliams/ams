<?php

include 'header.php';
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
        // Set a flag for successful password reset
        $success = true;
    } else {
        // Set a flag for failed password reset
        $error = "Failed to reset password. Try again.";
    }
}
?>

<style>
    /* Center the login box */
    .login-box {
      max-width: 400px;
      margin: 0 auto;
    }

    .login-logo {
      margin-bottom: 30px;
    }

    .login-logo b {
      font-size: 24px;
      color: #333;
    }

    /* Card styling */
    .card {
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    /* Adjust input fields */
    .form-control-lg {
      font-size: 16px;
      padding: 20px;
    }

    .input-group-text {
      background-color: #f8f9fa;
    }

    /* Button styling */
    .btn-primary {
      background-color: #007bff;
      border-color: #007bff;
      font-size: 16px;
      padding: 15px;
    }

    .btn-primary:hover {
      background-color: #0056b3;
      border-color: #0056b3;
    }

    /* Styling the message */
    #message p {
      font-size: 14px;
      font-weight: bold;
    }

    /* Mobile responsiveness */
    @media (max-width: 576px) {
      .login-box {
        width: 90%;
      }

      .btn-primary {
        font-size: 14px;
        padding: 12px;
      }
    }
  </style>

<body class="hold-transition login-page">
  <div class="login-box">
    <div class="card shadow-lg rounded">
      <div class="card-body login-card-body">
        
        <!-- Logo and title section -->
        <div class="login-logo text-center">
          <a href="#" class="text-dark">
            <b>Assignment Management System</b>
          </a>
        </div>

        <h5 class="text-center mb-4">Reset Password</h5>

        <!-- Forgot Password Form -->
        <form action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>" method="POST" id="reset-password-form">
          <!-- Email Input with Icon -->
          
          <div class="input-group mb-3">
            <input type="password" class="form-control form-control-lg" name="password" required placeholder="Enter your new password" aria-label="Email">
            <div class="input-group-append">
              <div class="input-group-text">
                <span class="fas fa-lock"></span>
              </div>
            </div>
          </div>
          
          <!-- Submit Button -->
          <div class="row">
            <div class="col-12">
              <button type="submit" name="reset_password" class="btn btn-primary btn-lg btn-block">Reset Password</button>
            </div>
          </div>

          <!-- Error or success message -->
          <div id="message" class="mt-3 text-center">
          <?php if (isset($_SESSION['error'])) { echo "<p style='color: red;'>" . $_SESSION['error'] . "</p>"; unset($_SESSION['error']); } ?>
            <?php
            // Display error or success message
            
            if (isset($message)) {
                echo "<p class='" . (isset($message_type) ? $message_type : 'text-danger') . "'>" . $message . "</p>";
            }
            ?>
          </div>

        </form>
      </div>
    </div>
  </div>
<script src="assets/plugins/sweetalert2/sweetalert2.min.js"></script>

  <script>
    <?php if (isset($success) && $success): ?>
      Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: 'Your password has been reset successfully. You can now log in.',
        confirmButtonText: 'OK'
      }).then(() => {
        window.location.href = 'login.php';
      });
    <?php elseif (isset($error)): ?>
      Swal.fire({
        icon: 'error',
        title: 'Error!',
        text: '<?php echo $error; ?>',
        confirmButtonText: 'OK'
      });
    <?php endif; ?>
  </script>


  <?php include 'footer.php'; ?>

  
</body>
</html>