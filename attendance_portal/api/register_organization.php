<?php
require_once __DIR__ . '/../includes/utils.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Invalid request method', 405);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate CSRF Token
if (!isset($input['csrf_token']) || !validateCSRFToken($input['csrf_token'])) {
    errorResponse('Invalid CSRF token', 403);
}

// Validate required fields
$required = ['org_name', 'org_type', 'org_location', 'start_time', 'end_time', 'admin_email', 'admin_password'];
foreach ($required as $field) {
    if (!isset($input[$field]) || empty(trim($input[$field]))) {
        errorResponse("Field '$field' is required");
    }
}

// Sanitize inputs
$org_name = sanitizeInput($input['org_name']);
$org_type = sanitizeInput($input['org_type']);
$org_location = sanitizeInput($input['org_location']);
$start_time = sanitizeInput($input['start_time']);
$end_time = sanitizeInput($input['end_time']);
$admin_email = sanitizeInput($input['admin_email']);
$admin_password = $input['admin_password'];

// Validate email
if (!validateEmail($admin_email)) {
    errorResponse('Invalid email address');
}

// Validate organization type
if (!in_array($org_type, ['college', 'office'])) {
    errorResponse('Invalid organization type');
}

// Validate password strength (min 8 characters)
if (strlen($admin_password) < 8) {
    errorResponse('Password must be at least 8 characters long');
}

try {
    $db = getDB();
    
    // Check if email already exists
    $stmt = $db->prepare("SELECT org_id FROM organizations WHERE admin_email = ?");
    $stmt->execute([$admin_email]);
    if ($stmt->fetch()) {
        errorResponse('Email already registered');
    }
    
    // Generate unique organization code
    do {
        $org_code = generateOrgCode();
        $stmt = $db->prepare("SELECT org_id FROM organizations WHERE org_code = ?");
        $stmt->execute([$org_code]);
    } while ($stmt->fetch());
    
    // Hash password
    $hashed_password = hashPassword($admin_password);
    
    // Insert organization
    $stmt = $db->prepare("
        INSERT INTO organizations (org_name, org_code, org_type, org_location, start_time, end_time, admin_email, admin_password) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $org_name,
        $org_code,
        $org_type,
        $org_location,
        $start_time,
        $end_time,
        $admin_email,
        $hashed_password
    ]);
    
    $org_id = $db->lastInsertId();
    
    successResponse('Organization registered successfully', [
        'org_id' => $org_id,
        'org_code' => $org_code,
        'org_name' => $org_name
    ]);
    
} catch (PDOException $e) {
    error_log("Organization registration error: " . $e->getMessage());
    errorResponse('Registration failed. Please try again.', 500);
}
?>