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

?>
<div class="col-lg-12">
    <div class="card card-outline card-primary">
        <div class="card-header d-flex">
            <!-- <h4 class="my-0 font-weight-normal flex-grow-1">All Assignments</h4> -->
            <a href="index.php?page=assignment_list" class="py-2 flex-grow-1">
                <i class="fa fa-list" aria-hidden="true"></i> List View
            </a>

            <div class="card-tools">
                <?php if ($login_role_id < 5): ?>
                    <a href="index.php?page=assignment" class="btn btn-danger btn-sm ml-2"><i class="fa fa-plus"></i> Add New Assignment</a>

                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <div id="calendar"> </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    var calendarEl = document.getElementById('calendar');

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth', // Month view by default
        headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,dayGridWeek,dayGridDay,listWeek'
            },
        selectable: true,
        editable: false, // Disable dragging
        events: 'calendar_list.php', // Load events dynamically from database
        eventClick: function(info) {
            console.log(info.event);
            // Show event details in a popup
            Swal.fire({
                    title: info.event.title,
                    html: `<p>${info.event.extendedProps.description || 'No description available.'}</p>`,
                    icon: '',
                    // showCancelButton: true,
                    confirmButtonText: `View Details&nbsp;<i class="fa fa-arrow-right"></i>`,
                    showCloseButton: true,
                    // cancelButtonText: 'Close'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = `index.php?page=view_assignment&id=${info.event.id}`;
                    }
                });
        }
    });

    calendar.render();
  });                                        
</script>

