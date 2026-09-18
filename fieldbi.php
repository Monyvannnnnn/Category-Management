<?php
// fieldbi.php - Ultra-Fast High-Performance Field BI Telegram Mini App Page
header("Cache-Control: public, max-age=3600");
header("X-Frame-Options: ALLOWALL");

$fieldBiTargetUrl = "https://app.fieldbi.com/?page=promptdemo&rpf=zGR88xyzPD&action=page&frm=RMt_ph898";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Field BI Web App</title>

    <!-- DNS Prefetch & Preconnect for Instant Domain Warmup -->
    <link rel="dns-prefetch" href="https://app.fieldbi.com">
    <link rel="preconnect" href="https://app.fieldbi.com" crossorigin>

    <!-- Telegram Mini App WebApp SDK -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script>
        // Immediate Telegram WebApp expansion (0ms delay)
        if (window.Telegram && window.Telegram.WebApp) {
            window.Telegram.WebApp.ready();
            window.Telegram.WebApp.expand();
        }
    </script>

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" media="print" onload="this.media='all'">

    <style>
        html, body {
            height: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: hidden !important;
            background-color: #0f141c;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        .fieldbi-container {
            width: 100vw;
            height: 100vh;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
            background-color: #0f141c;
            padding: 4px;
            gap: 4px;
        }

        .fieldbi-header {
            background: #161d2a;
            border: 1px solid #242f42;
            border-radius: 8px;
            padding: 6px 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            flex-shrink: 0;
        }

        .fieldbi-title-group {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
        }

        .fieldbi-icon {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            background: #10b981;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 13px;
            flex-shrink: 0;
        }

        .fieldbi-title-text h1 {
            font-size: 13px;
            font-weight: 700;
            color: #f8fafc;
            margin: 0;
            line-height: 1.2;
            white-space: nowrap;
        }

        .fieldbi-nav-actions {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .bi-btn {
            background: #1a2333;
            border: 1px solid #242f42;
            color: #f8fafc;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            text-decoration: none;
            white-space: nowrap;
        }

        .fieldbi-frame-wrapper {
            flex: 1;
            width: 100%;
            height: 100%;
            border-radius: 8px;
            overflow: hidden;
            position: relative;
            background: #161d2a;
            border: 1px solid #242f42;
        }

        .fieldbi-iframe {
            width: 100%;
            height: 100%;
            border: none;
            display: block;
        }

        .loading-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: #0f141c;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: #94a3b8;
            font-size: 12px;
            z-index: 10;
            transition: opacity 0.2s ease;
        }

        .spinner {
            width: 20px;
            height: 20px;
            border: 2px solid rgba(16, 185, 129, 0.2);
            border-top-color: #10b981;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

<div class="fieldbi-container">
    <div class="fieldbi-header">
        <div class="fieldbi-title-group">
            <div class="fieldbi-icon">🌾</div>
            <div class="fieldbi-title-text">
                <h1>Field BI App</h1>
            </div>
        </div>

        <div class="fieldbi-nav-actions">
            <button class="bi-btn" onclick="refreshIframe()" title="Reload">🔄</button>
            <a href="<?php echo htmlspecialchars($fieldBiTargetUrl); ?>" target="_blank" class="bi-btn" title="External Browser">↗️</a>
        </div>
    </div>

    <div class="fieldbi-frame-wrapper">
        <div class="loading-overlay" id="loadingOverlay">
            <div class="spinner"></div>
            <span>Fast Loading Field BI...</span>
        </div>
        <iframe 
            id="fieldbiIframe"
            class="fieldbi-iframe" 
            src="<?php echo htmlspecialchars($fieldBiTargetUrl); ?>" 
            loading="eager"
            fetchpriority="high"
            allow="geolocation; microphone; camera; clipboard-read; clipboard-write; autoplay; fullscreen"
            onload="hideLoading()">
        </iframe>
    </div>
</div>

<script>
    function hideLoading() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.style.opacity = '0';
            setTimeout(() => { overlay.style.display = 'none'; }, 200);
        }
    }

    function refreshIframe() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.style.display = 'flex';
            overlay.style.opacity = '1';
        }
        document.getElementById('fieldbiIframe').src = '<?php echo htmlspecialchars($fieldBiTargetUrl); ?>';
    }

    setTimeout(hideLoading, 2500);
</script>

</body>
</html>
