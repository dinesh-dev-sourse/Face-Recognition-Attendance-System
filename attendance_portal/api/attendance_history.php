<?php
require_once __DIR__ . '/../includes/utils.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Invalid request method', 405);
}

$org_id = $_SESSION['org_id'];
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$month = isset($_GET['month']) ? sanitizeInput($_GET['month']) : date('Y-m');
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

try {
    $db = getDB();
    
    // Build query based on search criteria
    $where = "WHERE a.org_id = ?";
    $params = [$org_id];
    
    // Add month filter
    if (!empty($month)) {
        $where .= " AND DATE_FORMAT(a.attendance_date, '%Y-%m') = ?";
        $params[] = $month;
    }
    
    // Add search filter
    if (!empty($search)) {
        $where .= " AND (u.email LIKE ? OR u.phone LIKE ? OR u.full_name LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    // Get total count
    $countQuery = "
        SELECT COUNT(*) as total 
        FROM attendance a 
        JOIN users u ON a.user_id = u.user_id 
        $where
    ";
    $stmt = $db->prepare($countQuery);
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];
    
    // Get paginated results
    $query = "
        SELECT 
            a.attendance_id,
            a.attendance_date,
            a.check_in_time,
            a.status,
            a.recognition_confidence,
            u.user_id,
            u.full_name,
            u.email,
            u.phone,
            u.role,
            u.class_name,
            u.roll_number,
            u.department
        FROM attendance a 
        JOIN users u ON a.user_id = u.user_id 
        $where
        ORDER BY a.attendance_date DESC, a.check_in_time DESC
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $records = $stmt->fetchAll();
    
    // Get summary statistics for the filters
    $summaryQuery = "
        SELECT 
            COUNT(*) as total_records,
            COUNT(DISTINCT a.user_id) as unique_users,
            SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count
        FROM attendance a
        JOIN users u ON a.user_id = u.user_id
        $where
    ";
    $stmt = $db->prepare($summaryQuery);
    $stmt->execute($params);
    $summary = $stmt->fetch();
    
    successResponse('Attendance history retrieved', [
        'data' => [
            'records' => $records,
            'summary' => $summary,
            'pagination' => [
                'current_page' => $page,
                'total_records' => $total,
                'total_pages' => ceil($total / $limit),
                'per_page' => $limit
            ]
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Attendance history error: " . $e->getMessage());
    errorResponse('Failed to load attendance history: ' . $e->getMessage(), 500);
}
?>