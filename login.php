<?php include 'header.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Assignment Management System Login</title>

  <link rel="stylesheet" href="assets/dist/css/styles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

  <style>
    /* Center the login box */
    .login-box {
      max-width: 400px;
      margin: 50px auto;
    }

    .login-logo {
      margin-bottom: 20px;
      text-align: center;
    }

    .login-logo img {
      width: 50%;
      height: auto;
    }

    /* Card styling */
    .card {
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      padding: 20px;
    }

    /* Adjust input fields */
    .form-control-lg {
      font-size: 16px;
      padding: 12px;
    }

    .input-group-text {
      background-color: #f8f9fa;
    }

    /* Title Styling */
    .login-title {
      font-size: 18px; /* Reduced font size */
      text-align: center; /* Centered */
      color: #007bff; /* Blue color */
      font-weight: bold;
      margin-bottom: 15px;
    }

    /* Button styling */
    .btn-primary {
      background-color: #007bff;
      border-color: #007bff;
      font-size: 16px;
      padding: 12px;
    }

    .btn-primary:hover {
      background-color: #0056b3;
      border-color: #0056b3;
    }

    /* Styling the error message */
    #error-message {
      font-size: 14px;
      font-weight: bold;
      color: #e74c3c;
      text-align: center;
      display: none;
    }

    /* Mobile responsiveness */
    @media (max-width: 576px) {
      .login-box {
        width: 90%;
      }

      .btn-primary {
        font-size: 14px;
        padding: 10px;
      }
    }
  </style>
</head>

<body class="hold-transition login-page">
  <div class="login-box">
    <div class="card">
      <div class="card-body login-card-body">
        <div class="login-logo">
          <a href="#" class="text-black">
            <img src="assets/uploads/ams_logo.png" alt="Logo">
          </a>
        </div>

       <h5 class="login-title">Assignment Management System</h5> 

        <form action="" id="login-form">
          <!-- Email Input -->
          <div class="input-group mb-3">
            <input type="email" class="form-control form-control-lg" name="email" required placeholder="Email" aria-label="Email">
            <div class="input-group-append">
              <div class="input-group-text">
                <span class="fas fa-envelope"></span>
              </div>
            </div>
          </div>

          <!-- Password Input with Show/Hide Functionality -->
          <div class="input-group mb-3">
            <input type="password" class="form-control form-control-lg" name="password" required placeholder="Password" aria-label="Password" id="passwordField">
            <div class="input-group-append">
              <div class="input-group-text">
                <span class="fas fa-lock"></span>
              </div>
              <span class="input-group-text" id="togglePassword" style="cursor: pointer;">
                <i class="fas fa-eye"></i>
              </span>
            </div>
          </div>

          <!-- Remember Me Option and Reset Password Link -->
          <div class="row mb-3">
            <div class="col-8">
              <div class="icheck-primary">
                <input type="checkbox" id="remember">
                <label for="remember">Remember Me</label>
              </div>
            </div>
          
          </div>

          <!-- Login Button -->
          <div class="row">
            <div class="col-12">
              <button type="submit" class="btn btn-primary btn-lg btn-block">Sign In</button>
            </div>
            <div class="col text-center m-3">
              <a href="forgot_password.php" class="text-muted" style="font-size: 12px;">Forgot Password?</a>
            </div>
          </div>

          <!-- Error Message -->
          <div id="error-message" class="mt-3">Invalid email or password! Please try again.</div>

        </form>
      </div>
    </div>
  </div>

  <!-- jQuery and JavaScript -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    $(document).ready(function(){
      // Toggle password visibility when eye icon is clicked
      $('#togglePassword').on('click', function() {
        var passwordField = $('#passwordField');
        var icon = $(this).find('i');

        if (passwordField.attr('type') === 'password') {
          passwordField.attr('type', 'text');
          icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
          passwordField.attr('type', 'password');
          icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
      });

      // Handle form submission
      $('#login-form').submit(function(e){
        e.preventDefault();
        $('#error-message').hide(); // Hide error message on form submit

        $.ajax({
          url: 'ajax.php?action=login',
          method: 'POST',
          data: $(this).serialize(),
          error: function(err) {
            console.log(err);
          },
          success: function(resp) {
            if (resp == 1) {
              // Get returnUrl from the current URL
              let urlParams = new URLSearchParams(window.location.search);
              let returnUrl = urlParams.get('returnUrl');

              // Redirect to returnUrl if available, otherwise go to home
              if (returnUrl) {
                  location.href = returnUrl;
              } else {
                  location.href = 'index.php?page=home';
              }
            } else {
              $('#error-message').show(); // Show error message
            }
          }
        });
      });
    });
  </script>

  <?php include 'footer.php'; ?>
</body>
</html>
