<?php
// خواندن اطلاعات برندگان از فایل
$winners = [];
if (file_exists('winners.txt')) {
    $lines = file('winners.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $winners[] = trim($line);
    }
}

// رنگ‌های مختلف برای باکس‌ها
$colors = ['#00cec9', '#0984e3', '#6c5ce7', '#fd79a8', '#e17055', '#fdcb6e', '#00b894', '#e84393'];
?>

<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لیست برندگان</title>
    <style>
        @font-face {
            font-family: 'Vazir Matn';
            src: url('fonts/Vazirmatn.woff2') format('woff2');
            font-weight: normal;
            font-style: normal;
        }
        body {
            font-family: 'Vazir Matn', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f6fa;
            direction: rtl;
        }
        .header {
            background-color: #2d3436;
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 28px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .winners-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 3px;
            padding: 10px;
            max-width: 100%;
            box-sizing: border-box;
        }
        .winner-box {
            border-radius: 6px;
            padding: 10px 5px;
            color: white;
            font-size: 32px;
            text-align: center;
            box-shadow: 0 2px 3px rgba(0,0,0,0.1);
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 60px;
            word-break: break-word;
        }
        .winner-box:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 6px rgba(0,0,0,0.2);
        }
        .winner-name {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 18px;
            line-height: 1.2;
        }
        .winner-id {
            letter-spacing: 1px;
            font-family: 'Vazir Matn', monospace;
            direction: ltr;
            display: inline-block;
            font-size: 16px;
            line-height: 1.2;
        }
        .no-winners {
            grid-column: 1 / -1;
            text-align: center;
            padding: 30px;
            font-size: 24px;
            color: #636e72;
        }
        
        /* رسپانسیو برای دستگاه‌های مختلف */
        @media (max-width: 1200px) {
            .winners-container {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        @media (max-width: 900px) {
            .winners-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 600px) {
            .header {
                font-size: 22px;
                padding: 15px;
            }
            .winners-container {
                grid-template-columns: repeat(2, 1fr);
                padding: 5px;
            }
            .winner-box {
                min-height: 50px;
                padding: 8px 3px;
            }
            .winner-name {
                font-size: 16px;
            }
            .winner-id {
                font-size: 14px;
            }
            .no-winners {
                font-size: 20px;
                padding: 20px;
            }
        }
    </style>
    <script>
        // تابع برای رفرش صفحه هر 2 ثانیه
        function autoRefresh() {
            setTimeout(function() {
                location.reload();
            }, 2000); // 2000 میلی‌ثانیه = 2 ثانیه
        }
        
        // هنگام لود صفحه، تابع رفرش را فراخوانی کن
        window.onload = autoRefresh;
    </script>
</head>
<body>
    <div class="header">  لیست برندگان قرعه کشی در بخش عمومی  </div>
    
    <div class="winners-container">
        <?php if (empty($winners)): ?>
            <div class="no-winners">هنوز برنده‌ای ثبت نشده است!</div>
        <?php else: ?>
            <?php foreach ($winners as $index => $winner): ?>
                <?php 
                $parts = explode(' - ', $winner);
                $name = $parts[0] ?? 'نامشخص';
                $id = $parts[1] ?? 'کد ملی نامشخص';
                $color = $colors[$index % count($colors)];
                ?>
                <div class="winner-box" style="background-color: <?php echo $color; ?>">
                    <div class="winner-name"><?php echo htmlspecialchars($name); ?></div>
                    <div class="winner-id"><?php echo htmlspecialchars($id); ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>