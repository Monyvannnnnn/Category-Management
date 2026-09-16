<?php
// report_bi.php - Executive 4-Panel BI Analytics Dashboard (Dark Theme)
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
    <title>BI Analytics Dashboard - Executive Overview</title>

    <!-- Telegram Mini App WebApp SDK -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- Chart.js for BI Charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

    <!-- Base Stylesheet -->
    <link rel="stylesheet" href="css/style.css?v=<?php echo date('Y-m-d-H-i-s', @filemtime(__DIR__ . '/css/style.css')); ?>">

    <style>
        .bi-page-container {
            width: 100%;
            max-width: 100%;
            min-height: 100vh;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            background-color: var(--bg-main, #0f141c);
            overflow-y: auto;
            padding: 12px 16px;
            gap: 16px;
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

        /* 4-Panel Grid Layout */
        .dashboard-4panel-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 1024px) {
            .dashboard-4panel-grid {
                grid-template-columns: 1fr;
            }
        }

        .panel-card {
            background: var(--surface-card, #161d2a);
            border: 1px solid var(--border-subtle, #242f42);
            border-radius: 14px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        .panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .panel-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-main, #f8fafc);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Double Donut Container (Top Right Panel) */
        .donuts-split-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        @media (max-width: 600px) {
            .donuts-split-container {
                grid-template-columns: 1fr;
            }
        }

        .donut-sub-card {
            background: var(--surface-alt, #1a2333);
            border: 1px solid var(--border-subtle, #242f42);
            border-radius: 10px;
            padding: 14px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            align-items: center;
        }

        .donut-sub-title {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted, #94a3b8);
            text-align: center;
        }

        .donut-canvas-holder {
            position: relative;
            width: 140px;
            height: 140px;
        }

        .mini-breakdown-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .mini-breakdown-table th {
            color: var(--text-muted, #94a3b8);
            font-weight: 600;
            border-bottom: 1px solid var(--border-subtle, #242f42);
            padding: 6px 4px;
            text-align: left;
        }

        .mini-breakdown-table td {
            padding: 6px 4px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
            color: var(--text-main, #f8fafc);
        }

        .dot-label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .dot-icon {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }

        /* Canvas Containers */
        .chart-panel-container {
            position: relative;
            width: 100%;
            height: 280px;
        }

        /* Embedded Progress Bar Table for Top 10 Restocked */
        .valuable-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            text-align: left;
        }

        .valuable-table th {
            color: var(--text-muted, #94a3b8);
            font-weight: 600;
            border-bottom: 1px solid var(--border-subtle, #242f42);
            padding: 10px 8px;
            font-size: 11px;
            text-transform: uppercase;
        }

        .valuable-table td {
            padding: 10px 8px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            color: var(--text-main, #f8fafc);
        }

        .valuable-table tbody tr:hover {
            background: rgba(255, 255, 255, 0.02);
        }

        .stock-value-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stock-val-text {
            min-width: 75px;
            font-weight: 600;
            color: #ffffff;
        }

        .stock-bar-outer {
            flex: 1;
            height: 12px;
            background: var(--surface-alt, #1a2333);
            border-radius: 4px;
            overflow: hidden;
            position: relative;
        }

        .stock-bar-inner {
            height: 100%;
            background: linear-gradient(90deg, #3b82f6 0%, #60a5fa 100%);
            border-radius: 4px;
            transition: width 0.4s ease;
        }

        @media print {
            body { background: #fff !important; color: #000 !important; }
            .bi-page-container { padding: 0; }
            .bi-header-actions { display: none !important; }
            .panel-card { background: #fff !important; color: #000 !important; border: 1px solid #ccc !important; box-shadow: none !important; }
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

            <div class="user-profile-badge" style="margin-left: 10px;">
                <i class="fa-solid fa-user-circle"></i>
                <span class="user-profile-name" id="userNameSpan"><?php echo htmlspecialchars($currentUser['name'] ?? 'Admin'); ?></span>
            </div>
            <a href="logout.php" class="logout-icon-btn" title="Sign Out" style="margin-left: 4px;">
                <i class="fa-solid fa-right-from-bracket"></i>
            </a>
        </div>
    </div>

    <!-- 4-Panel Grid Layout -->
    <div class="dashboard-4panel-grid">
        
        <!-- PANEL 1: Top Left - Total SKUs by Month -->
        <div class="panel-card">
            <div class="panel-header">
                <div class="panel-title">
                    <i class="fa-solid fa-chart-line" style="color: #3b82f6;"></i>
                    Total SKUs by Month
                </div>
            </div>
            <div class="chart-panel-container">
                <canvas id="skusByMonthChart"></canvas>
            </div>
        </div>

        <!-- PANEL 2: Top Right - Products by ReOrder Status & Stock_Level -->
        <div class="panel-card">
            <div class="donuts-split-container">
                
                <!-- Donut 1: ReOrder Status -->
                <div class="donut-sub-card">
                    <div class="donut-sub-title">Products by ReOrder Status</div>
                    <div class="donut-canvas-holder">
                        <canvas id="reorderStatusChart"></canvas>
                    </div>
                    <table class="mini-breakdown-table">
                        <thead>
                            <tr>
                                <th>ReOrder Status</th>
                                <th style="text-align: right;">Product%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="dot-label"><span class="dot-icon" style="background: #3b82f6;"></span> Order Now</span></td>
                                <td style="text-align: right;" id="percOrderNow">0.00%</td>
                            </tr>
                            <tr>
                                <td><span class="dot-label"><span class="dot-icon" style="background: #f59e0b;"></span> Plan to Reorder</span></td>
                                <td style="text-align: right;" id="percPlanReorder">0.00%</td>
                            </tr>
                            <tr>
                                <td><span class="dot-label"><span class="dot-icon" style="background: #22c55e;"></span> Reorder Not Required</span></td>
                                <td style="text-align: right;" id="percReorderNotReq">0.00%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Donut 2: Stock_Level -->
                <div class="donut-sub-card">
                    <div class="donut-sub-title">Products by Stock_Level</div>
                    <div class="donut-canvas-holder">
                        <canvas id="stockLevelChart"></canvas>
                    </div>
                    <table class="mini-breakdown-table">
                        <thead>
                            <tr>
                                <th>Stock_Level</th>
                                <th style="text-align: right;">Product%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="dot-label"><span class="dot-icon" style="background: #2563eb;"></span> High (&gt;50)</span></td>
                                <td style="text-align: right;" id="percHigh">0.00%</td>
                            </tr>
                            <tr>
                                <td><span class="dot-label"><span class="dot-icon" style="background: #f43f5e;"></span> Low (≤10)</span></td>
                                <td style="text-align: right;" id="percLow">0.00%</td>
                            </tr>
                            <tr>
                                <td><span class="dot-label"><span class="dot-icon" style="background: #60a5fa;"></span> Mid (11-50)</span></td>
                                <td style="text-align: right;" id="percMid">0.00%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

        <!-- PANEL 3: Bottom Left - Total SKUs by Category and Stock_Level -->
        <div class="panel-card">
            <div class="panel-header">
                <div class="panel-title">
                    <i class="fa-solid fa-chart-column" style="color: #60a5fa;"></i>
                    Total SKUs by Category and Stock_Level
                </div>
            </div>
            <div class="chart-panel-container">
                <canvas id="categoryStockLevelChart"></canvas>
            </div>
        </div>

        <!-- PANEL 4: Bottom Right - Top 10 Most Valuable Products & Restock Date -->
        <div class="panel-card">
            <div class="panel-header">
                <div class="panel-title">
                    <i class="fa-solid fa-trophy" style="color: #f59e0b;"></i>
                    TOP 10 MOST VALUABLE PRODUCTS AND LAST RESTOCKED DATE
                </div>
            </div>
            <div style="overflow-x: auto;">
                <table class="valuable-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th style="min-width: 180px;">Stock Value ($)</th>
                            <th style="text-align: right;">Qty</th>
                            <th style="text-align: right;">Avg Price</th>
                            <th style="text-align: right;">Leadtime</th>
                            <th style="text-align: center;">Last Restock</th>
                        </tr>
                    </thead>
                    <tbody id="top10TableBody">
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted, #94a3b8); padding: 24px;">Loading top products...</td>
                        </tr>
                    </tbody>
                </table>
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

let skusByMonthChartInstance = null;
let reorderChartInstance = null;
let stockLevelChartInstance = null;
let categoryStockChartInstance = null;

// Global Chart.js Defaults for Dark Theme
Chart.defaults.color = '#94a3b8';
Chart.defaults.font.family = "'Poppins', sans-serif";

async function fetchBiData() {
    const refreshBtn = document.getElementById('refreshBtn');
    if (refreshBtn) refreshBtn.innerHTML = '<i class="fa-solid fa-rotate fa-spin"></i> Loading...';

    try {
        const response = await fetch('api/bi_data.php');
        const data = await response.json();

        if (data.success) {
            renderSkusByMonthChart(data.skus_by_month);
            renderReorderStatus(data.reorder_status);
            renderStockLevel(data.stock_level);
            renderCategoryStockLevelChart(data.category_stock_levels);
            renderTop10ValuableProducts(data.top_10_most_valuable, data.max_stock_value);
        } else {
            console.error('Failed to load BI data:', data);
        }
    } catch (err) {
        console.error('Error fetching BI Data:', err);
    } finally {
        if (refreshBtn) refreshBtn.innerHTML = '<i class="fa-solid fa-rotate"></i> Refresh';
    }
}

// 1. Total SKUs by Month (Wave Curve Chart)
function renderSkusByMonthChart(skusData) {
    const labels = skusData.map(d => d.month);
    const values = skusData.map(d => d.count);

    const ctx = document.getElementById('skusByMonthChart').getContext('2d');
    if (skusByMonthChartInstance) skusByMonthChartInstance.destroy();

    skusByMonthChartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Total SKUs',
                data: values,
                borderColor: '#3b82f6',
                borderWidth: 3,
                tension: 0.45,
                pointRadius: 0,
                pointHoverRadius: 6,
                pointHoverBackgroundColor: '#3b82f6'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => `SKUs: ${ctx.raw}`
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: '#94a3b8' }
                },
                y: {
                    grid: { color: '#242f42' },
                    ticks: { color: '#94a3b8' }
                }
            }
        }
    });
}

// 2. ReOrder Status Donut & Table
function renderReorderStatus(status) {
    document.getElementById('percOrderNow').textContent = status.order_now.percent + '%';
    document.getElementById('percPlanReorder').textContent = status.plan_reorder.percent + '%';
    document.getElementById('percReorderNotReq').textContent = status.reorder_not_required.percent + '%';

    const ctx = document.getElementById('reorderStatusChart').getContext('2d');
    if (reorderChartInstance) reorderChartInstance.destroy();

    reorderChartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Order Now', 'Plan to Reorder', 'Reorder Not Required'],
            datasets: [{
                data: [
                    status.order_now.count,
                    status.plan_reorder.count,
                    status.reorder_not_required.count
                ],
                backgroundColor: ['#3b82f6', '#f59e0b', '#22c55e'],
                borderWidth: 2,
                borderColor: '#1a2333',
                cutout: '70%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            }
        }
    });
}

// 3. Stock Level Donut & Table
function renderStockLevel(level) {
    document.getElementById('percHigh').textContent = level.high.percent + '%';
    document.getElementById('percLow').textContent = level.low.percent + '%';
    document.getElementById('percMid').textContent = level.mid.percent + '%';

    const ctx = document.getElementById('stockLevelChart').getContext('2d');
    if (stockLevelChartInstance) stockLevelChartInstance.destroy();

    stockLevelChartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['High', 'Low', 'Mid'],
            datasets: [{
                data: [
                    level.high.count,
                    level.low.count,
                    level.mid.count
                ],
                backgroundColor: ['#2563eb', '#f43f5e', '#60a5fa'],
                borderWidth: 2,
                borderColor: '#1a2333',
                cutout: '70%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            }
        }
    });
}

// 4. Total SKUs by Category and Stock Level (Stacked Vertical Bar Chart)
function renderCategoryStockLevelChart(catStockLevels) {
    const labels = catStockLevels.map(c => c.name);
    const highData = catStockLevels.map(c => c.high_count);
    const lowData = catStockLevels.map(c => c.low_count);
    const midData = catStockLevels.map(c => c.mid_count);

    const ctx = document.getElementById('categoryStockLevelChart').getContext('2d');
    if (categoryStockChartInstance) categoryStockChartInstance.destroy();

    categoryStockChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'High',
                    data: highData,
                    backgroundColor: '#2563eb',
                    stack: 'Stack 0',
                    borderRadius: 2
                },
                {
                    label: 'Low',
                    data: lowData,
                    backgroundColor: '#f43f5e',
                    stack: 'Stack 0',
                    borderRadius: 2
                },
                {
                    label: 'Mid',
                    data: midData,
                    backgroundColor: '#60a5fa',
                    stack: 'Stack 0',
                    borderRadius: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    align: 'start',
                    labels: { boxWidth: 10, padding: 12 }
                }
            },
            scales: {
                x: {
                    stacked: true,
                    grid: { display: false },
                    ticks: { color: '#94a3b8' }
                },
                y: {
                    stacked: true,
                    grid: { color: '#242f42' },
                    ticks: { color: '#94a3b8' }
                }
            }
        }
    });
}

// 5. Top 10 Most Valuable Products and Restocked Date (Embedded Progress Bar Table)
function renderTop10ValuableProducts(products, maxValue) {
    const tbody = document.getElementById('top10TableBody');
    tbody.innerHTML = '';

    if (!products || products.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: var(--text-muted, #94a3b8); padding: 24px;">No products available.</td></tr>';
        return;
    }

    products.forEach(p => {
        const valFormatted = Number(p.stock_value).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        const widthPercent = Math.min(100, Math.max(8, (p.stock_value / maxValue) * 100));

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td style="font-weight: 600; color: #f8fafc;">${escapeHtml(p.product_name)}</td>
            <td>
                <div class="stock-value-cell">
                    <span class="stock-val-text">${valFormatted}</span>
                    <div class="stock-bar-outer">
                        <div class="stock-bar-inner" style="width: ${widthPercent}%;"></div>
                    </div>
                </div>
            </td>
            <td style="text-align: right;">${Number(p.quantity).toLocaleString()}</td>
            <td style="text-align: right;">${Number(p.avg_price).toFixed(2)}</td>
            <td style="text-align: right; color: var(--text-muted);">${p.leadtime_days}.00</td>
            <td style="text-align: center; color: var(--text-muted);">${p.last_restock}</td>
        `;
        tbody.appendChild(tr);
    });
}

function escapeHtml(str) {
    return String(str ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

document.addEventListener('DOMContentLoaded', fetchBiData);
</script>

</body>
</html>
