<?php 
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

// Redirect to login if session is not set
if (!isset($_SESSION['login_id'])) {
    header("Location: login.php?returnUrl=" . urlencode($_SERVER['REQUEST_URI']));
    //header('Location: login.php');
    exit;
}

include 'header.php';
include 'db_connect.php';
// include 'sidebar.php';

// ob_start();

// if (!isset($_SESSION['system'])) {
//     $systemQuery = "SELECT * FROM system_settings";
//     $systemResult = $conn->query($systemQuery);

//     if ($systemResult) {
//         $_SESSION['system'] = $systemResult->fetch_array(MYSQLI_ASSOC) ?? [];
//     } else {
//         $_SESSION['system'] = []; 
//     }
// }

// ob_end_flush();
?>


<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed sidebar-collapse">
<div class="wrapper">
    <?php 
    // include 'header.php'; 
    include 'topbar.php'; 
    include 'sidebar.php'; 
    ?>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <div class="toast d-none" id="alert_toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-body text-white"></div>
        </div>
        <div id="toastsContainerTopRight" class="toasts-top-right fixed"></div>
        
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-8">
                        <h1 class="m-0"><?php echo htmlspecialchars($title ?? 'Assignment Management System'); ?></h1>
                    </div>
                </div>
                <hr class="border-primary">
            </div>
        </div>
        

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <?php 
                $page = $_GET['page'] ?? 'home';
                $pagePath = $page . '.php';
                
                if (file_exists($pagePath)) {
                    include $pagePath;
                } else {
                    include '404.html';
                }
                ?>
            </div>
        </section>
        <!-- /.content -->

        <!-- Modals -->
        <!-- Confirmation Modal -->
        <div class="modal fade" id="confirm_modal" role='dialog'>
            <div class="modal-dialog modal-md" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirmation</h5>
                    </div>
                    <div class="modal-body">
                        <div id="delete_content"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" id='confirm'>Continue</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Other Modals -->
        <div class="modal fade" id="uni_modal" role='dialog'>
            <div class="modal-dialog modal-md" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"></h5>
                    </div>
                    <div class="modal-body"></div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" id='submit'>Save</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="modal fade" id="uni_modal_right" role='dialog'>
            <div class="modal-dialog modal-full-height modal-md" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span class="fa fa-arrow-right"></span>
                        </button>
                    </div>
                    <div class="modal-body"></div>
                </div>
            </div>
        </div>
        
        <div class="modal fade" id="viewer_modal" role='dialog'>
            <div class="modal-dialog modal-md" role="document">
                <div class="modal-content">
                    <button type="button" class="btn-close" data-dismiss="modal">
                        <span class="fa fa-times"></span>
                    </button>
                    <img src="" alt="">
                </div>
            </div>
        </div>
    </div>
    

   
    <aside class="control-sidebar control-sidebar-dark">
        <!-- Control sidebar content goes here -->
    </aside>
    <!-- /.control-sidebar -->

    
    <footer class="main-footer">
        <strong>Copyright &copy; 2024 Jamaica Observer Limited.</strong> All rights reserved.
        <div class="float-right d-none d-sm-inline-block">
            <b><?php echo htmlspecialchars($_SESSION['system']['name'] ?? 'Assignment Management System'); ?></b>
        </div>
    </footer>
</div>
<!-- ./wrapper -->



<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

<?php include 'footer.php'; ?>

<!-- 
</body>
</html>
-->
