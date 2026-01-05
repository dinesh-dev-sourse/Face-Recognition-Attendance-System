<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

// Security Headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

// CSRF Token Generation
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// CSRF Token Validation
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Password Hashing
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

// Password Verification
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Generate Unique Organization Code
function generateOrgCode() {
    $prefix = strtoupper(substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 4));
    $number = rand(1000, 9999);
    return $prefix . $number;
}

// Sanitize Input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Validate Email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Check if User is Logged In
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['org_id']);
}

// Redirect if Not Logged In
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// Redirect if Logged In
function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        header("Location: dashboard.php");
        exit();
    }
}

// Get Current User Data
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Get Organization Data
function getOrganization($org_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM organizations WHERE org_id = ?");
    $stmt->execute([$org_id]);
    return $stmt->fetch();
}

// JSON Response
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

// Error Response
function errorResponse($message, $status = 400) {
    jsonResponse(['success' => false, 'error' => $message], $status);
}

// Success Response
function successResponse($message, $data = []) {
    jsonResponse(array_merge(['success' => true, 'message' => $message], $data));
}

// Upload Profile Image
function uploadProfileImage($file, $user_id) {
    $target_dir = __DIR__ . "/../uploads/profiles/";
    
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    
    $imageFileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $target_file = $target_dir . $user_id . "_" . time() . "." . $imageFileType;
    
    // Check if image file is actual image
    $check = getimagesize($file["tmp_name"]);
    if($check === false) {
        return ['success' => false, 'error' => 'File is not an image.'];
    }
    
    // Check file size (max 5MB)
    if ($file["size"] > 5000000) {
        return ['success' => false, 'error' => 'File is too large. Max 5MB allowed.'];
    }
    
    // Allow certain file formats
    if(!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
        return ['success' => false, 'error' => 'Only JPG, JPEG, PNG & GIF files are allowed.'];
    }
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ['success' => true, 'path' => str_replace(__DIR__ . "/../", "", $target_file)];
    } else {
        return ['success' => false, 'error' => 'Error uploading file.'];
    }
}

// Format Date
function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

// Format Time
function formatTime($time) {
    return date('h:i A', strtotime($time));
}

// Check if attendance is late
function isLateAttendance($check_in_time, $org_start_time) {
    return strtotime($check_in_time) > strtotime($org_start_time);
}

// Get attendance status
function getAttendanceStatus($check_in_time, $org_start_time) {
    return isLateAttendance($check_in_time, $org_start_time) ? 'late' : 'present';
}

// Log Activity (Optional - for audit trail)
function logActivity($user_id, $action, $details = '') {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt->execute([$user_id, $action, $details, $ip]);
}
?>