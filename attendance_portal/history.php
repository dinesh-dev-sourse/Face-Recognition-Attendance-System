<?php
require_once 'includes/utils.php';
requireLogin();

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance History - Attendance Portal</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-logo">🎓 AttendanceAI</div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li><a href="attendance.php">✅ Mark Attendance</a></li>
                <li><a href="history.php" class="active">📜 History</a></li>
                <li><a href="profile.php">👤 Profile</a></li>
                <li><a href="logout.php">🚪 Logout</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="top-bar">
                <h1>Attendance History</h1>
            </div>
            
            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="Search by email or phone..." value="<?= htmlspecialchars($user['email']) ?>">
                <input type="month" id="monthFilter" value="<?= date('Y-m') ?>">
                <button class="btn btn-primary" onclick="searchAttendance()">Search</button>
                <button class="btn btn-secondary" onclick="exportToPDF()">📄 Export PDF</button>
            </div>
            
            <div class="stat-card" style="margin-bottom: 2rem;">
                <h3>Month Summary</h3>
                <div style="display: flex; gap: 2rem; margin-top: 1rem; flex-wrap: wrap;">
                    <div>
                        <strong>Total Records:</strong> <span id="totalRecords">0</span>
                    </div>
                    <div>
                        <strong>Unique Users:</strong> <span id="uniqueUsers">0</span>
                    </div>
                    <div>
                        <strong>Present:</strong> <span style="color: #22C55E;" id="presentCount">0</span>
                    </div>
                    <div>
                        <strong>Late:</strong> <span style="color: #F59E0B;" id="lateCount">0</span>
                    </div>
                </div>
            </div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Check-in Time</th>
                            <th>Status</th>
                            <th>Confidence</th>
                        </tr>
                    </thead>
                    <tbody id="historyTable">
                        <tr>
                            <td colspan="7" style="text-align: center;">
                                <div class="spinner"></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div id="pagination" style="display: flex; justify-content: center; gap: 1rem; margin-top: 2rem;">
            </div>
        </main>
    </div>

    <script src="js/theme.js"></script>
    <script>
        let currentPage = 1;
        
        async function loadHistory(page = 1) {
            const search = document.getElementById('searchInput').value;
            const month = document.getElementById('monthFilter').value;
            
            console.log('Loading history:', { search, month, page }); // Debug
            
            try {
                const response = await fetch(`api/attendance_history.php?page=${page}&search=${encodeURIComponent(search)}&month=${month}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                console.log('History data:', data); // Debug
                
                if (data.success) {
                    renderHistory(data.data.records);
                    renderSummary(data.data.summary);
                    renderPagination(data.data.pagination);
                    currentPage = page;
                } else {
                    showError(data.error || 'Failed to load history');
                }
            } catch (error) {
                console.error('Error loading history:', error);
                showError('Network error: ' + error.message);
            }
        }
        
        function showError(message) {
            document.getElementById('historyTable').innerHTML = `
                <tr>
                    <td colspan="7" style="text-align: center; color: #EF4444; padding: 2rem;">
                        ⚠️ ${message}
                    </td>
                </tr>
            `;
        }
        
        function renderHistory(records) {
            const tbody = document.getElementById('historyTable');
            
            if (!records || records.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">No records found for this search</td></tr>';
                return;
            }
            
            tbody.innerHTML = records.map(record => `
                <tr>
                    <td>${formatDate(record.attendance_date)}</td>
                    <td>${record.full_name}</td>
                    <td>${record.email}</td>
                    <td>${record.role.toUpperCase()}</td>
                    <td>${formatTime(record.check_in_time)}</td>
                    <td><span class="badge badge-${getBadgeClass(record.status)}">${record.status.toUpperCase()}</span></td>
                    <td>${parseFloat(record.recognition_confidence).toFixed(2)}%</td>
                </tr>
            `).join('');
        }
        
        function renderSummary(summary) {
            document.getElementById('totalRecords').textContent = summary?.total_records || 0;
            document.getElementById('uniqueUsers').textContent = summary?.unique_users || 0;
            document.getElementById('presentCount').textContent = summary?.present_count || 0;
            document.getElementById('lateCount').textContent = summary?.late_count || 0;
        }
        
        function renderPagination(pagination) {
            const container = document.getElementById('pagination');
            
            if (!pagination || pagination.total_pages <= 1) {
                container.innerHTML = '';
                return;
            }
            
            let html = '';
            
            if (pagination.current_page > 1) {
                html += `<button class="btn btn-secondary" onclick="loadHistory(${pagination.current_page - 1})">Previous</button>`;
            }
            
            html += `<span style="color: var(--text-primary); padding: 0.75rem;">Page ${pagination.current_page} of ${pagination.total_pages}</span>`;
            
            if (pagination.current_page < pagination.total_pages) {
                html += `<button class="btn btn-secondary" onclick="loadHistory(${pagination.current_page + 1})">Next</button>`;
            }
            
            container.innerHTML = html;
        }
        
        function searchAttendance() {
            loadHistory(1);
        }
        
        function exportToPDF() {
            // Create a printable version
            const printWindow = window.open('', '_blank');
            const month = document.getElementById('monthFilter').value;
            const search = document.getElementById('searchInput').value;
            const summary = {
                total: document.getElementById('totalRecords').textContent,
                users: document.getElementById('uniqueUsers').textContent,
                present: document.getElementById('presentCount').textContent,
                late: document.getElementById('lateCount').textContent
            };
            
            // Get table data
            const table = document.getElementById('historyTable');
            const hasData = table.querySelector('tr') && !table.querySelector('.spinner');
            
            if (!hasData) {
                alert('No data to export. Please search for records first.');
                return;
            }
            
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Attendance Report - ${month}</title>
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            padding: 20px;
                            color: #000;
                        }
                        .header {
                            text-align: center;
                            margin-bottom: 30px;
                            border-bottom: 3px solid #2563EB;
                            padding-bottom: 20px;
                        }
                        .header h1 {
                            color: #2563EB;
                            margin: 0;
                        }
                        .summary {
                            background: #f0f0f0;
                            padding: 15px;
                            margin-bottom: 20px;
                            border-radius: 5px;
                        }
                        .summary-row {
                            display: flex;
                            justify-content: space-around;
                            flex-wrap: wrap;
                        }
                        .summary-item {
                            margin: 10px;
                        }
                        table {
                            width: 100%;
                            border-collapse: collapse;
                            margin-top: 20px;
                        }
                        th, td {
                            border: 1px solid #ddd;
                            padding: 12px;
                            text-align: left;
                        }
                        th {
                            background-color: #2563EB;
                            color: white;
                        }
                        tr:nth-child(even) {
                            background-color: #f9f9f9;
                        }
                        .status-present {
                            color: #22C55E;
                            font-weight: bold;
                        }
                        .status-late {
                            color: #F59E0B;
                            font-weight: bold;
                        }
                        .footer {
                            margin-top: 30px;
                            text-align: center;
                            font-size: 12px;
                            color: #666;
                        }
                        @media print {
                            .no-print { display: none; }
                        }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1>🎓 AttendanceAI</h1>
                        <h2>Attendance Report</h2>
                        <p><strong>Period:</strong> ${month} | <strong>Filter:</strong> ${search || 'All'}</p>
                        <p><strong>Generated:</strong> ${new Date().toLocaleString()}</p>
                    </div>
                    
                    <div class="summary">
                        <h3>Month Summary</h3>
                        <div class="summary-row">
                            <div class="summary-item"><strong>Total Records:</strong> ${summary.total}</div>
                            <div class="summary-item"><strong>Unique Users:</strong> ${summary.users}</div>
                            <div class="summary-item"><strong>Present:</strong> <span style="color: #22C55E;">${summary.present}</span></div>
                            <div class="summary-item"><strong>Late:</strong> <span style="color: #F59E0B;">${summary.late}</span></div>
                        </div>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Check-in Time</th>
                                <th>Status</th>
                                <th>Confidence</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${table.innerHTML}
                        </tbody>
                    </table>
                    
                    <div class="footer">
                        <p>This is an official attendance record generated by AttendanceAI System</p>
                        <p>Organization: keystone school of engineering</p>
                    </div>
                    
                    <div class="no-print" style="text-align: center; margin-top: 30px;">
                        <button onclick="window.print()" style="padding: 10px 30px; background: #2563EB; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;">
                            Print / Save as PDF
                        </button>
                        <button onclick="window.close()" style="padding: 10px 30px; background: #6B7280; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; margin-left: 10px;">
                            Close
                        </button>
                    </div>
                </body>
                </html>
            `);
            
            printWindow.document.close();
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
        
        // Load history on page load
        loadHistory();
        
        // Allow Enter key to search
        document.getElementById('searchInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                searchAttendance();
            }
        });
        
        document.getElementById('monthFilter').addEventListener('change', () => {
            searchAttendance();
        });
    </script>
</body>
</html>