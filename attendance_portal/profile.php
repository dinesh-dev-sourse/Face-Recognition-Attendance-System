<?php
require_once 'includes/utils.php';
requireLogin();

$user = getCurrentUser();
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Attendance Portal</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-logo">🎓 AttendanceAI</div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li><a href="attendance.php">✅ Mark Attendance</a></li>
                <li><a href="history.php">📜 History</a></li>
                <li><a href="profile.php" class="active">👤 Profile</a></li>
                <li><a href="logout.php">🚪 Logout</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="top-bar">
                <h1>Profile Management</h1>
            </div>
            
            <div class="auth-card" style="max-width: 700px; margin: 0 auto;">
                <h2 style="margin-bottom: 1.5rem; color: var(--primary);">Personal Information</h2>
                
                <form id="profileForm">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    
                    <div class="form-group">
                        <label>Email (Cannot be changed)</label>
                        <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($user['phone']) ?>">
                    </div>
                    
                    <?php if ($user['role'] === 'student'): ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="class_name">Class/Grade</label>
                            <input type="text" id="class_name" name="class_name" value="<?= htmlspecialchars($user['class_name'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="roll_number">Roll Number</label>
                            <input type="text" id="roll_number" name="roll_number" value="<?= htmlspecialchars($user['roll_number'] ?? '') ?>">
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="department">Department</label>
                        <input type="text" id="department" name="department" value="<?= htmlspecialchars($user['department'] ?? '') ?>">
                    </div>
                    
                    <div id="successMessage" class="success-message" style="display: none;"></div>
                    <div id="errorMessage" class="error-message" style="display: none;"></div>
                    
                    <button type="submit" class="btn btn-primary" id="updateBtn">
                        Update Profile
                    </button>
                </form>
                
                <hr style="border-color: rgba(255, 255, 255, 0.1); margin: 2rem 0;">
                
                <h2 style="margin-bottom: 1.5rem; color: var(--primary);">Change Password</h2>
                
                <form id="passwordForm">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" required minlength="8">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_new_password">Confirm New Password</label>
                            <input type="password" id="confirm_new_password" name="confirm_new_password" required minlength="8">
                        </div>
                    </div>
                    
                    <div id="passwordSuccess" class="success-message" style="display: none;"></div>
                    <div id="passwordError" class="error-message" style="display: none;"></div>
                    
                    <button type="submit" class="btn btn-secondary" id="passwordBtn">
                        Change Password
                    </button>
                </form>
            </div>
        </main>
    </div>

    <script>
        // Update Profile
        document.getElementById('profileForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const btn = document.getElementById('updateBtn');
            const successMsg = document.getElementById('successMessage');
            const errorMsg = document.getElementById('errorMessage');
            
            btn.disabled = true;
            btn.textContent = 'Updating...';
            successMsg.style.display = 'none';
            errorMsg.style.display = 'none';
            
            const formData = {
                full_name: document.getElementById('full_name').value,
                phone: document.getElementById('phone').value,
                department: document.getElementById('department').value,
                csrf_token: document.querySelector('#profileForm [name="csrf_token"]').value
            };
            
            // Add student-specific fields if they exist
            const classField = document.getElementById('class_name');
            const rollField = document.getElementById('roll_number');
            if (classField) formData.class_name = classField.value;
            if (rollField) formData.roll_number = rollField.value;
            
            try {
                const response = await fetch('api/update_profile.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    successMsg.textContent = 'Profile updated successfully!';
                    successMsg.style.display = 'block';
                } else {
                    errorMsg.textContent = data.error || 'Update failed';
                    errorMsg.style.display = 'block';
                }
            } catch (error) {
                errorMsg.textContent = 'Network error. Please try again.';
                errorMsg.style.display = 'block';
            } finally {
                btn.disabled = false;
                btn.textContent = 'Update Profile';
            }
        });
        
        // Change Password
        document.getElementById('passwordForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const btn = document.getElementById('passwordBtn');
            const successMsg = document.getElementById('passwordSuccess');
            const errorMsg = document.getElementById('passwordError');
            
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_new_password').value;
            
            if (newPassword !== confirmPassword) {
                errorMsg.textContent = 'New passwords do not match';
                errorMsg.style.display = 'block';
                successMsg.style.display = 'none';
                return;
            }
            
            btn.disabled = true;
            btn.textContent = 'Changing...';
            successMsg.style.display = 'none';
            errorMsg.style.display = 'none';
            
            const formData = {
                current_password: document.getElementById('current_password').value,
                new_password: newPassword,
                csrf_token: document.querySelector('#passwordForm [name="csrf_token"]').value
            };
            
            try {
                const response = await fetch('api/update_profile.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    successMsg.textContent = 'Password changed successfully!';
                    successMsg.style.display = 'block';
                    document.getElementById('passwordForm').reset();
                } else {
                    errorMsg.textContent = data.error || 'Password change failed';
                    errorMsg.style.display = 'block';
                }
            } catch (error) {
                errorMsg.textContent = 'Network error. Please try again.';
                errorMsg.style.display = 'block';
            } finally {
                btn.disabled = false;
                btn.textContent = 'Change Password';
            }
        });
    </script>
</body>
</html>