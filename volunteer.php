<?php
// Database config
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "disaster management system";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle new volunteer submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    $designation = $_POST['designation'];
    $location = $_POST['location'];
    $status = $_POST['status'];

    $sql = "INSERT INTO Volunteer (user_id, designation, location, status) 
            VALUES ('$user_id', '$designation', '$location', '$status')";
    $conn->query($sql);
}

// Fetch all volunteers with user info
$sql = "SELECT v.volunteer_id, v.designation, v.location, v.status,
               u.First_name, u.Last_name, u.Email 
        FROM Volunteer v 
        JOIN Users u ON v.user_id = u.User_id
        ORDER BY v.volunteer_id DESC";
$result = $conn->query($sql);

// Fetch users for dropdown
$users = $conn->query("SELECT User_id, First_name, Last_name FROM Users");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Volunteers</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; background:#f5f7fa; margin:0; }
        header { background: linear-gradient(135deg,#1a6fc4,#0c4a8f); color:white; padding:1rem; }
        nav ul { list-style:none; display:flex; gap:20px; }
        nav a { color:white; text-decoration:none; }
        .container { width:90%; max-width:1100px; margin:20px auto; }
        h2 { color:#1a6fc4; }
        table { width:100%; border-collapse: collapse; margin-top:20px; background:white; }
        th, td { padding:12px; border:1px solid #ddd; text-align:center; }
        th { background:#1a6fc4; color:white; }
        .form-box { background:white; padding:20px; margin-top:30px; border-radius:8px; box-shadow:0 3px 10px rgba(0,0,0,0.1); }
        input, select, button { padding:10px; margin:10px 0; width:100%; }
        button { background:#1a6fc4; color:white; border:none; cursor:pointer; }
        button:hover { background:#0c4a8f; }
    </style>
</head>
<body>

<header>
    <div class="container">
        <h1><i class="fas fa-users"></i> Volunteer Management</h1>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="relief.php">Relief</a></li>
                <li><a href="can_contract.php">Can Contact</a></li>
                <li><a href="volunteer.php">Volunteers</a></li>
                <li><a href="donates.php">Donations</a></li>
            </ul>
        </nav>
    </div>
</header>

<div class="container">
    <h2>Registered Volunteers</h2>

    <table>
        <tr>
            <th>ID</th>
            <th>User Name</th>
            <th>Email</th>
            <th>Designation</th>
            <th>Location</th>
            <th>Status</th>
        </tr>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['volunteer_id'] ?></td>
            <td><?= $row['First_name'] . " " . $row['Last_name'] ?></td>
            <td><?= $row['Email'] ?></td>
            <td><?= $row['designation'] ?></td>
            <td><?= $row['location'] ?></td>
            <td><?= $row['status'] ?></td>
        </tr>
        <?php endwhile; ?>
    </table>

    <div class="form-box">
        <h2>Add New Volunteer</h2>
        <form method="POST">
            <label>Select User</label>
            <select name="user_id" required>
                <?php while($u = $users->fetch_assoc()): ?>
                <option value="<?= $u['User_id'] ?>">
                    <?= $u['First_name'] . " " . $u['Last_name'] ?>
                </option>
                <?php endwhile; ?>
            </select>

            <label>Designation</label>
            <input type="text" name="designation" placeholder="e.g. Doctor, Engineer" required>

            <label>Location</label>
            <input type="text" name="location" required>

            <label>Status</label>
            <select name="status">
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
            </select>

            <button type="submit">Add Volunteer</button>
        </form>
    </div>
</div>

</body>
</html>

<?php $conn->close(); ?>
