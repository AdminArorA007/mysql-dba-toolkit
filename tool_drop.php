<?php
// tool_drop.php - Updated to use session password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['db_name'])) {
    // Get the root password from session
    $root_password = $_SESSION['mysql_root_password'] ?? '';
    
    if (empty($root_password)) {
        echo '<div class="error fade-in">Please login first to drop databases.</div>';
    } else {
        $db_name = $_POST['db_name'];
        $db_user = $_POST['db_user'];
        $confirmation = $_POST['confirmation'] ?? '';
        
        if (!empty($db_name) && !empty($db_user)) {
            if ($confirmation === 'DELETE') {
                try {
                    $pdo = new PDO("mysql:host=localhost", 'root', $root_password);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    $output = [];
                    
                    // Drop database
                    $pdo->exec("DROP DATABASE IF EXISTS `$db_name`");
                    $output[] = "✅ Database '$db_name' dropped";
                    
                    // Drop user
                    $pdo->exec("DROP USER IF EXISTS '$db_user'@'localhost'");
                    $output[] = "✅ User '$db_user' dropped";
                    
                    $pdo->exec("FLUSH PRIVILEGES");
                    
                    echo '<div class="success fade-in">';
                    echo '<h3>✅ Database & User Removal Completed!</h3>';
                    echo implode('<br>', $output);
                    echo '</div>';
                    
                } catch (PDOException $e) {
                    echo '<div class="error fade-in">';
                    echo '❌ Error: ' . $e->getMessage();
                    echo '</div>';
                }
            } else {
                echo '<div class="error fade-in">';
                echo '❌ Please type "DELETE" in the confirmation box to proceed';
                echo '</div>';
            }
        } else {
            echo '<div class="error fade-in">Please fill all required fields!</div>';
        }
    }
}
?>

<div class="columns-container">
    <!-- Form Column -->
    <div class="form-column">
        <form method="post" action="?tool=drop">
            <div class="warning">
                <h4>⚠️ Warning: Destructive Operation</h4>
                <p>This action will permanently delete the database and user. This cannot be undone!</p>
            </div>
            
            <div class="form-group">
                <label for="db_name">Database Name to Drop:</label>
                <input type="text" id="db_name" name="db_name" required placeholder="e.g., myapp_db">
            </div>
            
            <div class="form-group">
                <label for="db_user">Database User to Drop:</label>
                <input type="text" id="db_user" name="db_user" required placeholder="e.g., myapp_user">
            </div>
            
            <div class="form-group">
                <label for="confirmation">Type "DELETE" to confirm:</label>
                <input type="text" id="confirmation" name="confirmation" required placeholder="DELETE" style="border-color: var(--danger);">
            </div>
            
            <button type="submit" style="background: var(--danger);">🗑️ Drop Database & User</button>
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
            <h3>⚠️ Dangerous Operation</h3>
            <p>This tool will permanently remove databases and users. Use with caution!</p>
            <div class="info">
                <h4>Safety Features:</h4>
                <ul>
                    <li>Confirmation requirement</li>
                    <li>No accidental deletions</li>
                    <li>Clear warnings</li>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>