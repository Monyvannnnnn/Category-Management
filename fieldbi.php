<?php
// fieldbi.php - Native External Browser Launcher via Telegram WebApp SDK for Full Browser Performance
$targetUrl = "https://app.fieldbi.com/?page=promptdemo&rpf=zGR88xyzPD&action=page&frm=RMt_ph898";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Field BI Web App</title>

    <!-- Telegram Web App SDK -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
</head>
<body style="background:#0f141c; color:#94a3b8; font-family:system-ui, -apple-system, sans-serif; display:flex; flex-direction:column; align-items:center; justify-content:center; height:100vh; margin:0; text-align:center; padding:20px;">

    <div style="font-size:36px; margin-bottom:12px;">🌾</div>
    <div style="font-size:16px; font-weight:600; color:#f8fafc; margin-bottom:6px;">Opening Field BI App...</div>
    <div style="font-size:12px; color:#94a3b8;">Launching in native device browser for 100% full performance...</div>

    <script>
        (function() {
            const targetUrl = "<?php echo $targetUrl; ?>";

            if (window.Telegram && window.Telegram.WebApp) {
                try {
                    Telegram.WebApp.ready();
                    // Open URL in device default native browser for full hardware performance
                    Telegram.WebApp.openLink(targetUrl);
                    // Close Mini App wrapper immediately so no blank screen remains
                    setTimeout(function() {
                        Telegram.WebApp.close();
                    }, 150);
                } catch (e) {
                    window.location.replace(targetUrl);
                }
            } else {
                window.location.replace(targetUrl);
            }
        })();
    </script>
</body>
</html>
