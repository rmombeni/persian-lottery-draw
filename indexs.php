<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>    قرعه کشی    </title>
    <style>
        @font-face {
            font-family: 'Vazir Matn';
            src: url('fonts/Vazirmatn.woff2') format('woff2');
        }
        body {
            font-family: 'Vazir Matn', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            height: 100vh;
            overflow: hidden;
        }
        .container {
            display: flex;
            height: 100vh;
            width: 100%;
        }
        .right-panel {
            flex: 1; /* یک‌سوم عرض */
            border-left: 2px solid #ddd;
            overflow: auto;
        }
        .left-panel {
            flex: 2; /* دوسوم عرض */
            overflow: auto;
        }
        .panel-content {
            width: 100%;
            height: 100%;
            border: none;
        }
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            .right-panel, .left-panel {
                flex: none;
                height: 50%; /* در موبایل هر کدام نصف صفحه */
                border: none;
                border-bottom: 2px solid #ddd;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="left-panel">
            <object data="index.php" class="panel-content" type="text/html"></object>
        </div>
        <div class="right-panel">
            <object data="data.php" class="panel-content" type="text/html"></object>
        </div>
    </div>
</body>
</html>