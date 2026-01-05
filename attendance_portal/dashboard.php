<?php
require_once 'includes/utils.php';
requireLogin();

$user = getCurrentUser();
$org = getOrganization($user['org_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Attendance Portal</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-logo">🎓 AttendanceAI</div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active">📊 Dashboard</a></li>
                <li><a href="attendance.php">✅ Mark Attendance</a></li>
                <li><a href="history.php">📜 History</a></li>
                <li><a href="profile.php">👤 Profile</a></li>
                <li><a href="logout.php">🚪 Logout</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="top-bar">
                <div>
                    <h1>Welcome, <?= htmlspecialchars($user['full_name']) ?>!</h1>
                    <p><?= ucfirst($user['role']) ?> • <?= htmlspecialchars($org['org_name']) ?></p>
                </div>
                <div class="user-info">
                    <div class="user-avatar">
                        <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                    </div>
                    <span><?= htmlspecialchars($user['email']) ?></span>
                </div>
            </div>
            
            <div class="stats-grid" id="statsGrid">
                <div class="stat-card">
                    <h3>Total Days</h3>
                    <div class="stat-value" id="totalDays">
                        <div class="spinner"></div>
                    </div>
                </div>
                <div class="stat-card">
                    <h3>Present Days</h3>
                    <div class="stat-value" style="color: #22C55E;" id="presentDays">
                        <div class="spinner"></div>
                    </div>
                </div>
                <div class="stat-card">
                    <h3>Late Days</h3>
                    <div class="stat-value" style="color: #F59E0B;" id="lateDays">
                        <div class="spinner"></div>
                    </div>
                </div>
                <div class="stat-card">
                    <h3>Attendance %</h3>
                    <div class="stat-value" id="attendancePercent">
                        <div class="spinner"></div>
                    </div>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                <div class="chart-container">
                    <h2>Monthly Attendance Trend</h2>
                    <canvas id="monthlyChart" style="max-height: 300px;"></canvas>
                </div>
                
                <div class="chart-container">
                    <h2>Gender Distribution</h2>
                    <canvas id="genderChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
            
            <div class="table-container">
                <h2>Recent Attendance</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Check-in Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="recentAttendance">
                        <tr>
                            <td colspan="3" style="text-align: center;">
                                <div class="spinner"></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script src="js/theme.js"></script>
    <script>
        let monthlyChart, genderChart;
        
        async function loadDashboard() {
            try {
                const response = await fetch('api/dashboard.php');
                
                if (!response.ok) {
                    throw new Error('API request failed');
                }
                
                const data = await response.json();
                
                console.log('Dashboard Data:', data); // Debug log
                
                if (data.success) {
                    // API returns data directly in response, not nested in data.data
                    updateStats(data.user_stats || data.data?.user_stats);
                    renderMonthlyChart(data.monthly_stats || data.data?.monthly_stats);
                    renderGenderChart(data.gender_stats || data.data?.gender_stats);
                    renderRecentAttendance(data.recent_attendance || data.data?.recent_attendance);
                } else {
                    console.error('API Error:', data.error);
                    showError('Failed to load dashboard data: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error loading dashboard:', error);
                showError('Network error. Please refresh the page.');
            }
        }
        
        function showError(message) {
            document.getElementById('totalDays').innerHTML = '<span style="font-size: 1rem; color: #EF4444;">Error</span>';
            document.getElementById('presentDays').innerHTML = '<span style="font-size: 1rem; color: #EF4444;">Error</span>';
            document.getElementById('lateDays').innerHTML = '<span style="font-size: 1rem; color: #EF4444;">Error</span>';
            document.getElementById('attendancePercent').innerHTML = '<span style="font-size: 1rem; color: #EF4444;">Error</span>';
            document.getElementById('recentAttendance').innerHTML = `<tr><td colspan="3" style="text-align: center; color: #EF4444;">${message}</td></tr>`;
        }
        
        function updateStats(stats) {
            console.log('Updating stats with:', stats); // Debug log
            
            // Safely handle null or undefined stats and convert to numbers
            const totalDays = parseInt(stats?.total_days) || 0;
            const presentDays = parseInt(stats?.present_days) || 0;
            const lateDays = parseInt(stats?.late_days) || 0;
            const percentage = parseFloat(stats?.attendance_percentage) || 0;
            
            document.getElementById('totalDays').textContent = totalDays;
            document.getElementById('presentDays').textContent = presentDays;
            document.getElementById('lateDays').textContent = lateDays;
            document.getElementById('attendancePercent').textContent = percentage.toFixed(2) + '%';
            
            console.log('Stats updated successfully'); // Debug log
        }
        
        function renderMonthlyChart(monthlyData) {
            const ctx = document.getElementById('monthlyChart').getContext('2d');
            
            if (monthlyChart) {
                monthlyChart.destroy();
            }
            
            // Handle empty data
            if (!monthlyData || monthlyData.length === 0) {
                monthlyData = [{ month: new Date().toISOString().slice(0, 7), attendance_percentage: 0 }];
            }
            
            const labels = monthlyData.map(d => d.month).reverse();
            const percentages = monthlyData.map(d => parseFloat(d.attendance_percentage) || 0).reverse();
            
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            const textColor = isDark ? '#E5E7EB' : '#1F2933';
            const gridColor = isDark ? '#334155' : '#E5E7EB';
            
            monthlyChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Attendance %',
                        data: percentages,
                        borderColor: '#2563EB',
                        backgroundColor: 'rgba(37, 99, 235, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 2,
                    plugins: {
                        legend: {
                            labels: { color: textColor }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: { color: textColor },
                            grid: { color: gridColor }
                        },
                        x: {
                            ticks: { color: textColor },
                            grid: { color: gridColor }
                        }
                    }
                }
            });
        }
        
        function renderGenderChart(genderData) {
            const ctx = document.getElementById('genderChart').getContext('2d');
            
            if (genderChart) {
                genderChart.destroy();
            }
            
            // Handle empty data
            if (!genderData || genderData.length === 0) {
                genderData = [{ gender: 'Unknown', count: 1 }];
            }
            
            const labels = genderData.map(d => d.gender.charAt(0).toUpperCase() + d.gender.slice(1));
            const counts = genderData.map(d => parseInt(d.count) || 0);
            
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            const textColor = isDark ? '#E5E7EB' : '#1F2933';
            
            genderChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: counts,
                        backgroundColor: ['#2563EB', '#38BDF8', '#1E3A8A']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 1.5,
                    plugins: {
                        legend: {
                            labels: { color: textColor },
                            position: 'bottom'
                        }
                    }
                }
            });
        }
        
        function renderRecentAttendance(attendance) {
            const tbody = document.getElementById('recentAttendance');
            
            if (!attendance || attendance.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3" style="text-align: center;">No attendance records yet</td></tr>';
                return;
            }
            
            tbody.innerHTML = attendance.map(record => `
                <tr>
                    <td>${formatDate(record.attendance_date)}</td>
                    <td>${formatTime(record.check_in_time)}</td>
                    <td><span class="badge badge-${getBadgeClass(record.status)}">${record.status.toUpperCase()}</span></td>
                </tr>
            `).join('');
        }
        
        function getBadgeClass(status) {
            switch(status) {
                case 'present': return 'success';
                case 'late': return 'warning';
                case 'absent': return 'danger';
                default: return 'secondary';
            }
        }
        
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        }
        
        function formatTime(timeString) {
            const [hours, minutes] = timeString.split(':');
            const hour = parseInt(hours);
            const ampm = hour >= 12 ? 'PM' : 'AM';
            const displayHour = hour % 12 || 12;
            return `${displayHour}:${minutes} ${ampm}`;
        }
        
        // Load dashboard on page load
        loadDashboard();
    </script>
</body>
</html>