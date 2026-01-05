<?php
// Complete Diagnostic Tool
?>
<!DOCTYPE html>
<html>
<head>
    <title>Complete System Check</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { background: #d4edda; color: #155724; padding: 10px; margin: 10px 0; border-radius: 4px; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 4px; }
        .warning { background: #fff3cd; color: #856404; padding: 10px; margin: 10px 0; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        th { background: #2563EB; color: white; }
        .exists { background: #d4edda; }
        .missing { background: #f8d7da; }
        .btn { padding: 10px 20px; background: #2563EB; color: white; text-decoration: none; border-radius: 4px; display: inline-block; margin: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Complete System Diagnostic</h1>
        
        <div class="success">
            <strong>✓ PHP is Working!</strong><br>
            Directory: <?= __DIR__ ?>
        </div>
        
        <h2>📁 All Files in Directory:</h2>
        <table>
            <tr>
                <th>Type</th>
                <th>Name</th>
                <th>Size</th>
                <th>Permissions</th>
            </tr>
            <?php
            $files = scandir(__DIR__);
            foreach ($files as $file) {
                if ($file != '.' && $file != '..') {
                    $fullPath = __DIR__ . '/' . $file;
                    $isDir = is_dir($fullPath);
                    $size = $isDir ? '-' : filesize($fullPath) . ' bytes';
                    $readable = is_readable($fullPath) ? '✓ Readable' : '✗ Not Readable';
                    $icon = $isDir ? '📁' : '📄';
                    
                    echo "<tr>";
                    echo "<td>$icon</td>";
                    echo "<td>$file</td>";
                    echo "<td>$size</td>";
                    echo "<td>$readable</td>";
                    echo "</tr>";
                }
            }
            ?>
        </table>
        
        <h2>📋 Required Files Check:</h2>
        <table>
            <tr>
                <th>File</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            <?php
            $required = [
                'index.html',
                'login.php',
                'register.php',
                'register_organization.php',
                'dashboard.php',
                'attendance.php',
                'history.php',
                'profile.php',
                'logout.php'
            ];
            
            $missing = [];
            
            foreach ($required as $file) {
                $exists = file_exists(__DIR__ . '/' . $file);
                $class = $exists ? 'exists' : 'missing';
                $status = $exists ? '✓ EXISTS' : '✗ MISSING';
                $action = $exists ? "<a href='$file' class='btn' target='_blank'>Open</a>" : "<strong>CREATE THIS FILE!</strong>";
                
                if (!$exists) $missing[] = $file;
                
                echo "<tr class='$class'>";
                echo "<td><strong>$file</strong></td>";
                echo "<td>$status</td>";
                echo "<td>$action</td>";
                echo "</tr>";
            }
            ?>
        </table>
        
        <?php if (count($missing) > 0): ?>
        <div class="error">
            <h3>⚠️ MISSING FILES (<?= count($missing) ?>):</h3>
            <?php foreach ($missing as $file): ?>
                <strong>• <?= $file ?></strong> - YOU NEED TO CREATE THIS!<br>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="success">
            <h3>✓ All main files present!</h3>
            <a href="index.html" class="btn">Go to Home Page</a>
            <a href="login.php" class="btn">Go to Login</a>
            <a href="dashboard.php" class="btn">Go to Dashboard</a>
        </div>
        <?php endif; ?>
        
        <h2>📂 Subdirectories Check:</h2>
        <table>
            <tr>
                <th>Folder</th>
                <th>Status</th>
                <th>Contents</th>
            </tr>
            <?php
            $requiredDirs = ['api', 'config', 'includes', 'css', 'js', 'uploads', 'python'];
            
            foreach ($requiredDirs as $dir) {
                $exists = is_dir(__DIR__ . '/' . $dir);
                $class = $exists ? 'exists' : 'missing';
                $status = $exists ? '✓ EXISTS' : '✗ MISSING';
                
                $contents = '-';
                if ($exists) {
                    $dirFiles = scandir(__DIR__ . '/' . $dir);
                    $fileCount = count($dirFiles) - 2; // exclude . and ..
                    $contents = "$fileCount files";
                }
                
                echo "<tr class='$class'>";
                echo "<td><strong>$dir/</strong></td>";
                echo "<td>$status</td>";
                echo "<td>$contents</td>";
                echo "</tr>";
            }
            ?>
        </table>
        
        <h2>🔌 Database Connection Test:</h2>
        <?php
        try {
            $pdo = new PDO("mysql:host=localhost;port=3307;dbname=attendance_portal", "root", "");
            echo "<div class='success'>✓ Database Connected!</div>";
            
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo "<table>";
            echo "<tr><th>Table Name</th><th>Status</th></tr>";
            $requiredTables = ['organizations', 'users', 'attendance'];
            foreach ($requiredTables as $table) {
                $exists = in_array($table, $tables);
                $class = $exists ? 'exists' : 'missing';
                $status = $exists ? '✓ EXISTS' : '✗ MISSING';
                echo "<tr class='$class'><td>$table</td><td>$status</td></tr>";
            }
            echo "</table>";
            
            // Count records
            if (in_array('users', $tables)) {
                $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
                echo "<div class='warning'>Total Users: <strong>$count</strong></div>";
            }
            
            if (in_array('attendance', $tables)) {
                $count = $pdo->query("SELECT COUNT(*) FROM attendance")->fetchColumn();
                echo "<div class='warning'>Total Attendance Records: <strong>$count</strong></div>";
            }
            
        } catch (PDOException $e) {
            echo "<div class='error'>✗ Database Error: " . $e->getMessage() . "</div>";
        }
        ?>
        
        <h2>📝 Next Steps:</h2>
        <?php if (count($missing) > 0): ?>
        <div class="warning">
            <p><strong>You need to create these files:</strong></p>
            <ol>
                <?php foreach ($missing as $file): ?>
                <li>Create <strong><?= $file ?></strong> in <?= __DIR__ ?></li>
                <?php endforeach; ?>
            </ol>
            <p><strong>How to create:</strong></p>
            <ul>
                <li>Go back to the chat</li>
                <li>Find the artifact for each missing file</li>
                <li>Copy the code</li>
                <li>Save in the correct location</li>
            </ul>
        </div>
        <?php else: ?>
        <div class="success">
            <p><strong>✓ System Ready!</strong> All files are present.</p>
            <a href="index.html" class="btn">Start Using the System</a>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>