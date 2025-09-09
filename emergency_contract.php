<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database configuration
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

$message = "";
$error = "";

// Handle form submission for adding emergency contacts
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_contact'])) {
    $role = trim($_POST['role'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($role) || empty($name) || empty($phone)) {
        $error = "Please fill all required fields (Role, Name, Phone).";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO Emergency_Contact_List (Role, Name, Ph_no, Email) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $role, $name, $phone, $email);

            if ($stmt->execute()) {
                $message = "Emergency contact added successfully!";
                $contact_id = $stmt->insert_id;
                $user_id = $_SESSION['user_id'];

                // Auto-link if selected
                if (isset($_POST['link_to_me']) && $_POST['link_to_me'] === 'yes') {
                    $check_stmt = $conn->prepare("SELECT 1 FROM Can_Contact WHERE E_contract_id = ? AND V_UserID = ?");
                    $check_stmt->bind_param("ii", $contact_id, $user_id);
                    $check_stmt->execute();
                    $check_stmt->store_result();

                    if ($check_stmt->num_rows == 0) {
                        $link_stmt = $conn->prepare("INSERT INTO Can_Contact (E_contract_id, V_UserID) VALUES (?, ?)");
                        $link_stmt->bind_param("ii", $contact_id, $user_id);
                        $link_stmt->execute();
                        $link_stmt->close();
                        $message .= " Contact linked to your account.";
                    }
                    $check_stmt->close();
                }
            } else {
                $error = "Error adding emergency contact: " . $conn->error;
            }
            $stmt->close();
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Handle link contact
if (isset($_GET['link_contact'])) {
    $contact_id = (int)$_GET['contact_id'];
    $user_id = $_SESSION['user_id'];

    if ($contact_id > 0) {
        $check_stmt = $conn->prepare("SELECT 1 FROM Can_Contact WHERE E_contract_id = ? AND V_UserID = ?");
        $check_stmt->bind_param("ii", $contact_id, $user_id);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $error = "This contact is already linked to your account.";
        } else {
            $stmt = $conn->prepare("INSERT INTO Can_Contact (E_contract_id, V_UserID) VALUES (?, ?)");
            $stmt->bind_param("ii", $contact_id, $user_id);
            if ($stmt->execute()) {
                $message = "Emergency contact linked to your account!";
            } else {
                $error = "Error linking contact: " . $conn->error;
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
}

// Handle unlink contact
if (isset($_GET['unlink_contact'])) {
    $contact_id = (int)$_GET['contact_id'];
    $user_id = $_SESSION['user_id'];

    if ($contact_id > 0) {
        $stmt = $conn->prepare("DELETE FROM Can_Contact WHERE E_contract_id = ? AND V_UserID = ?");
        $stmt->bind_param("ii", $contact_id, $user_id);
        if ($stmt->execute()) {
            $message = "Emergency contact unlinked!";
        } else {
            $error = "Error unlinking contact: " . $conn->error;
        }
        $stmt->close();
    }
}

// Handle delete contact (Admin only)
if (isset($_GET['delete_id']) && ($_SESSION['is_admin'] ?? false)) {
    $delete_id = (int)$_GET['delete_id'];
    if ($delete_id > 0) {
        $stmt = $conn->prepare("DELETE FROM Emergency_Contact_List WHERE contract_id = ?");
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            $message = "Emergency contact deleted successfully!";
        } else {
            $error = "Error deleting contact: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch all emergency contacts
$emergency_contacts = [];
$sql = "SELECT contract_id AS contract_id, Role, Name, Ph_no, Email FROM Emergency_Contact_List ORDER BY Role, Name";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $emergency_contacts[] = $row;
    }
}

// Fetch user linked contacts
$user_linked_contacts = [];
$user_id = $_SESSION['user_id'];
$linked_sql = "SELECT c.contract_id AS contract_id, c.Role, c.Name, c.Ph_no, c.Email 
               FROM Emergency_Contact_List c 
               INNER JOIN Can_Contact cc ON c.contract_id = cc.E_contract_id 
               WHERE cc.V_UserID = ? 
               ORDER BY c.Role, c.Name";
$linked_stmt = $conn->prepare($linked_sql);
$linked_stmt->bind_param("i", $user_id);
$linked_stmt->execute();
$linked_result = $linked_stmt->get_result();
while ($row = $linked_result->fetch_assoc()) {
    $user_linked_contacts[] = $row;
}
$linked_stmt->close();

$total_contacts = count($emergency_contacts);
$user_contacts = count($user_linked_contacts);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Emergency Contacts - Disaster Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* --- Reset & Base --- */
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
        body{background:#f5f7fa;color:#333;line-height:1.6;}
        .container{width:90%;max-width:1200px;margin:auto;}

        /* --- Header --- */
        header{background:linear-gradient(135deg,#1a6fc4,#0c4a8f);color:white;padding:1rem 0;}
        .header-content{display:flex;justify-content:space-between;align-items:center;}
        .logo{display:flex;align-items:center;gap:10px;}
        .logo h1{font-size:1.5rem;}
        nav ul{display:flex;list-style:none;gap:20px;}
        nav a{color:white;text-decoration:none;padding:5px 10px;border-radius:4px;}
        nav a:hover{background:rgba(255,255,255,0.2);}

        /* --- Messages --- */
        .message{padding:12px;margin:10px 0;border-radius:5px;text-align:center;}
        .success{background:#d4edda;color:#155724;}
        .error{background:#f8d7da;color:#721c24;}

        /* --- Forms --- */
        .form-container{background:white;padding:20px;border-radius:10px;box-shadow:0 4px 10px rgba(0,0,0,0.1);margin:20px 0;}
        .form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:15px;}
        label{font-weight:600;}
        input,select{width:100%;padding:10px;border:1px solid #ddd;border-radius:5px;}
        .btn{background:#1a6fc4;color:white;padding:10px 20px;border:none;border-radius:5px;cursor:pointer;}
        .btn:hover{background:#0c4a8f;}
        .btn-sm{padding:5px 10px;font-size:0.85rem;}
        .btn-danger{background:#dc3545;}
        .btn-warning{background:#ffc107;color:black;}
        .btn-success{background:#28a745;}

        /* --- Tables --- */
        .table-container{background:white;padding:20px;border-radius:10px;box-shadow:0 4px 10px rgba(0,0,0,0.1);margin:20px 0;overflow-x:auto;}
        table{width:100%;border-collapse:collapse;}
        th,td{padding:10px;border-bottom:1px solid #ddd;text-align:left;}
        th{background:#f8f9fa;color:#1a6fc4;}

        /* --- Footer --- */
        footer{background:#2c3e50;color:white;padding:20px;margin-top:40px;}
        .footer-content{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;}
        .footer-bottom{text-align:center;margin-top:10px;border-top:1px solid rgba(255,255,255,0.2);padding-top:10px;}
    </style>
</head>
<body>
<header>
    <div class="container header-content">
        <div class="logo">
            <i class="fas fa-phone-alt"></i>
            <h1>Emergency Contacts</h1>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="volunteer.php">Volunteers</a></li>
                <li><a href="relief.php">Relief</a></li>
                <li><a href="desaster.php">Disasters</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </div>
</header>

<div class="container">
    <h2 style="margin:20px 0;color:#1a6fc4;">Emergency Contacts Management</h2>

    <?php if ($message): ?><div class="message success"><?= $message ?></div><?php endif; ?>
    <?php if ($error): ?><div class="message error"><?= $error ?></div><?php endif; ?>

    <!-- Add Contact Form -->
    <div class="form-container">
        <h3>Add New Emergency Contact</h3>
        <form method="POST">
            <div class="form-grid">
                <div>
                    <label>Role*</label>
                    <select name="role" required>
                        <option value="">Select</option>
                        <option value="Police">Police</option>
                        <option value="Fire">Fire</option>
                        <option value="Ambulance">Ambulance</option>
                        <option value="Hospital">Hospital</option>
                        <option value="Rescue">Rescue</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div><label>Name*</label><input type="text" name="name" required></div>
                <div><label>Phone*</label><input type="tel" name="phone" required></div>
                <div><label>Email</label><input type="email" name="email"></div>
            </div>
            <label><input type="checkbox" name="link_to_me" value="yes" checked> Link this contact to my account</label>
            <br><br>
            <button type="submit" name="add_contact" class="btn">Add Contact</button>
        </form>
    </div>

    <!-- User Contacts -->
    <div class="table-container">
        <h3>Your Linked Contacts</h3>
        <?php if (empty($user_linked_contacts)): ?>
            <p>No contacts linked yet.</p>
        <?php else: ?>
            <table>
                <tr><th>Role</th><th>Name</th><th>Phone</th><th>Email</th><th>Actions</th></tr>
                <?php foreach ($user_linked_contacts as $c): ?>
                    <tr>
                        <td><?= htmlspecialchars($c['Role']) ?></td>
                        <td><?= htmlspecialchars($c['Name']) ?></td>
                        <td><?= htmlspecialchars($c['Ph_no']) ?></td>
                        <td><?= $c['Email'] ?: "N/A" ?></td>
                        <td>
                            <a href="?unlink_contact&contact_id=<?= $c['contract_id'] ?>" class="btn btn-warning btn-sm">Unlink</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>

    <!-- All Contacts -->
    <div class="table-container">
        <h3>All Emergency Contacts</h3>
        <?php if (empty($emergency_contacts)): ?>
            <p>No contacts found.</p>
        <?php else: ?>
            <table>
                <tr><th>Role</th><th>Name</th><th>Phone</th><th>Email</th><th>Actions</th></tr>
                <?php foreach ($emergency_contacts as $c): 
                    $linked = false;
                    foreach ($user_linked_contacts as $uc) {
                        if ($uc['contract_id'] == $c['contract_id']) { $linked = true; break; }
                    }
                ?>
                    <tr>
                        <td><?= htmlspecialchars($c['Role']) ?></td>
                        <td><?= htmlspecialchars($c['Name']) ?></td>
                        <td><?= htmlspecialchars($c['Ph_no']) ?></td>
                        <td><?= $c['Email'] ?: "N/A" ?></td>
                        <td>
                            <?php if (!$linked): ?>
                                <a href="?link_contact&contact_id=<?= $c['contract_id'] ?>" class="btn btn-sm">Link</a>
                            <?php else: ?>
                                <span class="btn btn-success btn-sm">Linked</span>
                            <?php endif; ?>
                            <?php if ($_SESSION['is_admin'] ?? false): ?>
                                <a href="?delete_id=<?= $c['contract_id'] ?>" class="btn btn-danger btn-sm">Delete</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</div>

<footer>
    <div class="container footer-content">
        <div><h3>About</h3><p>Manage emergency contacts during disasters.</p></div>
        <div><h3>Quick Links</h3><p><a href="index.php" style="color:white;">Dashboard</a></p></div>
        <div><h3>Support</h3><p>Email: support@disasterms.com</p></div>
    </div>
    <div class="footer-bottom">&copy; <?= date("Y") ?> Disaster Management System</div>
</footer>
</body>
</html>
