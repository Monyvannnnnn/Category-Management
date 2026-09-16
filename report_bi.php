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
            padding: 16px 20px;
            gap: 18px;
        }

        .bi-header-card {
            background: var(--surface-card, #161d2a);
            border: 1px solid var(--border-subtle, #242f42);
            border-radius: 14px;
            padding: 14px 18px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
            max-width: 100%;
            box-sizing: border-box;
        }

        @media (min-width: 900px) {
            .bi-header-card {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }
        }

        .bi-title-group {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .bi-title-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 18px;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.35);
        }

        .bi-title-text {
            min-width: 0;
        }

        .bi-title-text h1 {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-main, #f8fafc);
            margin: 0;
            line-height: 1.25;
            white-space: nowrap;
        }

        .bi-title-text p {
            font-size: 11px;
            color: var(--text-muted, #94a3b8);
            margin: 2px 0 0 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        @media (max-width: 480px) {
            .bi-title-text h1 {
                font-size: 16px;
                white-space: normal;
            }
            .bi-title-text p {
                white-space: normal;
            }
        }

        .bi-header-actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px;
            width: 100%;
        }

        @media (min-width: 900px) {
            .bi-header-actions {
                width: auto;
            }
        }

        .bi-btn {
            background: var(--surface-alt, #1a2333);
            border: 1px solid var(--border-subtle, #242f42);
            color: var(--text-main, #f8fafc);
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
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

        /* KPI Cards Grid */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
        }

        .kpi-card {
            background: var(--surface-card, #161d2a);
            border: 1px solid var(--border-subtle, #242f42);
            border-radius: 14px;
            padding: 16px 18px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            transition: transform 0.2s ease, border-color 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .kpi-card:hover {
            transform: translateY(-2px);
            border-color: var(--primary-color, #6366f1);
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--card-accent, var(--primary-color, #6366f1));
        }

        .kpi-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .kpi-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted, #94a3b8);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .kpi-icon-badge {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: var(--surface-alt, #1a2333);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--card-accent, var(--primary-color, #6366f1));
            font-size: 14px;
        }

        .kpi-value {
            font-size: 22px;
            font-weight: 700;
            color: var(--text-main, #f8fafc);
            letter-spacing: -0.5px;
        }

        .kpi-subtext {
            font-size: 11px;
            color: var(--text-muted, #94a3b8);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* Charts Layout */
        .charts-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 16px;
        }

        @media (max-width: 900px) {
            .charts-row {
                grid-template-columns: 1fr;
            }
        }

        .chart-card {
            background: var(--surface-card, #161d2a);
            border: 1px solid var(--border-subtle, #242f42);
            border-radius: 14px;
            padding: 18px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .chart-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .chart-card-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-main, #f8fafc);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .chart-canvas-container {
            position: relative;
            width: 100%;
            height: 260px;
        }

        /* Table Card */
        .bi-table-card {
            background: var(--surface-card, #161d2a);
            border: 1px solid var(--border-subtle, #242f42);
            border-radius: 14px;
            padding: 18px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            margin-bottom: 20px;
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
            padding: 10px 14px;
            border-bottom: 1px solid var(--border-subtle, #242f42);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .bi-data-table td {
            padding: 12px 14px;
            border-bottom: 1px solid var(--border-subtle, #242f42);
            color: var(--text-main, #f8fafc);
        }

        .bi-data-table tbody tr:hover {
            background: rgba(255, 255, 255, 0.02);
        }

        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
        }

        .badge-status.success {
            background: rgba(34, 197, 94, 0.15);
            color: #4ade80;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }

        .badge-status.warning {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .badge-status.danger {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
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
        <div class="bi-title-group">
            <div class="bi-title-icon">
                <i class="fa-solid fa-chart-pie"></i>
            </div>
            <div class="bi-title-text">
                <h1>BI Executive Analytics</h1>
                <p>Real-time Business Intelligence & Inventory Valuation Overview</p>
            </div>
        </div>

        <div class="bi-header-actions">
            <!-- Navigation Links -->
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

            <!-- User Badge -->
            <div class="user-profile-badge" style="margin-left: 6px;">
                <i class="fa-solid fa-user-circle"></i>
                <span class="user-profile-name" id="userNameSpan"><?php echo htmlspecialchars($currentUser['name'] ?? 'Admin'); ?></span>
            </div>
            <a href="logout.php" class="logout-icon-btn" title="Sign Out" style="margin-left: 2px;">
                <i class="fa-solid fa-right-from-bracket"></i>
            </a>
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
                    <i class="fa-solid fa-chart-column" style="color: #6366f1;"></i>
                    Inventory Value by Category ($)
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
                        <th style="text-align: right;">Product Count</th>
                        <th style="text-align: right;">Total Stock</th>
                        <th style="text-align: right;">Total Valuation ($)</th>
                        <th style="text-align: right;">Avg Unit Price ($)</th>
                        <th style="text-align: center;">Stock Status</th>
                    </tr>
                </thead>
                <tbody id="biTableBody">
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-muted, #94a3b8); padding: 24px;">Loading BI analytics data...</td>
                    </tr>
                </tbody>
            </table>
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
    // 1. Category Valuation Bar Chart
    const catLabels = data.top_categories_by_value.map(c => c.name);
    const catValues = data.top_categories_by_value.map(c => c.total_value);

    const ctx1 = document.getElementById('categoryValuationChart').getContext('2d');
    if (categoryValuationChartInstance) categoryValuationChartInstance.destroy();
    categoryValuationChartInstance = new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: catLabels,
            datasets: [{
                label: 'Valuation ($)',
                data: catValues,
                backgroundColor: 'rgba(99, 102, 241, 0.75)',
                borderColor: '#6366f1',
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => 'Valuation: $' + Number(ctx.raw).toLocaleString()
                    }
                }
            },
            scales: {
                x: { grid: { color: '#242f42' } },
                y: { 
                    grid: { color: '#242f42' },
                    ticks: { callback: v => '$' + v.toLocaleString() }
                }
            }
        }
    });

    // 2. Stock Health Breakdown Doughnut Chart
    const ctx2 = document.getElementById('stockStatusChart').getContext('2d');
    if (stockStatusChartInstance) stockStatusChartInstance.destroy();
    stockStatusChartInstance = new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: ['In Stock (>10)', 'Low Stock (1-10)', 'Out of Stock (0)'],
            datasets: [{
                data: [
                    data.stock_status.in_stock,
                    data.stock_status.low_stock,
                    data.stock_status.out_of_stock
                ],
                backgroundColor: ['#22c55e', '#f59e0b', '#ef4444'],
                borderWidth: 2,
                borderColor: '#161d2a'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, padding: 16 } }
            }
        }
    });

    // 3. Top 10 Valuable Products Horizontal Bar Chart
    const prodLabels = data.top_products_by_value.map(p => p.product_name);
    const prodValues = data.top_products_by_value.map(p => p.total_value);

    const ctx3 = document.getElementById('topProductsChart').getContext('2d');
    if (topProductsChartInstance) topProductsChartInstance.destroy();
    topProductsChartInstance = new Chart(ctx3, {
        type: 'bar',
        data: {
            labels: prodLabels,
            datasets: [{
                axis: 'y',
                label: 'Total Value ($)',
                data: prodValues,
                backgroundColor: 'rgba(245, 158, 11, 0.75)',
                borderColor: '#f59e0b',
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => 'Total Value: $' + Number(ctx.raw).toLocaleString()
                    }
                }
            },
            scales: {
                x: { 
                    grid: { color: '#242f42' },
                    ticks: { callback: v => '$' + v.toLocaleString() }
                },
                y: { grid: { color: '#242f42' } }
            }
        }
    });

    // 4. Products Count by Category Doughnut
    const catCountLabels = data.category_metrics.filter(c => c.product_count > 0).slice(0, 7).map(c => c.name);
    const catCountData = data.category_metrics.filter(c => c.product_count > 0).slice(0, 7).map(c => c.product_count);

    const ctx4 = document.getElementById('categoryCountChart').getContext('2d');
    if (categoryCountChartInstance) categoryCountChartInstance.destroy();
    categoryCountChartInstance = new Chart(ctx4, {
        type: 'pie',
        data: {
            labels: catCountLabels,
            datasets: [{
                data: catCountData,
                backgroundColor: [
                    '#6366f1', '#14b8a6', '#8b5cf6', '#3b82f6', 
                    '#ec4899', '#f97316', '#10b981'
                ],
                borderWidth: 2,
                borderColor: '#161d2a'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12 } }
            }
        }
    });
}

function renderTable(categories) {
    const tbody = document.getElementById('biTableBody');
    tbody.innerHTML = '';

    if (!categories || categories.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: var(--text-muted, #94a3b8); padding: 24px;">No categories found.</td></tr>';
        return;
    }

    categories.forEach(c => {
        let statusBadge = '';
        if (c.out_of_stock_count > 0) {
            statusBadge = `<span class="badge-status danger"><i class="fa-solid fa-circle-xmark"></i> ${c.out_of_stock_count} Out of Stock</span>`;
        } else if (c.low_stock_count > 0) {
            statusBadge = `<span class="badge-status warning"><i class="fa-solid fa-triangle-exclamation"></i> ${c.low_stock_count} Low Stock</span>`;
        } else {
            statusBadge = `<span class="badge-status success"><i class="fa-solid fa-circle-check"></i> Healthy</span>`;
        }

        const tr = document.createElement('tr');
        tr.className = 'bi-row';
        tr.innerHTML = `
            <td><code>${escapeHtml(c.code)}</code></td>
            <td style="font-weight: 600; color: var(--text-main, #f8fafc);">${escapeHtml(c.name)}</td>
            <td style="text-align: right;">${Number(c.product_count).toLocaleString()}</td>
            <td style="text-align: right;">${Number(c.total_stock).toLocaleString()}</td>
            <td style="text-align: right; font-weight: 600; color: #4ade80;">$${Number(c.total_value).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            <td style="text-align: right;">$${Number(c.avg_price).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            <td style="text-align: center;">${statusBadge}</td>
        `;
        tbody.appendChild(tr);
    });
}

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
