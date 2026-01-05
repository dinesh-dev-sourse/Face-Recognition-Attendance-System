<?php
require_once __DIR__ . '/../includes/utils.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Invalid request method', 405);
}

$user_id = $_SESSION['user_id'];
$org_id = $_SESSION['org_id'];

try {
    $db = getDB();
    
    // Get user's attendance statistics
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
    
    // If no records, set defaults
    if (!$userStats || $userStats['total_days'] == 0) {
        $userStats = [
            'total_days' => 0,
            'present_days' => 0,
            'late_days' => 0,
            'absent_days' => 0,
            'attendance_percentage' => 0
        ];
    }
    
    // Get gender statistics for organization
    $stmt = $db->prepare("
        SELECT 
            gender,
            COUNT(*) as count,
            ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM users WHERE org_id = ?)), 2) as percentage
        FROM users 
        WHERE org_id = ?
        GROUP BY gender
    ");
    $stmt->execute([$org_id, $org_id]);
    $genderStats = $stmt->fetchAll();
    
    // Get monthly attendance for current user (last 6 months)
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
    
    // If no monthly stats, provide current month with 0
    if (empty($monthlyStats)) {
        $monthlyStats = [
            [
                'month' => date('Y-m'),
                'total_days' => 0,
                'present_days' => 0,
                'attendance_percentage' => 0
            ]
        ];
    }
    
    // Get recent attendance records
    $stmt = $db->prepare("
        SELECT attendance_date, check_in_time, status 
        FROM attendance 
        WHERE user_id = ? 
        ORDER BY attendance_date DESC, check_in_time DESC
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $recentAttendance = $stmt->fetchAll();
    
    // Get today's attendance status
    $today = date('Y-m-d');
    $stmt = $db->prepare("
        SELECT attendance_id, check_in_time, status 
        FROM attendance 
        WHERE user_id = ? AND attendance_date = ?
    ");
    $stmt->execute([$user_id, $today]);
    $todayAttendance = $stmt->fetch();
    
    // Get organization-wide statistics
    $stmt = $db->prepare("
        SELECT 
            COUNT(DISTINCT user_id) as total_users,
            COUNT(*) as total_attendance_records,
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as total_present,
            SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as total_late
        FROM attendance 
        WHERE org_id = ?
    ");
    $stmt->execute([$org_id]);
    $orgStats = $stmt->fetch();
    
    successResponse('Dashboard data retrieved', [
        'data' => [
            'user_stats' => $userStats,
            'gender_stats' => $genderStats,
            'monthly_stats' => $monthlyStats,
            'recent_attendance' => $recentAttendance,
            'today_attendance' => $todayAttendance,
            'org_stats' => $orgStats
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Dashboard data error: " . $e->getMessage());
    errorResponse('Failed to load dashboard data: ' . $e->getMessage(), 500);
}
?>