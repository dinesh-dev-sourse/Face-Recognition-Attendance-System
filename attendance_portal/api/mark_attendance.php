<?php
require_once __DIR__ . '/../includes/utils.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Invalid request method', 405);
}

$input = json_decode(file_get_contents('php://input'), true);

// Validate CSRF Token
if (!isset($input['csrf_token']) || !validateCSRFToken($input['csrf_token'])) {
    errorResponse('Invalid CSRF token', 403);
}

// Validate required fields
if (!isset($input['user_id']) || !isset($input['recognition_confidence'])) {
    errorResponse('User ID and recognition confidence are required');
}

$user_id = (int)$input['user_id'];
$recognition_confidence = (float)$input['recognition_confidence'];

try {
    $db = getDB();
    
    // Get user and organization details
    $stmt = $db->prepare("
        SELECT u.user_id, u.org_id, u.full_name, o.start_time 
        FROM users u 
        JOIN organizations o ON u.org_id = o.org_id 
        WHERE u.user_id = ? AND u.is_active = 1
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        errorResponse('User not found or inactive', 404);
    }
    
    $today = date('Y-m-d');
    $current_time = date('H:i:s');
    
    // Check if attendance already marked today
    $stmt = $db->prepare("
        SELECT attendance_id 
        FROM attendance 
        WHERE user_id = ? AND attendance_date = ?
    ");
    $stmt->execute([$user_id, $today]);
    
    if ($stmt->fetch()) {
        errorResponse('Attendance already marked for today');
    }
    
    // Determine attendance status
    $status = getAttendanceStatus($current_time, $user['start_time']);
    
    // Insert attendance record
    $stmt = $db->prepare("
        INSERT INTO attendance (user_id, org_id, attendance_date, check_in_time, status, recognition_confidence) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $user_id,
        $user['org_id'],
        $today,
        $current_time,
        $status,
        $recognition_confidence
    ]);
    
    $attendance_id = $db->lastInsertId();
    
    successResponse('Attendance marked successfully', [
        'attendance_id' => $attendance_id,
        'user_name' => $user['full_name'],
        'date' => $today,
        'check_in_time' => formatTime($current_time),
        'status' => $status,
        'confidence' => $recognition_confidence
    ]);
    
} catch (PDOException $e) {
    error_log("Attendance marking error: " . $e->getMessage());
    errorResponse('Failed to mark attendance. Please try again.', 500);
}
?>