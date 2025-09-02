<?php
// Get the backup directory and files
$backup_dir = '/tmp/backups/';
$files = [];
$total_size = 0;
$file_count = 0;

if (is_dir($backup_dir)) {
    $all_files = scandir($backup_dir);
    foreach ($all_files as $file) {
        if ($file !== '.' && $file !== '..') {
            $file_path = $backup_dir . $file;
            $file_size = filesize($file_path);
            $files[] = [
                'name' => $file,
                'path' => $file_path,
                'size' => $file_size,
                'modified' => filemtime($file_path),
                'extension' => pathinfo($file, PATHINFO_EXTENSION)
            ];
            $total_size += $file_size;
            $file_count++;
        }
    }
    
    // Sort by modification time (newest first)
    usort($files, function($a, $b) {
        return $b['modified'] - $a['modified'];
    });
}

// Handle delete all request
$delete_status = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_all'])) {
    $delete_status = 'error';
    $confirmed = $_POST['confirmation'] ?? '';
    
    if ($confirmed === 'DELETE') {
        $deleted_count = 0;
        $errors = [];
        
        foreach ($files as $file) {
            if (unlink($file['path'])) {
                $deleted_count++;
            } else {
                $errors[] = "Could not delete: " . $file['name'];
            }
        }
        
        if ($deleted_count > 0) {
            $delete_status = 'success';
            // Refresh page to show updated file list
            echo '<script>setTimeout(function(){ window.location.reload(); }, 2000);</script>';
        }
        
        if (!empty($errors)) {
            $delete_status = 'partial';
        }
    } else {
        $delete_status = 'confirm_error';
    }
}

// Handle individual file deletion
if (isset($_GET['delete_file'])) {
    $file_to_delete = $_GET['delete_file'];
    if (file_exists($file_to_delete) && strpos(realpath($file_to_delete), realpath($backup_dir)) === 0) {
        if (unlink($file_to_delete)) {
            $delete_status = 'success';
            echo '<script>setTimeout(function(){ window.location.href = "?"; }, 1000);</script>';
        } else {
            $delete_status = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup File Manager</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --dark: #2b2d42;
            --light: #f8f9fa;
            --gray: #6c757d;
            --border: #dee2e6;
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
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            overflow: hidden;
        }
        
        .header {
            background: var(--dark);
            color: white;
            padding: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .header h1 {
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .stats {
            display: flex;
            gap: 20px;
            background: rgba(255,255,255,0.1);
            padding: 12px 20px;
            border-radius: 8px;
        }
        
        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .stat-value {
            font-size: 1.2rem;
            font-weight: bold;
        }
        
        .stat-label {
            font-size: 0.85rem;
            opacity: 0.8;
        }
        
        .content {
            padding: 25px;
        }
        
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .path-display {
            background: var(--light);
            padding: 12px 15px;
            border-radius: 8px;
            font-family: monospace;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background: #e50c67;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(247, 37, 133, 0.3);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }
        
        .files-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .file-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid var(--border);
            position: relative;
        }
        
        .file-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }
        
        .file-header {
            padding: 15px;
            background: var(--light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .file-icon {
            font-size: 1.8rem;
        }
        
        .sql-icon {
            color: #336791;
        }
        
        .zip-icon {
            color: #ff6b35;
        }
        
        .file-actions {
            display: flex;
            gap: 8px;
        }
        
        .action-btn {
            padding: 6px 10px;
            border-radius: 6px;
            background: white;
            border: 1px solid var(--border);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .delete-btn {
            color: var(--danger);
        }
        
        .delete-btn:hover {
            background: var(--danger);
            color: white;
        }
        
        .file-body {
            padding: 15px;
        }
        
        .file-name {
            font-weight: 600;
            margin-bottom: 10px;
            word-break: break-all;
        }
        
        .file-details {
            display: flex;
            justify-content: space-between;
            color: var(--gray);
            font-size: 0.85rem;
        }
        
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            color: var(--gray);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }
        
        .modal.active {
            opacity: 1;
            pointer-events: all;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            transform: translateY(20px);
            transition: transform 0.3s ease;
        }
        
        .modal.active .modal-content {
            transform: translateY(0);
        }
        
        .modal-header {
            margin-bottom: 20px;
            text-align: center;
        }
        
        .modal-icon {
            font-size: 3rem;
            color: var(--danger);
            margin-bottom: 15px;
        }
        
        .modal-body {
            margin-bottom: 25px;
        }
        
        .confirmation-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 16px;
            margin-top: 10px;
            text-align: center;
            letter-spacing: 1px;
            font-weight: 600;
        }
        
        .confirmation-input:focus {
            outline: none;
            border-color: var(--danger);
        }
        
        .modal-footer {
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        
        .btn-cancel {
            background: var(--light);
            color: var(--dark);
        }
        
        .btn-cancel:hover {
            background: #e9ecef;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .alert-partial {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
            }
            
            .stats {
                width: 100%;
                justify-content: center;
            }
            
            .toolbar {
                flex-direction: column;
            }
            
            .path-display {
                width: 100%;
                justify-content: center;
            }
            
            .files-container {
                grid-template-columns: 1fr;
            }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }
        
        .file-age {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
        }
    </style>
</head>
<body>
    <div class="container fade-in">
        <div class="header">
            <h1><i class="fas fa-database"></i> Backup File Manager</h1>
            <div class="stats">
                <div class="stat-item">
                    <div class="stat-value"><?php echo $file_count; ?></div>
                    <div class="stat-label">Files</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo format_size($total_size); ?></div>
                    <div class="stat-label">Total Size</div>
                </div>
            </div>
        </div>
        
        <div class="content">
            <?php if ($delete_status === 'success'): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <div>Files have been successfully deleted.</div>
                </div>
            <?php elseif ($delete_status === 'error' || $delete_status === 'confirm_error'): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <?php 
                        if ($delete_status === 'confirm_error') {
                            echo "Confirmation text was incorrect. No files were deleted.";
                        } else {
                            echo "There was an error deleting some files.";
                        }
                        ?>
                    </div>
                </div>
            <?php elseif ($delete_status === 'partial'): ?>
                <div class="alert alert-partial">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>Some files could not be deleted. Please check file permissions.</div>
                </div>
            <?php endif; ?>
            
            <div class="toolbar">
                <div class="path-display">
                    <i class="fas fa-folder"></i>
                    <span><?php echo realpath($backup_dir); ?></span>
                </div>
                
                <?php if ($file_count > 0): ?>
                <button class="btn btn-danger" onclick="openModal()">
                    <i class="fas fa-trash-alt"></i> Delete All Files
                </button>
                <?php endif; ?>
            </div>
            
            <div class="files-container">
                <?php if ($file_count > 0): ?>
                    <?php foreach ($files as $file): 
                        $file_age = time() - $file['modified'];
                        $age_text = '';
                        if ($file_age < 3600) {
                            $age_text = floor($file_age / 60) . 'm ago';
                        } elseif ($file_age < 86400) {
                            $age_text = floor($file_age / 3600) . 'h ago';
                        } else {
                            $age_text = floor($file_age / 86400) . 'd ago';
                        }
                    ?>
                    <div class="file-card">
                        <span class="file-age"><?php echo $age_text; ?></span>
                        
                        <div class="file-header">
                            <div class="file-icon">
                                <?php if ($file['extension'] === 'sql'): ?>
                                    <i class="fas fa-database sql-icon"></i>
                                <?php elseif ($file['extension'] === 'zip'): ?>
                                    <i class="fas fa-file-archive zip-icon"></i>
                                <?php else: ?>
                                    <i class="fas fa-file"></i>
                                <?php endif; ?>
                            </div>
                            <div class="file-actions">
                                <a href="?delete_file=<?php echo urlencode($file['path']); ?>" 
                                   class="action-btn delete-btn" title="Delete"
                                   onclick="return confirm('Are you sure you want to delete <?php echo htmlspecialchars($file['name']); ?>?')">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                        </div>
                        <div class="file-body">
                            <div class="file-name"><?php echo htmlspecialchars($file['name']); ?></div>
                            <div class="file-details">
                                <span><?php echo format_size($file['size']); ?></span>
                                <span><?php echo date('Y-m-d H:i', $file['modified']); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <h3>No Backup Files Found</h3>
                        <p>The backup directory is empty. No files to manage.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h2>Delete All Backup Files?</h2>
            </div>
            <div class="modal-body">
                <p>This action will permanently delete <strong><?php echo $file_count; ?> files</strong> (<?php echo format_size($total_size); ?>).</p>
                <p>This cannot be undone. Please type <strong>DELETE</strong> to confirm.</p>
                
                <form id="deleteForm" method="post" action="">
                    <input type="hidden" name="delete_all" value="1">
                    <input type="text" name="confirmation" class="confirmation-input" 
                           placeholder="Type DELETE here" required autocomplete="off">
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-cancel" onclick="closeModal()">Cancel</button>
                <button class="btn btn-danger" onclick="confirmDelete()" disabled>
                    <i class="fas fa-trash-alt"></i> Delete All
                </button>
            </div>
        </div>
    </div>
    
    <script>
        const modal = document.getElementById('deleteModal');
        const confirmationInput = document.querySelector('input[name="confirmation"]');
        const deleteButton = document.querySelector('.modal-footer .btn-danger');
        
        function openModal() {
            modal.classList.add('active');
            confirmationInput.focus();
        }
        
        function closeModal() {
            modal.classList.remove('active');
            confirmationInput.value = '';
            deleteButton.disabled = true;
        }
        
        function confirmDelete() {
            document.getElementById('deleteForm').submit();
        }
        
        // Enable delete button only when user types "DELETE"
        confirmationInput.addEventListener('input', function() {
            deleteButton.disabled = this.value !== 'DELETE';
        });
        
        // Close modal when clicking outside
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.classList.contains('active')) {
                closeModal();
            }
        });
    </script>
</body>
</html>

<?php
// Helper function to format file sizes
function format_size($bytes) {
    if ($bytes == 0) return '0 B';
    
    $sizes = ['B', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $i), 2) . ' ' . $sizes[$i];
}
?>