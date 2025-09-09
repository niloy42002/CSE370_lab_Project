<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "disaster management system";

// Initialize variables
$success_message = $error_message = "";
$first_name = $last_name = $email = $phone = $district = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Get form data
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $house_no = trim($_POST['house_no']);
    $road_no = trim($_POST['road_no']);
    $area = trim($_POST['area']);
    $district = trim($_POST['district']);
    $post_code = trim($_POST['post_code']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
     $is_admin = isset($_POST['is_admin']) ? 1 : 0;
  

    try {
        // Insert into Users table
        $stmt = $conn->prepare("INSERT INTO Users (first_name, last_name, phone, email, house_no, road_no, area, district, post_code, pass,is_admin) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssssi", $first_name, $last_name, $phone, $email, $house_no, $road_no, $area, $district, $post_code, $password,$is_admin);
        
        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;
            
            // If user is admin, insert into admin table (if exists)
            if ($is_admin) {
                $designation = trim($_POST['designation']);
                $stmt2 = $conn->prepare("INSERT INTO UserRoles (user_id, designation, additional_email) VALUES (?, ?, ?)");
                $stmt2->bind_param("iss", $user_id, $designation, $email);
                $stmt2->execute();
                $stmt2->close();
            }
            
            $success_message = "Registration successful! You can now login.";
            // Clear form fields
            $first_name = $last_name = $email = $phone = $district = "";
        } else {
            $error_message = "Error: " . $conn->error;
        }
        
        $stmt->close();
    } catch (Exception $e) {
        $error_message = "Registration failed: " . $e->getMessage();
    }
    
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Disaster Management System</title>
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
        
        /* Registration Form */
        .registration-section {
            padding: 3rem 0;
        }
        
        .form-container {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-title {
            text-align: center;
            margin-bottom: 2rem;
            color: #1a6fc4;
        }
        
        .form-title h2 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
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
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            border-color: #1a6fc4;
            outline: none;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkbox-group input {
            width: auto;
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
            display: block;
            width: 100%;
        }
        
        .btn:hover {
            background: #0c4a8f;
        }
        
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
        
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .login-link a {
            color: #1a6fc4;
            text-decoration: none;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        /* Footer */
        footer {
            background: #2c3e50;
            color: white;
            padding: 2rem 0 1rem;
            margin-top: 2rem;
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
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .header-content {
                flex-direction: column;
                gap: 15px;
            }
            
            nav ul {
                gap: 15px;
                flex-wrap: wrap;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container header-content">
            <div class="logo">
                <i class="fas fa-house-damage"></i>
                <h1>Disaster Management System</h1>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="index.php#features">Features</a></li>
                    <li><a href="index.php#stats">Statistics</a></li>
                    <li><a href="login.php">Login</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Registration Form -->
    <section class="registration-section">
        <div class="container">
            <div class="form-container">
                <div class="form-title">
                    <h2>Create Account</h2>
                    <p>Join our disaster management system to help your community</p>
                </div>

                <?php if ($success_message): ?>
                    <div class="message success"><?php echo $success_message; ?></div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="message error"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="first_name">First Name *</label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo $first_name; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo $last_name; ?>" required>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" value="<?php echo $email; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo $phone; ?>">
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="password">Password *</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="district">District *</label>
                            <input type="text" id="district" name="district" value="<?php echo $district; ?>" required>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="house_no">House Number</label>
                            <input type="text" id="house_no" name="house_no">
                        </div>
                        
                        <div class="form-group">
                            <label for="road_no">Road Number</label>
                            <input type="text" id="road_no" name="road_no">
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="area">Area</label>
                            <input type="text" id="area" name="area">
                        </div>
                        
                        <div class="form-group">
                            <label for="post_code">Postal Code</label>
                            <input type="text" id="post_code" name="post_code">
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="is_admin" name="is_admin" value="1">
                            <label for="is_admin">Register as Administrator</label>
                        </div>
                    </div>

                    <div class="form-group" id="designation-field" style="display: none;">
                        <label for="designation">Designation (for administrators)</label>
                        <input type="text" id="designation" name="designation" placeholder="e.g., System Admin, Manager">
                    </div>

                    <button type="submit" class="btn">Register Now</button>
                </form>

                <div class="login-link">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>About Us</h3>
                    <p>We provide a comprehensive disaster management system to help communities prepare for, respond to, and recover from emergencies.</p>
                </div>
                <div class="footer-section">
                    <h3>Contact Info</h3>
                    <p><i class="fas fa-map-marker-alt"></i> 123 Emergency Response Ave, City</p>
                    <p><i class="fas fa-phone"></i> (123) 456-7890</p>
                    <p><i class="fas fa-envelope"></i> info@disastermanagement.org</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <p><a href="index.php">Home</a></p>
                    <p><a href="index.php#features">Features</a></p>
                    <p><a href="index.php#stats">Statistics</a></p>
                    <p><a href="#">Emergency Contacts</a></p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2023 Disaster Management System. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Show/hide designation field based on admin checkbox
        document.getElementById('is_admin').addEventListener('change', function() {
            document.getElementById('designation-field').style.display = this.checked ? 'block' : 'none';
        });
    </script>
</body>
</html>