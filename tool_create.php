<?php
// tool_create.php - Updated to use session password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['db_name'])) {
    // Get the root password from session
    $root_password = $_SESSION['mysql_root_password'] ?? '';
    
    if (empty($root_password)) {
        echo '<div class="error fade-in">Please login first to create databases.</div>';
    } else {
        $db_name = $_POST['db_name'] ?? '';
        $db_user = $_POST['db_user'] ?? '';
        $db_password = $_POST['db_password'] ?? '';
        
        if (!empty($db_name) && !empty($db_user) && !empty($db_password)) {
            try {
                // Connect as root using session password
                $pdo = new PDO("mysql:host=localhost", 'root', $root_password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                $output = [];
                
                // 1. Create database
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name`");
                $output[] = "✅ Database '$db_name' created";
                
                // 2. Create user
                $pdo->exec("CREATE USER IF NOT EXISTS '$db_user'@'localhost' IDENTIFIED BY '$db_password'");
                $output[] = "✅ User '$db_user' created";
                
                // 3. Grant privileges
                $pdo->exec("GRANT ALL PRIVILEGES ON `$db_name`.* TO '$db_user'@'localhost'");
                $pdo->exec("FLUSH PRIVILEGES");
                $output[] = "✅ Privileges granted to '$db_user' on database '$db_name'";
                
                // Generate config file content
                $config_content = <<<EOT
<?php
// config_db.php - Auto-generated database configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', '$db_user');
define('DB_PASSWORD', '$db_password');
define('DB_NAME', '$db_name');

try {
    \$pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    \$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException \$e) {
    die("ERROR: Could not connect. " . \$e->getMessage());
}
?>
EOT;
                
                echo '<div class="success fade-in">';
                echo '<h3>✅ Database & User Setup Completed!</h3>';
                echo implode('<br>', $output);
                echo '</div>';
                
                echo '<div class="info fade-in">';
                echo '<h4>📋 Next Steps:</h4>';
                echo 'The database and user have been created. Now you can:';
                echo '<ol>';
                echo '<li>Use the generated config file below in your PHP applications</li>';
                echo '<li>Connect as the new user to create tables and insert data</li>';
                echo '<li>Use phpMyAdmin or command line to manage your database</li>';
                echo '</ol>';
                echo '</div>';
                
                echo '<div class="code fade-in">';
                echo '<h4>📁 config_db.php content:</h4>';
                echo '<pre>' . htmlspecialchars($config_content) . '</pre>';
                echo '<p>Copy this content and save it as <strong>config_db.php</strong> in your project directory.</p>';
                echo '</div>';
                
            } catch (PDOException $e) {
                echo '<div class="error fade-in">';
                echo '❌ Error: ' . $e->getMessage();
                echo '<br>Check your MySQL root password and try again.';
                echo '</div>';
            }
        } else {
            echo '<div class="error fade-in">Please fill all fields!</div>';
        }
    }
}
?>

<div class="columns-container">
    <!-- Form Column -->
    <div class="form-column">
        <form method="post" action="?tool=create">
            <div class="form-group">
                <label for="db_name">Database Name:</label>
                <input type="text" id="db_name" name="db_name" required placeholder="e.g., myapp_db">
            </div>
            
            <div class="form-group">
                <label for="db_user">Database User:</label>
                <input type="text" id="db_user" name="db_user" required placeholder="e.g., myapp_user">
            </div>
            
            <div class="form-group">
                <label for="db_password">Database User Password:</label>
                <input type="password" id="db_password" name="db_password" required placeholder="Strong password">
            </div>
            
            <button type="submit">🚀 Create Database & User</button>
        </form>
        
        <div class="info" style="margin-top: 20px;">
            <h4>ℹ️ Note:</h4>
            <p>Using MySQL root password from your current session. No need to enter it again.</p>
        </div>
    </div>
    
    <!-- Output Column -->
    <div class="output-column">
        <?php if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['db_name'])): ?>
        <div class="output-placeholder">
            <h3>👈 Fill out the form to create a database</h3>
            <p>Your setup results will appear here after submission</p>
        </div>
        <?php endif; ?>
    </div>
</div>