<?php
// report_bi.php - BI Report & Business Intelligence Dashboard (Dark Glassmorphic Theme)
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>BI Report Dashboard - Inventory Intelligence</title>

    <!-- Telegram Mini App WebApp SDK -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- Chart.js for BI Charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    
    <!-- Base Stylesheet -->
    <link rel="stylesheet" href="css/style.css?v=<?php echo date('Y-m-d-H-i-s', @filemtime(__DIR__ . '/css/style.css')); ?>">

    <style>
        html, body {
            height: auto !important;
            min-height: 100% !important;
            max-height: none !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
        }

        .bi-page-container {
            width: 100%;
            max-width: 100%;
            min-height: 100vh;
            height: auto !important;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            background-color: var(--bg-main, #0f141c);
            overflow: visible !important;
            padding: 8px 10px;
            gap: 8px;
        }

        .bi-header-card {
            background: var(--surface-card, #161d2a);
            border: 1px solid var(--border-subtle, #242f42);
            border-radius: 10px;
            padding: 10px 14px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
            max-width: 100%;
            box-sizing: border-box;
        }

        .bi-header-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            width: 100%;
        }

        .bi-profile-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 20px;
            background: var(--surface-alt, #1a2333);
            border: 1px solid var(--border-subtle, #242f42);
            font-size: 11.5px;
            font-weight: 600;
            color: #f8fafc;
            flex-shrink: 0;
        }

        .bi-profile-pill i.fa-user-circle {
            color: #818cf8;
            font-size: 13px;
        }

        .bi-logout-btn {
            color: #f87171;
            margin-left: 4px;
            padding: 2px 5px;
            border-radius: 4px;
            transition: color 0.15s ease, background 0.15s ease;
            display: inline-flex;
            align-items: center;
            text-decoration: none;
            font-size: 12px;
        }

        .bi-logout-btn:hover {
            color: #ef4444;
            background: rgba(239, 68, 68, 0.15);
        }

        .bi-title-group {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
        }

        .bi-title-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 14px;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
        }

        .bi-title-text {
            min-width: 0;
        }

        .bi-title-text h1 {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-main, #f8fafc);
            margin: 0;
            line-height: 1.2;
            white-space: nowrap;
        }

        .bi-title-text p {
            font-size: 10px;
            color: var(--text-muted, #94a3b8);
            margin: 1px 0 0 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        @media (max-width: 480px) {
            .bi-title-text h1 {
                font-size: 13px !important;
                white-space: nowrap !important;
                letter-spacing: -0.2px;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .bi-title-text p {
                display: none;
            }
        }

        .bi-header-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            width: 100%;
        }

        @media (min-width: 900px) {
            .bi-header-card {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }
            .bi-header-top {
                width: auto;
            }
            .bi-header-actions {
                display: flex;
                width: auto;
                gap: 6px;
            }
        }

        .bi-btn {
            background: var(--surface-alt, #1a2333);
            border: 1px solid var(--border-subtle, #242f42);
            color: var(--text-main, #f8fafc);
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 500;
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
            border-color: var(--primary-color, #6366f1);
        }

        .bi-btn.primary {
            background: var(--primary-color, #6366f1);
            border-color: var(--primary-color, #6366f1);
            color: #ffffff;
        }

        .bi-btn.primary:hover {
            background: var(--primary-hover, #4f46e5);
        }

        /* KPI Cards Grid - Ultra-Compact Spacing */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 8px;
        }

        @media (max-width: 1200px) {
            .kpi-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 600px) {
            .kpi-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 5px;
            }
        }

        .kpi-card {
            background: var(--surface-card, #161d2a);
            border: 1px solid var(--border-subtle, #242f42);
            border-radius: 8px;
            padding: 8px 10px;
            display: flex;
            flex-direction: column;
            gap: 2px;
            transition: transform 0.2s ease, border-color 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .kpi-card:hover {
            transform: translateY(-2px);
            border-color: var(--card-accent, var(--primary-color, #6366f1));
        }

        .kpi-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .kpi-label {
            font-size: 10px;
            font-weight: 600;
            color: var(--text-muted, #94a3b8);
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .kpi-icon-badge {
            width: 22px;
            height: 22px;
            border-radius: 5px;
            background: var(--surface-alt, #1a2333);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--card-accent, var(--primary-color, #6366f1));
            font-size: 11px;
        }

        .kpi-value {
            font-size: 17px;
            font-weight: 700;
            color: var(--text-main, #f8fafc);
            letter-spacing: -0.3px;
            line-height: 1.2;
        }

        .kpi-subtext {
            font-size: 9.5px;
            color: var(--text-muted, #94a3b8);
            display: flex;
            align-items: center;
            gap: 3px;
        }

        /* Charts Layout - Ultra-Compact Grid Spacing */
        .charts-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 8px;
        }

        @media (max-width: 900px) {
            .charts-row {
                grid-template-columns: 1fr;
                gap: 8px;
            }
        }

        .chart-card {
            background: var(--surface-card, #161d2a);
            border: 1px solid var(--border-subtle, #242f42);
            border-radius: 8px;
            padding: 8px 10px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .chart-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .chart-card-title {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-main, #f8fafc);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .chart-canvas-container {
            position: relative;
            width: 100%;
            height: 180px;
        }

        /* Table Card - Compact */
        .bi-table-card {
            background: var(--surface-card, #161d2a);
            border: 1px solid var(--border-subtle, #242f42);
            border-radius: 8px;
            padding: 10px 12px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 10px;
        }

        .table-controls {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .bi-search-input {
            background: var(--surface-alt, #1a2333);
            border: 1px solid var(--border-subtle, #242f42);
            border-radius: 8px;
            padding: 6px 12px 6px 32px;
            color: var(--text-main, #f8fafc);
            font-size: 13px;
            outline: none;
            width: 240px;
            transition: border-color 0.2s;
        }

        .bi-search-input:focus {
            border-color: var(--primary-color, #6366f1);
        }

        .bi-search-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .bi-search-wrapper i {
            position: absolute;
            left: 10px;
            color: var(--text-muted, #94a3b8);
            font-size: 13px;
        }

        .bi-data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            text-align: left;
        }

        .bi-data-table th {
            background: var(--surface-alt, #1a2333);
            color: var(--text-muted, #94a3b8);
            font-weight: 600;
            padding: 6px 10px;
            border-bottom: 1px solid var(--border-subtle, #242f42);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .bi-data-table td {
            padding: 7px 10px;
            border-bottom: 1px solid var(--border-subtle, #242f42);
            color: var(--text-main, #f8fafc);
        }

        .bi-data-table tbody tr:hover {
            background: rgba(255, 255, 255, 0.02);
        }

        .badge-status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap !important;
            flex-shrink: 0;
            letter-spacing: 0.2px;
            transition: all 0.2s ease;
        }

        .badge-status i {
            font-size: 11px;
            flex-shrink: 0;
        }

        .badge-status.success {
            background: rgba(16, 185, 129, 0.14);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.35);
            box-shadow: none;
        }

        .badge-status.warning {
            background: rgba(245, 158, 11, 0.14);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.35);
            box-shadow: none;
        }

        .badge-status.danger {
            background: rgba(239, 68, 68, 0.14);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.35);
            box-shadow: none;
        }

        .badge-text-mobile {
            display: none;
        }

        @media (max-width: 600px) {
            .badge-status {
                padding: 3px 8px;
                font-size: 10px;
                gap: 4px;
            }
            .badge-text-full {
                display: none;
            }
            .badge-text-mobile {
                display: inline;
                font-weight: 700;
            }
        }

        /* Product Thumbnails & Avatar Stacks in BI Table */
        /* Single Featured Image + Count Badge Style */
        .prod-single-wrapper {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            white-space: nowrap;
            flex-wrap: nowrap;
        }
        .prod-single-thumb {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid #334155;
            background: #0f172a;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
            transition: transform 0.15s ease, border-color 0.15s ease;
            flex-shrink: 0;
        }
        .prod-single-thumb:hover {
            transform: scale(1.08);
            border-color: #475569;
        }
        .prod-single-no-img {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: 1px dashed rgba(148, 163, 184, 0.3);
            background: rgba(30, 41, 59, 0.6);
            color: #64748b;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            cursor: pointer;
            transition: transform 0.15s ease;
            flex-shrink: 0;
        }
        .prod-single-no-img:hover {
            transform: scale(1.08);
            color: #94a3b8;
            border-color: #475569;
        }
        .prod-single-count-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 12px;
            background: rgba(99, 102, 241, 0.15);
            color: #a5b4fc;
            border: 1px solid rgba(99, 102, 241, 0.3);
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s ease, transform 0.15s ease;
            white-space: nowrap !important;
            flex-shrink: 0 !important;
            line-height: 1.2;
        }
        .prod-single-count-pill:hover {
            background: rgba(99, 102, 241, 0.3);
            color: #ffffff;
            transform: scale(1.05);
        }

        .table-scroll-container {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 8px;
        }

        .bi-data-table {
            width: 100%;
            min-width: 680px;
            border-collapse: collapse;
            font-size: 13px;
            text-align: left;
        }

        @media (max-width: 768px) {
            .bi-page-container {
                padding: 6px;
                gap: 6px;
            }
            .bi-header-card {
                padding: 10px;
                gap: 8px;
            }
            .bi-title-text h1 {
                font-size: 13.5px !important;
                white-space: nowrap !important;
            }
            .bi-title-text p {
                font-size: 10.5px;
            }
            .bi-header-actions {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 6px;
                width: 100%;
            }
            .bi-btn {
                justify-content: center;
                font-size: 11px;
                padding: 6px 8px;
            }
            .table-controls {
                flex-direction: column;
                align-items: stretch;
                gap: 8px;
            }
            .bi-search-wrapper, .bi-search-input {
                width: 100%;
                box-sizing: border-box;
            }
            .bi-data-table th, .bi-data-table td {
                padding: 6px 8px;
                font-size: 11px;
                white-space: nowrap;
            }
            .prod-single-wrapper {
                gap: 6px;
            }
            .prod-single-thumb, .prod-single-no-img {
                width: 32px;
                height: 32px;
            }
            .prod-single-count-pill {
                padding: 3px 8px;
                font-size: 10.5px;
                white-space: nowrap !important;
            }
            .bi-modal-card {
                max-width: 94vw;
                border-radius: 12px;
            }
            .cat-products-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Lightbox & Category Product Modals */
        .bi-modal-backdrop {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(8px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.25s ease;
        }
        .bi-modal-backdrop.active {
            opacity: 1;
            pointer-events: auto;
        }
        .bi-modal-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.6);
            max-width: 650px;
            width: 100%;
            overflow: hidden;
            transform: scale(0.92);
            transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .bi-modal-backdrop.active .bi-modal-card {
            transform: scale(1);
        }
        .bi-modal-header {
            padding: 16px 20px;
            border-bottom: 1px solid #334155;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #0f172a;
        }
        .bi-modal-title {
            font-size: 16px;
            font-weight: 700;
            color: #f8fafc;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .bi-modal-close {
            background: transparent;
            border: none;
            color: #94a3b8;
            font-size: 15px;
            cursor: pointer;
            padding: 0;
            width: 32px;
            height: 32px;
            min-width: 32px;
            min-height: 32px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            box-sizing: border-box;
            flex-shrink: 0;
        }
        .bi-modal-close:hover {
            color: #f8fafc;
            background: rgba(255, 255, 255, 0.12);
            transform: scale(1.08);
        }
        .bi-modal-body {
            padding: 20px;
            max-height: 75vh;
            overflow-y: auto;
        }
        .cat-products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 14px;
        }
        .cat-prod-card {
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 12px;
            display: flex;
            gap: 12px;
            align-items: center;
        }
        .cat-prod-card:hover {
            border-color: #475569;
        }
        .cat-prod-img-wrap {
            width: 52px;
            height: 52px;
            border-radius: 8px;
            overflow: hidden;
            flex-shrink: 0;
            background: #1e293b;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .cat-prod-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            cursor: pointer;
        }
        .cat-prod-info {
            flex-grow: 1;
            min-width: 0;
        }
        .cat-prod-code {
            font-size: 10px;
            color: #38bdf8;
        }
        .cat-prod-name {
            font-size: 13px;
            font-weight: 600;
            color: #f8fafc;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .cat-prod-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 4px;
        }
        .cat-prod-price {
            font-size: 12px;
            font-weight: 700;
            color: #4ade80;
        }

        @media print {
            body { background: #ffffff !important; color: #000000 !important; }
            .bi-page-container { padding: 0; }
            .bi-header-actions, .table-controls { display: none !important; }
            .kpi-card, .chart-card, .bi-table-card { border: 1px solid #ccc !important; background: #fff !important; color: #000 !important; box-shadow: none !important; }
        }
    </style>
</head>
<body>

<div class="bi-page-container">

    <!-- Top Header Bar -->
    <div class="bi-header-card">
        <div class="bi-header-top">
            <div class="bi-title-group">
                <div class="bi-title-icon">
                    <i class="fa-solid fa-chart-pie"></i>
                </div>
                <div class="bi-title-text">
                    <h1>BI Executive Analytics</h1>
                    <p>Real-time Business Intelligence & Inventory Valuation Overview</p>
                </div>
            </div>

            <div class="bi-profile-pill">
                <i class="fa-solid fa-user-circle"></i>
                <span class="user-profile-name" id="userNameSpan"><?php echo htmlspecialchars($currentUser['name'] ?? 'Admin'); ?></span>
                <a href="logout.php" class="bi-logout-btn" title="Sign Out">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </a>
            </div>
        </div>

        <div class="bi-header-actions">
            <!-- Navigation Links -->
            <a href="fieldbi.php" class="bi-btn" style="background: rgba(16, 185, 129, 0.2); border-color: rgba(16, 185, 129, 0.4); color: #34d399;" title="Switch to Field BI App">
                <i class="fa-solid fa-wheat-field"></i> 🌾 Field BI
            </a>
            <a href="index.php" class="bi-btn" title="Categories Management">
                <i class="fa-solid fa-layer-group"></i> Categories
            </a>
            <a href="products.php" class="bi-btn" title="Products Management">
                <i class="fa-solid fa-box"></i> Products
            </a>
            <button class="bi-btn" onclick="fetchBiData()" id="refreshBtn" title="Refresh BI Data">
                <i class="fa-solid fa-rotate"></i> Refresh
            </button>
            <button class="bi-btn primary" onclick="window.print()" title="Print BI Report / Save PDF">
                <i class="fa-solid fa-print"></i> Export Report
            </button>
        </div>
    </div>

    <!-- Executive KPI Grid -->
    <div class="kpi-grid">
        <!-- KPI 1: Valuation -->
        <div class="kpi-card" style="--card-accent: #6366f1;">
            <div class="kpi-top">
                <span class="kpi-label">Total Stock Value</span>
                <div class="kpi-icon-badge"><i class="fa-solid fa-dollar-sign"></i></div>
            </div>
            <div class="kpi-value" id="kpiTotalValuation">$0.00</div>
            <div class="kpi-subtext"><i class="fa-solid fa-arrow-trend-up"></i> Live Inventory Valuation</div>
        </div>

        <!-- KPI 2: Total Products -->
        <div class="kpi-card" style="--card-accent: #3b82f6;">
            <div class="kpi-top">
                <span class="kpi-label">Total Products</span>
                <div class="kpi-icon-badge"><i class="fa-solid fa-boxes-stacked"></i></div>
            </div>
            <div class="kpi-value" id="kpiTotalProducts">0</div>
            <div class="kpi-subtext" id="kpiTotalStockUnits">0 total units in stock</div>
        </div>

        <!-- KPI 3: Total Categories -->
        <div class="kpi-card" style="--card-accent: #8b5cf6;">
            <div class="kpi-top">
                <span class="kpi-label">Active Categories</span>
                <div class="kpi-icon-badge"><i class="fa-solid fa-folder-tree"></i></div>
            </div>
            <div class="kpi-value" id="kpiTotalCategories">0</div>
            <div class="kpi-subtext">Catalog Classifications</div>
        </div>

        <!-- KPI 4: Average Unit Price -->
        <div class="kpi-card" style="--card-accent: #14b8a6;">
            <div class="kpi-top">
                <span class="kpi-label">Avg Unit Price</span>
                <div class="kpi-icon-badge"><i class="fa-solid fa-tag"></i></div>
            </div>
            <div class="kpi-value" id="kpiAvgPrice">$0.00</div>
            <div class="kpi-subtext">Mean Product Valuation</div>
        </div>

        <!-- KPI 5: Low Stock Warning -->
        <div class="kpi-card" style="--card-accent: #f59e0b;">
            <div class="kpi-top">
                <span class="kpi-label">Low Stock Alerts</span>
                <div class="kpi-icon-badge"><i class="fa-solid fa-triangle-exclamation"></i></div>
            </div>
            <div class="kpi-value" id="kpiLowStock">0</div>
            <div class="kpi-subtext" style="color: #fbbf24;">Stock quantity ≤ 10 units</div>
        </div>

        <!-- KPI 6: Out of Stock -->
        <div class="kpi-card" style="--card-accent: #ef4444;">
            <div class="kpi-top">
                <span class="kpi-label">Out of Stock</span>
                <div class="kpi-icon-badge"><i class="fa-solid fa-circle-xmark"></i></div>
            </div>
            <div class="kpi-value" id="kpiOutOfStock">0</div>
            <div class="kpi-subtext" style="color: #f87171;">Action required immediately</div>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="charts-row">
        <!-- Category Valuation Bar Chart -->
        <div class="chart-card">
            <div class="chart-card-header">
                <div class="chart-card-title">
                    <i class="fa-solid fa-chart-line" style="color: #6366f1;"></i>
                    Inventory Value Trend by Category ($)
                </div>
            </div>
            <div class="chart-canvas-container">
                <canvas id="categoryValuationChart"></canvas>
            </div>
        </div>

        <!-- Stock Status Doughnut Chart -->
        <div class="chart-card">
            <div class="chart-card-header">
                <div class="chart-card-title">
                    <i class="fa-solid fa-chart-pie" style="color: #14b8a6;"></i>
                    Stock Health Breakdown
                </div>
            </div>
            <div class="chart-canvas-container">
                <canvas id="stockStatusChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="charts-row">
        <!-- Top 10 High Value Products Chart -->
        <div class="chart-card">
            <div class="chart-card-header">
                <div class="chart-card-title">
                    <i class="fa-solid fa-trophy" style="color: #f59e0b;"></i>
                    Top 10 Most Valuable Inventory Items
                </div>
            </div>
            <div class="chart-canvas-container">
                <canvas id="topProductsChart"></canvas>
            </div>
        </div>

        <!-- Product Count by Category Doughnut -->
        <div class="chart-card">
            <div class="chart-card-header">
                <div class="chart-card-title">
                    <i class="fa-solid fa-layer-group" style="color: #8b5cf6;"></i>
                    Products Count by Category
                </div>
            </div>
            <div class="chart-canvas-container">
                <canvas id="categoryCountChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Category Performance Table -->
    <div class="bi-table-card">
        <div class="table-controls">
            <div class="chart-card-title">
                <i class="fa-solid fa-table-list" style="color: #6366f1;"></i>
                Category BI Performance Breakdown
            </div>
            <div class="bi-search-wrapper">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="tableSearchInput" class="bi-search-input" placeholder="Search category..." oninput="filterBiTable()">
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table class="bi-data-table" id="biTable">
                <thead>
                    <tr>
                        <th>Category Code</th>
                        <th>Category Name</th>
                        <th style="text-align: center;">Product Images</th>
                        <th style="text-align: right;">Product Count</th>
                        <th style="text-align: right;">Total Stock</th>
                        <th style="text-align: right;">Total Valuation ($)</th>
                        <th style="text-align: right;">Avg Unit Price ($)</th>
                        <th style="text-align: center;">Stock Status</th>
                    </tr>
                </thead>
                <tbody id="biTableBody">
                    <tr>
                        <td colspan="8" style="text-align: center; color: var(--text-muted, #94a3b8); padding: 24px;">Loading BI analytics data...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Product Image Lightbox Modal -->
<div id="biImageLightboxModal" class="bi-modal-backdrop" onclick="if(event.target===this) closeBiImageLightbox()">
    <div class="bi-modal-card" style="max-width: 480px;">
        <div class="bi-modal-header">
            <div class="bi-modal-title">
                <i class="fa-solid fa-image" style="color: #38bdf8;"></i>
                <span id="biLightboxTitle">Product Image</span>
                <span id="biLightboxCode" class="badge-status success" style="font-size: 10px; margin-left: 6px;"></span>
            </div>
            <button type="button" class="bi-modal-close" onclick="closeBiImageLightbox()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="bi-modal-body" style="text-align: center; padding: 24px;">
            <div style="background: #0f172a; border-radius: 12px; padding: 12px; border: 1px solid #334155; display: inline-block;">
                <img id="biLightboxImg" src="" alt="Product Image Preview" style="max-width: 100%; max-height: 320px; border-radius: 8px; object-fit: contain;">
            </div>
            <div style="margin-top: 16px; display: flex; justify-content: space-around; background: #0f172a; padding: 12px; border-radius: 10px; border: 1px solid #334155;">
                <div>
                    <div style="font-size: 11px; color: #94a3b8;">Unit Price</div>
                    <div id="biLightboxPrice" style="font-size: 15px; font-weight: 700; color: #4ade80; margin-top: 2px;">$0.00</div>
                </div>
                <div>
                    <div style="font-size: 11px; color: #94a3b8;">Stock Quantity</div>
                    <div id="biLightboxQty" style="font-size: 15px; font-weight: 700; color: #f8fafc; margin-top: 2px;">0</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Category Products Showcase Modal -->
<div id="biCategoryProductsModal" class="bi-modal-backdrop" onclick="if(event.target===this) closeCategoryProductsModal()">
    <div class="bi-modal-card" style="max-width: 720px;">
        <div class="bi-modal-header">
            <div>
                <div class="bi-modal-title">
                    <i class="fa-solid fa-boxes-stacked" style="color: #6366f1;"></i>
                    <span id="biCatModalTitle">Category Products</span>
                </div>
                <div id="biCatModalSubtitle" style="font-size: 12px; color: #94a3b8; margin-top: 2px;"></div>
            </div>
            <button type="button" class="bi-modal-close" onclick="closeCategoryProductsModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="bi-modal-body">
            <div id="biCatProductsGrid" class="cat-products-grid">
                <!-- Dynamically populated product cards -->
            </div>
        </div>
    </div>
</div>

<script>
// Telegram Mini App Initialization
if (window.Telegram && window.Telegram.WebApp) {
    try {
        window.Telegram.WebApp.ready();
        window.Telegram.WebApp.expand();
        if (window.Telegram.WebApp.initDataUnsafe && window.Telegram.WebApp.initDataUnsafe.user) {
            const tgUser = window.Telegram.WebApp.initDataUnsafe.user;
            const nameSpan = document.getElementById('userNameSpan');
            if (nameSpan && tgUser.first_name) {
                nameSpan.textContent = tgUser.first_name + (tgUser.last_name ? ' ' + tgUser.last_name : '');
            }
        }
    } catch(e) {
        console.error('Telegram WebApp init error:', e);
    }
}

let biDataRaw = null;
let categoryValuationChartInstance = null;
let stockStatusChartInstance = null;
let topProductsChartInstance = null;
let categoryCountChartInstance = null;

// Global Chart.js Defaults for Dark Mode
Chart.defaults.color = '#94a3b8';
Chart.defaults.font.family = "'Poppins', sans-serif";

async function fetchBiData() {
    const refreshBtn = document.getElementById('refreshBtn');
    if (refreshBtn) refreshBtn.innerHTML = '<i class="fa-solid fa-rotate fa-spin"></i> Loading...';

    try {
        const response = await fetch('api/bi_data.php');
        const data = await response.json();

        if (data.success) {
            biDataRaw = data;
            renderKpis(data.summary);
            renderCharts(data);
            renderTable(data.category_metrics);
        } else {
            console.error('Failed to load BI data:', data);
        }
    } catch (err) {
        console.error('Error fetching BI Data:', err);
    } finally {
        if (refreshBtn) refreshBtn.innerHTML = '<i class="fa-solid fa-rotate"></i> Refresh';
    }
}

function renderKpis(summary) {
    document.getElementById('kpiTotalValuation').textContent = '$' + Number(summary.total_valuation).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('kpiTotalProducts').textContent = Number(summary.total_products).toLocaleString();
    document.getElementById('kpiTotalStockUnits').textContent = Number(summary.total_quantity).toLocaleString() + ' total units';
    document.getElementById('kpiTotalCategories').textContent = Number(summary.total_categories).toLocaleString();
    document.getElementById('kpiAvgPrice').textContent = '$' + Number(summary.avg_price).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('kpiLowStock').textContent = Number(summary.low_stock_count).toLocaleString();
    document.getElementById('kpiOutOfStock').textContent = Number(summary.out_of_stock_count).toLocaleString();
}

function renderCharts(data) {
    // 1. Multi-Color Gradient Wave Chart
    const catLabels = data.top_categories_by_value.map(c => c.name);
    const catValues = data.top_categories_by_value.map(c => c.total_value);

    const ctx1 = document.getElementById('categoryValuationChart').getContext('2d');
    if (categoryValuationChartInstance) categoryValuationChartInstance.destroy();

    // Horizontal multi-color stroke gradient across the wave curve
    const strokeGradient = ctx1.createLinearGradient(0, 0, 550, 0);
    strokeGradient.addColorStop(0, '#6366f1');   // Indigo
    strokeGradient.addColorStop(0.25, '#38bdf8'); // Cyan
    strokeGradient.addColorStop(0.5, '#10b981');  // Emerald Green
    strokeGradient.addColorStop(0.75, '#f59e0b'); // Amber
    strokeGradient.addColorStop(1, '#ec4899');    // Pink

    // Vertical fill gradient under the curve
    const fillGradient = ctx1.createLinearGradient(0, 0, 0, 180);
    fillGradient.addColorStop(0, 'rgba(56, 189, 248, 0.35)');
    fillGradient.addColorStop(0.5, 'rgba(99, 102, 241, 0.15)');
    fillGradient.addColorStop(1, 'rgba(15, 23, 42, 0.0)');

    categoryValuationChartInstance = new Chart(ctx1, {
        type: 'line',
        data: {
            labels: catLabels,
            datasets: [{
                label: 'Valuation ($)',
                data: catValues,
                borderColor: strokeGradient,
                borderWidth: 4,
                backgroundColor: fillGradient,
                fill: true,
                tension: 0.45,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#38bdf8',
                pointBorderWidth: 3,
                pointRadius: 5.5,
                pointHoverRadius: 8,
                pointHoverBackgroundColor: '#38bdf8',
                pointHoverBorderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => 'Valuation: $' + Number(ctx.raw).toLocaleString(undefined, {minimumFractionDigits: 2})
                    }
                }
            },
            scales: {
                x: { 
                    grid: { color: '#242f42', drawBorder: false },
                    ticks: { color: '#94a3b8', font: { size: 11 } }
                },
                y: { 
                    grid: { color: '#242f42', drawBorder: false },
                    ticks: { 
                        color: '#94a3b8',
                        font: { size: 11 },
                        callback: function(v) {
                            if (v >= 1000) {
                                return '$' + (v / 1000).toFixed(0) + 'K';
                            }
                            return '$' + v;
                        }
                    }
                }
            }
        }
    });

    // 2. Stock Health Breakdown Semi-Circle Speedometer Gauge Chart
    const ctx2 = document.getElementById('stockStatusChart').getContext('2d');
    if (stockStatusChartInstance) stockStatusChartInstance.destroy();

    const inStock = Number(data.stock_status.in_stock || 0);
    const lowStock = Number(data.stock_status.low_stock || 0);
    const outStock = Number(data.stock_status.out_of_stock || 0);

    stockStatusChartInstance = new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: ['In Stock (>10)', 'Low Stock (1-10)', 'Out of Stock (0)'],
            datasets: [{
                data: [inStock, lowStock, outStock],
                backgroundColor: [
                    'rgba(16, 185, 129, 0.9)',
                    'rgba(245, 158, 11, 0.9)',
                    'rgba(239, 68, 68, 0.9)'
                ],
                borderWidth: 3,
                borderColor: '#161d2a',
                borderRadius: 6,
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            circumference: 180,
            rotation: -90,
            cutout: '72%',
            plugins: {
                legend: { 
                    position: 'bottom',
                    labels: { boxWidth: 12, padding: 14, color: '#94a3b8', font: { size: 11 } } 
                },
                tooltip: {
                    callbacks: {
                        label: ctx => ` ${ctx.label}: ${ctx.raw} items`
                    }
                }
            }
        }
    });

    // 3. Top 10 Valuable Products 3D Gradient Column Bar Chart
    const prodLabels = data.top_products_by_value.map(p => p.product_name);
    const prodValues = data.top_products_by_value.map(p => p.total_value);

    const ctx3 = document.getElementById('topProductsChart').getContext('2d');
    if (topProductsChartInstance) topProductsChartInstance.destroy();

    const barGradient = ctx3.createLinearGradient(0, 0, 0, 180);
    barGradient.addColorStop(0, 'rgba(56, 189, 248, 0.95)'); // Cyan top
    barGradient.addColorStop(1, 'rgba(99, 102, 241, 0.45)'); // Indigo bottom

    topProductsChartInstance = new Chart(ctx3, {
        type: 'bar',
        data: {
            labels: prodLabels,
            datasets: [{
                label: 'Total Value ($)',
                data: prodValues,
                backgroundColor: barGradient,
                borderColor: '#38bdf8',
                borderWidth: 1.5,
                borderRadius: { topLeft: 8, topRight: 8 },
                maxBarThickness: 36
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => ' Total Value: $' + Number(ctx.raw).toLocaleString(undefined, {minimumFractionDigits: 2})
                    }
                }
            },
            scales: {
                x: { 
                    grid: { color: '#242f42', drawBorder: false },
                    ticks: { 
                        color: '#94a3b8', 
                        font: { size: 10.5 },
                        maxRotation: 35,
                        minRotation: 0
                    } 
                },
                y: { 
                    grid: { color: '#242f42', drawBorder: false },
                    ticks: { 
                        color: '#94a3b8',
                        font: { size: 11 },
                        callback: function(v) {
                            if (v >= 1000) {
                                return '$' + (v / 1000).toFixed(0) + 'K';
                            }
                            return '$' + v;
                        }
                    }
                }
            }
        }
    });

    // 4. Products Count by Category Modern Doughnut Chart
    const catCountLabels = data.category_metrics.filter(c => c.product_count > 0).slice(0, 7).map(c => c.name);
    const catCountData = data.category_metrics.filter(c => c.product_count > 0).slice(0, 7).map(c => c.product_count);

    const ctx4 = document.getElementById('categoryCountChart').getContext('2d');
    if (categoryCountChartInstance) categoryCountChartInstance.destroy();
    categoryCountChartInstance = new Chart(ctx4, {
        type: 'doughnut',
        data: {
            labels: catCountLabels,
            datasets: [{
                data: catCountData,
                backgroundColor: [
                    '#6366f1', '#38bdf8', '#8b5cf6', '#10b981', 
                    '#ec4899', '#f59e0b', '#3b82f6'
                ],
                borderWidth: 3,
                borderColor: '#161d2a',
                borderRadius: 5,
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '66%',
            plugins: {
                legend: { 
                    position: 'bottom', 
                    labels: { boxWidth: 12, padding: 12, color: '#94a3b8', font: { size: 10.5 } } 
                },
                tooltip: {
                    callbacks: {
                        label: ctx => ` ${ctx.label}: ${ctx.raw} products`
                    }
                }
            }
        }
    });
}

function renderTable(categories) {
    const tbody = document.getElementById('biTableBody');
    tbody.innerHTML = '';

    if (!categories || categories.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; color: var(--text-muted, #94a3b8); padding: 24px;">No categories found.</td></tr>';
        return;
    }

    categories.forEach(c => {
        let statusBadge = '';
        if (c.out_of_stock_count > 0) {
            statusBadge = `<span class="badge-status danger" title="${c.out_of_stock_count} Out of Stock"><i class="fa-solid fa-circle-xmark"></i> <span class="badge-text-full">${c.out_of_stock_count} Out of Stock</span><span class="badge-text-mobile">${c.out_of_stock_count} Out</span></span>`;
        } else if (c.low_stock_count > 0) {
            statusBadge = `<span class="badge-status warning" title="${c.low_stock_count} Low Stock"><i class="fa-solid fa-triangle-exclamation"></i> <span class="badge-text-full">${c.low_stock_count} Low Stock</span><span class="badge-text-mobile">${c.low_stock_count} Low</span></span>`;
        } else {
            statusBadge = `<span class="badge-status success" title="Healthy Stock"><i class="fa-solid fa-circle-check"></i> <span class="badge-text-full">Healthy</span><span class="badge-text-mobile">Healthy</span></span>`;
        }

        // Build Single Featured Image + Count Badge
        let imagesHtml = '';
        const products = c.products || [];
        if (products.length === 0) {
            imagesHtml = `<span style="font-size: 11px; color: #64748b; font-style: italic;"><i class="fa-regular fa-image"></i> No products</span>`;
        } else {
            const featuredProd = products.find(p => p.image) || products[0];
            const titleAttr = `${escapeHtml(featuredProd.product_name)} (${featuredProd.product_code}) - $${Number(featuredProd.price).toFixed(2)} | Qty: ${featuredProd.quantity}`;

            let imgElement = '';
            if (featuredProd.image) {
                imgElement = `<img src="${escapeHtml(featuredProd.image)}" class="prod-single-thumb" title="${titleAttr}" onclick="event.stopPropagation(); openBiImageLightbox('${escapeHtml(featuredProd.image)}', '${escapeHtml(featuredProd.product_name)}', '${escapeHtml(featuredProd.product_code)}', ${featuredProd.price}, ${featuredProd.quantity})">`;
            } else {
                imgElement = `<div class="prod-single-no-img" title="${titleAttr}" onclick="event.stopPropagation(); openCategoryProductsModal(${c.id})"><i class="fa-solid fa-box-open"></i></div>`;
            }

            let pillHtml = '';
            if (products.length > 1) {
                pillHtml = `<span class="prod-single-count-pill" onclick="event.stopPropagation(); openCategoryProductsModal(${c.id})" title="View all ${products.length} products in this category"><i class="fa-solid fa-boxes-stacked"></i> ${products.length} items</span>`;
            } else {
                pillHtml = `<span class="prod-single-count-pill" onclick="event.stopPropagation(); openCategoryProductsModal(${c.id})" title="View product details" style="background: rgba(148, 163, 184, 0.1); color: #94a3b8; border-color: rgba(148, 163, 184, 0.2);"><i class="fa-solid fa-box"></i> 1 item</span>`;
            }

            imagesHtml = `<div class="prod-single-wrapper">${imgElement} ${pillHtml}</div>`;
        }

        const tr = document.createElement('tr');
        tr.className = 'bi-row';
        tr.style.cursor = 'pointer';
        tr.onclick = function(e) {
            if (c.products && c.products.length > 0) {
                openCategoryProductsModal(c.id);
            }
        };
        tr.innerHTML = `
            <td><code>${escapeHtml(c.code)}</code></td>
            <td style="font-weight: 600; color: var(--text-main, #f8fafc);">${escapeHtml(c.name)}</td>
            <td style="text-align: center;">${imagesHtml}</td>
            <td style="text-align: right;">${Number(c.product_count).toLocaleString()}</td>
            <td style="text-align: right;">${Number(c.total_stock).toLocaleString()}</td>
            <td style="text-align: right; font-weight: 600; color: #4ade80;">$${Number(c.total_value).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            <td style="text-align: right;">$${Number(c.avg_price).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            <td style="text-align: center;">${statusBadge}</td>
        `;
        tbody.appendChild(tr);
    });
}

function openBiImageLightbox(imgUrl, prodName, prodCode, price, qty) {
    document.getElementById('biLightboxImg').src = imgUrl;
    document.getElementById('biLightboxTitle').textContent = prodName || 'Product Image';
    document.getElementById('biLightboxCode').textContent = prodCode || '';
    document.getElementById('biLightboxPrice').textContent = '$' + Number(price || 0).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
    document.getElementById('biLightboxQty').textContent = Number(qty || 0).toLocaleString();
    document.getElementById('biImageLightboxModal').classList.add('active');
}

function closeBiImageLightbox() {
    document.getElementById('biImageLightboxModal').classList.remove('active');
}

function openCategoryProductsModal(categoryId) {
    if (!biDataRaw || !biDataRaw.category_metrics) return;
    const cat = biDataRaw.category_metrics.find(c => c.id == categoryId);
    if (!cat) return;

    document.getElementById('biCatModalTitle').textContent = `${cat.name} (${cat.code})`;
    document.getElementById('biCatModalSubtitle').textContent = `${cat.product_count} products | Total Stock: ${cat.total_stock} units | Valuation: $${Number(cat.total_value).toLocaleString(undefined, {minimumFractionDigits:2})}`;
    
    const container = document.getElementById('biCatProductsGrid');
    container.innerHTML = '';

    if (!cat.products || cat.products.length === 0) {
        container.innerHTML = '<div style="text-align: center; color: #94a3b8; padding: 24px; grid-column: 1/-1;">No products found in this category.</div>';
    } else {
        cat.products.forEach(p => {
            const card = document.createElement('div');
            card.className = 'cat-prod-card';
            const imgHtml = p.image 
                ? `<img src="${escapeHtml(p.image)}" class="cat-prod-img" onclick="openBiImageLightbox('${escapeHtml(p.image)}', '${escapeHtml(p.product_name)}', '${escapeHtml(p.product_code)}', ${p.price}, ${p.quantity})">`
                : `<div class="cat-prod-img" style="background:#1e293b; display:flex; flex-direction:column; align-items:center; justify-content:center;"><i class="fa-solid fa-box-open" style="font-size: 20px; color: #475569;"></i><span style="font-size: 10px; color: #64748b; margin-top: 2px;">No Image</span></div>`;
            
            let badgeClass = p.quantity === 0 ? 'danger' : (p.quantity <= 10 ? 'warning' : 'success');
            let badgeText = p.quantity === 0 ? 'Out of Stock' : (p.quantity <= 10 ? `Low (${p.quantity})` : `In Stock (${p.quantity})`);

            card.innerHTML = `
                <div class="cat-prod-img-wrap">${imgHtml}</div>
                <div class="cat-prod-info">
                    <div class="cat-prod-code"><code>${escapeHtml(p.product_code)}</code></div>
                    <div class="cat-prod-name" title="${escapeHtml(p.product_name)}">${escapeHtml(p.product_name)}</div>
                    <div class="cat-prod-meta">
                        <span class="cat-prod-price">$${Number(p.price).toLocaleString(undefined, {minimumFractionDigits:2})}</span>
                        <span class="badge-status ${badgeClass}" style="font-size: 10px;">${badgeText}</span>
                    </div>
                </div>
            `;
            container.appendChild(card);
        });
    }

    document.getElementById('biCategoryProductsModal').classList.add('active');
}

function closeCategoryProductsModal() {
    document.getElementById('biCategoryProductsModal').classList.remove('active');
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeBiImageLightbox();
        closeCategoryProductsModal();
    }
});

function filterBiTable() {
    const q = document.getElementById('tableSearchInput').value.toLowerCase().trim();
    const rows = document.querySelectorAll('.bi-row');
    rows.forEach(r => {
        const text = r.textContent.toLowerCase();
        r.style.display = text.includes(q) ? '' : 'none';
    });
}

function escapeHtml(str) {
    return String(str ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

document.addEventListener('DOMContentLoaded', fetchBiData);
</script>

</body>
</html>
