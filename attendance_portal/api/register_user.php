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
$required = ['org_code', 'email', 'password', 'full_name', 'phone', 'gender', 'role'];
foreach ($required as $field) {
    if (!isset($input[$field]) || empty(trim($input[$field]))) {
        errorResponse("Field '$field' is required");
    }
}

// Sanitize inputs
$org_code = sanitizeInput($input['org_code']);
$email = sanitizeInput($input['email']);
$password = $input['password'];
$full_name = sanitizeInput($input['full_name']);
$phone = sanitizeInput($input['phone']);
$gender = sanitizeInput($input['gender']);
$role = sanitizeInput($input['role']);
$class_name = isset($input['class_name']) ? sanitizeInput($input['class_name']) : null;
$roll_number = isset($input['roll_number']) ? sanitizeInput($input['roll_number']) : null;
$department = isset($input['department']) ? sanitizeInput($input['department']) : null;

// Validate email
if (!validateEmail($email)) {
    errorResponse('Invalid email address');
}

// Validate role
if (!in_array($role, ['student', 'teacher', 'employee'])) {
    errorResponse('Invalid role');
}

// Validate gender
if (!in_array($gender, ['male', 'female', 'other'])) {
    errorResponse('Invalid gender');
}

// Validate password strength
if (strlen($password) < 8) {
    errorResponse('Password must be at least 8 characters long');
}

try {
    $db = getDB();
    
    // Check if organization code exists
    $stmt = $db->prepare("SELECT org_id FROM organizations WHERE org_code = ?");
    $stmt->execute([$org_code]);
    $org = $stmt->fetch();
    
    if (!$org) {
        errorResponse('Invalid organization code');
    }
    
    $org_id = $org['org_id'];
    
    // Check if email already exists
    $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        errorResponse('Email already registered');
    }
    
    // Hash password
    $hashed_password = hashPassword($password);
    
    // Insert user
    $stmt = $db->prepare("
        INSERT INTO users (org_id, email, password, full_name, phone, gender, role, class_name, roll_number, department) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $org_id,
        $email,
        $hashed_password,
        $full_name,
        $phone,
        $gender,
        $role,
        $class_name,
        $roll_number,
        $department
    ]);
    
    $user_id = $db->lastInsertId();
    
    successResponse('User registered successfully', [
        'user_id' => $user_id,
        'email' => $email,
        'full_name' => $full_name
    ]);
    
} catch (PDOException $e) {
    error_log("User registration error: " . $e->getMessage());
    errorResponse('Registration failed. Please try again.', 500);
}
?>