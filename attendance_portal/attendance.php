<?php
require_once 'includes/utils.php';
requireLogin();

$user = getCurrentUser();
$org = getOrganization($user['org_id']);
$csrf_token = generateCSRFToken();

// Check today's attendance
$db = getDB();
$today = date('Y-m-d');
$stmt = $db->prepare("SELECT * FROM attendance WHERE user_id = ? AND attendance_date = ?");
$stmt->execute([$user['user_id'], $today]);
$todayAttendance = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance - Attendance Portal</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-logo">🎓 AttendanceAI</div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li><a href="attendance.php" class="active">✅ Mark Attendance</a></li>
                <li><a href="history.php">📜 History</a></li>
                <li><a href="profile.php">👤 Profile</a></li>
                <li><a href="logout.php">🚪 Logout</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="top-bar">
                <h1>Mark Attendance</h1>
                <div class="user-info">
                    <div class="user-avatar">
                        <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                    </div>
                    <span><?= htmlspecialchars($user['full_name']) ?></span>
                </div>
            </div>
            
            <div class="stat-card" style="margin-bottom: 2rem; background: rgba(37, 99, 235, 0.1); border-color: var(--primary);">
                <h3>📅 Today's Date</h3>
                <div style="font-size: 1.5rem; color: var(--primary); margin-top: 0.5rem;">
                    <?= date('l, F j, Y') ?>
                </div>
                <div style="font-size: 1.2rem; color: var(--text-muted); margin-top: 0.5rem;">
                    Current Time: <span id="currentTime"></span>
                </div>
                <div style="margin-top: 1rem; color: var(--text-muted);">
                    Organization Hours: <?= formatTime($org['start_time']) ?> - <?= formatTime($org['end_time']) ?>
                </div>
            </div>
            
            <?php if ($todayAttendance): ?>
            <div class="success-message" style="margin-bottom: 2rem;">
                <strong>✓ Attendance Already Marked for Today</strong><br>
                Check-in Time: <?= formatTime($todayAttendance['check_in_time']) ?><br>
                Status: <?= strtoupper($todayAttendance['status']) ?><br>
                You cannot mark attendance twice in one day.
            </div>
            
            <div class="table-container" style="margin-top: 2rem;">
                <h2>Today's Attendance Details</h2>
                <table>
                    <tr>
                        <th>Date</th>
                        <th>Check-in Time</th>
                        <th>Status</th>
                        <th>Confidence</th>
                    </tr>
                    <tr>
                        <td><?= formatDate($todayAttendance['attendance_date']) ?></td>
                        <td><?= formatTime($todayAttendance['check_in_time']) ?></td>
                        <td><span class="badge badge-<?= $todayAttendance['status'] == 'present' ? 'success' : 'warning' ?>"><?= strtoupper($todayAttendance['status']) ?></span></td>
                        <td><?= $todayAttendance['recognition_confidence'] ?>%</td>
                    </tr>
                </table>
                
                <div style="margin-top: 1.5rem; text-align: center;">
                    <a href="dashboard.php" class="btn btn-primary">📊 View Dashboard</a>
                    <a href="history.php" class="btn btn-secondary">📜 View History</a>
                </div>
            </div>
            
            <?php else: ?>
            
            <div class="auth-card" style="max-width: 700px; margin: 0 auto;">
                <div class="auth-header">
                    <h2>Attendance Options</h2>
                    <p>Choose your preferred method to mark attendance</p>
                </div>
                
                <div style="display: grid; gap: 2rem; margin-top: 2rem;">
                    <!-- Face Recognition Method -->
                    <div class="stat-card" style="cursor: pointer; transition: all 0.3s;" onclick="showFaceRecognition()">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="font-size: 3rem;">📸</div>
                            <div style="flex: 1;">
                                <h3 style="color: var(--primary); margin-bottom: 0.5rem;">Face Recognition</h3>
                                <p style="color: var(--text-muted); margin: 0;">Use your webcam for automatic attendance</p>
                            </div>
                            <button class="btn btn-primary" style="width: auto;">Start Camera</button>
                        </div>
                    </div>
                    
                    <!-- Manual Method -->
                    <div class="stat-card" style="cursor: pointer; transition: all 0.3s;" onclick="showManualEntry()">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="font-size: 3rem;">✍️</div>
                            <div style="flex: 1;">
                                <h3 style="color: var(--primary); margin-bottom: 0.5rem;">Manual Entry</h3>
                                <p style="color: var(--text-muted); margin: 0;">Mark attendance manually with confirmation</p>
                            </div>
                            <button class="btn btn-secondary" style="width: auto;">Enter Manually</button>
                        </div>
                    </div>
                </div>
                
                <!-- Face Recognition Section -->
                <div id="faceRecognitionSection" style="display: none; margin-top: 2rem;">
                    <hr style="border-color: var(--border); margin: 2rem 0;">
                    <h3 style="color: var(--primary); margin-bottom: 1rem;">📸 Face Recognition</h3>
                    
                    <div style="background: var(--background); padding: 1.5rem; border-radius: 12px; margin-bottom: 1rem;">
                        <video id="webcam" width="100%" height="400" autoplay style="border-radius: 8px; background: #000;"></video>
                        <canvas id="canvas" style="display: none;"></canvas>
                    </div>
                    
                    <div style="display: flex; gap: 1rem; justify-content: center;">
                        <button class="btn btn-primary" id="captureBtn" onclick="captureAttendance()">
                            📸 Capture & Mark Attendance
                        </button>
                        <button class="btn btn-secondary" onclick="stopCamera()">
                            ❌ Cancel
                        </button>
                    </div>
                    
                    <div id="faceError" class="error-message" style="display: none; margin-top: 1rem;"></div>
                    <div id="faceSuccess" class="success-message" style="display: none; margin-top: 1rem;"></div>
                </div>
                
                <!-- Manual Entry Section -->
                <div id="manualEntrySection" style="display: none; margin-top: 2rem;">
                    <hr style="border-color: var(--border); margin: 2rem 0;">
                    <h3 style="color: var(--primary); margin-bottom: 1rem;">✍️ Manual Attendance</h3>
                    
                    <form id="manualAttendanceForm">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        
                        <div class="form-group">
                            <label>Your Name</label>
                            <input type="text" value="<?= htmlspecialchars($user['full_name']) ?>" disabled>
                        </div>
                        
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                        </div>
                        
                        <div class="form-group">
                            <label>Role</label>
                            <input type="text" value="<?= ucfirst($user['role']) ?>" disabled>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirmation">Type "CONFIRM" to mark your attendance</label>
                            <input type="text" id="confirmation" name="confirmation" required placeholder="Type CONFIRM">
                        </div>
                        
                        <div id="manualError" class="error-message" style="display: none;"></div>
                        <div id="manualSuccess" class="success-message" style="display: none;"></div>
                        
                        <button type="submit" class="btn btn-primary" id="manualBtn">
                            ✓ Mark Attendance
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="table-container" style="margin-top: 2rem;">
                <h2>Today's Attendance Summary</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
                    <div class="stat-card">
                        <h3>Your Status</h3>
                        <div class="stat-value" style="color: var(--warning);">Not Marked</div>
                    </div>
                    <div class="stat-card">
                        <h3>Check-in Time</h3>
                        <div class="stat-value" style="font-size: 1.5rem;">--:--</div>
                    </div>
                    <div class="stat-card">
                        <h3>Status</h3>
                        <div class="stat-value" style="font-size: 1.5rem;">--</div>
                    </div>
                </div>
            </div>
            
            <?php endif; ?>
        </main>
    </div>

    <script src="js/theme.js"></script>
    <script>
        let stream = null;
        
        // Update current time
        function updateTime() {
            const now = new Date();
            document.getElementById('currentTime').textContent = now.toLocaleTimeString();
        }
        setInterval(updateTime, 1000);
        updateTime();
        
        // Show Face Recognition
        async function showFaceRecognition() {
            document.getElementById('faceRecognitionSection').style.display = 'block';
            document.getElementById('manualEntrySection').style.display = 'none';
            
            try {
                stream = await navigator.mediaDevices.getUserMedia({ video: true });
                document.getElementById('webcam').srcObject = stream;
            } catch (error) {
                document.getElementById('faceError').textContent = 'Camera access denied. Please enable camera permissions.';
                document.getElementById('faceError').style.display = 'block';
            }
        }
        
        // Show Manual Entry
        function showManualEntry() {
            document.getElementById('manualEntrySection').style.display = 'block';
            document.getElementById('faceRecognitionSection').style.display = 'none';
            stopCamera();
        }
        
        // Stop Camera
        function stopCamera() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
            }
            document.getElementById('faceRecognitionSection').style.display = 'none';
        }
        
        // Capture Attendance (Simulated)
        async function captureAttendance() {
            const btn = document.getElementById('captureBtn');
            const errorMsg = document.getElementById('faceError');
            const successMsg = document.getElementById('faceSuccess');
            
            btn.disabled = true;
            btn.textContent = 'Processing...';
            errorMsg.style.display = 'none';
            successMsg.style.display = 'none';
            
            try {
                const response = await fetch('api/mark_attendance.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        user_id: <?= $user['user_id'] ?>,
                        recognition_confidence: 95.5,
                        csrf_token: '<?= $csrf_token ?>'
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    successMsg.innerHTML = `<strong>✓ Success!</strong><br>Attendance marked at ${data.data.check_in_time}<br>Status: ${data.data.status.toUpperCase()}`;
                    successMsg.style.display = 'block';
                    stopCamera();
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    errorMsg.textContent = data.error || 'Failed to mark attendance';
                    errorMsg.style.display = 'block';
                }
            } catch (error) {
                errorMsg.textContent = 'Network error. Please try again.';
                errorMsg.style.display = 'block';
            } finally {
                btn.disabled = false;
                btn.textContent = '📸 Capture & Mark Attendance';
            }
        }
        
        // Manual Attendance Form
        document.getElementById('manualAttendanceForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const btn = document.getElementById('manualBtn');
            const errorMsg = document.getElementById('manualError');
            const successMsg = document.getElementById('manualSuccess');
            const confirmation = document.getElementById('confirmation').value;
            
            if (confirmation !== 'CONFIRM') {
                errorMsg.textContent = 'Please type "CONFIRM" exactly to proceed';
                errorMsg.style.display = 'block';
                return;
            }
            
            btn.disabled = true;
            btn.textContent = 'Marking...';
            errorMsg.style.display = 'none';
            successMsg.style.display = 'none';
            
            try {
                const response = await fetch('api/mark_attendance.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        user_id: <?= $user['user_id'] ?>,
                        recognition_confidence: 100,
                        csrf_token: '<?= $csrf_token ?>'
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    successMsg.innerHTML = `<strong>✓ Success!</strong><br>Attendance marked at ${data.data.check_in_time}<br>Status: ${data.data.status.toUpperCase()}`;
                    successMsg.style.display = 'block';
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    errorMsg.textContent = data.error || 'Failed to mark attendance';
                    errorMsg.style.display = 'block';
                }
            } catch (error) {
                errorMsg.textContent = 'Network error. Please try again.';
                errorMsg.style.display = 'block';
            } finally {
                btn.disabled = false;
                btn.textContent = '✓ Mark Attendance';
            }
        });
    </script>
</body>
</html>