<?php
// [file name]: admin.php
session_start();

// Credentials: set env vars or fall back to defaults (CHANGE THESE!)
define('ADMIN_USERNAME', getenv('LOTTERY_ADMIN_USER') ?: 'admin');
define('ADMIN_PASSWORD', getenv('LOTTERY_ADMIN_PASS') ?: 'admin123');

// بررسی لاگین
if (!isset($_SESSION['logged_in'])) {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
        if ($_POST['username'] == ADMIN_USERNAME && $_POST['password'] == ADMIN_PASSWORD) {
            $_SESSION['logged_in'] = true;
        } else {
            $error = "نام کاربری یا رمز عبور اشتباه است!";
        }
    }
    
    if (!isset($_SESSION['logged_in'])) {
        showLoginPage($error ?? null);
        exit;
    }
}

// پردازش درخواست‌ها
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        handlePostRequest($_POST, $_FILES);
    }
}

showAdminPanel();

// ================ توابع ================

function showLoginPage($error = null) {
    ?>
    <!DOCTYPE html>
    <html lang="fa" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ورود به پنل مدیریت</title>
        <style>
            @font-face {
                font-family: 'Vazir Matn';
                src: url('fonts/Vazirmatn.woff2') format('woff2');
            }
            body {
                font-family: 'Vazir Matn', sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                height: 100vh;
                margin: 0;
                display: flex;
                justify-content: center;
                align-items: center;
            }
            .login-container {
                background: white;
                padding: 40px;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                width: 350px;
                animation: fadeIn 0.5s;
            }
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(-20px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .login-container h2 {
                text-align: center;
                color: #333;
                margin-bottom: 30px;
            }
            .form-group {
                margin-bottom: 20px;
            }
            .form-group label {
                display: block;
                margin-bottom: 5px;
                color: #555;
            }
            .form-group input {
                width: 100%;
                padding: 12px;
                border: 2px solid #e0e0e0;
                border-radius: 10px;
                font-family: 'Vazir Matn', sans-serif;
                box-sizing: border-box;
            }
            .login-btn {
                width: 100%;
                padding: 12px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                border-radius: 10px;
                font-family: 'Vazir Matn', sans-serif;
                font-size: 16px;
                cursor: pointer;
            }
            .error {
                color: #e74c3c;
                text-align: center;
                margin-bottom: 20px;
                padding: 10px;
                background: #fde8e8;
                border-radius: 10px;
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <h2>🎯 ورود به پنل مدیریت</h2>
            <?php if ($error): ?>
                <div class="error">❌ <?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label>👤 نام کاربری</label>
                    <input type="text" name="username" required autofocus>
                </div>
                <div class="form-group">
                    <label>🔒 رمز عبور</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" name="login" class="login-btn">🚀 ورود</button>
            </form>
        </div>
    </body>
    </html>
    <?php
}

function handlePostRequest($post, $files) {
    switch ($post['action']) {
        case 'logout':
            session_destroy();
            header('Location: admin.php');
            exit;
            
        case 'add_participant':
            addParticipant($post);
            break;
            
        case 'edit_participant':
            editParticipant($post);
            break;
            
        case 'delete_participant':
            deleteParticipant($post['index']);
            break;
            
        case 'delete_all_participants':
            deleteAllParticipants();
            break;
            
        case 'delete_winner':
            deleteWinner($post['index']);
            break;
            
        case 'delete_all_winners':
            deleteAllWinners();
            break;
            
        case 'clear_winners':
            clearWinners();
            break;
            
        case 'import_text':
            importFromText($post['text_data']);
            break;
            
        case 'import_excel':
            importFromExcel($files['excel_file'] ?? null);
            break;
            
        case 'upload_background':
            uploadBackground($files['background_image'] ?? null);
            break;
    }
    
    header('Location: admin.php');
    exit;
}

function getParticipants() {
    if (!file_exists('participants.json')) {
        return [];
    }
    $content = file_get_contents('participants.json');
    return json_decode($content, true) ?: [];
}

function saveParticipants($participants) {
    file_put_contents('participants.json', json_encode($participants, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function addParticipant($data) {
    $participants = getParticipants();
    
    // بررسی تکراری نبودن کد ملی
    foreach ($participants as $p) {
        if ($p['national_id'] == $data['national_id']) {
            $_SESSION['error'] = "کد ملی {$data['national_id']} تکراری است!";
            return;
        }
    }
    
    $participants[] = [
        'name' => trim($data['name']),
        'national_id' => trim($data['national_id']),
        'phone' => trim($data['phone'] ?? ''),
        'email' => trim($data['email'] ?? ''),
        'register_date' => date('Y-m-d H:i:s')
    ];
    
    saveParticipants($participants);
    $_SESSION['success'] = "شرکت‌کننده با موفقیت اضافه شد.";
}

function editParticipant($data) {
    $participants = getParticipants();
    $index = $data['index'];
    
    $participants[$index] = [
        'name' => trim($data['name']),
        'national_id' => trim($data['national_id']),
        'phone' => trim($data['phone'] ?? ''),
        'email' => trim($data['email'] ?? ''),
        'register_date' => $participants[$index]['register_date'] ?? date('Y-m-d H:i:s')
    ];
    
    saveParticipants($participants);
    $_SESSION['success'] = "اطلاعات با موفقیت ویرایش شد.";
}

function deleteParticipant($index) {
    $participants = getParticipants();
    array_splice($participants, $index, 1);
    saveParticipants($participants);
    $_SESSION['success'] = "شرکت‌کننده با موفقیت حذف شد.";
}

function deleteAllParticipants() {
    saveParticipants([]);
    $_SESSION['success'] = "همه شرکت‌کنندگان با موفقیت حذف شدند.";
}

function getWinners() {
    $winners = [];
    if (file_exists('winners.txt')) {
        $lines = file('winners.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                $parts = explode(' - ', $line);
                $winners[] = [
                    'name' => $parts[0] ?? 'نامشخص',
                    'national_id' => $parts[1] ?? 'نامشخص',
                    'full_text' => $line
                ];
            }
        }
    }
    return $winners;
}

function saveWinners($winners) {
    $lines = array_column($winners, 'full_text');
    file_put_contents('winners.txt', implode(PHP_EOL, $lines));
}

function deleteWinner($index) {
    $winners = getWinners();
    array_splice($winners, $index, 1);
    saveWinners($winners);
    $_SESSION['success'] = "برنده با موفقیت از لیست حذف شد.";
}

function deleteAllWinners() {
    file_put_contents('winners.txt', '');
    $_SESSION['success'] = "همه برندگان با موفقیت حذف شدند.";
}

function clearWinners() {
    file_put_contents('winners.txt', '');
    $_SESSION['success'] = "لیست برندگان پاک شد.";
}

function importFromText($text) {
    $lines = explode("\n", trim($text));
    $participants = getParticipants();
    $added = 0;
    $duplicate = 0;
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        // تشخیص فرمت‌های مختلف
        if (strpos($line, ' - ') !== false) {
            list($name, $national_id) = explode(' - ', $line);
        } elseif (strpos($line, ',') !== false) {
            list($name, $national_id) = explode(',', $line);
        } elseif (strpos($line, "\t") !== false) {
            list($name, $national_id) = explode("\t", $line);
        } else {
            // اگر فقط کد ملی باشه
            $parts = preg_split('/\s+/', $line);
            if (count($parts) >= 2) {
                $national_id = array_pop($parts);
                $name = implode(' ', $parts);
            } else {
                continue;
            }
        }
        
        $name = trim($name);
        $national_id = trim($national_id);
        
        // بررسی تکراری بودن
        $exists = false;
        foreach ($participants as $p) {
            if ($p['national_id'] == $national_id) {
                $exists = true;
                $duplicate++;
                break;
            }
        }
        
        if (!$exists && !empty($name) && !empty($national_id)) {
            $participants[] = [
                'name' => $name,
                'national_id' => $national_id,
                'phone' => '',
                'email' => '',
                'register_date' => date('Y-m-d H:i:s')
            ];
            $added++;
        }
    }
    
    saveParticipants($participants);
    $_SESSION['success'] = "{$added} شرکت‌کننده اضافه شد. {$duplicate} مورد تکراری بود.";
}

function importFromExcel($file) {
    if (!$file || $file['error'] != 0) {
        $_SESSION['error'] = "خطا در آپلود فایل!";
        return;
    }
    
    // خواندن فایل CSV (ساده‌ترین فرمت اکسل)
    $handle = fopen($file['tmp_name'], 'r');
    $participants = getParticipants();
    $added = 0;
    $duplicate = 0;
    $firstRow = true;
    
    while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
        // رد کردن هدر اگر وجود داشت
        if ($firstRow && (strpos($data[0] ?? '', 'نام') !== false || strpos($data[0] ?? '', 'name') !== false)) {
            $firstRow = false;
            continue;
        }
        $firstRow = false;
        
        if (count($data) >= 2) {
            $name = trim($data[0]);
            $national_id = trim($data[1]);
            
            // بررسی تکراری بودن
            $exists = false;
            foreach ($participants as $p) {
                if ($p['national_id'] == $national_id) {
                    $exists = true;
                    $duplicate++;
                    break;
                }
            }
            
            if (!$exists && !empty($name) && !empty($national_id)) {
                $participants[] = [
                    'name' => $name,
                    'national_id' => $national_id,
                    'phone' => trim($data[2] ?? ''),
                    'email' => trim($data[3] ?? ''),
                    'register_date' => date('Y-m-d H:i:s')
                ];
                $added++;
            }
        }
    }
    
    fclose($handle);
    saveParticipants($participants);
    $_SESSION['success'] = "{$added} شرکت‌کننده از فایل اکسل اضافه شد. {$duplicate} مورد تکراری بود.";
}

function uploadBackground($file) {
    if (!$file || $file['error'] != 0) {
        $_SESSION['error'] = "خطا در آپلود فایل!";
        return;
    }
    
    $allowed = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif'];
    if (!in_array($file['type'], $allowed)) {
        $_SESSION['error'] = "فرمت فایل مجاز نیست!";
        return;
    }
    
    $target_path = 'bg.png';
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        $_SESSION['success'] = "تصویر پس‌زمینه با موفقیت آپلود شد.";
    } else {
        $_SESSION['error'] = "خطا در ذخیره فایل!";
    }
}

function getStats() {
    $participants = getParticipants();
    $winners = getWinners();
    
    return [
        'total_participants' => count($participants),
        'total_winners' => count($winners),
        'remaining' => count($participants) - count($winners),
    ];
}

function showAdminPanel() {
    $participants = getParticipants();
    $winners = getWinners();
    $stats = getStats();
    $success = $_SESSION['success'] ?? null;
    $error = $_SESSION['error'] ?? null;
    unset($_SESSION['success'], $_SESSION['error']);
    ?>
    <!DOCTYPE html>
    <html lang="fa" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>پنل مدیریت قرعه‌کشی</title>
        <style>
            @font-face {
                font-family: 'Vazir Matn';
                src: url('fonts/Vazirmatn.woff2') format('woff2');
            }
            
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: 'Vazir Matn', sans-serif;
                background: #f0f2f5;
            }
            
            /* سایدبار */
            .sidebar {
                position: fixed;
                right: 0;
                top: 0;
                width: 280px;
                height: 100vh;
                background: linear-gradient(180deg, #1a2634 0%, #2c3e50 100%);
                color: white;
                padding: 20px 0;
                box-shadow: -2px 0 10px rgba(0,0,0,0.1);
                overflow-y: auto;
                z-index: 1000;
            }
            
            .sidebar-header {
                padding: 20px 25px;
                border-bottom: 1px solid rgba(255,255,255,0.1);
                text-align: center;
            }
            
            .sidebar-header h3 {
                font-size: 22px;
                font-weight: 500;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }
            
            .menu {
                list-style: none;
                padding: 20px 0;
            }
            
            .menu-item {
                padding: 15px 25px;
                cursor: pointer;
                transition: all 0.3s;
                display: flex;
                align-items: center;
                border-right: 4px solid transparent;
            }
            
            .menu-item:hover {
                background: rgba(255,255,255,0.1);
                border-right-color: #667eea;
            }
            
            .menu-item.active {
                background: rgba(102, 126, 234, 0.2);
                border-right-color: #667eea;
            }
            
            .menu-item i {
                margin-left: 15px;
                font-size: 20px;
                width: 30px;
            }
            
            .logout-btn {
                background: rgba(231, 76, 60, 0.2);
                color: #e74c3c;
                border: 1px solid #e74c3c;
                padding: 12px 25px;
                border-radius: 10px;
                cursor: pointer;
                font-family: 'Vazir Matn', sans-serif;
                margin: 20px 25px;
                width: calc(100% - 50px);
                transition: all 0.3s;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .logout-btn:hover {
                background: #e74c3c;
                color: white;
            }
            
            /* محتوای اصلی */
            .main-content {
                margin-right: 280px;
                padding: 30px;
                min-height: 100vh;
            }
            
            .page {
                display: none;
            }
            
            .page.active {
                display: block;
                animation: fadeIn 0.5s;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            .page-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 30px;
                background: white;
                padding: 20px 30px;
                border-radius: 15px;
                box-shadow: 0 5px 20px rgba(0,0,0,0.05);
                flex-wrap: wrap;
                gap: 15px;
            }
            
            .page-header h2 {
                color: #2c3e50;
                font-size: 24px;
            }
            
            .header-buttons {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
            }
            
            /* کارت‌های آمار */
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 25px;
                margin-bottom: 30px;
            }
            
            .stat-card {
                background: white;
                padding: 25px;
                border-radius: 20px;
                box-shadow: 0 5px 20px rgba(0,0,0,0.05);
                transition: all 0.3s;
            }
            
            .stat-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 15px 40px rgba(102, 126, 234, 0.2);
            }
            
            .stat-icon {
                font-size: 40px;
                color: #667eea;
                margin-bottom: 15px;
            }
            
            .stat-value {
                font-size: 36px;
                font-weight: bold;
                color: #2c3e50;
            }
            
            .stat-label {
                color: #7f8c8d;
                font-size: 16px;
                margin-top: 5px;
            }
            
            /* کارت‌ها */
            .card {
                background: white;
                border-radius: 20px;
                padding: 25px;
                box-shadow: 0 5px 20px rgba(0,0,0,0.05);
                margin-bottom: 30px;
            }
            
            .card-title {
                font-size: 18px;
                color: #2c3e50;
                margin-bottom: 20px;
                padding-bottom: 10px;
                border-bottom: 2px solid #f0f2f5;
                display: flex;
                align-items: center;
            }
            
            .card-title i {
                margin-left: 10px;
                color: #667eea;
            }
            
            /* فرم‌ها */
            .form-group {
                margin-bottom: 20px;
            }
            
            .form-group label {
                display: block;
                margin-bottom: 8px;
                color: #555;
                font-weight: 500;
            }
            
            .form-control {
                width: 100%;
                padding: 12px 15px;
                border: 2px solid #e0e0e0;
                border-radius: 12px;
                font-family: 'Vazir Matn', sans-serif;
                font-size: 14px;
                transition: all 0.3s;
            }
            
            .form-control:focus {
                outline: none;
                border-color: #667eea;
            }
            
            textarea.form-control {
                min-height: 200px;
                direction: ltr;
                font-family: monospace;
            }
            
            /* دکمه‌ها */
            .btn {
                padding: 12px 25px;
                border: none;
                border-radius: 12px;
                font-family: 'Vazir Matn', sans-serif;
                font-size: 14px;
                cursor: pointer;
                transition: all 0.3s;
                display: inline-flex;
                align-items: center;
                gap: 8px;
            }
            
            .btn-primary {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
            }
            
            .btn-primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
            }
            
            .btn-danger {
                background: #e74c3c;
                color: white;
            }
            
            .btn-danger:hover {
                background: #c0392b;
                transform: translateY(-2px);
            }
            
            .btn-warning {
                background: #f39c12;
                color: white;
            }
            
            .btn-success {
                background: #27ae60;
                color: white;
            }
            
            .btn-info {
                background: #3498db;
                color: white;
            }
            
            .btn-sm {
                padding: 8px 15px;
                font-size: 12px;
            }
            
            /* جدول */
            .table-container {
                overflow-x: auto;
                border-radius: 15px;
            }
            
            table {
                width: 100%;
                border-collapse: collapse;
            }
            
            table th {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 15px;
                text-align: center;
                font-weight: 500;
            }
            
            table td {
                padding: 15px;
                text-align: center;
                border-bottom: 1px solid #e0e0e0;
            }
            
            table tr:hover {
                background: #f8f9fa;
            }
            
            .badge {
                padding: 5px 15px;
                border-radius: 50px;
                font-size: 12px;
                font-weight: 500;
                display: inline-block;
            }
            
            .badge-success {
                background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
                color: white;
            }
            
            .badge-warning {
                background: linear-gradient(135deg, #f39c12 0%, #f1c40f 100%);
                color: white;
            }
            
            /* پیام‌ها */
            .message {
                padding: 15px 25px;
                border-radius: 12px;
                margin-bottom: 30px;
                animation: slideDown 0.5s;
            }
            
            @keyframes slideDown {
                from { opacity: 0; transform: translateY(-20px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            .message.success {
                background: #d4edda;
                color: #155724;
                border-right: 4px solid #27ae60;
            }
            
            .message.error {
                background: #f8d7da;
                color: #721c24;
                border-right: 4px solid #e74c3c;
            }
            
            /* مودال */
            .modal {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                justify-content: center;
                align-items: center;
                z-index: 2000;
                backdrop-filter: blur(5px);
            }
            
            .modal.active {
                display: flex;
            }
            
            .modal-content {
                background: white;
                padding: 30px;
                border-radius: 25px;
                width: 600px;
                max-width: 95%;
                max-height: 90vh;
                overflow-y: auto;
                animation: modalFadeIn 0.3s;
            }
            
            @keyframes modalFadeIn {
                from { opacity: 0; transform: scale(0.9); }
                to { opacity: 1; transform: scale(1); }
            }
            
            .modal-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
            }
            
            .modal-header h3 {
                color: #2c3e50;
            }
            
            .close-btn {
                background: none;
                border: none;
                font-size: 28px;
                cursor: pointer;
                color: #999;
                transition: color 0.3s;
            }
            
            .close-btn:hover {
                color: #e74c3c;
            }
            
            .bg-preview {
                max-width: 300px;
                max-height: 200px;
                margin: 20px 0;
                border-radius: 15px;
                border: 3px solid #e0e0e0;
            }
            
            /* راهنما */
            .sample-text {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 10px;
                font-family: monospace;
                direction: ltr;
                text-align: left;
                margin: 15px 0;
                border: 1px solid #e0e0e0;
            }
            
            /* رسپانسیو */
            @media (max-width: 768px) {
                .sidebar {
                    width: 0;
                    transform: translateX(100%);
                }
                
                .main-content {
                    margin-right: 0;
                    padding: 20px;
                }
                
                .page-header {
                    flex-direction: column;
                    align-items: stretch;
                }
                
                .header-buttons {
                    justify-content: flex-start;
                }
            }
        </style>
    </head>
    <body>
        <!-- سایدبار -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>🎯 پنل مدیریت</h3>
                <p>سیستم قرعه‌کشی</p>
            </div>
            
            <ul class="menu">
                <li class="menu-item active" onclick="showPage('dashboard')">
                    <i>📊</i> داشبورد
                </li>
                <li class="menu-item" onclick="showPage('participants')">
                    <i>👥</i> شرکت‌کنندگان
                </li>
                <li class="menu-item" onclick="showPage('winners')">
                    <i>🏆</i> برندگان
                </li>
                <li class="menu-item" onclick="showPage('import')">
                    <i>📥</i> ورود اطلاعات
                </li>
                <li class="menu-item" onclick="showPage('settings')">
                    <i>⚙️</i> تنظیمات
                </li>
            </ul>
            
            <form method="POST">
                <input type="hidden" name="action" value="logout">
                <button type="submit" class="logout-btn">
                    <i>🚪</i> خروج
                </button>
            </form>
        </div>
        
        <!-- محتوای اصلی -->
        <div class="main-content">
            <?php if ($success): ?>
                <div class="message success">
                    ✅ <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message error">
                    ❌ <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <!-- صفحه داشبورد -->
            <div id="dashboard" class="page active">
                <div class="page-header">
                    <h2>📊 داشبورد</h2>
                    <span class="badge badge-success"><?php echo date('Y/m/d H:i'); ?></span>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">👥</div>
                        <div class="stat-value"><?php echo $stats['total_participants']; ?></div>
                        <div class="stat-label">کل شرکت‌کنندگان</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">🏆</div>
                        <div class="stat-value"><?php echo $stats['total_winners']; ?></div>
                        <div class="stat-label">برندگان</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">⏳</div>
                        <div class="stat-value"><?php echo $stats['remaining']; ?></div>
                        <div class="stat-label">در انتظار</div>
                    </div>
                </div>
            </div>
            
            <!-- صفحه شرکت‌کنندگان -->
            <div id="participants" class="page">
                <div class="page-header">
                    <h2>👥 مدیریت شرکت‌کنندگان</h2>
                    <div class="header-buttons">
                        <button class="btn btn-primary" onclick="openAddParticipantModal()">
                            <i>➕</i> افزودن تکی
                        </button>
                        <button class="btn btn-danger" onclick="deleteAllParticipants()">
                            <i>🗑️</i> حذف همه
                        </button>
                    </div>
                </div>
                
                <div class="card">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>نام</th>
                                    <th>کد ملی</th>
                                    <th>تلفن</th>
                                    <th>تاریخ ثبت</th>
                                    <th>وضعیت</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($participants as $index => $p): 
                                    $isWinner = false;
                                    foreach ($winners as $w) {
                                        if ($w['national_id'] == $p['national_id']) {
                                            $isWinner = true;
                                            break;
                                        }
                                    }
                                ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($p['name']); ?></td>
                                    <td><?php echo htmlspecialchars($p['national_id']); ?></td>
                                    <td><?php echo htmlspecialchars($p['phone'] ?? '-'); ?></td>
                                    <td><?php echo $p['register_date'] ?? '-'; ?></td>
                                    <td>
                                        <?php if ($isWinner): ?>
                                            <span class="badge badge-success">🏆 برنده</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">⏳ در انتظار</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" onclick="openEditParticipantModal(<?php echo $index; ?>)">✏️</button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteParticipant(<?php echo $index; ?>)">🗑️</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($participants)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 30px; color: #999;">
                                        👥 هیچ شرکت‌کننده‌ای ثبت نشده است
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- صفحه برندگان -->
            <div id="winners" class="page">
                <div class="page-header">
                    <h2>🏆 لیست برندگان</h2>
                    <div class="header-buttons">
                        <button class="btn btn-danger" onclick="deleteAllWinners()">
                            <i>🗑️</i> حذف همه برندگان
                        </button>
                    </div>
                </div>
                
                <div class="card">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>نام</th>
                                    <th>کد ملی</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($winners as $index => $winner): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($winner['name']); ?></td>
                                    <td><?php echo htmlspecialchars($winner['national_id']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-danger" onclick="deleteWinner(<?php echo $index; ?>)">🗑️</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($winners)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 30px; color: #999;">
                                        🏆 هنوز برنده‌ای ثبت نشده است
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- صفحه ورود اطلاعات -->
            <div id="import" class="page">
                <div class="page-header">
                    <h2>📥 ورود اطلاعات</h2>
                </div>
                
                <div class="card">
                    <div class="card-title">
                        <i>📝</i> ورود با متن
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="import_text">
                        
                        <div class="form-group">
                            <label>متن لیست شرکت‌کنندگان</label>
                            <textarea name="text_data" class="form-control" placeholder="هر خط: نام - کد ملی&#10;مثال:&#10;علی محمدی - 1234567890&#10;زهرا حسینی,9876543210"><?php echo htmlspecialchars($_POST['text_data'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="sample-text">
                            فرمت‌های قابل قبول:<br>
                            نام - کد ملی<br>
                            نام,کد ملی<br>
                            نام کد ملی
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i>📥</i> ورود اطلاعات
                        </button>
                    </form>
                </div>
                
                <div class="card">
                    <div class="card-title">
                        <i>📊</i> ورود از فایل اکسل (CSV)
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="import_excel">
                        
                        <div class="form-group">
                            <label>فایل CSV را انتخاب کنید</label>
                            <input type="file" name="excel_file" class="form-control" accept=".csv,.txt" required>
                        </div>
                        
                        <div class="sample-text">
                            ساختار فایل CSV:<br>
                            نام,کد ملی,تلفن,ایمیل<br>
                            علی محمدی,1234567890,09123456789,ali@test.com
                        </div>
                        
                        <button type="submit" class="btn btn-success">
                            <i>📤</i> آپلود و ورود
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- صفحه تنظیمات -->
            <div id="settings" class="page">
                <div class="page-header">
                    <h2>⚙️ تنظیمات</h2>
                </div>
                
                <div class="card">
                    <div class="card-title">
                        <i>🖼️</i> تصویر پس‌زمینه
                    </div>
                    
                    <?php if (file_exists('bg.png')): ?>
                        <div style="text-align: center;">
                            <img src="bg.png?<?php echo time(); ?>" class="bg-preview">
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="upload_background">
                        
                        <div class="form-group">
                            <label>انتخاب تصویر جدید</label>
                            <input type="file" name="background_image" class="form-control" accept="image/*" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i>📤</i> آپلود
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- مودال شرکت‌کننده -->
        <div id="participantModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="modalTitle">➕ افزودن شرکت‌کننده</h3>
                    <button class="close-btn" onclick="closeModal()">&times;</button>
                </div>
                
                <form method="POST" id="participantForm">
                    <input type="hidden" name="action" id="formAction" value="add_participant">
                    <input type="hidden" name="index" id="participantIndex" value="">
                    
                    <div class="form-group">
                        <label>نام و نام خانوادگی</label>
                        <input type="text" name="name" id="participantName" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>کد ملی</label>
                        <input type="text" name="national_id" id="participantNationalId" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>تلفن (اختیاری)</label>
                        <input type="text" name="phone" id="participantPhone" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label>ایمیل (اختیاری)</label>
                        <input type="email" name="email" id="participantEmail" class="form-control">
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i>💾</i> ذخیره
                    </button>
                </form>
            </div>
        </div>
        
        <script>
            const participants = <?php echo json_encode($participants, JSON_UNESCAPED_UNICODE); ?>;
            
            function showPage(pageId) {
                document.querySelectorAll('.menu-item').forEach(item => {
                    item.classList.remove('active');
                });
                event.currentTarget.classList.add('active');
                
                document.querySelectorAll('.page').forEach(page => {
                    page.classList.remove('active');
                });
                document.getElementById(pageId).classList.add('active');
            }
            
            function openAddParticipantModal() {
                document.getElementById('modalTitle').textContent = '➕ افزودن شرکت‌کننده';
                document.getElementById('formAction').value = 'add_participant';
                document.getElementById('participantName').value = '';
                document.getElementById('participantNationalId').value = '';
                document.getElementById('participantPhone').value = '';
                document.getElementById('participantEmail').value = '';
                document.getElementById('participantIndex').value = '';
                document.getElementById('participantModal').classList.add('active');
            }
            
            function openEditParticipantModal(index) {
                const p = participants[index];
                
                document.getElementById('modalTitle').textContent = '✏️ ویرایش شرکت‌کننده';
                document.getElementById('formAction').value = 'edit_participant';
                document.getElementById('participantName').value = p.name;
                document.getElementById('participantNationalId').value = p.national_id;
                document.getElementById('participantPhone').value = p.phone || '';
                document.getElementById('participantEmail').value = p.email || '';
                document.getElementById('participantIndex').value = index;
                document.getElementById('participantModal').classList.add('active');
            }
            
            function closeModal() {
                document.getElementById('participantModal').classList.remove('active');
            }
            
            function deleteParticipant(index) {
                if (confirm('آیا از حذف این شرکت‌کننده اطمینان دارید؟')) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    
                    const action = document.createElement('input');
                    action.type = 'hidden';
                    action.name = 'action';
                    action.value = 'delete_participant';
                    
                    const idx = document.createElement('input');
                    idx.type = 'hidden';
                    idx.name = 'index';
                    idx.value = index;
                    
                    form.appendChild(action);
                    form.appendChild(idx);
                    document.body.appendChild(form);
                    form.submit();
                }
            }
            
            function deleteAllParticipants() {
                if (confirm('⚠️ آیا از حذف همه شرکت‌کنندگان اطمینان دارید؟')) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    
                    const action = document.createElement('input');
                    action.type = 'hidden';
                    action.name = 'action';
                    action.value = 'delete_all_participants';
                    
                    form.appendChild(action);
                    document.body.appendChild(form);
                    form.submit();
                }
            }
            
            function deleteWinner(index) {
                if (confirm('آیا از حذف این برنده اطمینان دارید؟')) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    
                    const action = document.createElement('input');
                    action.type = 'hidden';
                    action.name = 'action';
                    action.value = 'delete_winner';
                    
                    const idx = document.createElement('input');
                    idx.type = 'hidden';
                    idx.name = 'index';
                    idx.value = index;
                    
                    form.appendChild(action);
                    form.appendChild(idx);
                    document.body.appendChild(form);
                    form.submit();
                }
            }
            
            function deleteAllWinners() {
                if (confirm('⚠️ آیا از حذف همه برندگان اطمینان دارید؟')) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    
                    const action = document.createElement('input');
                    action.type = 'hidden';
                    action.name = 'action';
                    action.value = 'delete_all_winners';
                    
                    form.appendChild(action);
                    document.body.appendChild(form);
                    form.submit();
                }
            }
            
            window.onclick = function(event) {
                const modal = document.getElementById('participantModal');
                if (event.target == modal) {
                    modal.classList.remove('active');
                }
            }
        </script>
    </body>
    </html>
    <?php
}
?>