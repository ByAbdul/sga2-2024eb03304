<?php
// 1. Enable Error Reporting (To see exactly what goes wrong)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database configuration
$host = "localhost";
$username = "root"; 
$password = "";     
$dbname = "university_db";

// 2. Create Connection
$conn = new mysqli($host, $username, $password);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 3. Create Database and Tables
$conn->query("CREATE DATABASE IF NOT EXISTS $dbname");
$conn->select_db($dbname);

// Create Department table
$conn->query("CREATE TABLE IF NOT EXISTS Department (
    department_id INT PRIMARY KEY,
    department_name VARCHAR(100) NOT NULL
)");

// --- NEW: Pre-fill Departments so Foreign Keys work ---
$conn->query("INSERT IGNORE INTO Department (department_id, department_name) VALUES 
    (1, 'Computer Science'), 
    (2, 'Information Technology'), 
    (3, 'Mechanical Engineering')");

// Create Student table
$conn->query("CREATE TABLE IF NOT EXISTS Student (
    student_id VARCHAR(20) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    dob DATE,
    gender VARCHAR(10),
    address TEXT,
    mobile VARCHAR(15),
    email VARCHAR(100)
)");

// Create Mapping table
$conn->query("CREATE TABLE IF NOT EXISTS Mapping (
    student_id VARCHAR(20),
    department_id INT,
    PRIMARY KEY (student_id, department_id),
    FOREIGN KEY (student_id) REFERENCES Student(student_id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES Department(department_id) ON DELETE CASCADE
)");

// 4. Handle Form Submissions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action == "create") {
        $s_id = $_POST['student_id'];
        $name = $_POST['name'];
        $dob  = $_POST['dob'];
        $gender = $_POST['gender'];
        $addr = $_POST['address'];
        $mob  = $_POST['mobile'];
        $email = $_POST['email'];
        $d_id = $_POST['dept_id'];

        // Insert into Student table
        $stmt = $conn->prepare("INSERT INTO Student (student_id, name, dob, gender, address, mobile, email) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $s_id, $name, $dob, $gender, $addr, $mob, $email);
        
        if ($stmt->execute()) {
            // Insert into Mapping table (This only works if d_id is 1, 2, or 3)
            $stmt_map = $conn->prepare("INSERT INTO Mapping (student_id, department_id) VALUES (?, ?)");
            $stmt_map->bind_param("si", $s_id, $d_id);
            
            if ($stmt_map->execute()) {
                echo "<p style='color:green;'>Record added successfully!</p>";
            } else {
                echo "<p style='color:red;'>Mapping Error: " . $stmt_map->error . "</p>";
            }
        } else {
            echo "<p style='color:red;'>Student Error: " . $stmt->error . "</p>";
        }
    }

    if ($action == "delete") {
        $id = $_POST['student_id'];
        $conn->query("DELETE FROM Student WHERE student_id = '$id'");
        // Refresh the page to show updated list
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// 5. Display Records
echo "<h2>Student Records</h2>";
echo "<a href='student_form.html'>+ Add New Student</a><br><br>";

$query = "SELECT s.*, d.department_name 
          FROM Student s 
          LEFT JOIN Mapping m ON s.student_id = m.student_id 
          LEFT JOIN Department d ON m.department_id = d.department_id";

$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='10'>
            <tr>
                <th>ID</th><th>Name</th><th>Email</th><th>Department</th><th>Action</th>
            </tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>".$row['student_id']."</td>
                <td>".$row['name']."</td>
                <td>".$row['email']."</td>
                <td>".($row['department_name'] ?? 'Unassigned')."</td>
                <td>
                    <form method='POST' style='display:inline;'>
                        <input type='hidden' name='student_id' value='".$row['student_id']."'>
                        <input type='hidden' name='action' value='delete'>
                        <button type='submit' onclick='return confirm(\"Are you sure?\")'>Delete</button>
                    </form>
                </td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "No records found.";
}

$conn->close();
?>
