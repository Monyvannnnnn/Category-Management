<?php
// fieldbi.php - Field BI Platform Mini App View (Dark Glassmorphic Theme)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

require_once "database.php";
require_once "includes/auth_helper.php";

$currentUser = getCurrentUser();
if (!$currentUser) {
    // Fallback profile when accessed directly inside Telegram Mini App webview
    $currentUser = ['id' => 1, 'name' => 'Telegram User', 'role' => 'admin'];
}

$fieldBiTargetUrl = "https://app.fieldbi.com/?page=promptdemo&rpf=zGR88xyzPD&action=page&frm=RMt_ph898";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Field BI Web App - Telegram Mini App</title>

    <!-- Telegram Mini App WebApp SDK -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <!-- Base Stylesheet -->
    <link rel="stylesheet" href="css/style.css?v=<?php echo date('Y-m-d-H-i-s', @filemtime(__DIR__ . '/css/style.css')); ?>">

    <style>
        html, body {
            height: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: hidden !important;
            background-color: var(--bg-main, #0f141c);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        .fieldbi-container {
            width: 100vw;
            height: 100vh;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
            background-color: var(--bg-main, #0f141c);
            padding: 6px;
            gap: 6px;
        }

        .fieldbi-header {
            background: var(--surface-card, #161d2a);
            border: 1px solid var(--border-subtle, #242f42);
            border-radius: 10px;
            padding: 8px 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
            flex-shrink: 0;
        }

        .fieldbi-title-group {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
        }

        .fieldbi-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 14px;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.35);
        }

        .fieldbi-title-text {
            min-width: 0;
        }

        .fieldbi-title-text h1 {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-main, #f8fafc);
            margin: 0;
            line-height: 1.2;
            white-space: nowrap;
        }

        .fieldbi-title-text p {
            font-size: 10px;
            color: var(--text-muted, #94a3b8);
            margin: 1px 0 0 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .fieldbi-nav-actions {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: nowrap;
        }

        .bi-btn {
            background: var(--surface-alt, #1a2333);
            border: 1px solid var(--border-subtle, #242f42);
            color: var(--text-main, #f8fafc);
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            transition: all 0.2s ease;
            text-decoration: none;
            white-space: nowrap;
        }

        .bi-btn:hover {
            background: var(--border-subtle, #242f42);
            color: #ffffff;
            border-color: var(--primary-color, #10b981);
        }

        .bi-btn.primary {
            background: #10b981;
            border-color: #10b981;
            color: #ffffff;
        }

        .bi-btn.primary:hover {
            background: #059669;
        }

        .fieldbi-frame-wrapper {
            flex: 1;
            width: 100%;
            height: 100%;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
            background: #161d2a;
            border: 1px solid var(--border-subtle, #242f42);
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
            background: rgba(15, 20, 28, 0.9);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            color: #94a3b8;
            font-size: 13px;
            z-index: 10;
            transition: opacity 0.3s ease;
        }

        .spinner {
            width: 32px;
            height: 32px;
            border: 3px solid rgba(16, 185, 129, 0.2);
            border-top-color: #10b981;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 600px) {
            .fieldbi-container {
                padding: 4px;
                gap: 4px;
            }
            .fieldbi-header {
                padding: 6px 8px;
            }
            .fieldbi-title-text p {
                display: none;
            }
            .fieldbi-title-text h1 {
                font-size: 12.5px;
            }
            .bi-btn {
                padding: 4px 7px;
                font-size: 10.5px;
            }
        }
    </style>
</head>
<body>

<div class="fieldbi-container">
    <!-- Top Header Navigation Bar -->
    <div class="fieldbi-header">
        <div class="fieldbi-title-group">
            <div class="fieldbi-icon">
                <i class="fa-solid fa-wheat-field"></i>
            </div>
            <div class="fieldbi-title-text">
                <h1>Field BI App</h1>
                <p>Prompt Demo Page • Field BI Integration</p>
            </div>
        </div>

        <div class="fieldbi-nav-actions">
            <!-- Switch Website Tab 1 -->
            <a href="report_bi.php" class="bi-btn" title="Switch to Inventory BI Analytics">
                <i class="fa-solid fa-chart-pie"></i> 📊 Inventory BI
            </a>

            <!-- Switch Website Tab 2 (Active) -->
            <button class="bi-btn primary" title="Currently viewing Field BI App">
                <i class="fa-solid fa-wheat-field"></i> 🌾 Field BI
            </button>

            <!-- Refresh Button -->
            <button class="bi-btn" onclick="refreshIframe()" title="Reload Web View">
                <i class="fa-solid fa-rotate"></i>
            </button>

            <!-- External Link -->
            <a href="<?php echo htmlspecialchars($fieldBiTargetUrl); ?>" target="_blank" class="bi-btn" title="Open in Browser">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
            </a>
        </div>
    </div>

    <!-- Embedded Web View Container -->
    <div class="fieldbi-frame-wrapper">
        <div class="loading-overlay" id="loadingOverlay">
            <div class="spinner"></div>
            <span>Loading Field BI App...</span>
        </div>
        <iframe 
            id="fieldbiIframe"
            class="fieldbi-iframe" 
            src="<?php echo htmlspecialchars($fieldBiTargetUrl); ?>" 
            allow="geolocation; microphone; camera; clipboard-read; clipboard-write; autoplay; fullscreen"
            onload="hideLoading()">
        </iframe>
    </div>
</div>

<script>
    // Initialize Telegram WebApp SDK if running inside Telegram
    if (window.Telegram && window.Telegram.WebApp) {
        window.Telegram.WebApp.ready();
        window.Telegram.WebApp.expand();
    }

    function hideLoading() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.style.opacity = '0';
            setTimeout(() => { overlay.style.display = 'none'; }, 300);
        }
    }

    function refreshIframe() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.style.display = 'flex';
            overlay.style.opacity = '1';
        }
        const iframe = document.getElementById('fieldbiIframe');
        iframe.src = iframe.src;
    }

    // Auto-hide spinner fallback after 5s if iframe onload event is restricted by cross-origin policy
    setTimeout(hideLoading, 5000);
</script>

</body>
</html>
