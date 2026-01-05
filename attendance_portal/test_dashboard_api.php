<?php
// Test Dashboard API
// Save as: C:\xampp\htdocs\attendance_portal\test_dashboard_api.php
session_start();

// Simulate logged in user for testing
$_SESSION['user_id'] = 1;
$_SESSION['org_id'] = 2;

echo "<h1>Dashboard API Test</h1>";
echo "<pre>";

echo "Session Data:\n";
print_r($_SESSION);

echo "\n\nTrying to call dashboard API...\n\n";

require_once 'includes/utils.php';

try {
    $db = getDB();
    echo "✓ Database connected\n\n";
    
    $user_id = $_SESSION['user_id'];
    $org_id = $_SESSION['org_id'];
    
    echo "User ID: $user_id\n";
    echo "Org ID: $org_id\n\n";
    
    // Test user stats query
    echo "--- Testing User Stats Query ---\n";
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_days,
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
            SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days,
            SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
            ROUND((SUM(CASE WHEN status IN ('present', 'late') THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as attendance_percentage
        FROM attendance 
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $userStats = $stmt->fetch();
    
    echo "User Stats:\n";
    print_r($userStats);
    echo "\n";
    
    // Test gender stats
    echo "--- Testing Gender Stats Query ---\n";
    $stmt = $db->prepare("
        SELECT 
            gender,
            COUNT(*) as count
        FROM users 
        WHERE org_id = ?
        GROUP BY gender
    ");
    $stmt->execute([$org_id]);
    $genderStats = $stmt->fetchAll();
    
    echo "Gender Stats:\n";
    print_r($genderStats);
    echo "\n";
    
    // Test monthly stats
    echo "--- Testing Monthly Stats Query ---\n";
    $stmt = $db->prepare("
        SELECT 
            DATE_FORMAT(attendance_date, '%Y-%m') as month,
            COUNT(*) as total_days,
            SUM(CASE WHEN status IN ('present', 'late') THEN 1 ELSE 0 END) as present_days,
            ROUND((SUM(CASE WHEN status IN ('present', 'late') THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as attendance_percentage
        FROM attendance 
        WHERE user_id = ? 
        GROUP BY DATE_FORMAT(attendance_date, '%Y-%m')
        ORDER BY month DESC 
        LIMIT 6
    ");
    $stmt->execute([$user_id]);
    $monthlyStats = $stmt->fetchAll();
    
    echo "Monthly Stats:\n";
    print_r($monthlyStats);
    echo "\n";
    
    // Test recent attendance
    echo "--- Testing Recent Attendance Query ---\n";
    $stmt = $db->prepare("
        SELECT attendance_date, check_in_time, status 
        FROM attendance 
        WHERE user_id = ? 
        ORDER BY attendance_date DESC, check_in_time DESC
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $recentAttendance = $stmt->fetchAll();
    
    echo "Recent Attendance:\n";
    print_r($recentAttendance);
    echo "\n";
    
    echo "\n✓ All queries executed successfully!\n";
    echo "\nNow test the actual API at:\n";
    echo "http://localhost/attendance_portal/api/dashboard.php\n";
    
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString();
}

echo "</pre>";

echo "<hr>";
echo "<a href='dashboard.php'>Back to Dashboard</a> | ";
echo "<a href='api/dashboard.php' target='_blank'>Test API Directly</a>";
?>