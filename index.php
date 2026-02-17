<?php 
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Redirect to login if session is not set
    if (!isset($_SESSION['login_id'])) {
        header("Location: login.php?returnUrl=" . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }

    include 'header.php';
    include 'db_connect.php';

?>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed sidebar-collapse">
<div class="wrapper">
    <?php 
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
    </div>
    
    <footer class="main-footer">
        <strong>Copyright &copy; <?= date('Y') ?> Jamaica Observer Limited.</strong> All rights reserved.
        <div class="float-right d-none d-sm-inline-block">
            <b><?php echo htmlspecialchars($_SESSION['system']['name'] ?? 'Assignment Management System'); ?></b>
        </div>
    </footer>
</div>
<!-- ./wrapper -->

<!-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

<?php include 'footer.php'; ?>
