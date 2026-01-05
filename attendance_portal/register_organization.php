<?php
require_once 'includes/utils.php';
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Organization - Attendance Portal</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card" style="max-width: 700px;">
            <div class="auth-header">
                <h1>Register Organization</h1>
                <p>Set up your college or office attendance system</p>
            </div>
            
            <form id="orgRegisterForm">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                
                <div class="form-group">
                    <label for="org_name">Organization Name *</label>
                    <input type="text" id="org_name" name="org_name" required placeholder="e.g., ABC College">
                </div>
                
                <div class="form-group">
                    <label for="org_type">Organization Type *</label>
                    <select id="org_type" name="org_type" required>
                        <option value="">Select Type</option>
                        <option value="college">College/University</option>
                        <option value="office">Office/Company</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="org_location">Location/Address *</label>
                    <textarea id="org_location" name="org_location" rows="3" required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="start_time">Start Time *</label>
                        <input type="time" id="start_time" name="start_time" required value="09:00">
                    </div>
                    
                    <div class="form-group">
                        <label for="end_time">End Time *</label>
                        <input type="time" id="end_time" name="end_time" required value="17:00">
                    </div>
                </div>
                
                <hr style="border-color: rgba(255, 255, 255, 0.1); margin: 2rem 0;">
                
                <h3 style="margin-bottom: 1rem; color: var(--primary);">Admin Account</h3>
                
                <div class="form-group">
                    <label for="admin_email">Admin Email *</label>
                    <input type="email" id="admin_email" name="admin_email" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="admin_password">Admin Password *</label>
                        <input type="password" id="admin_password" name="admin_password" required minlength="8">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                    </div>
                </div>
                
                <div id="errorMessage" class="error-message" style="display: none;"></div>
                <div id="successMessage" class="success-message" style="display: none;"></div>
                
                <button type="submit" class="btn btn-primary" id="registerBtn">
                    Register Organization
                </button>
            </form>
            
            <div class="auth-footer">
                <p>Already have an organization? <a href="login.php">Sign in here</a></p>
                <p><a href="register.php">Join existing organization</a></p>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('orgRegisterForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const btn = document.getElementById('registerBtn');
            const errorMsg = document.getElementById('errorMessage');
            const successMsg = document.getElementById('successMessage');
            
            // Validate passwords match
            const password = document.getElementById('admin_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                errorMsg.textContent = 'Passwords do not match';
                errorMsg.style.display = 'block';
                successMsg.style.display = 'none';
                return;
            }
            
            btn.disabled = true;
            btn.textContent = 'Registering...';
            errorMsg.style.display = 'none';
            successMsg.style.display = 'none';
            
            const formData = {
                org_name: document.getElementById('org_name').value,
                org_type: document.getElementById('org_type').value,
                org_location: document.getElementById('org_location').value,
                start_time: document.getElementById('start_time').value,
                end_time: document.getElementById('end_time').value,
                admin_email: document.getElementById('admin_email').value,
                admin_password: password,
                csrf_token: document.querySelector('[name="csrf_token"]').value
            };
            
            try {
                const response = await fetch('api/register_organization.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    successMsg.innerHTML = `
                        <strong>Organization registered successfully!</strong><br>
                        Your organization code is: <strong>${data.data.org_code}</strong><br>
                        Please save this code. Redirecting to login...
                    `;
                    successMsg.style.display = 'block';
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 5000);
                } else {
                    errorMsg.textContent = data.error || 'Registration failed';
                    errorMsg.style.display = 'block';
                }
            } catch (error) {
                errorMsg.textContent = 'Network error. Please try again.';
                errorMsg.style.display = 'block';
            } finally {
                btn.disabled = false;
                btn.textContent = 'Register Organization';
            }
        });
    </script>
</body>
</html>