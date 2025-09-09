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

// Handle new relationship submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $contact_id = $_POST['contact_id'];
    $user_id = $_POST['user_id'];

    $sql = "INSERT INTO Can_Contact (E_contract_id, V_UserID) VALUES ('$contact_id', '$user_id')";
    $conn->query($sql);
}

// Fetch joined Can_Contact data
$sql = "SELECT c.E_contract_id, c.V_UserID, e.Name AS contact_name, e.Role, u.First_name, u.Last_name, u.Email
        FROM Can_Contact c
        JOIN Emergency_Contact_List e ON c.E_contract_id = e.Contract_id
        JOIN Users u ON c.V_UserID = u.User_id
        ORDER BY c.E_contract_id";
$result = $conn->query($sql);

// Fetch data for dropdowns
$contacts = $conn->query("SELECT Contract_id, Name, Role FROM Emergency_Contact_List");
$users = $conn->query("SELECT User_id, First_name, Last_name FROM Users");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Can Contact</title>
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
        select, button { padding:10px; margin:10px 0; width:100%; }
        button { background:#1a6fc4; color:white; border:none; cursor:pointer; }
        button:hover { background:#0c4a8f; }
    </style>
</head>
<body>

<header>
    <div class="container">
        <h1><i class="fas fa-address-book"></i> Can Contact</h1>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="relief.php">Relief</a></li>
                <li><a href="can_contact.php">Can Contact</a></li>
                <li><a href="#">Disasters</a></li>
                <li><a href="#">Missing Persons</a></li>
            </ul>
        </nav>
    </div>
</header>

<div class="container">
    <h2>Linked Emergency Contacts & Users</h2>

    <table>
        <tr>
            <th>Contact ID</th>
            <th>Contact Name</th>
            <th>Role</th>
            <th>User ID</th>
            <th>User Name</th>
            <th>User Email</th>
        </tr>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['E_contract_id'] ?></td>
            <td><?= $row['contact_name'] ?></td>
            <td><?= $row['Role'] ?></td>
            <td><?= $row['V_UserID'] ?></td>
            <td><?= $row['First_name'] . " " . $row['Last_name'] ?></td>
            <td><?= $row['Email'] ?></td>
        </tr>
        <?php endwhile; ?>
    </table>

    <div class="form-box">
        <h2>Link New Contact with User</h2>
        <form method="POST">
            <label>Select Emergency Contact</label>
            <select name="contact_id" required>
                <?php while($c = $contacts->fetch_assoc()): ?>
                <option value="<?= $c['Contract_id'] ?>">
                    <?= $c['Name'] ?> (<?= $c['Role'] ?>)
                </option>
                <?php endwhile; ?>
            </select>

            <label>Select User</label>
            <select name="user_id" required>
                <?php while($u = $users->fetch_assoc()): ?>
                <option value="<?= $u['User_id'] ?>">
                    <?= $u['First_name'] . " " . $u['Last_name'] ?>
                </option>
                <?php endwhile; ?>
            </select>

            <button type="submit">Add Relationship</button>
        </form>
    </div>
</div>

</body>
</html>

<?php $conn->close(); ?>
