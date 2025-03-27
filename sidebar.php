<?php
include('./db_connect.php');
// include('topbar.php');

// Start the session if it is not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_role = $_SESSION['role_name'];
$create_roles = ['Manager', 'ITAdmin', 'Editor', 'Dept Admin', 'Security','Op Manager', 'Broadcast Coordinator' ];
$report_roles = ['Manager', 'ITAdmin', 'Editor', 'Dept Admin','Op Manager' ];
$show_roles = ['Op Manager','ITAdmin'];
$it_roles = ['ITAdmin'];


// Check if the required session variables are set, otherwise, default to a safe state
$login_role_id = $_SESSION['role_id']? $_SESSION['role_id'] : 0;
$login_name = isset($_SESSION['login_name']) ? $_SESSION['login_name'] : 'User';
?>

<aside class="main-sidebar sidebar-dark-primary elevation-6">
    <div class="dropdown">
        <a href="./" class="brand-link">
            <!-- <?php if ($login_role_id== 1): ?>
                <h3 class="text-center p-0 m-0"><b>ADMIN</b></h3>
            <?php else: ?>
                <h3 class="text-center p-0 m-0"><b>USER</b></h3>
            <?php endif; ?> -->
            <img src="assets/uploads/ams_logo.png" class="img" width="60" />
        </a>
    </div>

    <div class="sidebar pb-4 mb-6">
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column nav-flat" data-widget="treeview" role="menu" data-accordion="false">
                <li class="nav-item dropdown">
                    <a href="./" class="nav-link nav-home">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a href="./index.php?page=assignment_list" class="nav-link nav-assignment_list nav-calendar">
                        <i class="nav-icon fas fa-tasks"></i>
                        <p>Assignments</p>
                    </a>
                </li>
                <?php if (in_array($user_role, $show_roles)): ?>
                    <li class="nav-item dropdown">
                        <a href="./index.php?page=site_reports" class="nav-link nav-site_reports">
                            <i class="nav-icon fas fa-search-location"></i>
                            <p>Inspections</p>
                        </a>
                    </li>

                    <li class="nav-item dropdown">
                        <a href="./index.php?page=station_shows" class="nav-link nav-shows">
                            <i class="nav-icon fa fa-bullhorn"></i>
                            <p>Shows</p>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                    <a href="./index.php?page=ob_items" class="nav-link nav-ob_items">
                        <i class="nav-icon fas fa-clipboard-list"></i>
                        <p>OB Items</p>
                    </a>
                </li>
                    <?php endif; ?>
                <?php if (in_array($user_role, $create_roles)): ?>

                <li class="nav-item dropdown">
                    <a href="./index.php?page=user_list" class="nav-link nav-user_list">
                        <i class="nav-icon fas fa-users"></i>
                        <p>Users</p>
                    </a>
                </li>
                <?php endif; ?>

                <?php if (in_array($user_role, $it_roles)): ?>

                <li class="nav-item dropdown">
                    <a href="./index.php?page=roles" class="nav-link nav-roles">
                        <i class="nav-icon fa fa-user-secret"></i>
                        <p>Roles</p>
                    </a>
                </li>
               
                <?php endif; ?>
                <li class="nav-item d-none">
                    <a href="#" class="nav-link nav-assignment nav-view_assignment">
                      <i class="fas fa-tasks nav-icon"></i>
                        <p>Assignments <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <?php if ($login_role_id <  5): ?>
                            <li class="nav-item">
                                <a href="./index.php?page=assignment" class="nav-link nav-assignment tree-item">
                                    <i class="fas fa-angle-right nav-icon"></i>
                                    <p>Add New</p>
                                </a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a href="./index.php?page=assignment_list" class="nav-link nav-assignment_list tree-item">
                                <i class="fas fa-angle-right nav-icon"></i>
                                <p>Assignment List</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <?php if ($login_role_id < 5): ?>
                    <li class="nav-item d-none">
                        <a href="#" class="nav-link nav-edit_user">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Users <i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="./index.php?page=user" class="nav-link nav-create_user tree-item">
                                    <i class="fas fa-angle-right nav-icon"></i>
                                    <p>Add New</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="./index.php?page=user_list" class="nav-link nav-user_list tree-item">
                                    <i class="fas fa-angle-right nav-icon"></i>
                                    <p>User List</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php endif; ?>

            
                <?php if (in_array($user_role, $report_roles)): ?>
                    <li class="nav-item">
                    <a href="#" class="nav-link nav-reports">
                        <a href="#" class="nav-link nav-reports"></i>
                            <i class="nav-icon fas fa-th-list"></i>
                            <p>Reports <i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="./index.php?page=user_performance_metrics" class="nav-link nav-performance tree-item">
                                <i class="fas fa-angle-right nav-icon"></i>
                                    <p>Performance Metrics</p>
                                </a>
                            </li>
                             
                           
                            <li class="nav-item">
                                <a href="./index.php?page=trends_insights" class="nav-link nav-trends_insight tree-item">
                                <i class="fas fa-angle-right nav-icon"></i>
                                    <p>Trends and Insights</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</aside>

<script>
    $(document).ready(function(){
        var page = '<?php echo isset($_GET['page']) ? $_GET['page'] : 'home'; ?>';
        var s = '<?php echo isset($_GET['s']) ? $_GET['s'] : ''; ?>';
        if(s != '') {
            page = page + '_' + s;
        }
        if($('.nav-link.nav-' + page).length > 0) {
            $('.nav-link.nav-' + page).addClass('active');
            if($('.nav-link.nav-' + page).hasClass('tree-item') == true) {
                $('.nav-link.nav-' + page).closest('.nav-treeview').siblings('a').addClass('active');
                $('.nav-link.nav-' + page).closest('.nav-treeview').parent().addClass('menu-open');
            }
            if($('.nav-link.nav-' + page).hasClass('nav-is-tree') == true) {
                $('.nav-link.nav-' + page).parent().addClass('menu-open');
            }
        }
    });
</script>
