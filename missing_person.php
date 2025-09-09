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

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_missing'])) {
    $name = trim($_POST['name']);
    $last_seen = trim($_POST['last_seen']);
    $status = $_POST['status'];
    $photo_url = trim($_POST['photo_url']);
    $report_date = $_POST['report_date'];
    $disaster_id = !empty($_POST['disaster_id']) ? (int)$_POST['disaster_id'] : NULL;
    $reporter_id = $_SESSION['user_id'];

    if (empty($name) || empty($last_seen)) {
        $error = "Please fill required fields (Name, Last Seen Location).";
    } else {
        $stmt = $conn->prepare("INSERT INTO Missing_Person (Name, Last_seen_location, Status, Photo_url, Report_date, Devent_id, Reported_by_v_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssi", $name, $last_seen, $status, $photo_url, $report_date, $disaster_id, $reporter_id);
        if ($stmt->execute()) {
            $message = "Missing person reported successfully!";
        } else {
            $error = "Error: " . $conn->error;
        }
        $stmt->close();
    }
}

// Update status
if (isset($_GET['update_status'])) {
    $missing_id = (int)$_GET['missing_id'];
    $new_status = $_GET['new_status'];
    if ($missing_id > 0 && in_array($new_status, ['Missing', 'Found'])) {
        $stmt = $conn->prepare("UPDATE Missing_Person SET Status = ? WHERE Missing_id = ?");
        $stmt->bind_param("si", $new_status, $missing_id);
        $stmt->execute();
        $message = "Status updated!";
        $stmt->close();
    }
}

// Delete report
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM Missing_Person WHERE Missing_id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $message = "Missing person record deleted.";
    $stmt->close();
}

// Fetch all missing persons
$sql = "SELECT m.*, d.Title AS Disaster, u.First_name, u.Last_name 
        FROM Missing_Person m
        LEFT JOIN Disaster d ON m.Devent_id = d.Event_id
        LEFT JOIN Users u ON m.Reported_by_v_id = u.User_id
        ORDER BY m.Report_date DESC";
$result = $conn->query($sql);

// Fetch disasters for dropdown
$disasters = $conn->query("SELECT Event_id, Title FROM Disaster ORDER BY Date_reported DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Missing Persons</title>
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
        input, select, textarea, button { padding:10px; margin:10px 0; width:100%; }
        button { background:#1a6fc4; color:white; border:none; cursor:pointer; }
        button:hover { background:#0c4a8f; }
        img { max-width:80px; border-radius:5px; }
    </style>
</head>
<body>
<header>
    <div class="container header-content">
        <div class="logo">
            <i class="fas fa-users"></i>
            <h1>Missing Persons</h1>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="desaster.php">Disasters</a></li>
                <li><a href="relief.php">Relief</a></li>
                <li><a href="volunteer.php">Volunteers</a></li>
                <li><a href="can_contract.php">Contacts</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </div>
</header>

<div class="container">
    <h2>Reported Missing Persons</h2>

    <?php if ($message): ?><p style="color:green;"><?php echo $message; ?></p><?php endif; ?>
    <?php if ($error): ?><p style="color:red;"><?php echo $error; ?></p><?php endif; ?>

    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Last Seen</th>
            <th>Status</th>
            <th>Photo</th>
            <th>Reported Date</th>
            <th>Disaster</th>
            <th>Reported By</th>
            <th>Actions</th>
        </tr>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['Missing_id'] ?></td>
            <td><?= htmlspecialchars($row['Name']) ?></td>
            <td><?= htmlspecialchars($row['Last_seen_location']) ?></td>
            <td><?= $row['Status'] ?></td>
            <td><?php if($row['Photo_url']): ?><img src="<?= $row['Photo_url'] ?>" alt="Photo"><?php else: ?>N/A<?php endif; ?></td>
            <td><?= $row['Report_date'] ?></td>
            <td><?= $row['Disaster'] ?: 'N/A' ?></td>
            <td><?= $row['First_name'] . " " . $row['Last_name'] ?></td>
            <td>
                <a href="?update_status&missing_id=<?= $row['Missing_id'] ?>&new_status=Found" class="btn">Mark Found</a>
                <a href="?delete_id=<?= $row['Missing_id'] ?>" class="btn" style="background:red;" onclick="return confirm('Delete this record?')">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>

    <div class="form-box">
        <h2>Report Missing Person</h2>
        <form method="POST">
            <input type="text" name="name" placeholder="Name" required>
            <input type="text" name="last_seen" placeholder="Last Seen Location" required>
            <select name="status">
                <option value="Missing" selected>Missing</option>
                <option value="Found">Found</option>
            </select>
            <input type="text" name="photo_url" placeholder="Photo URL">
            <input type="date" name="report_date" value="<?= date('Y-m-d') ?>" required>
            <label>Related Disaster</label>
            <select name="disaster_id">
                <option value="">None</option>
                <?php while($d = $disasters->fetch_assoc()): ?>
                <option value="<?= $d['Event_id'] ?>"><?= $d['Title'] ?></option>
                <?php endwhile; ?>
            </select>
            <button type="submit" name="add_missing">Report Missing Person</button>
        </form>
    </div>
</div>
</body>
</html>

<?php $conn->close(); ?>
