<?php
// fieldbi.php - Full-Screen Fast-Rendering Telegram Mini App View
$targetUrl = "https://app.fieldbi.com/?page=promptdemo&rpf=zGR88xyzPD&action=page&frm=RMt_ph898";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Field BI Mini App</title>

    <!-- Telegram Mini App WebApp SDK -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script>
        // Expand Telegram Mini App to 100% full screen height immediately
        if (window.Telegram && window.Telegram.WebApp) {
            window.Telegram.WebApp.ready();
            window.Telegram.WebApp.expand();
            if (window.Telegram.WebApp.setHeaderColor) {
                window.Telegram.WebApp.setHeaderColor('#0f141c');
            }
            if (window.Telegram.WebApp.setBackgroundColor) {
                window.Telegram.WebApp.setBackgroundColor('#0f141c');
            }
        }
    </script>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        html, body {
            width: 100% !important;
            height: 100% !important;
            min-height: 100vh !important;
            overflow: hidden !important;
            background-color: #0f141c;
        }
        iframe {
            width: 100% !important;
            height: 100% !important;
            min-height: 100vh !important;
            border: none !important;
            display: block !important;
        }
    </style>
</head>
<body>
    <iframe 
        src="<?php echo htmlspecialchars($targetUrl); ?>" 
        allow="geolocation; microphone; camera; clipboard-read; clipboard-write; autoplay; fullscreen"
        loading="eager">
    </iframe>
</body>
</html>
