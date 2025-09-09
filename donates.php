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
$dbname = "disaster management system"; // 🚨 avoid spaces in DB name

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
$error = "";

// Handle new donation submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_donation'])) {
    $donor_id = (int)$_POST['donor_id'];
    $relief_id = (int)$_POST['relief_id'];
    $quantity = (int)$_POST['quantity'];
    $donate_date = date("Y-m-d");

    if ($donor_id <= 0 || $relief_id <= 0 || $quantity <= 0) {
        $error = "Please select valid donor, relief and enter quantity.";
    } else {
        $stmt = $conn->prepare("INSERT INTO Donates (donor_id, relief_id, quantity, donate_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $donor_id, $relief_id, $quantity, $donate_date);
        if ($stmt->execute()) {
            $message = "Donation recorded successfully!";
        } else {
            $error = "Error: " . $conn->error;
        }
        $stmt->close();
    }
}

// Handle delete donation
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM Donates WHERE donate_id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $message = "Donation deleted successfully.";
    } else {
        $error = "Error deleting donation.";
    }
    $stmt->close();
}

// Fetch donations with donor + relief info
$sql = "SELECT dn.donate_id, dn.quantity, dn.donate_date, 
               u.First_name, u.Last_name, r.Resource_type
        FROM Donates dn
        JOIN Donor d ON dn.donor_id = d.donor_id
        JOIN Users u ON d.user_id = u.User_id
        JOIN Relief r ON dn.relief_id = r.Relief_id
        ORDER BY dn.donate_id DESC";
$result = $conn->query($sql);

// Fetch donors + reliefs for dropdowns
$donors = $conn->query("SELECT d.donor_id, u.First_name, u.Last_name 
                        FROM Donor d 
                        JOIN Users u ON d.user_id = u.User_id
                        ORDER BY d.donor_id DESC");
$reliefs = $conn->query("SELECT Relief_id, Resource_type FROM Relief ORDER BY Relief_id DESC");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Donation Management</title>
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
            <h1>Donation Management</h1>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="desaster.php">Disasters</a></li>
                <li><a href="relief.php">Relief</a></li>
                <li><a href="volunteer.php">Volunteers</a></li>
                <li><a href="missing_person.php">Missing Persons</a></li>
                <li><a href="donor.php">Donors</a></li>
                <li><a href="donates.php">Donations</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </div>
</header>

<div class="container">
    <h2>Recorded Donations</h2>

    <?php if ($message): ?><p style="color:green;"><?php echo $message; ?></p><?php endif; ?>
    <?php if ($error): ?><p style="color:red;"><?php echo $error; ?></p><?php endif; ?>

    <table>
        <tr>
            <th>ID</th>
            <th>Donor</th>
            <th>Relief Type</th>
            <th>Quantity</th>
            <th>Date</th>
            <th>Actions</th>
        </tr>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['donate_id'] ?></td>
                <td><?= htmlspecialchars($row['First_name']." ".$row['Last_name']) ?></td>
                <td><?= htmlspecialchars($row['Resource_type']) ?></td>
                <td><?= htmlspecialchars($row['quantity']) ?></td>
                <td><?= htmlspecialchars($row['donate_date']) ?></td>
                <td>
                    <a href="?delete_id=<?= $row['donate_id'] ?>" onclick="return confirm('Delete this donation?')" style="color:red;">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6">No donations recorded yet.</td></tr>
        <?php endif; ?>
    </table>

    <div class="form-box">
        <h2>Add New Donation</h2>
        <form method="POST">
            <label>Donor</label>
            <select name="donor_id" required>
                <option value="">Select Donor</option>
                <?php while($d = $donors->fetch_assoc()): ?>
                <option value="<?= $d['donor_id'] ?>">
                    <?= $d['First_name'] . " " . $d['Last_name'] ?>
                </option>
                <?php endwhile; ?>
            </select>

            <label>Relief Resource</label>
            <select name="relief_id" required>
                <option value="">Select Relief</option>
                <?php while($r = $reliefs->fetch_assoc()): ?>
                <option value="<?= $r['Relief_id'] ?>">
                    <?= $r['Resource_type'] ?>
                </option>
                <?php endwhile; ?>
            </select>

            <label>Quantity</label>
            <input type="number" name="quantity" min="1" required>

            <button type="submit" name="add_donation">Record Donation</button>
        </form>
    </div>
</div>
</body>
</html>
