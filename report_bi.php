<?php
// report_bi.php - First-Party Top-Level Direct Load for Fast GPU Chart Rendering (No Iframe Bottleneck)
$targetUrl = "https://app.fieldbi.com/?page=promptdemo&rpf=zGR88xyzPD&action=page&frm=RMt_ph898";
header("Location: " . $targetUrl, true, 302);
header("Cache-Control: no-cache, must-revalidate");
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
        (function() {
            const targetUrl = "<?php echo $targetUrl; ?>";
            if (window.Telegram && window.Telegram.WebApp) {
                window.Telegram.WebApp.ready();
                window.Telegram.WebApp.expand();
            }
            // Navigate top-level window directly to target URL (eliminates iframe canvas sandbox throttling)
            window.location.replace(targetUrl);
        })();
    </script>
</head>
<body style="background:#0f141c; color:#94a3b8; font-family:sans-serif; display:flex; align-items:center; justify-content:center; height:100vh; margin:0;">
    <p>Loading Field BI Analytics... <a href="<?php echo htmlspecialchars($targetUrl); ?>" style="color:#10b981;">Click here if not loaded</a></p>
</body>
</html>
