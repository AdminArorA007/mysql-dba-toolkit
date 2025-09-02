<?php
// tool_home.php - Updated with login status
$root_password = $_SESSION['mysql_root_password'] ?? '';
$is_logged_in = !empty($root_password);
?>

<div class="welcome-container">
    <h2>🚀 Welcome to DBA Toolkit</h2>
    <p>Your complete database administration solution for MySQL</p>
    
    <?php if ($is_logged_in): ?>
    <div class="success" style="max-width: 600px; margin: 20px auto;">
        <h3>✅ Logged In Successfully</h3>
        <p>You are connected to MySQL as root. You can now use all the tools without entering your password again.</p>
    </div>
    <?php else: ?>
    <div class="warning" style="max-width: 600px; margin: 20px auto;">
        <h3>🔒 Authentication Required</h3>
        <p>Please login with your MySQL root password to access the DBA tools.</p>
    </div>
    <?php endif; ?>
</div>

<div class="feature-grid">
    <div class="feature-card">
        <div class="feature-icon">🆕</div>
        <h3>Create Database & User</h3>
        <p>Quickly create new databases and users with full privileges. Generate config files for your applications.</p>
    </div>
    
    <div class="feature-card">
        <div class="feature-icon">🗑️</div>
        <h3>Drop Database & User</h3>
        <p>Safely remove databases and users with confirmation prompts to prevent accidental deletions.</p>
    </div>
    
    <div class="feature-card">
        <div class="feature-icon">💾</div>
        <h3>Backup & Restore</h3>
        <p>Create full database backups, download them, or restore from existing backup files.</p>
    </div>
    
<!--    <div class="feature-card">
        <div class="feature-icon">👥</div>
        <h3>Manage Users</h3>
        <p>View, modify, and manage database user accounts and their privileges (coming soon).</p>
    </div>  
    
    <div class="feature-card">
        <div class="feature-icon">📊</div>
        <h3>Database Monitoring</h3>
        <p>Monitor database performance, view active connections, and analyze query performance (coming soon).</p>
    </div>  -->
    
    <div class="feature-card">
        <div class="feature-icon">🔒</div>
        <h3>Secure Session</h3>
        <p>Your MySQL root password is stored securely in your session and cleared when you logout.</p>
    </div>
</div>

<div class="info" style="margin-top: 40px; max-width: 1000px; margin-left: auto; margin-right: auto;">
    <h3>ℹ️ How It Works</h3>
    <ol>
        <li>Login once with your MySQL root password</li>
        <li>Use any tool without re-entering your credentials</li>
        <li>Your password is stored only in your current browser session</li>
        <li>Logout to clear your session and password</li>
    </ol>
    <p><strong>Security Note:</strong> Your password is never stored on disk or transmitted to any server other than your MySQL server.</p>
</div>