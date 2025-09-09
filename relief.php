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

// Initialize variables
$message = "";
$error = "";

// Handle form submission for adding relief resources
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_relief'])) {
    // Get form data with proper validation
    $resource_type = isset($_POST['resource_type']) ? trim($_POST['resource_type']) : '';
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
    $urgency_level = isset($_POST['urgency_level']) ? trim($_POST['urgency_level']) : '';
 

    // Validate required fields
    if (empty($resource_type) || $quantity <= 0 || empty($urgency_level)) {
        $error = "Please fill all required fields with valid values.";
    } else {
        try {
            // Check if description column exists, if not, don't include it
            $column_check = $conn->query("SHOW COLUMNS FROM Relief LIKE 'description'");
            $has_description = ($column_check->num_rows > 0);
            
            if ($has_description) {
                $stmt = $conn->prepare("INSERT INTO Relief (Resource_type, T_Quantity, Urgency_level, description) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("siss", $resource_type, $quantity, $urgency_level, $description);
            } else {
                $stmt = $conn->prepare("INSERT INTO Relief (Resource_type, T_Quantity, Urgency_level) VALUES (?, ?, ?)");
                $stmt->bind_param("sis", $resource_type, $quantity, $urgency_level);
            }
            
            if ($stmt->execute()) {
                $message = "Relief resource added successfully!";
            } else {
                $error = "Error adding relief resource: " . $conn->error;
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
            $stmt = $conn->prepare("DELETE FROM Relief WHERE Relief_id = ?");
            $stmt->bind_param("i", $delete_id);
            if ($stmt->execute()) {
                $message = "Relief resource deleted successfully!";
            } else {
                $error = "Error deleting relief resource: " . $conn->error;
            }
            $stmt->close();
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    } else {
        $error = "Invalid resource ID";
    }
}

// Get all relief resources
$relief_resources = [];
$sql = "SELECT * FROM Relief ORDER BY 
        CASE Urgency_level 
            WHEN 'Critical' THEN 1
            WHEN 'High' THEN 2
            WHEN 'Medium' THEN 3
            WHEN 'Low' THEN 4
            ELSE 5
        END, Resource_type";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $relief_resources[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relief Management - Disaster Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* Header Styles */
        header {
            background: linear-gradient(135deg, #1a6fc4 0%, #0c4a8f 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo i {
            font-size: 2.5rem;
        }
        
        .logo h1 {
            font-size: 1.8rem;
            font-weight: 700;
        }
        
        nav ul {
            display: flex;
            list-style: none;
            gap: 25px;
        }
        
        nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 5px 10px;
            border-radius: 4px;
            transition: background 0.3s;
        }
        
        nav a:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        /* Main Content */
        .main-content {
            padding: 2rem 0;
        }
        
        .page-title {
            text-align: center;
            margin-bottom: 2rem;
            color: #1a6fc4;
        }
        
        .page-title h2 {
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
        }
        
        /* Messages */
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Forms */
        .form-container {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #444;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #1a6fc4;
            outline: none;
        }
        
        .btn {
            background: #1a6fc4;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #0c4a8f;
        }
        
        .btn-danger {
            background: #dc3545;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-sm {
            padding: 8px 15px;
            font-size: 0.9rem;
        }
        
        /* Tables */
        .table-container {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #1a6fc4;
        }
        
        tr:hover {
            background-color: #f8f9fa;
        }
        
        .urgency-critical {
            background-color: #ffebee;
            color: #c62828;
            font-weight: 600;
        }
        
        .urgency-high {
            background-color: #fff3e0;
            color: #ef6c00;
        }
        
        .urgency-medium {
            background-color: #fff9c4;
            color: #f57f17;
        }
        
        .urgency-low {
            background-color: #e8f5e8;
            color: #2e7d32;
        }
        
        .actions {
            display: flex;
            gap: 10px;
        }
        
        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1a6fc4;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #666;
            font-weight: 600;
        }
        
        /* Footer */
        footer {
            background: #2c3e50;
            color: white;
            padding: 2rem 0 1rem;
            margin-top: 3rem;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 2rem;
        }
        
        .footer-section h3 {
            font-size: 1.3rem;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 10px;
        }
        
        .footer-section h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 2px;
            background: #1a6fc4;
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
            }
            
            nav ul {
                gap: 15px;
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container header-content">
            <div class="logo">
                <i class="fas fa-box-open"></i>
                <h1>Relief Management</h1>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="desaster.php">Disasters</a></li>
                    <li><a href="missing_person.php">Missing Persons</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container main-content">
        <div class="page-title">
            <h2>Relief Resources Management</h2>
            <p>Manage and track relief resources for disaster response</p>
        </div>

        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Stats Overview -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($relief_resources); ?></div>
                <div class="stat-label">Total Resources</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php
                    $critical = array_filter($relief_resources, function($item) {
                        return isset($item['Urgency_level']) && $item['Urgency_level'] === 'Critical';
                    });
                    echo count($critical);
                    ?>
                </div>
                <div class="stat-label">Critical Needs</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php
                    $total_quantity = 0;
                    foreach ($relief_resources as $resource) {
                        if (isset($resource['T_Quantity'])) {
                            $total_quantity += (int)$resource['T_Quantity'];
                        }
                    }
                    echo $total_quantity;
                    ?>
                </div>
                <div class="stat-label">Total Items</div>
            </div>
        </div>

        <!-- Add Relief Resource Form -->
        <div class="form-container">
            <h3 style="margin-bottom: 1.5rem; color: #1a6fc4;">Add New Relief Resource</h3>
            <form method="POST" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="resource_type">Resource Type *</label>
                        <select id="resource_type" name="resource_type" required>
                            <option value="">Select Resource Type</option>
                            <option value="Food">Food</option>
                            <option value="Water">Water</option>
                            <option value="Medicine">Medicine</option>
                            <option value="Clothing">Clothing</option>
                            <option value="Shelter">Shelter</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="quantity">Quantity *</label>
                        <input type="number" id="quantity" name="quantity" min="1" required>
                    </div>

                    <div class="form-group">
                        <label for="urgency_level">Urgency Level *</label>
                        <select id="urgency_level" name="urgency_level" required>
                            <option value="">Select Urgency Level</option>
                            <option value="Critical">Critical</option>
                            <option value="High">High</option>
                            <option value="Medium">Medium</option>
                            <option value="Low">Low</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3" placeholder="Additional details about the resource"></textarea>
                </div>

                <button type="submit" name="add_relief" class="btn">Add Relief Resource</button>
            </form>
        </div>

        <!-- Relief Resources Table -->
        <div class="table-container">
            <h3 style="margin-bottom: 1.5rem; color: #1a6fc4;">Current Relief Resources</h3>
            
            <?php if (empty($relief_resources)): ?>
                <p style="text-align: center; color: #666; padding: 2rem;">No relief resources found. Add some resources using the form above.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Resource ID</th>
                            <th>Resource Type</th>
                            <th>Quantity</th>
                            <th>Urgency Level</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($relief_resources as $resource): ?>
                            <tr>
                                <td><?php echo isset($resource['Relief_id']) ? htmlspecialchars($resource['Relief_id']) : 'N/A'; ?></td>
                                <td><?php echo isset($resource['Resource_type']) ? htmlspecialchars($resource['Resource_type']) : 'N/A'; ?></td>
                                <td><?php echo isset($resource['T_Quantity']) ? htmlspecialchars($resource['T_Quantity']) : 'N/A'; ?></td>
                                <td>
                                    <?php if (isset($resource['Urgency_level'])): ?>
                                        <span class="urgency-<?php echo strtolower($resource['Urgency_level']); ?>">
                                            <?php echo htmlspecialchars($resource['Urgency_level']); ?>
                                        </span>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td><?php echo isset($resource['description']) ? htmlspecialchars($resource['description']) : 'N/A'; ?></td>
                                <td class="actions">
                                    <?php if (isset($resource['Relief_id'])): ?>
                                        <a href="edit_relief.php?id=<?php echo $resource['Relief_id']; ?>" class="btn btn-sm">Edit</a>
                                        <a href="?delete_id=<?php echo $resource['Relief_id']; ?>" class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Are you sure you want to delete this resource?')">Delete</a>
                                    <?php else: ?>
                                        <span>N/A</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>About Relief Management</h3>
                    <p>Coordinate and manage relief resources for effective disaster response and recovery operations.</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Actions</h3>
                    <p><a href="index.php" style="color: white;">Dashboard</a></p>
                    <p><a href="disasters.php" style="color: white;">View Disasters</a></p>
                    <p><a href="missing_persons.php" style="color: white;">Missing Persons</a></p>
                </div>
                <div class="footer-section">
                    <h3>Emergency Contacts</h3>
                    <p><i class="fas fa-phone"></i> Emergency: 911</p>
                    <p><i class="fas fa-phone"></i> Relief Coordination: 1-800-HELP-NOW</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2023 Disaster Management System. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Add urgency level styling to select options
        document.getElementById('urgency_level').addEventListener('change', function() {
            this.className = 'urgency-' + this.value.toLowerCase();
        });

        // Show confirmation before deleting
        const deleteButtons = document.querySelectorAll('.btn-danger');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this resource?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>