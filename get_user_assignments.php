<?php
include 'db_connect.php';

$assignmentsHtml = '';

if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];

    $assignments = $conn->query("
        SELECT id, title, description, status, assignment_date
        FROM assignment_list
        WHERE team_members LIKE '%$user_id%'
        ORDER BY assignment_date DESC
    ");

    if ($assignments->num_rows > 0) {
        $assignmentsHtml .= '<table border="1" cellspacing="0" cellpadding="8" style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Assignment Date</th>
                    </tr>
                </thead>
                <tbody>';
        while ($assignment = $assignments->fetch_assoc()) {
            $assignmentsHtml .= "<tr>
                                    <td>{$assignment['title']}</td>
                                    <td>{$assignment['description']}</td>
                                    <td>{$assignment['status']}</td>
                                    <td>{$assignment['assignment_date']}</td>
                                </tr>";
        }
        $assignmentsHtml .= '</tbody></table>';
    } else {
        $assignmentsHtml .= '<p>No assignments found for this user.</p>';
    }
} else {
    $assignmentsHtml .= '<p>User ID not provided.</p>';
}
?>

<!-- Button to Open Modal -->
<button onclick="openModal()" style="padding: 10px 15px; background: blue; color: white; border: none; cursor: pointer;">
    View Assignments
</button>
<?php //echo $assignmentsHtml; ?>

<!-- Modal Structure -->
<div id="assignmentModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>User Assignments</h2>
        <div id="modalBody">
            <?php echo $assignmentsHtml; ?>
        </div>
        <div class="modal-footer">
            <button onclick="closeModal()" style="padding: 10px 15px; background: gray; color: white; border: none;">Back</button>
            <button onclick="printAssignments()" style="padding: 10px 15px; background: green; color: white; border: none;">Print</button>
        </div>
    </div>
</div>

<!-- CSS -->
<style>
.modal {
    display: none;
    position: fixed;
    z-index: 1;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
}

.modal-content {
    background-color: white;
    margin: 10% auto;
    padding: 20px;
    width: 60%;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    border-radius: 5px;
}

.close {
    float: right;
    font-size: 24px;
    cursor: pointer;
}

.modal-footer {
    margin-top: 15px;
    display: flex;
    justify-content: space-between;
}
</style>

<!-- JavaScript -->
<script>
function openModal() {
    document.getElementById("assignmentModal").style.display = "block";
}

function closeModal() {
    document.getElementById("assignmentModal").style.display = "none";
}

function printAssignments() {
    var printContent = document.getElementById("modalBody").innerHTML;
    var newWin = window.open("", "_blank");
    newWin.document.write('<html><head><title>Print Assignments</title></head><body>');
    newWin.document.write(printContent);
    newWin.document.write('</body></html>');
    newWin.document.close();
    newWin.print();
}
</script>
