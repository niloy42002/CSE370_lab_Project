<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database config
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "disaster management system";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
$error = "";

// Handle new donation submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_donation'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $item = trim($_POST['item']);
    $spend = trim($_POST['spend']);
    $where = trim($_POST['where']);
    $volunteer_id = (int)$_POST['volunteer_id'];

    if (empty($name) || empty($phone) || empty($item) || empty($spend) || empty($where) || $volunteer_id <= 0) {
        $error = "Please fill all fields.";
    } else {
        $stmt = $conn->prepare("INSERT INTO donation_tracking (name, phone, item, spend, `where`, volunteer_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssi", $name, $phone, $item, $spend, $where, $volunteer_id);
        if ($stmt->execute()) {
            $message = "Donation record added successfully!";
        } else {
            $error = "Error: " . $conn->error;
        }
        $stmt->close();
    }
}

// Handle delete donation
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM donation_tracking WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $message = "Donation record deleted.";
    } else {
        $error = "Error deleting donation.";
    }
    $stmt->close();
}

// Fetch donations with volunteer + user info
$sql = "SELECT dt.id, dt.name, dt.phone, dt.item, dt.spend, dt.`where`,
               u.First_name, u.Last_name
        FROM donation_tracking dt
        JOIN Volunteer v ON dt.volunteer_id = v.volunteer_id
        JOIN Users u ON v.user_id = u.User_id
        ORDER BY dt.id DESC";
$result = $conn->query($sql);

// Fetch volunteers for dropdown
$volunteers = $conn->query("SELECT v.volunteer_id, u.First_name, u.Last_name 
                            FROM Volunteer v
                            JOIN Users u ON v.user_id = u.User_id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Donation Tracking</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; background:#f5f7fa; margin:0; }
        header { background: linear-gradient(135deg,#1a6fc4,#0c4a8f); color:white; padding:1rem; }
        nav ul { list-style:none; display:flex; gap:20px; }
        nav a { color:white; text-decoration:none; }
        .container { width:90%; max-width:1200px; margin:20px auto; }
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
    <div class="container header-content">
        <div class="logo">
            <i class="fas fa-hand-holding-usd"></i>
            <h1>Donation Tracking</h1>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="desaster.php">Disasters</a></li>
                <li><a href="relief.php">Relief</a></li>
                <li><a href="volunteer.php">Volunteers</a></li>
                <li><a href="missing_person.php">Missing Persons</a></li>
                <li><a href="donor.php">Donors</a></li>
                <li><a href="donation_tracking.php">Donations</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </div>
</header>

<div class="container">
    <h2>Donation Records</h2>

    <?php if ($message): ?><p style="color:green;"><?php echo $message; ?></p><?php endif; ?>
    <?php if ($error): ?><p style="color:red;"><?php echo $error; ?></p><?php endif; ?>

    <table>
        <tr>
            <th>ID</th>
            <th>Donor Name</th>
            <th>Phone</th>
            <th>Item</th>
            <th>Spend</th>
            <th>Where</th>
            <th>Handled By</th>
            <th>Actions</th>
        </tr>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['phone']) ?></td>
            <td><?= htmlspecialchars($row['item']) ?></td>
            <td><?= htmlspecialchars($row['spend']) ?></td>
            <td><?= htmlspecialchars($row['where']) ?></td>
            <td><?= htmlspecialchars($row['First_name'] . " " . $row['Last_name']) ?></td>
            <td>
                <a href="?delete_id=<?= $row['id'] ?>" onclick="return confirm('Delete this donation record?')" style="color:red;">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>

    <div class="form-box">
        <h2>Add New Donation Record</h2>
        <form method="POST">
            <input type="text" name="name" placeholder="Donor Name" required>
            <input type="text" name="phone" placeholder="Phone" required>
            <input type="text" name="item" placeholder="Donated Item" required>
            <input type="text" name="spend" placeholder="Spend Amount" required>
            <input type="text" name="where" placeholder="Where Used / Location" required>

            <label>Assign Volunteer</label>
            <select name="volunteer_id" required>
                <?php while($v = $volunteers->fetch_assoc()): ?>
                <option value="<?= $v['volunteer_id'] ?>">
                    <?= $v['First_name'] . " " . $v['Last_name'] ?>
                </option>
                <?php endwhile; ?>
            </select>

            <button type="submit" name="add_donation">Add Donation</button>
        </form>
    </div>
</div>
</body>
</html>

<?php $conn->close(); ?>
