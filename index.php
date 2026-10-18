<?php
// تنظیمات امنیتی
error_reporting(E_ALL);
ini_set('display_errors', 0);

// تابع برای پاسخ‌های JSON
function sendJsonResponse($success, $message = '', $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// مدیریت درخواست‌های AJAX
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    
    // ذخیره برنده جدید
    if ($_POST['action'] == 'saveWinner') {
        $winnerData = $_POST['winnerData'] ?? '';
        
        if (empty($winnerData)) {
            sendJsonResponse(false, 'اطلاعات برنده معتبر نیست');
        }
        
        // اعتبارسنجی فرمت داده
        $parts = explode(' - ', $winnerData);
        if (count($parts) != 2) {
            sendJsonResponse(false, 'فرمت اطلاعات برنده صحیح نیست');
        }
        
        $result = file_put_contents('winners.txt', $winnerData . PHP_EOL, FILE_APPEND | LOCK_EX);
        
        if ($result === false) {
            sendJsonResponse(false, 'خطا در ذخیره اطلاعات');
        }
        
        sendJsonResponse(true, 'برنده با موفقیت ذخیره شد');
    }
    
    // دریافت لیست برندگان
    elseif ($_POST['action'] == 'getWinners') {
        $winners = [];
        
        if (file_exists('winners.txt')) {
            $content = file_get_contents('winners.txt');
            if ($content) {
                $lines = explode("\n", trim($content));
                foreach ($lines as $line) {
                    $parts = explode(' - ', $line);
                    if (count($parts) == 2) {
                        $winners[] = [
                            'name' => $parts[0],
                            'national_id' => $parts[1]
                        ];
                    }
                }
            }
        }
        
        sendJsonResponse(true, '', $winners);
    }
}

// خواندن شرکت‌کنندگان از فایل
function readParticipants() {
    $jsonFile = 'participants.json';
    
    if (!file_exists($jsonFile)) {
        return [];
    }
    
    $json = file_get_contents($jsonFile);
    if ($json === false) {
        return [];
    }
    
    $data = json_decode($json, true);
    if (!is_array($data)) {
        return [];
    }
    
    return $data;
}

// دریافت شرکت‌کنندگان
$participants = readParticipants();
?>

<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سامانه قرعه‌کشی</title>
    <style>
        @font-face {
            font-family: 'Vazir Matn';
            src: url('fonts/Vazirmatn.woff2') format('woff2');
            font-weight: 100 900;
            font-style: normal;
            font-display: swap;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Vazir Matn', -apple-system, BlinkMacSystemFont, sans-serif;
            margin: 0;
            padding: 0;
            background-image: url('bg.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            min-height: 100vh;
            text-align: center;
            color: #f5e6d3;
            direction: rtl;
            position: relative;
            overflow-x: hidden;
        }

        /* الگوی اسلامی (کاشی‌کاری) - فقط روی بکگراند اصلی */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" opacity="0.1"><path d="M50 0 L100 50 L50 100 L0 50 Z" fill="none" stroke="%23d4af37" stroke-width="0.5"/><circle cx="50" cy="50" r="15" fill="none" stroke="%23d4af37" stroke-width="0.5"/></svg>');
            background-size: 100px 100px;
            pointer-events: none;
            z-index: 1;
        }

        .container {
            position: relative;
            z-index: 2;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
            transition: all 0.5s ease;
        }

        .bismillah {
            font-family: 'Vazir Matn', sans-serif;
            font-size: 32px;
            font-weight: 700;
            color: #d4af37;
            margin-bottom: 20px;
            text-shadow: 0 0 15px rgba(212, 175, 55, 0.5);
            position: relative;
            display: inline-block;
            padding: 10px 30px;
            background: linear-gradient(135deg, rgba(0,0,0,0.3) 0%, rgba(212,175,55,0.1) 100%);
            border-radius: 50px;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(212, 175, 55, 0.3);
            letter-spacing: 2px;
        }

        .bismillah::before,
        .bismillah::after {
            content: '☪';
            font-family: 'Vazir Matn', sans-serif;
            font-size: 24px;
            margin: 0 15px;
            color: #d4af37;
            opacity: 0.7;
        }

        .header {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 3;
        }

        .stats-button {
            background: rgba(0, 0, 0, 0.7);
            color: #f5e6d3;
            border: 2px solid #d4af37;
            padding: 8px 16px;
            border-radius: 30px;
            cursor: pointer;
            font-size: 14px;
            font-family: 'Vazir Matn', sans-serif;
            font-weight: 400;
            transition: all 0.3s;
            backdrop-filter: blur(5px);
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }

        .stats-button:hover {
            background: #d4af37;
            color: #000;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(212, 175, 55, 0.4);
        }

        .dua-text {
            font-family: 'Vazir Matn', sans-serif;
            font-size: 18px;
            font-weight: 500;
            color: #f5e6d3;
            margin-bottom: 30px;
            opacity: 0.9;
            background: rgba(0,0,0,0.2);
            padding: 8px 25px;
            border-radius: 30px;
            border: 1px solid rgba(212, 175, 55, 0.3);
            backdrop-filter: blur(5px);
        }

        .result-container {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
            perspective: 1000px;
        }

        .result {
            font-family: 'Vazir Matn', sans-serif;
            font-size: 70px;
            font-weight: 700;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            width: 110px;
            height: 110px;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #f5e6d3;
            margin: 5px;
            transition: all 0.3s;
            border: 2px solid #d4af37;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5), 0 0 20px rgba(212, 175, 55, 0.3);
            position: relative;
            overflow: hidden;
        }

        .result::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(212, 175, 55, 0.1), transparent);
            transform: rotate(45deg);
            animation: shine 3s infinite;
        }

        @keyframes shine {
            0% { transform: translateX(-100%) rotate(45deg); }
            100% { transform: translateX(100%) rotate(45deg); }
        }

        .result.digit-spin {
            animation: quranicSpin 0.15s infinite ease-in-out;
        }

        @keyframes quranicSpin {
            0% { transform: scale(1) rotate(0deg); filter: brightness(1); }
            25% { transform: scale(1.1) rotate(5deg); filter: brightness(1.3); }
            50% { transform: scale(0.9) rotate(-5deg); filter: brightness(0.7); }
            100% { transform: scale(1) rotate(0deg); filter: brightness(1); }
        }

        .result.hidden {
            display: none;
        }

        #winnerInfo {
            display: none;
            text-align: center;
            z-index: 1000;
            animation: winnerAppear 0.8s ease-out;
            position: relative;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(10px);
            padding: 50px 80px;
            border-radius: 30px;
            border: 3px solid #d4af37;
            box-shadow: 0 0 60px rgba(212, 175, 55, 0.4);
            margin-top: 20px;
        }

        @keyframes winnerAppear {
            0% {
                opacity: 0;
                transform: scale(0.5) translateY(50px);
            }
            70% {
                transform: scale(1.05) translateY(-10px);
            }
            100% {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        #winnerInfo::before {
            content: '﴿﴾';
            font-family: 'Vazir Matn', sans-serif;
            position: absolute;
            top: -25px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 50px;
            color: #d4af37;
            opacity: 0.6;
            text-shadow: 0 0 20px rgba(212, 175, 55, 0.5);
        }

        #winnerName {
            font-family: 'Vazir Matn', sans-serif;
            font-size: 64px;
            font-weight: 700;
            color: #f5e6d3;
            margin-bottom: 25px;
            text-shadow: 0 0 30px #d4af37;
            background: linear-gradient(135deg, #fff, #d4af37);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.3;
        }

        #winnerNationalId {
            font-family: 'Vazir Matn', monospace;
            font-size: 48px;
            font-weight: 500;
            color: #d4af37;
            text-shadow: 0 0 20px rgba(212, 175, 55, 0.5);
            background: rgba(0,0,0,0.4);
            padding: 15px 40px;
            border-radius: 60px;
            display: inline-block;
            border: 2px solid #d4af37;
            box-shadow: 0 0 30px rgba(212, 175, 55, 0.3);
            letter-spacing: 8px;
        }

        .button-container {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1001;
        }

        #startButton {
            font-family: 'Vazir Matn', sans-serif;
            background: rgba(0, 0, 0, 0.7);
            color: #d4af37;
            padding: 12px 35px;
            font-size: 18px;
            font-weight: 600;
            border: 2px solid #d4af37;
            border-radius: 40px;
            cursor: pointer;
            transition: all 0.3s;
            letter-spacing: 1px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3), 0 0 20px rgba(212, 175, 55, 0.2);
            backdrop-filter: blur(5px);
            position: relative;
            overflow: hidden;
        }

        #startButton::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(212, 175, 55, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        #startButton:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.4);
            background: #d4af37;
            color: #000;
        }

        #startButton:hover:not(:disabled)::before {
            width: 300px;
            height: 300px;
        }

        #startButton:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            filter: grayscale(50%);
        }

        .participants-count {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.7);
            color: #f5e6d3;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 14px;
            font-family: 'Vazir Matn', sans-serif;
            font-weight: 400;
            border: 2px solid #d4af37;
            backdrop-filter: blur(5px);
            z-index: 3;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.85);
            z-index: 2000;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(8px);
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(15px);
            padding: 30px;
            border-radius: 30px;
            max-width: 80%;
            max-height: 80vh;
            overflow-y: auto;
            border: 3px solid #d4af37;
            box-shadow: 0 0 60px rgba(212, 175, 55, 0.4);
            position: relative;
        }

        .modal-content h2 {
            font-family: 'Vazir Matn', sans-serif;
            color: #d4af37;
            margin-bottom: 20px;
            font-size: 28px;
            font-weight: 700;
            text-align: center;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(212, 175, 55, 0.3);
        }

        .winners-list {
            color: #f5e6d3;
            text-align: right;
        }

        .winner-item {
            font-family: 'Vazir Matn', sans-serif;
            padding: 15px;
            border-bottom: 1px solid rgba(212, 175, 55, 0.2);
            display: flex;
            justify-content: space-between;
            font-size: 16px;
            font-weight: 400;
            transition: background 0.3s;
        }

        .winner-item:hover {
            background: rgba(212, 175, 55, 0.1);
        }

        .winner-item:last-child {
            border-bottom: none;
        }

        .winner-number {
            color: #d4af37;
            margin-left: 15px;
            font-weight: 600;
        }

        .close-modal {
            font-family: 'Vazir Matn', sans-serif;
            background: linear-gradient(135deg, #8b0000, #5a0000);
            color: #f5e6d3;
            border: 2px solid #d4af37;
            padding: 10px 40px;
            border-radius: 40px;
            margin-top: 25px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            width: 100%;
            transition: all 0.3s;
        }

        .close-modal:hover {
            background: linear-gradient(135deg, #a00000, #6a0000);
            transform: scale(1.05);
            box-shadow: 0 5px 20px rgba(212, 175, 55, 0.3);
        }

        .no-data {
            font-family: 'Vazir Matn', sans-serif;
            color: #f5e6d3;
            font-size: 16px;
            font-weight: 400;
            padding: 30px;
            text-align: center;
            opacity: 0.7;
        }

        .footer-text {
            position: fixed;
            bottom: 10px;
            right: 20px;
            color: #f5e6d3;
            font-size: 12px;
            font-family: 'Vazir Matn', sans-serif;
            font-weight: 300;
            opacity: 0.5;
            z-index: 3;
            background: rgba(0,0,0,0.3);
            padding: 4px 12px;
            border-radius: 20px;
        }

        /* کانفتی برای جشن */
        .confetti {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 999;
            overflow: hidden;
        }

        .confetti-piece {
            position: absolute;
            width: 10px;
            height: 20px;
            background: #d4af37;
            opacity: 0.7;
            animation: confettiFall 3s linear infinite;
        }

        @keyframes confettiFall {
            0% { transform: translateY(-100%) rotate(0deg); opacity: 1; }
            100% { transform: translateY(100vh) rotate(720deg); opacity: 0; }
        }

        @media (max-width: 768px) {
            .bismillah { font-size: 20px; }
            .result { width: 70px; height: 70px; font-size: 40px; }
            #winnerName { font-size: 40px; }
            #winnerNationalId { font-size: 28px; letter-spacing: 4px; padding: 10px 20px; }
            #winnerInfo { padding: 30px 40px; }
            #startButton { font-size: 16px; padding: 10px 25px; }
            .modal-content { max-width: 95%; padding: 20px; }
            .bismillah::before, .bismillah::after { margin: 0 8px; }
        }
    </style>
</head>
<body>
    <div class="container" id="mainContainer">
        <div class="bismillah">
            بِسْمِ اللَّهِ الرَّحْمَـٰنِ الرَّحِيمِ
        </div>

        <div class="dua-text">
            اللَّهُمَّ اجْعَلْ هَذَا الْقُرْعَةَ مُبَارَكَةً
        </div>

        <div class="participants-count" id="participantsCount">
            تعداد شرکت‌کنندگان: <?php echo count($participants); ?>
        </div>

        <div class="header">
            <button class="stats-button" onclick="showWinnersModal()">
                📖 ثبت‌نام‌شدگان در سامانه
            </button>
        </div>

        <div class="result-container" id="resultContainer">
            <div class="result" id="result1">۰</div>
            <div class="result" id="result2">۰</div>
            <div class="result" id="result3">۰</div>
            <div class="result" id="result4">۰</div>
        </div>
        
        <div id="winnerInfo">
            <div id="winnerName"></div>
            <div id="winnerNationalId"></div>
        </div>
    </div>

    <div class="button-container">
        <button id="startButton" onclick="startLottery()">
            <span>✨ شروع قرعه‌کشی ✨</span>
        </button>
    </div>

    <div class="footer-text">
        قرعه‌کشی آنلاین
    </div>

    <!-- المان کانفتی (مخفی در ابتدا) -->
    <div class="confetti" id="confetti" style="display: none;"></div>

    <!-- Modal آمار برندگان -->
    <div class="modal" id="winnersModal">
        <div class="modal-content">
            <h2>📖 برگزیدگان سامانه</h2>
            <div class="winners-list" id="winnersList">
                <div class="no-data">در حال بارگذاری...</div>
            </div>
            <button class="close-modal" onclick="closeWinnersModal()">بستن</button>
        </div>
    </div>

    <script>
        const participants = <?php echo json_encode($participants, JSON_UNESCAPED_UNICODE); ?>;
        let isLotteryRunning = false;
        let previousWinners = [];

        // بارگذاری برندگان قبلی
        async function loadPreviousWinners() {
            try {
                const formData = new FormData();
                formData.append('action', 'getWinners');
                
                const response = await fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success && result.data) {
                    previousWinners = result.data.map(w => w.national_id);
                }
            } catch (error) {
                console.error('خطا در بارگذاری برندگان:', error);
            }
        }

        // نمایش modal برندگان
        async function showWinnersModal() {
            const modal = document.getElementById('winnersModal');
            const winnersList = document.getElementById('winnersList');
            
            modal.classList.add('active');
            winnersList.innerHTML = '<div class="no-data">در حال بارگذاری...</div>';
            
            try {
                const formData = new FormData();
                formData.append('action', 'getWinners');
                
                const response = await fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success && result.data && result.data.length > 0) {
                    winnersList.innerHTML = result.data.map((winner, index) => `
                        <div class="winner-item">
                            <span class="winner-number">🔹 ${index + 1}</span>
                            <span>${winner.name}</span>
                            <span>${winner.national_id}</span>
                        </div>
                    `).join('');
                } else {
                    winnersList.innerHTML = '<div class="no-data">هنوز کسی برگزیده نشده است</div>';
                }
            } catch (error) {
                winnersList.innerHTML = '<div class="no-data">خطا در بارگذاری اطلاعات</div>';
            }
        }

        function closeWinnersModal() {
            document.getElementById('winnersModal').classList.remove('active');
        }

        // دریافت برنده جدید
        function getRandomParticipant() {
            if (!participants || participants.length === 0) {
                alert('شرکت‌کننده‌ای وجود ندارد!');
                return null;
            }
            
            const eligibleParticipants = participants.filter(p => !previousWinners.includes(p.national_id));
            
            if (eligibleParticipants.length === 0) {
                alert('همگی برگزیده شدند، اللهم صل علی محمد و آل محمد');
                return null;
            }
            
            const randomIndex = Math.floor(Math.random() * eligibleParticipants.length);
            return eligibleParticipants[randomIndex];
        }

        // ذخیره برنده
        async function saveWinner(winnerData) {
            try {
                const formData = new FormData();
                formData.append('action', 'saveWinner');
                formData.append('winnerData', winnerData);
                
                const response = await fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (!result.success) {
                    console.error('خطا در ذخیره:', result.message);
                }
            } catch (error) {
                console.error('خطا در ذخیره برنده:', error);
            }
        }

        // ایجاد کانفتی برای جشن
        function createConfetti() {
            const confettiContainer = document.getElementById('confetti');
            confettiContainer.style.display = 'block';
            confettiContainer.innerHTML = '';
            
            const colors = ['#d4af37', '#f5e6d3', '#c0c0c0', '#ffd700', '#daa520'];
            
            for (let i = 0; i < 100; i++) {
                const piece = document.createElement('div');
                piece.className = 'confetti-piece';
                piece.style.left = Math.random() * 100 + '%';
                piece.style.animationDelay = Math.random() * 3 + 's';
                piece.style.background = colors[Math.floor(Math.random() * colors.length)];
                piece.style.width = Math.random() * 15 + 5 + 'px';
                piece.style.height = Math.random() * 25 + 10 + 'px';
                confettiContainer.appendChild(piece);
            }
            
            // بعد از 5 ثانیه کانفتی را مخفی کن
            setTimeout(() => {
                confettiContainer.style.display = 'none';
            }, 5000);
        }

        // شروع قرعه‌کشی
        function startLottery() {
            if (isLotteryRunning) return;
            isLotteryRunning = true;
            
            const startButton = document.getElementById('startButton');
            const winnerInfo = document.getElementById('winnerInfo');
            const resultContainer = document.getElementById('resultContainer');
            const resultElements = [
                document.getElementById('result1'),
                document.getElementById('result2'),
                document.getElementById('result3'),
                document.getElementById('result4')
            ];
            
            startButton.disabled = true;
            winnerInfo.style.display = 'none';
            resultContainer.style.display = 'flex';
            
            const persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            
            // حذف کلاس hidden از المان‌های نتیجه
            resultElements.forEach(el => {
                el.classList.remove('hidden');
                el.classList.add('digit-spin');
                el.classList.remove('fade-out');
                el.style.opacity = '1';
            });
            
            const intervals = resultElements.map(el => {
                return setInterval(() => {
                    const randomDigit = persianDigits[Math.floor(Math.random() * 10)];
                    el.textContent = randomDigit;
                }, 100);
            });
            
            // توقف انیمیشن بعد از ۴ ثانیه
            setTimeout(() => {
                intervals.forEach(clearInterval);
                
                // مخفی کردن باکس‌های اعداد
                resultElements.forEach(el => {
                    el.classList.remove('digit-spin');
                    el.classList.add('hidden');
                });
                
                // نمایش برنده
                setTimeout(async () => {
                    const winner = getRandomParticipant();
                    
                    if (winner) {
                        document.getElementById('winnerName').textContent = winner.name;
                        document.getElementById('winnerNationalId').textContent = winner.national_id;
                        
                        // نمایش اطلاعات برنده با انیمیشن
                        winnerInfo.style.display = 'block';
                        
                        // ایجاد کانفتی برای جشن
                        createConfetti();
                        
                        // ذخیره برنده
                        await saveWinner(`${winner.name} - ${winner.national_id}`);
                        previousWinners.push(winner.national_id);
                        
                        // به روز رسانی آمار
                        const participantsCount = document.getElementById('participantsCount');
                        const remaining = participants.length - previousWinners.length;
                        participantsCount.innerHTML = `تعداد شرکت‌کنندگان: ${participants.length} | باقی‌مانده: ${remaining}`;
                    }
                    
                    startButton.disabled = false;
                    isLotteryRunning = false;
                }, 200);
            }, 4000);
        }

        // بستن modal با کلیک خارج از آن
        window.onclick = function(event) {
            const modal = document.getElementById('winnersModal');
            if (event.target === modal) {
                closeWinnersModal();
            }
        };

        // بارگذاری اولیه
        window.addEventListener('load', async () => {
            await loadPreviousWinners();
            
            // نمایش تعداد باقی‌مانده
            const remaining = participants.length - previousWinners.length;
            document.getElementById('participantsCount').innerHTML = 
                `تعداد شرکت‌کنندگان: ${participants.length} | باقی‌مانده: ${remaining}`;
        });
    </script>
</body>
</html>