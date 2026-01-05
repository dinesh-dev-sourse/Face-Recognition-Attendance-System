<?php
// Show existing users
// Save as: C:\xampp\htdocs\attendance_portal\show_users.php
?>
<!DOCTYPE html>
<html>
<head>
    <title>User List</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        th { background: #2563EB; color: white; }
        .btn { padding: 10px 20px; background: #2563EB; color: white; text-decoration: none; border-radius: 4px; display: inline-block; margin: 5px; }
        .info { background: #d1ecf1; padding: 15px; border-radius: 4px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>👥 Registered Users</h1>
        
        <?php
        try {
            $pdo = new PDO("mysql:host=localhost;port=3307;dbname=attendance_portal", "root", "");
            
            // Get all users
            $stmt = $pdo->query("SELECT user_id, email, full_name, phone, gender, role, class_name, roll_number, department, created_at FROM users");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($users) > 0) {
                echo "<table>";
                echo "<tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Phone</th>
                        <th>Gender</th>
                        <th>Class/Dept</th>
                        <th>Registered</th>
                      </tr>";
                
                foreach ($users as $user) {
                    echo "<tr>";
                    echo "<td>{$user['user_id']}</td>";
                    echo "<td><strong>{$user['full_name']}</strong></td>";
                    echo "<td>{$user['email']}</td>";
                    echo "<td>" . ucfirst($user['role']) . "</td>";
                    echo "<td>{$user['phone']}</td>";
                    echo "<td>" . ucfirst($user['gender']) . "</td>";
                    echo "<td>{$user['class_name']} {$user['roll_number']} {$user['department']}</td>";
                    echo "<td>{$user['created_at']}</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
                
                echo "<div class='info'>";
                echo "<strong>ℹ️ Login Information:</strong><br>";
                echo "Use the <strong>EMAIL</strong> from above to login.<br>";
                echo "If you forgot the password, you'll need to reset it in the database or register a new account.";
                echo "</div>";
            } else {
                echo "<p>No users found. Please register first.</p>";
            }
            
            // Get attendance records
            echo "<h2>📊 Attendance Records</h2>";
            $stmt = $pdo->query("
                SELECT a.*, u.full_name, u.email 
                FROM attendance a 
                JOIN users u ON a.user_id = u.user_id 
                ORDER BY a.attendance_date DESC, a.check_in_time DESC
            ");
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($records) > 0) {
                echo "<table>";
                echo "<tr>
                        <th>Date</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Check-in Time</th>
                        <th>Status</th>
                        <th>Confidence</th>
                      </tr>";
                
                foreach ($records as $record) {
                    $statusColor = $record['status'] == 'present' ? '#28a745' : ($record['status'] == 'late' ? '#ffc107' : '#dc3545');
                    echo "<tr>";
                    echo "<td>{$record['attendance_date']}</td>";
                    echo "<td><strong>{$record['full_name']}</strong></td>";
                    echo "<td>{$record['email']}</td>";
                    echo "<td>{$record['check_in_time']}</td>";
                    echo "<td style='color: $statusColor; font-weight: bold;'>" . strtoupper($record['status']) . "</td>";
                    echo "<td>{$record['recognition_confidence']}%</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
            } else {
                echo "<p>No attendance records yet.</p>";
            }
            
            // Get organization info
            echo "<h2>🏢 Organization Details</h2>";
            $stmt = $pdo->query("SELECT * FROM organizations");
            $orgs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($orgs) > 0) {
                echo "<table>";
                echo "<tr>
                        <th>Org Code</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Location</th>
                        <th>Timings</th>
                        <th>Admin Email</th>
                      </tr>";
                
                foreach ($orgs as $org) {
                    echo "<tr>";
                    echo "<td><strong>{$org['org_code']}</strong></td>";
                    echo "<td>{$org['org_name']}</td>";
                    echo "<td>" . ucfirst($org['org_type']) . "</td>";
                    echo "<td>{$org['org_location']}</td>";
                    echo "<td>{$org['start_time']} - {$org['end_time']}</td>";
                    echo "<td>{$org['admin_email']}</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
            }
            
        } catch (PDOException $e) {
            echo "<p style='color: red;'>Database Error: " . $e->getMessage() . "</p>";
        }
        ?>
        
        <div style="margin-top: 30px;">
            <a href="index.html" class="btn">🏠 Home</a>
            <a href="login.php" class="btn">🔐 Login</a>
            <a href="register.php" class="btn">📝 Register New User</a>
            <a href="dashboard.php" class="btn">📊 Dashboard</a>
        </div>
    </div>
</body>
</html>