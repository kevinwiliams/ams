<?php
include 'header.php'; 

// Enable error reporting to display errors directly on the screen
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

?>

<body class="hold-transition login-page">
  <div class="login-box">
    <div class="card shadow-lg rounded">
      <div class="card-body login-card-body">
        
        <!-- Logo and title section -->
        <div class="login-logo text-center">
          <a href="#" class="text-dark fs-5">
            <b>Assignment Management System</b>
          </a>
        </div>
        

        <h5 class="text-center mb-4">Forgot Password</h5>

        <!-- Forgot Password Form -->
        <form action="forgot_password.php" method="POST" id="forgot-password-form">
          <!-- Email Input with Icon -->
          <div class="input-group mb-3">
            <input type="email" class="form-control form-control-lg" name="email" required placeholder="Enter your registered email" aria-label="Email">
            <div class="input-group-append">
              <div class="input-group-text">
                <span class="fas fa-envelope"></span>
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

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    $(document).ready(function(){
      $('#forgot-password-form').submit(function(e){
        e.preventDefault();
        $('#message').html(''); // Clear previous messages
      
        $.ajax({
          url: 'ajax.php?action=forgot_password',
          method: 'POST',
          data: $(this).serialize(),
          error: function(err) {
            console.log(err);
            $('#message').html('<p class="text-danger">An error occurred. Please try again.</p>');
          },
          success: function(resp) {
            console.log(resp);
            if (resp == 1) {
              $('#message').html('<p class="text-success">A password reset link has been sent to your email address. Please check your inbox.</p>');
            } else {
              $('#message').html('<p class="text-danger">Error: No account found with that email address.</p>');
            }
          }
        });
      });
    });
  </script>

  <?php include 'footer.php'; ?>

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
</body>
</html>
