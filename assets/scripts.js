// Array to hold assignments
let assignments = [];

// Function to render assignments to the UI
function renderAssignments() {
    const assignmentList = document.getElementById('assignment-list');
    assignmentList.innerHTML = ''; // Clear existing assignments

    assignments.forEach((assignment, index) => {
        const assignmentItem = document.createElement('li');
        assignmentItem.innerHTML = `
            <strong>${assignment.title}</strong> - Due: ${assignment.dueDate}
            <button onclick="editAssignment(${index})">Edit</button>
            <button onclick="deleteAssignment(${index})">Delete</button>
        `;
        assignmentList.appendChild(assignmentItem);
    });
}

// Function to add a new assignment
function addAssignment() {
    const title = document.getElementById('assignment-title').value;
    const dueDate = document.getElementById('assignment-due-date').value;

    if (title && dueDate) {
        assignments.push({ title, dueDate });
        renderAssignments();
        document.getElementById('assignment-title').value = '';
        document.getElementById('assignment-due-date').value = '';
    } else {
        alert('Please enter both title and due date.');
    }
}

// Function to edit an existing assignment
function editAssignment(index) {
    const title = prompt('Enter new title:', assignments[index].title);
    const dueDate = prompt('Enter new due date:', assignments[index].dueDate);

    if (title && dueDate) {
        assignments[index] = { title, dueDate };
        renderAssignments();
    }
}

// Function to delete an assignment
function deleteAssignment(index) {
    if (confirm('Are you sure you want to delete this assignment?')) {
        assignments.splice(index, 1);
        renderAssignments();
    }
}

// Function to view assignment details
function viewAssignment(index) {
    const assignment = assignments[index];
    alert(`Title: ${assignment.title}\nDue Date: ${assignment.dueDate}`);
}

// Event listener for the add button
document.getElementById('add-assignment-button').addEventListener('click', addAssignment);

// Initial render
renderAssignments();
