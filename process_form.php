<?php
// Displaying errors so we can catch any SQL or syntax issues locally
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Local database credentials for XAMPP
$host = "localhost";
$username = "root"; 
$password = "";     
$dbname = "university_db";

// Setting up the initial connection
$conn = new mysqli($host, $username, $password);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Ensure the database exists and is selected
$conn->query("CREATE DATABASE IF NOT EXISTS $dbname");
$conn->select_db($dbname);

// Creating the master department table first
$conn->query("CREATE TABLE IF NOT EXISTS Department (
    department_id INT PRIMARY KEY,
    department_name VARCHAR(100) NOT NULL
)");

// Pre-populating the departments so the mapping foreign keys have something to point to
$conn->query("INSERT IGNORE INTO Department (department_id, department_name) VALUES 
    (1, 'Computer Science'), 
    (2, 'Information Technology'), 
    (3, 'Mechanical Engineering')");

// Setting up the student info table
$conn->query("CREATE TABLE IF NOT EXISTS Student (
    student_id VARCHAR(20) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    dob DATE,
    gender VARCHAR(10),
    address TEXT,
    mobile VARCHAR(15),
    email VARCHAR(100)
)");

// Creating the relational mapping table with cascade deletes for clean data
$conn->query("CREATE TABLE IF NOT EXISTS Mapping (
    student_id VARCHAR(20),
    department_id INT,
    PRIMARY KEY (student_id, department_id),
    FOREIGN KEY (student_id) REFERENCES Student(student_id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES Department(department_id) ON DELETE CASCADE
)");

// Processing different POST actions: Create, Update, or Delete
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Handling the insertion of a new student
    if ($action == "create") {
        $s_id = $_POST['student_id'];
        $name = $_POST['name'];
        $dob  = $_POST['dob'];
        $gender = $_POST['gender'];
        $addr = $_POST['address'];
        $mob  = $_POST['mobile'];
        $email = $_POST['email'];
        $d_id = $_POST['dept_id'];

        $stmt = $conn->prepare("INSERT INTO Student (student_id, name, dob, gender, address, mobile, email) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $s_id, $name, $dob, $gender, $addr, $mob, $email);
        
        if ($stmt->execute()) {
            $stmt_map = $conn->prepare("INSERT INTO Mapping (student_id, department_id) VALUES (?, ?)");
            $stmt_map->bind_param("si", $s_id, $d_id);
            $stmt_map->execute();
            echo "<p style='color:blue; font-weight:bold;'>Record created successfully.</p>";
        }
    }

    // Updating existing record data (The 'U' in CRUD)
    if ($action == "update") {
        $s_id = $_POST['student_id'];
        $name = $_POST['name'];
        $email = $_POST['email'];

        $stmt = $conn->prepare("UPDATE Student SET name = ?, email = ? WHERE student_id = ?");
        $stmt->bind_param("sss", $name, $email, $s_id);
        
        if ($stmt->execute()) {
            echo "<p style='color:blue; font-weight:bold;'>Update successful for ID: $s_id</p>";
        }
    }

    // Deleting a record from the database
    if ($action == "delete") {
        $id = $_POST['student_id'];
        $conn->query("DELETE FROM Student WHERE student_id = '$id'");
        // Reloading the page to refresh the table
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Display logic: Fetching all students and their joined department names
echo "<h2>Registered Student Records</h2>";
echo "<a href='student_form.html' style='color:#3498db; text-decoration:none;'>+ Register New Student</a><br><br>";

$query = "SELECT s.*, d.department_name FROM Student s 
          LEFT JOIN Mapping m ON s.student_id = m.student_id 
          LEFT JOIN Department d ON m.department_id = d.department_id";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 90%; text-align: left;'>
            <tr style='background-color: #f9f9f9;'>
                <th>ID</th><th>Name</th><th>Email</th><th>Department</th><th>Operations</th>
            </tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>".$row['student_id']."</td>
                <td>".$row['name']."</td>
                <td>".$row['email']."</td>
                <td>".($row['department_name'] ?? 'Not Assigned')."</td>
                <td style='white-space: nowrap;'>
                    <form method='POST' style='display:inline;'>
                        <input type='hidden' name='student_id' value='".$row['student_id']."'>
                        <input type='hidden' name='action' value='delete'>
                        <button type='submit' onclick='return confirm(\"Permanently delete?\")' style='cursor:pointer;'>Delete</button>
                    </form>
                    
                    <a href='?edit_id=".$row['student_id']."' 
                       style='text-decoration: none; 
                              display: inline-block; 
                              padding: 3px 8px; 
                              background: #efefef; 
                              color: black; 
                              border: 1px solid #767676; 
                              border-radius: 2px; 
                              font-size: 13px; 
                              margin-left: 5px;'>Edit</a>
                </td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "The database is currently empty.";
}

// This section only pops up if the user clicked the 'Edit' button
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $res = $conn->query("SELECT * FROM Student WHERE student_id = '$edit_id'");
    $student = $res->fetch_assoc();

    if ($student) {
        echo "<div style='margin-top:30px; padding:20px; border:1px solid #ddd; display:inline-block; background:#fff;'>";
        echo "<h3>Editing Record: ".$student['student_id']."</h3>";
        echo "<form method='POST'>
                <input type='hidden' name='action' value='update'>
                <input type='hidden' name='student_id' value='".$student['student_id']."'>
                <p>New Name:<br> <input type='text' name='name' value='".$student['name']."' required></p>
                <p>New Email:<br> <input type='email' name='email' value='".$student['email']."' required></p>
                <button type='submit' style='background:#3498db; color:white; border:none; padding:8px 15px; border-radius:4px;'>Apply Changes</button>
                <a href='".$_SERVER['PHP_SELF']."' style='margin-left:10px;'>Cancel</a>
              </form>";
        echo "</div>";
    }
}

$conn->close();
?>
