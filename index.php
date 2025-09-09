<?php
// Database configuration
$servername = "localhost";
$username = "root"; // Change if needed
$password = ""; // Change if needed
$dbname = "disaster management system";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get statistics from database
$disastersManaged = 0;
$missingPersonsFound = 0;
$reliefPackagesDistributed = 0;
$activeVolunteers = 0;

// Query for disasters managed
$sql = "SELECT COUNT(*) as count FROM disaster";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $disastersManaged = $row["count"];
}

// Query for missing persons found
$sql = "SELECT COUNT(*) as count FROM missing_person WHERE status = 'Found'";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $missingPersonsFound = $row["count"];
}

// Query for relief packages distributed
$sql = "SELECT SUM(T_Quantity) as total FROM relief";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $reliefPackagesDistributed = $row["total"] ? $row["total"] : 0;
}

// Query for active volunteers (users who are not admins)
$sql = "SELECT COUNT(*) as count FROM general_user";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $activeVolunteers = $row["count"];
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disaster Management System</title>
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
        
        /* Hero Section */
        .hero {
            background:url('https://bracusa.org/wp-content/uploads/2024/08/9.png') no-repeat center center/cover;
            color: white;
            padding: 5rem 0;
            text-align: center;
            position: relative;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
        }
        
        .hero-content {
            position: relative;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .hero h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .hero p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
        }
        
        .btn {
            display: inline-block;
            background: #1a6fc4;
            color: white;
            padding: 12px 25px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #0c4a8f;
        }
        
        /* Features Section */
        .features {
            padding: 4rem 0;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .section-title h2 {
            font-size: 2.2rem;
            color: rgba(237, 240, 244, 1);
            margin-bottom: 1rem;
        }
        
        .section-title p {
            color: #fdefefff;
            max-width: 700px;
            margin: 0 auto;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .feature-card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
        }
        
        .feature-card i {
            font-size: 2.5rem;
            color: #1a6fc4;
            margin-bottom: 20px;
        }
        
        .feature-card h3 {
            font-size: 1.4rem;
            margin-bottom: 15px;
        }
        
        /* Stats Section */
        .stats {
            background: linear-gradient(135deg, #1a6fc4 0%, #0c4a8f 100%);
            color: white;
            padding: 4rem 0;
            text-align: center;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            margin-top: 2rem;
        }
        
        .stat-item {
            padding: 20px;
        }
        
        .stat-item i {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        /* Footer */
        footer {
            background: #2c3e50;
            color: white;
            padding: 3rem 0 1rem;
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
        
        .footer-section p {
            margin-bottom: 10px;
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        /* Responsive Design */
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
            
            .hero h2 {
                font-size: 2rem;
            }
            
            .hero p {
                font-size: 1rem;
            }
        }
        
        /* Database status indicator */
        .db-status {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 10px 15px;
            border-radius: 5px;
            font-weight: 500;
            z-index: 100;
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        }
        
        .db-connected {
            background: #4caf50;
            color: white;
        }
        
        .db-error {
            background: #f44336;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Database Connection Status Indicator -->
    <?php if(isset($conn) && !$conn->connect_error): ?>
    <div class="db-status db-connected">
        <i class="fas fa-database"></i> Database Connected
    </div>
    <?php else: ?>
    <div class="db-status db-error">
        <i class="fas fa-exclamation-circle"></i> Database Connection Error
    </div>
    <?php endif; ?>
    
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
                    <li><a href="desaster.php">Disasters</a></li>
                    <li><a href="relief.php">Resources</a></li>
                    <li><a href="missing_person.php">Missing Persons</a></li>
                    <li><a href="emergency_contract.php">Emergency Contacts</a></li>
                    <li><a href="login.php">Login</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <!-- Hero Section -->
    <section class="hero">
        <div class="container hero-content">
            <h2>Coordinating Disaster Response Efforts</h2>
            <p>Our platform helps manage disasters, coordinate relief efforts, track missing persons, and connect those in need with resources.</p>
             <a href="register.php" class="btn">Register Now</a>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <div class="section-title">
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <i class="fas fa-hand-holding-heart"></i>
                    <h3><a href="volunteer.php">Volunteer list</a></h3>
                </div>
                <div class="feature-card">
                    <i class="fas fa-users"></i>
                    <h3><a href="donation_tracking.php">Donation Tracking</a></h3>
                </div>
                <div class="feature-card">
                    <i class="fas fa-box-open"></i>
                    <h3><a href="donor.php">Be a Donor</a></h3>
                </div>
                <div class="feature-card">
                    <i class="fas fa-address-book"></i>
                    <h3><a href="emergency_contract.php">Emergency contacts</a></h3>
                </div>
                <div class="feature-card">
                    <i class="fas fa-tornado"></i>
                    <h3><a href="desaster.php">Desaster information</a></h3>
                </div>
                <div class="feature-card">
                    <i class="fas fa-map-marked-alt"></i>
                    <h3>Notification</h3>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="container">
            <div class="section-title">
                <h2>System Impact</h2>
                <p>Our disaster management system has made a significant impact in emergency response efforts</p>
            </div>
            <div class="stats-grid">
                <div class="stat-item">
                    <i class="fas fa-tornado"></i>
                    <div class="stat-number"><?php echo $disastersManaged; ?></div>
                    <p>Disasters Managed</p>
                </div>
                <div class="stat-item">
                    <i class="fas fa-users"></i>
                    <div class="stat-number"><?php echo $missingPersonsFound; ?></div>
                    <p>Missing Persons Found</p>
                </div>
                <div class="stat-item">
                    <i class="fas fa-box-open"></i>
                    <div class="stat-number"><?php echo $reliefPackagesDistributed; ?></div>
                    <p>Relief Packages Distributed</p>
                </div>
                <div class="stat-item">
                    <i class="fas fa-hand-holding-heart"></i>
                    <div class="stat-number"><?php echo $activeVolunteers; ?></div>
                    <p>Active Volunteers</p>
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
                    <p><i class="fas fa-map-marker-alt"></i> BRAC University, Badda, Dhaka-1212</p>
                    <p><i class="fas fa-phone"></i> 0999 </p>
                    <p><i class="fas fa-envelope"></i> info@disastermanagement.org</p>
                </div>
                
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Disaster Management System. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Simple animation for stats counter
        document.addEventListener('DOMContentLoaded', function() {
            const statNumbers = document.querySelectorAll('.stat-number');
            const speed = 200; // The lower the slower
            
            statNumbers.forEach(statNumber => {
                const target = parseInt(statNumber.textContent);
                let count = 0;
                
                const updateCount = () => {
                    const increment = Math.ceil(target / speed);
                    
                    if (count < target) {
                        count += increment;
                        statNumber.textContent = count;
                        setTimeout(updateCount, 1);
                    } else {
                        statNumber.textContent = target;
                    }
                };
                
                updateCount();
            });
        });
    </script>
</body>
</html>