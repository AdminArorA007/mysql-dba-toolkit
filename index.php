<?php
// dba_toolkit.php - Complete Database Administration Toolkit
session_start();

// Handle file downloads FIRST - before any output
if (isset($_GET['download'])) {
    $file = $_GET['download'];
    if (file_exists($file) && strpos(realpath($file), realpath('/tmp/backups/')) === 0) {
        $filename = basename($file);
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        
        // Clear any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set appropriate headers based on file type
        if ($extension === 'zip') {
            header('Content-Type: application/zip');
        } elseif ($extension === 'sql') {
            header('Content-Type: application/sql');
        } else {
            header('Content-Type: application/octet-stream');
        }
        
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($file));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Output file content
        readfile($file);
        exit;
    } else {
        // File not found or outside allowed directory - redirect back with error
        header('Location: ?tool=backup&error=file_not_found');
        exit;
    }
}

$page_title = "DBA Toolkit";
$current_tool = $_GET['tool'] ?? 'home';

// Check if user is logged in (has root password in session)
$is_logged_in = isset($_SESSION['mysql_root_password']);

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ?tool=home');
    exit;
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $root_password = $_POST['root_password'] ?? '';
    
    if (!empty($root_password)) {
        // Test the connection with provided credentials
        try {
            $pdo = new PDO("mysql:host=localhost", 'root', $root_password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Store password in session if connection successful
            $_SESSION['mysql_root_password'] = $root_password;
            $is_logged_in = true;
            
            // Redirect to home page
            header('Location: ?tool=home');
            exit;
        } catch (PDOException $e) {
            $login_error = "Failed to connect: " . $e->getMessage();
        }
    } else {
        $login_error = "Please enter the MySQL root password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <style>
        :root {
            --primary: #007bff;
            --primary-dark: #0056b3;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
            --light: #f8f9fa;
            --dark: #343a40;
            --gray: #6c757d;
            --sidebar-width: 250px;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .app-container {
            max-width: 1600px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            display: flex;
            min-height: 800px;
        }
        
        /* Login overlay */
        .login-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .login-form {
            background: white;
            padding: 30px;
            border-radius: 12px;
            width: 400px;
            max-width: 90%;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .login-form h2 {
            text-align: center;
            margin-bottom: 20px;
            color: var(--primary);
        }
        
        /* Sidebar Navigation */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--dark);
            color: white;
            padding: 0;
        }
        
        .sidebar-header {
            padding: 25px;
            background: rgba(0,0,0,0.2);
            text-align: center;
        }
        
        .sidebar-header h1 {
            font-size: 1.5em;
            margin-bottom: 5px;
            color: white;
        }
        
        .sidebar-header p {
            opacity: 0.8;
            font-size: 0.9em;
        }
        
        .nav-menu {
            list-style: none;
            padding: 20px 0;
        }
        
        .nav-item {
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .nav-link {
            display: block;
            padding: 15px 25px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .nav-link:hover {
            background: rgba(255,255,255,0.1);
            padding-left: 30px;
        }
        
        .nav-link.active {
            background: var(--primary);
            border-left: 4px solid var(--warning);
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .content-header {
            background: var(--primary);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .content-header h2 {
            font-size: 1.8em;
            font-weight: 300;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 15px;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .tool-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
            background: var(--light);
        }
        
        /* Two-column layout for tools */
        .columns-container {
            display: flex;
            gap: 30px;
            min-height: 600px;
        }
        
        .form-column {
            flex: 1;
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .output-column {
            flex: 1;
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            overflow-y: auto;
            max-height: 600px;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }
        
        input[type="text"], 
        input[type="password"],
        input[type="file"],
        select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }
        
        button {
            background: var(--primary);
            color: white;
            padding: 15px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        button:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,123,255,0.3);
        }
        
        /* Message Styles */
        .success { background: #d4edda; color: #155724; padding: 20px; border-radius: 8px; border-left: 4px solid var(--success); margin-bottom: 20px; }
        .error { background: #f8d7da; color: #721c24; padding: 20px; border-radius: 8px; border-left: 4px solid var(--danger); margin-bottom: 20px; }
        .info { background: #d1ecf1; color: #0c5460; padding: 20px; border-radius: 8px; border-left: 4px solid var(--info); margin-bottom: 20px; }
        .warning { background: #fff3cd; color: #856404; padding: 20px; border-radius: 8px; border-left: 4px solid var(--warning); margin-bottom: 20px; }
        
        .code {
            background: #2d3748;
            color: #e2e8f0;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Fira Code', monospace;
            font-size: 14px;
            line-height: 1.5;
            overflow-x: auto;
            margin-bottom: 20px;
        }
        
        .output-placeholder {
            text-align: center;
            color: var(--gray);
            padding: 60px 20px;
            font-style: italic;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .columns-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 200px;
            }
        }
        
        @media (max-width: 768px) {
            .app-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                order: 2;
            }
            
            .main-content {
                order: 1;
            }
            
            .nav-menu {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                padding: 10px;
            }
            
            .nav-item {
                border-bottom: none;
                margin: 5px;
            }
            
            .nav-link {
                padding: 10px 15px;
                border-radius: 6px;
                background: rgba(255,255,255,0.1);
            }
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }
        
        /* Homepage */
        .welcome-container {
            text-align: center;
            padding: 40px 20px;
        }
        
        .welcome-container h2 {
            font-size: 2.5em;
            color: var(--primary);
            margin-bottom: 20px;
        }
        
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 40px;
        }
        
        .feature-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            text-align: center;
        }
        
        .feature-card h3 {
            color: var(--primary);
            margin-bottom: 15px;
        }
        
        .feature-icon {
            font-size: 3em;
            margin-bottom: 15px;
            color: var(--primary);
        }
    </style>
</head>
<body>
    <?php if (!$is_logged_in): ?>
    <div class="login-overlay">
        <div class="login-form fade-in">
            <h2>🔒 DBA Toolkit Login</h2>
            <?php if (isset($login_error)): ?>
                <div class="error"><?php echo $login_error; ?></div>
            <?php endif; ?>
            <form method="post" action="">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label for="root_password">MySQL Root Password:</label>
                    <input type="password" id="root_password" name="root_password" required placeholder="Enter MySQL root password">
                </div>
                <button type="submit">🔓 Login</button>
            </form>
            <div class="info" style="margin-top: 20px;">
                <p>Your password is stored only in your current session and will be cleared when you logout or close your browser.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="app-container">
        <!-- Sidebar Navigation -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h1>🚀 DBA Toolkit</h1>
                <p>Database Administration Made Easy</p>
            </div>
            <ul class="nav-menu">
                <li class="nav-item"><a href="?tool=home" class="nav-link <?php echo $current_tool == 'home' ? 'active' : ''; ?>">🏠 Home</a></li>
                <li class="nav-item"><a href="?tool=create" class="nav-link <?php echo $current_tool == 'create' ? 'active' : ''; ?>">🆕 Create DB & User</a></li>
                <li class="nav-item"><a href="?tool=drop" class="nav-link <?php echo $current_tool == 'drop' ? 'active' : ''; ?>">🗑️ Drop DB & User</a></li>
                <li class="nav-item"><a href="?tool=backup" class="nav-link <?php echo $current_tool == 'backup' ? 'active' : ''; ?>">💾 Backup & Download</a></li>
                <li class="nav-item"><a href="https://www.it-india.net/test/dba-tools/backup-manager.php" class="nav-link" target="_blank">👨🏻‍💼 Backup Manager</a></li>
<!--                <li class="nav-item"><a href="?tool=users" class="nav-link <?php echo $current_tool == 'users' ? 'active' : ''; ?>">👥 Manage Users</a></li>
                <li class="nav-item"><a href="?tool=monitor" class="nav-link <?php echo $current_tool == 'monitor' ? 'active' : ''; ?>">📊 Monitor</a></li>  -->
            </ul>
        </nav>



        <!-- Main Content -->
        <main class="main-content">
            <div class="content-header">
                <h2>
                    <?php
                    $tool_titles = [
                        'home' => 'Welcome to DBA Toolkit',
                        'create' => 'Create Database & User',
                        'drop' => 'Drop Database & User',
                        'backup' => 'Backup & Download Database',
                        'users' => 'Manage Database Users',
                        'monitor' => 'Database Monitoring'
                    ];
                    echo $tool_titles[$current_tool] ?? 'DBA Toolkit';
                    ?>
                </h2>
                <?php if ($is_logged_in): ?>
                <a href="?logout=1" class="logout-btn">🚪 Logout</a>
                <?php endif; ?>
            </div>

            <div class="tool-content">
                <?php
                // Show error message if file not found
                if (isset($_GET['error']) && $_GET['error'] === 'file_not_found') {
                    echo '<div class="error fade-in">File not found or has been deleted.</div>';
                }
                
                // Check if user is logged in before showing tools
                if (!$is_logged_in) {
                    echo '<div class="error">Please login first to access the DBA tools.</div>';
                } else {
                    // Include the appropriate tool
                    switch ($current_tool) {
                        case 'home':
                            include 'tool_home.php';
                            break;
                        case 'create':
                            include 'tool_create.php';
                            break;
                        case 'drop':
                            include 'tool_drop.php';
                            break;
                        case 'backup':
                            include 'tool_backup.php';
                            break;
                        case 'users':
                            echo '<div class="info">🚧 User Management tool coming soon!</div>';
                            break;
                        case 'monitor':
                            echo '<div class="info">🚧 Monitoring tool coming soon!</div>';
                            break;
                        default:
                            include 'tool_home.php';
                    }
                }
                ?>
            </div>
        </main>
    </div>
</body>
</html>