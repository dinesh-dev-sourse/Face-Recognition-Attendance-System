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
if (!isset($input['email']) || !isset($input['password'])) {
    errorResponse('Email and password are required');
}

$email = sanitizeInput($input['email']);
$password = $input['password'];

// Validate email
if (!validateEmail($email)) {
    errorResponse('Invalid email address');
}

try {
    $db = getDB();
    
    // Check user credentials
    $stmt = $db->prepare("
        SELECT u.*, o.org_name, o.org_code, o.org_type 
        FROM users u 
        JOIN organizations o ON u.org_id = o.org_id 
        WHERE u.email = ? AND u.is_active = 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user || !verifyPassword($password, $user['password'])) {
        errorResponse('Invalid email or password', 401);
    }
    
    // Set session variables
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['org_id'] = $user['org_id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['org_code'] = $user['org_code'];
    
    // Generate new CSRF token
    unset($_SESSION['csrf_token']);
    $new_token = generateCSRFToken();
    
    successResponse('Login successful', [
        'user' => [
            'user_id' => $user['user_id'],
            'email' => $user['email'],
            'full_name' => $user['full_name'],
            'role' => $user['role'],
            'org_name' => $user['org_name'],
            'org_code' => $user['org_code']
        ],
        'csrf_token' => $new_token
    ]);
    
} catch (PDOException $e) {
    error_log("Login error: " . $e->getMessage());
    errorResponse('Login failed. Please try again.', 500);
}
?>