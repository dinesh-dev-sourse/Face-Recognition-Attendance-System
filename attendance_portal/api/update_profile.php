<?php
require_once __DIR__ . '/../includes/utils.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Invalid request method', 405);
}

$input = json_decode(file_get_contents('php://input'), true);

// Validate CSRF Token
if (!isset($input['csrf_token']) || !validateCSRFToken($input['csrf_token'])) {
    errorResponse('Invalid CSRF token', 403);
}

$user_id = $_SESSION['user_id'];

try {
    $db = getDB();
    
    // Get current user data
    $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        errorResponse('User not found', 404);
    }
    
    // Email is immutable
    if (isset($input['email']) && $input['email'] !== $user['email']) {
        errorResponse('Email cannot be changed');
    }
    
    // Build update query dynamically
    $updateFields = [];
    $params = [];
    
    // Allowed editable fields
    $editableFields = ['full_name', 'phone', 'class_name', 'roll_number', 'department'];
    
    foreach ($editableFields as $field) {
        if (isset($input[$field])) {
            $updateFields[] = "$field = ?";
            $params[] = sanitizeInput($input[$field]);
        }
    }
    
    // Handle password change separately
    if (isset($input['current_password']) && isset($input['new_password'])) {
        if (!verifyPassword($input['current_password'], $user['password'])) {
            errorResponse('Current password is incorrect');
        }
        
        if (strlen($input['new_password']) < 8) {
            errorResponse('New password must be at least 8 characters long');
        }
        
        $updateFields[] = "password = ?";
        $params[] = hashPassword($input['new_password']);
    }
    
    if (empty($updateFields)) {
        errorResponse('No fields to update');
    }
    
    // Add user_id to params
    $params[] = $user_id;
    
    // Update user
    $query = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE user_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    
    // Get updated user data
    $stmt = $db->prepare("SELECT user_id, email, full_name, phone, role, class_name, roll_number, department FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $updatedUser = $stmt->fetch();
    
    successResponse('Profile updated successfully', [
        'user' => $updatedUser
    ]);
    
} catch (PDOException $e) {
    error_log("Profile update error: " . $e->getMessage());
    errorResponse('Failed to update profile', 500);
}
?>