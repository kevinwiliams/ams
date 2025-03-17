Database
--ams_db


CREATE TABLE roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL,
    UNIQUE (role_name)
);

INSERT INTO roles (role_name) VALUES 
    ('ITAdmin'), 
    ('Dept_Admin'), 
    ('Manager'), 
    ('Editor'), 
    ('Reporter'), 
    ('Paginater'), 
    ('Photographer'), 
    ('Videographer'), 
    ('Driver');

**********************************
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empid VARCHAR(20) UNIQUE NOT NULL,
    firstname VARCHAR(50) NOT NULL,
    lastname VARCHAR(50) NOT NULL,
    address TEXT,
    contact_number VARCHAR(20),
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    reset_token VARCHAR(255),
    role_id INT,
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
);
***********************************************

CREATE TABLE assignment_list (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    location VARCHAR(255),
    assignment_date DATE,
    assignee INT,
    assigned_by INT,
    due_date DATE,
    status ENUM('Pending', 'In Progress', 'Completed', 'On Hold') DEFAULT 'Pending',
    actions TEXT,
    FOREIGN KEY (assignee) REFERENCES users(id),
    FOREIGN KEY (assigned_by) REFERENCES users(id)
);

*************************************

CREATE TABLE assignment_list (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    location VARCHAR(255),
    assignment_date DATE,
    assignee INT,
    assigned_by INT,
    due_date DATE,
    status ENUM('Pending', 'In Progress', 'Completed', 'On Hold') DEFAULT 'Pending',
    actions TEXT,
    FOREIGN KEY (assignee) REFERENCES users(id),
    FOREIGN KEY (assigned_by) REFERENCES users(id)
);

**********************************************

-- Insert roles if they don't already exist
INSERT IGNORE INTO roles (role_name) VALUES 
    ('ITAdmin'), 
    ('Dept_Admin'), 
    ('Manager'), 
    ('Editor'), 
    ('Reporter'), 
    ('Paginater'), 
    ('Photographer'), 
    ('Videographer'), 
    ('Driver');

-- Insert users
INSERT INTO users (empid, firstname, lastname, address, contact_number, email, password, reset_token, role_id) VALUES
    ('E0001', 'Petulia', 'Clarke-Lawrence', '123 Main St', '555-0001', 'petulia.clarke@example.com', 'hashed_password', NULL, 3),
    ('E0002', 'Andre', 'Lowe', '124 Main St', '555-0002', 'andre.lowe@example.com', 'hashed_password', NULL, 3),
    ('E0003', 'Dashan', 'Hendricks', '125 Main St', '555-0003', 'dashan.hendricks@example.com', 'hashed_password', NULL, 3),
    ('E0004', 'Charmaine', 'Clarke', '126 Main St', '555-0004', 'charmaine.clarke@example.com', 'hashed_password', NULL, 3),
    ('E0005', 'Novia', 'McDonald-Whyte', '127 Main St', '555-0005', 'novia.mcdonald@example.com', 'hashed_password', NULL, 3),
    ('E0006', 'Arthur', 'Hall', '128 Main St', '555-0006', 'arthur.hall@example.com', 'hashed_password', NULL, 3),
    ('E0007', 'Pete', 'Sankey', '129 Main St', '555-0007', 'pete.sankey@example.com', 'hashed_password', NULL, 3),
    ('E0008', 'Vernon', 'Davidson', '130 Main St', '555-0008', 'vernon.davidson@example.com', 'hashed_password', NULL, 3),
    ('E0009', 'Miguel', 'Thomas', '131 Main St', '555-0009', 'miguel.thomas@example.com', 'hashed_password', NULL, 3),
    ('E0010', 'Joseph', 'Wellington', '132 Main St', '555-0010', 'joseph.wellington@example.com', 'hashed_password', NULL, 3),
    ('E0011', 'Desmond', 'Allen', '133 Main St', '555-0011', 'desmond.allen@example.com', 'hashed_password', NULL, 3),
    ('E0012', 'Alicia', 'Dunkley-Willis', '134 Main St', '555-0012', 'alicia.dunkley@example.com', 'hashed_password', NULL, 3),
    ('E0013', 'Alecia', 'Smith', '135 Main St', '555-0013', 'alecia.smith@example.com', 'hashed_password', NULL, 3),
    ('E0014', 'Jason', 'Cross', '136 Main St', '555-0014', 'jason.cross@example.com', 'hashed_password', NULL, 3),
    ('E0015', 'Tamoy', 'Ashman', '137 Main St', '555-0015', 'tamoy.ashman@example.com', 'hashed_password', NULL, 3),
    ('E0016', 'Codie-Ann', 'Barrett', '138 Main St', '555-0016', 'codie-ann.barrett@example.com', 'hashed_password', NULL, 3),
    ('E0017', 'Kellaray', 'Miles', '139 Main St', '555-0017', 'kellaray.miles@example.com', 'hashed_password', NULL, 3),
    ('E0018', 'Karena', 'Bennett', '140 Main St', '555-0018', 'karena.bennett@example.com', 'hashed_password', NULL, 3),
    ('E0019', 'Julian', 'Richardson', '141 Main St', '555-0019', 'julian.richardson@example.com', 'hashed_password', NULL, 3),
    ('E0020', 'Vanessa', 'James', '142 Main St', '555-0020', 'vanessa.james@example.com', 'hashed_password', NULL, 3),
    ('E0021', 'Davina', 'Henry', '143 Main St', '555-0021', 'davina.henry@example.com', 'hashed_password', NULL, 3),
    ('E0022', 'Shereita', 'Grizzle', '144 Main St', '555-0022', 'shereita.grizzle@example.com', 'hashed_password', NULL, 3),
    ('E0023', 'Athena', 'Clarke', '145 Main St', '555-0023', 'athena.clarke@example.com', 'hashed_password', NULL, 3),
    ('E0024', 'Mark', 'Dennis', '146 Main St', '555-0024', 'mark.dennis@example.com', 'hashed_password', NULL, 3),
    ('E0025', 'Diwani', 'Masters', '147 Main St', '555-0025', 'diwani.masters@example.com', 'hashed_password', NULL, 3),
    ('E0026', 'Mark', 'Cummings', '148 Main St', '555-0026', 'mark.cummings@example.com', 'hashed_password', NULL, 3);
**********************************************


-- Insert users (including E000 user)
INSERT INTO users (empid, firstname, lastname, address, contact_number, email, password, reset_token, role_id) VALUES
    ('E000', 'John', 'Doe', '123 Main St', '555-0000', 'john.doe@example.com', 'hashed_password', NULL, NULL);

*************************************************

-- Sample insertions into the assignment_list table

-- Assignment 1
INSERT INTO assignment_list (
    title, 
    description, 
    location, 
    assignment_date, 
    assignee, 
    assigned_by, 
    due_date, 
    status,
    team_members
) VALUES (
    'Prepare Budget Report', 
    'Compile the budget report for Q3 and submit to the finance department.', 
    'Finance Department', 
    '2024-08-28', 
    5, -- Assignee ID (ensure this ID exists in the users table)
    1, -- Assigned By ID (must have role_id 1, 2, 3, or 4)
    '2024-09-15', 
    'Pending', 
    NULL  -- Assuming you don't have any team members yet
);


-- Assignment 2
INSERT INTO assignment_list (
    title, 
    description, 
    location, 
    assignment_date, 
    assignee, 
    assigned_by, 
    date_created, 
    status, 
    image_path
) VALUES (
    'Update Website Content', 
    'Update the website with new product information and promotional banners.', 
    'Marketing Department', 
    '2024-08-30', 
    6, -- Assignee ID (make sure this ID exists in the users table)
    2, -- Assigned By ID (must have role_id 1, 2, 3, or 4)
    CURDATE(), -- Sets date_created to the current date
    'In Progress', 
    'N/A' -- Assuming no image path is provided; adjust if necessary
);


-- Assignment 3
INSERT INTO assignment_list (
    title, 
    description, 
    location, 
    assignment_date, 
    assignee, 
    assigned_by, 
    date_created, 
    status, 
    image_path
) VALUES (
    'Organize Team Building Event', 
    'Plan and organize a team building event for the department.', 
    'Company HQ', 
    '2024-09-01', 
    7, -- Assignee ID (make sure this ID exists in the users table)
    3, -- Assigned By ID (must have role_id 1, 2, 3, or 4)
    CURDATE(), -- Sets date_created to the current date
    'Pending', 
    'N/A' -- Assuming no image path is provided; adjust if necessary
);

***************************************************************************
INSERT INTO users (
    empid, 
    firstname, 
    lastname, 
    address, 
    contact_number, 
    email, 
    password, 
    reset_token, 
    role_id
) VALUES (
    'E1001', 
    'Shane', 
    'Reid', 
    '123 Main St', 
    '555-0321', 
    'admin@example.com', 
    MD5('admin123'), 
    NULL, 
    1
);


***************************************************************************

ALTER TABLE users
ADD CONSTRAINT fk_role
FOREIGN KEY (role_id) REFERENCES roles(role_id);

**************************************************************
ALTER TABLE assignment_list
DROP COLUMN due_date;

ALTER TABLE assignment_list
DROP COLUMN due_date;