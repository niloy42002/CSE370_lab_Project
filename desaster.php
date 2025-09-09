<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "disaster management system";

// connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$message = "";
$error = "";

// Handle form submission for adding disasters
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_disaster'])) {
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $category = isset($_POST['category']) ? trim($_POST['category']) : '';
    $affected_people = isset($_POST['affected_people']) ? (int)$_POST['affected_people'] : 0;
    $location_address = isset($_POST['location_address']) ? trim($_POST['location_address']) : '';
    $date_reported = isset($_POST['date_reported']) ? trim($_POST['date_reported']) : date('Y-m-d');
    $status = isset($_POST['status']) ? trim($_POST['status']) : 'Active';

    // Validate required fields
    if (empty($title) || empty($category) || empty($location_address)) {
        $error = "Please fill all required fields (Title, Category, Location Address).";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO Disaster (Title, Category, Affected_people, Location_address, Date_reported, Status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssisss", $title, $category, $affected_people, $location_address, $date_reported, $status);
            
            if ($stmt->execute()) {
                $message = "Disaster reported successfully!";
            } else {
                $error = "Error reporting disaster: " . $conn->error;
            }
            $stmt->close();
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Handle status update
if (isset($_GET['update_status'])) {
    $disaster_id = (int)$_GET['disaster_id'];
    $new_status = $_GET['new_status'];
    
    if ($disaster_id > 0 && in_array($new_status, ['Active', 'Resolved'])) {
        try {
            $stmt = $conn->prepare("UPDATE Disaster SET Status = ? WHERE Event_id = ?");
            $stmt->bind_param("si", $new_status, $disaster_id);
            if ($stmt->execute()) {
                $message = "Disaster status updated successfully!";
            } else {
                $error = "Error updating status: " . $conn->error;
            }
            $stmt->close();
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Handle delete action
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    if ($delete_id > 0) {
        try {
            $stmt = $conn->prepare("DELETE FROM Disaster WHERE Event_id = ?");
            $stmt->bind_param("i", $delete_id);
            if ($stmt->execute()) {
                $message = "Disaster record deleted successfully!";
            } else {
                $error = "Error deleting disaster record: " . $conn->error;
            }
            $stmt->close();
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    } else {
        $error = "Invalid disaster ID";
    }
}

// Get all disasters
$disasters = [];
$sql = "SELECT * FROM Disaster ORDER BY Date_reported DESC, Status";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $disasters[] = $row;
    }
}

// Get statistics
$total_disasters = count($disasters);
$active_disasters = 0;
$contained_disasters = 0;
$resolved_disasters = 0;
$total_affected = 0;

foreach ($disasters as $disaster) {
    $total_affected += (int)$disaster['Affected_people'];
    switch ($disaster['Status']) {
        case 'Active':
            $active_disasters++;
            break;
     
        case 'Resolved':
            $resolved_disasters++;
            break;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disaster Management - Disaster Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Keep your previous CSS (shortened for clarity) */
        body { font-family: 'Segoe UI', sans-serif; background:#f5f7fa; margin:0; }
        header { background: linear-gradient(135deg,#1a6fc4,#0c4a8f); color:white; padding:1rem 0; }
        nav ul { list-style:none; display:flex; gap:20px; }
        nav a { color:white; text-decoration:none; }
        .container { width:90%; max-width:1200px; margin:20px auto; }
        h2 { color:#1a6fc4; }
        table { width:100%; border-collapse: collapse; margin-top:20px; background:white; }
        th, td { padding:12px; border:1px solid #ddd; text-align:center; }
        th { background:#1a6fc4; color:white; }
        .btn { padding:8px 12px; border:none; border-radius:4px; cursor:pointer; }
        .btn-danger { background:#dc3545; color:white; }
        .btn-warning { background:#ffc107; }
        .btn-success { background:#28a745; color:white; }
    </style>
</head>
<body>
<header>
    <div class="container header-content">
        <div class="logo">
            <i class="fas fa-exclamation-triangle"></i>
            <h1>Disaster Management</h1>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="volunteer.php">Volunteers</a></li>
                <li><a href="relief.php">Relief</a></li>
                <li><a href="can_contract.php">Can Contact</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </div>
</header>

<div class="container">
    <h2>Disaster Management</h2>

    <?php if ($message): ?>
        <p style="color:green;"><?php echo $message; ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p style="color:red;"><?php echo $error; ?></p>
    <?php endif; ?>

    <h3>Report New Disaster</h3>
    <form method="POST" action="">
        <input type="text" name="title" placeholder="Title" required><br>
        <input type="text" name="category" placeholder="Category" required><br>
        <input type="number" name="affected_people" placeholder="Affected People"><br>
        <input type="text" name="location_address" placeholder="Location Address" required><br>
        <input type="date" name="date_reported" value="<?php echo date('Y-m-d'); ?>"><br>
        <select name="status" required>
            <option value="Active">Active</option>
            <option value="Resolved">Resolved</option>
        </select><br>
        <button type="submit" name="add_disaster" class="btn">Add Disaster</button>
    </form>

    <h3>Reported Disasters</h3>
    <table>
        <tr>
            <th>Title</th>
            <th>Category</th>
            <th>Affected</th>
            <th>Location</th>
            <th>Date Reported</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($disasters as $d): ?>
        <tr>
            <td><?php echo htmlspecialchars($d['Title']); ?></td>
            <td><?php echo htmlspecialchars($d['Category']); ?></td>
            <td><?php echo $d['Affected_people'] ?: 'N/A'; ?></td>
            <td><?php echo htmlspecialchars($d['Location_address']); ?></td>
            <td><?php echo htmlspecialchars($d['Date_reported']); ?></td>
            <td><?php echo htmlspecialchars($d['Status']); ?></td>
            <td>
                <a href="?update_status&disaster_id=<?php echo $d['Event_id']; ?>&new_status=Active" class="btn btn-danger btn-sm">Active</a>
                <a href="?update_status&disaster_id=<?php echo $d['Event_id']; ?>&new_status=Resolved" class="btn btn-success btn-sm">Resolved</a>
                <a href="?delete_id=<?php echo $d['Event_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this record?')">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>
