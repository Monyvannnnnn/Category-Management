<?php
// fieldbi.php - Instant Direct Navigation to Field BI Website inside Telegram Mini App
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
        // Direct top-level location replace for instant Field BI website loading inside Telegram Mini App
        window.location.replace("<?php echo $targetUrl; ?>");
    </script>
    <style>
        html, body {
            width: 100%;
            height: 100vh;
            margin: 0;
            padding: 0;
            background-color: #0f141c;
            color: #ffffff;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div style="text-align: center;">
        <h2 style="font-weight: 500; margin-bottom: 8px;">🌾 Opening Field BI...</h2>
        <p style="color: #94a3b8; font-size: 14px;">Redirecting to Field BI Platform</p>
    </div>
</body>
</html>
