// Function to start a loading indicator
function start_loader() {
    // Show loading overlay or spinner
    $('body').prepend('<div id="preloader2"><div class="spinner"></div></div>');
}

// Function to stop the loading indicator
function end_load() {
    // Hide loading overlay or spinner
    $('.loading-overlay').remove();
}

// Function to show alerts (toast notifications)
function alert_toast(message, type = 'info', title='Info') {
    let toast = `<div aria-live="polite" aria-atomic="true" class="d-flex justify-content-center align-items-center" style="height: 200px;">
    <div class="toast toast-${type}" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="toast-header">
        <strong class="mr-auto">${title}</strong>
        <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="toast-body">
        ${message}
      </div>
    </div>
  </div>`;
    $('body').append(toast);
    setTimeout(() => {
        $('.toast').fadeOut(300, function() {
            $(this).remove();
        });
    }, 3000);
}



// Additional functionality for search form
$(document).ready(function() {
    $('#search-form').submit(function(e) {
        e.preventDefault();
        let searchQuery = $('#search-input').val();
        start_load();
        $.ajax({
            url: 'ajax.php?action=search_assignments',
            method: 'GET',
            data: { query: searchQuery },
            success: function(resp) {
                end_load();
                $('#assignment-list').html(resp);
            },
            error: function() {
                end_load();
                alert_toast('Search failed. Please try again.', 'error');
            }
        });
    });
});


