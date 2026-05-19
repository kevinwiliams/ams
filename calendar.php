<style>
    .swal2-title{
        font-size:1.5em;
    }
    .swal2-content{
        text-align: left;
    }
    </style>
<?php
    include 'db_connect.php';
    // Start the session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $driverQuery = "SELECT u.empid, CONCAT(u.firstname, ' ', u.lastname) AS name
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.role_id
        WHERE u.is_deleted = 0
          AND (LOWER(r.role_name) = 'driver' OR LOWER(r.role_name) LIKE '%driver%')
        ORDER BY u.firstname, u.lastname";
    $driverResult = $conn->query($driverQuery);
?>
<div class="col-lg-12">
    <div class="card card-outline card-primary">
        <div class="card-header d-flex">
            <!-- <h4 class="my-0 font-weight-normal flex-grow-1">All Assignments</h4> -->
              <?php if(!in_array($user_role,['Driver'])):?>
            <a href="index.php?page=assignment_list" class="py-2 flex-grow-1">
                <i class="fa fa-list" aria-hidden="true"></i> List View
            </a>
            <?php endif; ?>
            <div class="card-tools">
                <?php if ($login_role_id < 5): ?>
                    <a href="index.php?page=assignment" class="btn btn-danger btn-sm ml-2"><i class="fa fa-plus"></i> Add New Assignment</a>

                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <?php if(in_array($user_role,['Driver'])):?>
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="calendarDriverFilter">Filter by Driver:</label>
                        <select id="calendarDriverFilter" class="form-control form-control-sm">
                            <option value="">All Drivers</option>
                            <?php while ($driver = $driverResult->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($driver['empid']) ?>"><?= htmlspecialchars($driver['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                        <input type="hidden" id="calendarIsDriver" value="1">
                    </div>
                </div>
            </div>
            <?php else: ?>
                <input type="hidden" id="calendarIsDriver" value="0">
            <?php endif; ?>
            <div id="calendar"> </div>

        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    var calendarEl = document.getElementById('calendar');

    var initialView = $('#calendarIsDriver').val() === '1' ? 'dayGridWeek' : 'dayGridMonth';
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: initialView,
        headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,dayGridWeek,dayGridDay,listWeek'
            },
        selectable: true,
        editable: false, // Disable dragging
        events: {
            url: 'calendar_list.php',
            method: 'GET',
            extraParams: function() {
                return {
                    driver: $('#calendarDriverFilter').val()
                };
            }
        },
        eventClick: function(info) {
            // console.log(info.event);
            // Show event details in a popup
            Swal.fire({
                    title: info.event.title,
                    html: `<p>${info.event.extendedProps.description || 'No description available.'}</p>`,
                    icon: '',
                    showCancelButton: true,
                    confirmButtonText: `View Details&nbsp;<i class="fa fa-arrow-right"></i>`,
                    showCloseButton: true,
                    cancelButtonText: 'Close'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = `index.php?page=view_assignment&id=${info.event.id}`;
                    }
                });
        }
    });

    $('#calendarDriverFilter').on('change', function() {
        calendar.refetchEvents();
    });

    calendar.render();
  });                                        
</script>

