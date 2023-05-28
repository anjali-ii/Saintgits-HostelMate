<?php
session_start();

// Retrieve the stf_id from the session variable
$stf_id = $_SESSION['stf_id'];

// Assuming you have established a database connection
$conn = new mysqli('localhost', 'root', '', 'hostel');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch the name from the staff_details table based on the stf_id
$query = "SELECT name FROM staff_details WHERE stf_id = '$stf_id'";
$result = $conn->query($query);

if ($result->num_rows == 1) {
    // Name found, fetch and store it in a variable
    $row = $result->fetch_assoc();
    $name = $row['name'];
} else {
    // Name not found, handle the error
    $name = "Unknown";
}

// Get the current date
$currentDate = date('Y-m-d');

// Fetch all student IDs from the std_details table
$query = "SELECT std_id FROM std_details";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    // Loop through each student ID and insert into the attendance table if an entry doesn't already exist for the current date
    while ($row = $result->fetch_assoc()) {
        $std_id = $row['std_id'];

        // Check if an entry already exists for the current date and the student ID
        $existingQuery = "SELECT * FROM attendance WHERE std_id = '$std_id' AND date = '$currentDate'";
        $existingResult = $conn->query($existingQuery);

        if ($existingResult->num_rows == 0) {
            // Insert the std_id, current date, and status into the attendance table
            $insertQuery = "INSERT INTO attendance (std_id, date, status) VALUES ('$std_id', '$currentDate', 'not_marked')";
            $conn->query($insertQuery);
        }
    }
}

// Update the attendance status for students who are absent
$updateQuery = "UPDATE attendance SET status = 'Absent' WHERE std_id IN (SELECT std_id FROM leave_history WHERE CURDATE() >= from_date AND CURDATE() < to_date AND status='accepted') ";

// Execute the update query
$conn->query($updateQuery);

$absentQuery = "
SELECT s.room_no,s.name,a.status
FROM std_details s
INNER JOIN attendance a ON s.std_id = a.std_id
WHERE a.status = 'Absent'
ORDER BY s.room_no ASC";
$absentResult = $conn->query($absentQuery);

$presentQuery = "
SELECT s.room_no,s.name,a.status,s.std_id
FROM std_details s
INNER JOIN attendance a ON s.std_id = a.std_id
WHERE a.status != 'Absent' and a.status != 'Present'
ORDER BY s.room_no ASC";
$presentResult = $conn->query($presentQuery);
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="style3.css">
</head>
<body>
    <div class="row">
        <div class="stdhead">
            <h3 class="stdtag">SaintgitsHostelMate</h3>
        </div>
    </div>
    <div class="row">
        <div class="stfreg1">
            <h3 class="stdtag">Attendance</h3>
        </div>
    </div>
    <div class="row">
        <div class="col-3">
            <img src="icon1.png" class="img-fluid1">
            <h5 style="padding-left:27%; color: rgb(32, 32, 50); padding-top: 1%; font-weight: bold; background: whitesmoke;"><?php echo $name; ?></h5>

            <form action="staffhome.php">
                <button class="stfbtn" style="color: rgb(32, 32, 50);">Home</button>
            </form>
            <form action="stfprofile.php">
                <button class="stfbtn" style="color: rgb(32, 32, 50);">Profile</button>
            </form>
            <form action="stfleave.php">
                <button class="stfbtn" style="color: rgb(32, 32, 50);">Leave Request</button>
            </form>
            <form action="stfatdnce.php">
                <button class="stfbtn" style="color: rgb(32, 32, 50);">Attendance</button>
            </form>
            <form action="stfreport.php">
                <button class="stfbtn" style="color: rgb(32, 32, 50);">Report</button>
            </form>
            <form action="stfstock.php">
                <button class="stfbtn" style="color: rgb(32, 32, 50);">Stock</button>
            </form>
            <form action="stfremainder.php">
                <button class="stfbtn" style="color: rgb(32, 32, 50);">Remainder</button>
            </form>
        </div>

        <div class="col-9">
            <h3 style="color: rgb(33, 33, 74); padding-left: 35%; background-color: whitesmoke;">Absentees</h3>
            <br>
            <?php
                if ($absentResult->num_rows > 0) {
                    echo '<table class=table table stripped>
                        <tr>
                            <th>Room No</th>
                            <th>Name</th>
                            <th>Status</th>
                        </tr>';
                    while ($row = $absentResult->fetch_assoc()) {
                        $roomNo = $row['room_no'];
                        $name = $row['name'];
                        $status = $row['status'];

                        echo '<tr>';
                        echo "<td>$roomNo</td>";
                        echo "<td>$name</td>";
                        echo "<td>$status</td>";
                        echo '</tr>';
                    }
                    echo '</table>';
                } else {
                    echo "<p>No students are absent.</p>";
                }
            ?>
            <h3 style="color: rgb(33, 33, 74); padding-left: 35%; background-color: whitesmoke;">Attendance</h3>
            <br>
            <?php
                if ($presentResult->num_rows > 0) {
                    echo '<form action="update_attendance.php" method="POST">';
                    echo '<table class="table table-stripped">';
                    echo '<tr>
                            <th>Room No</th>
                            <th>Name</th>
                            <th>Present</th>
                        </tr>';

                    while ($row = $presentResult->fetch_assoc()) {
                        $roomNo = $row['room_no'];
                        $name = $row['name'];
                        $stdId = $row['std_id'];
                        echo '<tr>';
                        echo "<td>$roomNo</td>";
                        echo "<td>$name</td>";
                        echo "<td><input type='checkbox' name='present[]' value='$stdId'></td>";
                        echo '</tr>';
                    }

                    echo '</table>';
                    echo '<input type="submit" style="color:white; margin-left: 40%; background-color: rgb(33, 33, 74); width: 15%;" value="Submit">';
                    echo '</form>';
                } else {
                    echo "<p>No students are present.</p>";
                }
            ?>

        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
