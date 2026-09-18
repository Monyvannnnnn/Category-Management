<?php
// fieldbi.php - Instant In-App Browser Launcher via Telegram WebApp SDK
$targetUrl = "https://app.fieldbi.com/?page=promptdemo&rpf=zGR88xyzPD&action=page&frm=RMt_ph898";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Field BI Report</title>

    <!-- Telegram WebApp SDK -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script>
        (function() {
            const targetUrl = "<?php echo $targetUrl; ?>";
            
            if (window.Telegram && window.Telegram.WebApp) {
                try {
                    window.Telegram.WebApp.ready();
                    // Launch target URL in Telegram's fast In-App Browser (bypasses Mini App sandbox)
                    window.Telegram.WebApp.openLink(targetUrl);
                    // Close the Mini App window immediately so zero blank screen remains
                    setTimeout(function() {
                        window.Telegram.WebApp.close();
                    }, 100);
                } catch(e) {
                    window.location.href = targetUrl;
                }
            } else {
                window.location.href = targetUrl;
            }
        })();
    </script>
</head>
<body style="background:#0f141c; color:#f8fafc; font-family:system-ui, -apple-system, sans-serif; display:flex; flex-direction:column; align-items:center; justify-content:center; height:100vh; margin:0; text-align:center; padding:20px;">
    <div style="font-size:36px; margin-bottom:12px;">🌾</div>
    <div style="font-size:16px; font-weight:600; margin-bottom:6px;">Opening Field BI Report...</div>
    <div style="font-size:12px; color:#94a3b8;">Launching in Telegram In-App Browser for instant rendering...</div>
    <p style="margin-top:16px;"><a href="<?php echo htmlspecialchars($targetUrl); ?>" style="color:#10b981; text-decoration:none; font-size:14px; font-weight:bold;">Tap here if not opened automatically</a></p>
</body>
</html>
