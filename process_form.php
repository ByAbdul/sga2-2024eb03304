<?php
// Database configuration
$host = "localhost";
$username = "root"; // Default Coursera Labs / Local user
$password = "";     // Default password
$dbname = "university_db";

// 1. Create Connection
$conn = new mysqli($host, $username, $password);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. Create Database if it doesn't exist (Requirement 1c)
$sql_db = "CREATE DATABASE IF NOT EXISTS $dbname";
$conn->query($sql_db);
$conn->select_db($dbname);

// 3. Create Tables (Requirement 1d)
// Create Department table
$conn->query("CREATE TABLE IF NOT EXISTS Department (
    department_id INT PRIMARY KEY,
    department_name VARCHAR(100) NOT NULL
)");

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
    FOREIGN KEY (student_id) REFERENCES Student(student_id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES Department(department_id) ON DELETE CASCADE
)");

// 4. Handle Form Submissions (Requirement 1b & 1e)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'];
    
    // Create Operation
    if ($action == "create") {
        $s_id = $_POST['student_id'];
        $name = $_POST['name'];
        $dob = $_POST['dob'];
        $gender = $_POST['gender'];
        $addr = $_POST['address'];
        $mob = $_POST['mobile'];
        $email = $_POST['email'];
        $d_id = $_POST['dept_id'];

        // Insert into Student table
        $stmt = $conn->prepare("INSERT INTO Student VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $s_id, $name, $dob, $gender, $addr, $mob, $email);
        $stmt->execute();

        // Insert into Mapping table
        $stmt_map = $conn->prepare("INSERT INTO Mapping VALUES (?, ?)");
        $stmt_map->bind_param("si", $s_id, $d_id);
        $stmt_map->execute();

        echo "Record added successfully! <a href='student_form.html'>Add another</a> | <a href='process_form.php'>View Records</a>";
    }

    // Delete Operation
    if ($action == "delete") {
        $id = $_POST['student_id'];
        $conn->query("DELETE FROM Student WHERE student_id = '$id'");
        header("Location: process_form.php");
    }
}

// 5. Read Operation: Displaying Records
echo "<h2>Student Records</h2>";
$result = $conn->query("SELECT s.*, d.department_name FROM Student s 
                        LEFT JOIN Mapping m ON s.student_id = m.student_id 
                        LEFT JOIN Department d ON m.department_id = d.department_id");

if ($result->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Email</th><th>Dept</th><th>Action</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>".$row['student_id']."</td>
                <td>".$row['name']."</td>
                <td>".$row['email']."</td>
                <td>".$row['department_name']."</td>
                <td>
                    <form method='POST' style='display:inline;'>
                        <input type='hidden' name='student_id' value='".$row['student_id']."'>
                        <input type='hidden' name='action' value='delete'>
                        <button type='submit'>Delete</button>
                    </form>
                </td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "0 results found.";
}

$conn->close();
?>
