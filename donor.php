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

// Handle new donor submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_donor'])) {
    $user_id = $_POST['user_id'];
    $email = trim($_POST['email']);

    if (empty($user_id) || empty($email)) {
        $error = "Please select a user and provide an email.";
    } else {
        $stmt = $conn->prepare("INSERT INTO Donor (user_id, email) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $email);
        if ($stmt->execute()) {
            $message = "Donor added successfully!";
        } else {
            $error = "Error: " . $conn->error;
        }
        $stmt->close();
    }
}

// Delete donor
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM Donor WHERE donor_id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $message = "Donor deleted successfully.";
    } else {
        $error = "Error deleting donor.";
    }
    $stmt->close();
}

// Fetch donors with user info
$sql = "SELECT d.donor_id, d.email, u.First_name, u.Last_name 
        FROM Donor d
        JOIN Users u ON d.user_id = u.User_id
        ORDER BY d.donor_id DESC";
$result = $conn->query($sql);

// Fetch users for dropdown
$users = $conn->query("SELECT User_id, First_name, Last_name, Email FROM Users");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Donors</title>
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
            <i class="fas fa-hand-holding-heart"></i>
            <h1>Donor Management</h1>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="desaster.php">Disasters</a></li>
                <li><a href="relief.php">Relief</a></li>
                <li><a href="volunteer.php">Volunteers</a></li>
                <li><a href="can_contract.php">Contacts</a></li>
                <li><a href="donates.php">Donations</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </div>
</header>

<div class="container">
    <h2>Registered Donors</h2>

    <?php if ($message): ?><p style="color:green;"><?php echo $message; ?></p><?php endif; ?>
    <?php if ($error): ?><p style="color:red;"><?php echo $error; ?></p><?php endif; ?>

    <table>
        <tr>
            <th>ID</th>
            <th>User</th>
            <th>Email</th>
            <th>Actions</th>
        </tr>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['donor_id'] ?></td>
            <td><?= htmlspecialchars($row['First_name'] . " " . $row['Last_name']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td>
                <a href="?delete_id=<?= $row['donor_id'] ?>" onclick="return confirm('Delete this donor?')" style="color:red;">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>

    <div class="form-box">
        <h2>Add New Donor</h2>
        <form method="POST">
            <label>Select User</label>
            <select name="user_id" required>
                <?php while($u = $users->fetch_assoc()): ?>
                <option value="<?= $u['User_id'] ?>">
                    <?= $u['First_name'] . " " . $u['Last_name'] ?> (<?= $u['Email'] ?>)
                </option>
                <?php endwhile; ?>
            </select>

            <label>Email</label>
            <input type="email" name="email" required>

            <button type="submit" name="add_donor">Add Donor</button>
        </form>
    </div>
</div>
</body>
</html>

<?php $conn->close(); ?>
