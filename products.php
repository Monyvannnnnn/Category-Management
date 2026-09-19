<?php

// Prevent the browser from caching this HTML page, so edits to the grid
// config (paging/scrolling) always take effect on reload instead of running
// a stale cached version. Safe for the JSON API too (no harmful side-effects).
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

require_once "database.php";
require_once "includes/auth_helper.php";

requireAuth();
$currentUser = getCurrentUser();

// API Endpoint to read product list
if (isset($_GET["action"]) && $_GET["action"] === "read") {
    header("Content-Type: application/json");
    // Prevent caching so newly added/edited/deleted rows always show up live
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: 0");
    $userId = (int)($currentUser['id'] ?? 1);
    $stmt = db_prepare($conn, "SELECT product.*, category.category_name FROM product LEFT JOIN category ON product.category_id = category.id WHERE product.user_id = ? ORDER BY product.id DESC");
    if ($stmt) {
        db_stmt_bind_param($stmt, "i", $userId);
        db_stmt_execute($stmt);
        $result = db_stmt_get_result($stmt);
        $products = [];
        if ($result) {
            while ($row = db_fetch_assoc($result)) {
                $products[] = $row;
            }
        }
        db_stmt_close($stmt);
    } else {
        $products = [];
    }
    echo json_encode($products);
    exit;
}

// API Endpoint to read categories for lookup
if (isset($_GET["action"]) && $_GET["action"] === "get_categories") {
    header("Content-Type: application/json");
    $userId = (int)($currentUser['id'] ?? 1);
    $stmt = db_prepare($conn, "SELECT id, category_name FROM category WHERE user_id = ? ORDER BY category_name ASC");
    if ($stmt) {
        db_stmt_bind_param($stmt, "i", $userId);
        db_stmt_execute($stmt);
        $result = db_stmt_get_result($stmt);
        $categories = [];
        if ($result) {
            while ($row = db_fetch_assoc($result)) {
                $categories[] = $row;
            }
        }
        db_stmt_close($stmt);
    } else {
        $categories = [];
    }
    echo json_encode($categories);
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <!-- Telegram Mini App WebApp SDK -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script>
        if (window.Telegram && window.Telegram.WebApp) {
            window.Telegram.WebApp.ready();
            window.Telegram.WebApp.expand();
        }
    </script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- Required for DevExtreme DataGrid PDF Export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
    <!-- Khmer font (sets window.KhmerOsSeimreapBase64 for the exporter) -->
    <script src="js/KhmerOSSiemreap.js"></script>
    <!-- html2canvas: captures the browser-shaped Khmer HTML table into the PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script>
    // Expose the Khmer base64 for the PDF webfont, and register it as a real
    // CSS webfont so the BROWSER shapes Khmer (jsPDF cannot shape complex scripts).
    window.__khmerB64 = window.KhmerOsSeimreapBase64 || "";
    (function() {
        if (!window.__khmerB64) return;
        var s = document.createElement("style");
        s.textContent = '@font-face{font-family:"KhmerOSWeb";src:url(data:font/ttf;base64,' +
            window.__khmerB64 + ') format("truetype");font-weight:normal;font-style:normal;font-display:swap;}';
        document.head.appendChild(s);
    })();
    </script>
    <!-- Required for DevExtreme PDF Export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <!-- Required for DevExtreme Excel Export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.4.0/exceljs.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>

    <!-- DevExtreme CSS & JS -->
    <link rel="stylesheet" href="https://cdn3.devexpress.com/jslib/23.1.6/css/dx.dark.css" />

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script src="https://cdn3.devexpress.com/jslib/23.1.6/js/dx.all.js"></script>
    <script src="js/KhmerOSSiemreap.js"></script>
    <script src="js/app.js?v=<?php echo date('Y-m-d-H-i-s', @filemtime(__DIR__ . '/js/app.js')); ?>"></script>
    <link rel="stylesheet" href="css/style.css?v=<?php echo date('Y-m-d-H-i-s', @filemtime(__DIR__ . '/css/style.css')); ?>">
    <style>
    /* Force vertical centering for all data grid cells, action buttons, text, and icons */
    .dx-datagrid .dx-row > td,
    .dx-datagrid-rowsview .dx-data-row > td,
    .dx-datagrid-rowsview .dx-row > td,
    .dx-datagrid .actions-cell {
        vertical-align: middle !important;
    }
    .dx-datagrid .actions-wrapper,
    .dx-datagrid .desktop-actions-wrapper,
    .dx-datagrid .actions {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        vertical-align: middle !important;
    }

    .img-lightbox-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        z-index: 999999;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .img-lightbox-backdrop {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(15, 23, 42, 0.85);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
    }
    .img-lightbox-content {
        position: relative;
        z-index: 2;
        background: #1e293b;
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 14px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
        width: 90%;
        max-width: 520px;
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 14px;
        animation: lightboxZoomIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .img-lightbox-close {
        position: absolute;
        top: 12px;
        right: 16px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        color: #94a3b8;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        cursor: pointer;
        line-height: 1;
        transition: all 0.15s ease;
    }
    .img-lightbox-close:hover {
        color: #f8fafc;
        background: rgba(255, 255, 255, 0.15);
    }
    .img-lightbox-header {
        display: flex;
        align-items: center;
        gap: 10px;
        padding-right: 36px;
    }
    .img-lightbox-header h4 {
        margin: 0;
        color: #f8fafc;
        font-size: 16px;
        font-weight: 600;
    }
    .img-lightbox-body {
        display: flex;
        justify-content: center;
        align-items: center;
        background: #0f172a;
        border-radius: 10px;
        border: 1px solid rgba(255, 255, 255, 0.08);
        padding: 12px;
        max-height: 380px;
        overflow: hidden;
    }
    .img-lightbox-body img {
        max-width: 100%;
        max-height: 350px;
        object-fit: contain;
        border-radius: 6px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4);
    }
    .img-lightbox-footer {
        display: flex;
        justify-content: flex-end;
    }
    .lightbox-btn-tab {
        color: #38bdf8;
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: opacity 0.15s;
    }
    .lightbox-btn-tab:hover {
        opacity: 0.8;
    }
    @keyframes lightboxZoomIn {
        from { transform: scale(0.94); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
    </style>
</head>

<body>
    <div class="page">
        <div class="category-card">
            <div class="header">
                <h1>
                    <i class="fa-solid fa-boxes-stacked" style="font-size: 22px;"></i>
                    Products
                </h1>
                <div class="user-profile-widget">
                    <div class="user-profile-badge">
                        <i class="fa-solid fa-user-circle"></i>
                        <span class="user-profile-name"><?php echo htmlspecialchars($currentUser['name'] ?? 'Admin'); ?></span>
                        <span class="user-role-badge"><?php echo htmlspecialchars($currentUser['role'] ?? 'admin'); ?></span>
                    </div>
                    <a href="logout.php" class="logout-icon-btn" data-tooltip="Sign Out" aria-label="Sign Out">
                        <i class="fa-solid fa-right-from-bracket"></i>
                    </a>
                </div>
            </div>
            <div class="options-container">
                <div class="search-and-export">
                    <div class="search-wrapper">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="searchInput" placeholder="Search...">
                    </div>
                    <div class="action-buttons-group">
                        <button type="button" class="add-btn telegram-file-btn" id="openPushExcelPdfBtn" data-tooltip="⚡ Push Excel, PDF & HTML Reports to Telegram" aria-label="Push Excel, PDF & HTML Reports to Telegram">
                            <i class="fa-solid fa-file-arrow-up"></i>
                        </button>
                        <button type="button" class="add-btn telegram-push-btn" id="openPushModalBtn" data-tooltip="Report Push Settings" aria-label="Report Push Settings">
                            <i class="fa-solid fa-gear"></i>
                        </button>
                        <button type="button" class="add-btn nav-link-btn" onclick="window.location.href='report_bi.php'" data-tooltip="BI Analytics Report" aria-label="BI Analytics Report">
                            <i class="fa-solid fa-chart-pie"></i>
                        </button>
                        <button type="button" class="add-btn nav-link-btn" onclick="window.location.href='index.php'" data-tooltip="Manage Categories" aria-label="Manage Categories">
                            <i class="fa-solid fa-list"></i>
                        </button>
                        <button type="button" class="add-btn" id="openAddModalBtn" data-tooltip="Add Product" aria-label="Add Product">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                        <div class="export-wrapper" id="masterExportWrapper" data-tooltip="Export">
                            <button class="export-btn" id="masterExportTrigger" type="button" aria-label="Export">
                                <i class="fa-solid fa-download"></i>
                            </button>
                            <div class="export-menu" id="masterExportMenu">
                                <!-- Excel -->
                                <div class="orientation-section" style="border-top: none; margin-top: 0; padding-top: 0;">
                                    <div class="orientation-label">Excel</div>
                                    <button class="export-item" data-format="excel" data-action="all">
                                        <i class="fa-solid fa-file-excel" style="width:16px; text-align:center; color:#107c41;"></i>
                                        Export all pages
                                    </button>
                                    <button class="export-item" data-format="excel" data-action="current">
                                        <i class="fa-solid fa-file-excel" style="width:16px; text-align:center; color:#107c41;"></i>
                                        Export current page
                                    </button>
                                </div>
                                
                                <!-- CSV -->
                                <div class="orientation-section">
                                    <div class="orientation-label">CSV</div>
                                    <button class="export-item" data-format="csv" data-action="all">
                                        <i class="fa-solid fa-file-csv" style="width:16px; text-align:center; color:#217346;"></i>
                                        Export all pages
                                    </button>
                                    <button class="export-item" data-format="csv" data-action="current">
                                        <i class="fa-solid fa-file-csv" style="width:16px; text-align:center; color:#217346;"></i>
                                        Export current page
                                    </button>
                                </div>

                                <!-- PDF -->
                                <div class="orientation-section">
                                    <div class="orientation-label">PDF</div>
                                    <button class="export-item" data-format="pdf" data-action="all">
                                        <i class="fa-solid fa-file-pdf" style="width:16px; text-align:center; color:#e3242b;"></i>
                                        Export all pages
                                    </button>
                                    <button class="export-item" data-format="pdf" data-action="current">
                                        <i class="fa-solid fa-file-pdf" style="width:16px; text-align:center; color:#e3242b;"></i>
                                        Export current page
                                    </button>
                                </div>

                                <!-- Image / Picture (JPG) -->
                                <div class="orientation-section">
                                    <div class="orientation-label">Image / Picture (JPG)</div>
                                    <button class="export-item" id="btnDownloadImageJpg">
                                        <i class="fa-solid fa-file-image" style="width:16px; text-align:center; color:#f59e0b;"></i>
                                        Download Page Picture (JPG)
                                    </button>
                                </div>
                                
                                <!-- PDF Settings -->
                                <div class="orientation-section">
                                    <div class="orientation-label">PDF Orientation</div>
                                    <div class="orientation-toggle">
                                        <button type="button" data-orientation="portrait" class="active">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2">
                                                <rect x="6" y="2" width="12" height="20" rx="2" />
                                            </svg>
                                            Portrait
                                        </button>
                                        <button type="button" data-orientation="landscape">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2">
                                                <rect x="2" y="6" width="20" height="12" rx="2" />
                                            </svg>
                                            Landscape
                                        </button>
                                    </div>
                                </div>
                                <div class="orientation-section">
                                    <div class="orientation-label">PDF Paper</div>
                                    <div class="orientation-toggle">
                                        <button type="button" data-paper="a4" class="active">A4</button>
                                        <button type="button" data-paper="a3">A3</button>
                                        <button type="button" data-paper="a2">A2</button>
                                        <button type="button" data-paper="a1">A1</button>
                                        <button type="button" data-paper="letter">Letter</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="customFieldChooserBtn"></div>
                    </div>
                </div>
            </div>


            <!-- =========================
             TABLE (DevExtreme DataGrid Container)
        ========================== -->

            <div class="table-wrapper">

                <div id="gridContainer"></div>

            </div>

        </div>

    </div>

    <script>
    function standardizeImageFile(file, callback) {
        if (!file || !file.type || !file.type.startsWith("image/")) {
            callback(file, null);
            return;
        }
        var reader = new FileReader();
        reader.onload = function(e) {
            var dataUrl = e.target.result;
            var img = new Image();
            if (typeof dataUrl === "string" && (dataUrl.startsWith("http://") || dataUrl.startsWith("https://"))) {
                img.crossOrigin = "Anonymous";
            }
            img.onload = function() {
                try {
                    var canvas = document.createElement("canvas");
                    var targetSize = 300; // Fixed 300x300 px compact box size
                    canvas.width = targetSize;
                    canvas.height = targetSize;
                    var ctx = canvas.getContext("2d");

                    // Fill clean white studio canvas background
                    ctx.fillStyle = "#ffffff";
                    ctx.fillRect(0, 0, targetSize, targetSize);

                    // Calculate contain scale with 16px padding
                    var padding = 16;
                    var maxW = targetSize - (padding * 2);
                    var maxH = targetSize - (padding * 2);

                    var scale = Math.min(maxW / img.width, maxH / img.height);
                    var drawW = img.width * scale;
                    var drawH = img.height * scale;
                    var drawX = padding + (maxW - drawW) / 2;
                    var drawY = padding + (maxH - drawH) / 2;

                    // Soft shadow for depth
                    ctx.save();
                    ctx.shadowColor = "rgba(0, 0, 0, 0.12)";
                    ctx.shadowBlur = 8;
                    ctx.shadowOffsetX = 0;
                    ctx.shadowOffsetY = 3;
                    ctx.drawImage(img, drawX, drawY, drawW, drawH);
                    ctx.restore();

                    // Crisp border frame around canvas box
                    ctx.strokeStyle = "#cbd5e1"; // Slate 300
                    ctx.lineWidth = 4;
                    ctx.strokeRect(2, 2, targetSize - 4, targetSize - 4);

                    var canvasDataUrl = canvas.toDataURL("image/jpeg", 0.90);

                    canvas.toBlob(function(blob) {
                        if (blob) {
                            var standardizedFile = new File([blob], "product_" + Date.now() + ".jpg", { type: "image/jpeg" });
                            callback(standardizedFile, canvasDataUrl);
                        } else {
                            callback(file, canvasDataUrl || dataUrl);
                        }
                    }, "image/jpeg", 0.90);
                } catch (err) {
                    callback(file, dataUrl);
                }
            };
            img.onerror = function() {
                callback(file, dataUrl);
            };
            img.src = dataUrl;
        };
        reader.onerror = function() {
            callback(file, null);
        };
        reader.readAsDataURL(file);
    }

    function timeAgo(date) {
        const seconds = Math.floor((new Date() - date) / 1000);
        let interval = Math.floor(seconds / 31536000);
        if (interval >= 1) return interval + "y ago";
        interval = Math.floor(seconds / 2592000);
        if (interval >= 1) return interval + "mo ago";
        interval = Math.floor(seconds / 86400);
        if (interval >= 1) return interval + "d ago";
        interval = Math.floor(seconds / 3600);
        if (interval >= 1) return interval + "h ago";
        interval = Math.floor(seconds / 60);
        if (interval >= 1) return interval + "m ago";
        return "just now";
    }

    function showImageLightbox(imgUrl, productName, productCode) {
        var $modal = $("#imageLightboxModal");
        if (!$modal.length) {
            $modal = $(
                '<div id="imageLightboxModal" class="img-lightbox-modal" style="display: none;">' +
                    '<div class="img-lightbox-backdrop"></div>' +
                    '<div class="img-lightbox-content">' +
                        '<button type="button" class="img-lightbox-close" id="closeLightboxBtn"><i class="fa-solid fa-xmark"></i></button>' +
                        '<div class="img-lightbox-header">' +
                            '<h4 id="lightboxTitle">Product Image</h4>' +
                            '<span id="lightboxBadge" class="category-badge badge-purple" style="font-size: 11px; padding: 2px 8px;"></span>' +
                        '</div>' +
                        '<div class="img-lightbox-body">' +
                            '<img id="lightboxImg" src="" alt="Enlarged Product Image">' +
                        '</div>' +
                        '<div class="img-lightbox-footer">' +
                            '<a id="lightboxOpenTab" href="#" target="_blank" class="lightbox-btn-tab">' +
                                '<i class="fa-solid fa-arrow-up-right-from-square"></i> Open Full Image' +
                            '</a>' +
                        '</div>' +
                    '</div>' +
                '</div>'
            ).appendTo("body");

            $modal.on("click", "#closeLightboxBtn, .img-lightbox-backdrop", function() {
                $modal.fadeOut(150);
            });

            $(document).on("keydown.lightbox", function(e) {
                if (e.key === "Escape" && $modal.is(":visible")) {
                    $modal.fadeOut(150);
                }
            });
        }

        $modal.find("#lightboxTitle").text(productName || "Product Image");
        if (productCode) {
            $modal.find("#lightboxBadge").text(productCode).show();
        } else {
            $modal.find("#lightboxBadge").hide();
        }
        $modal.find("#lightboxImg").attr("src", imgUrl);
        $modal.find("#lightboxOpenTab").attr("href", imgUrl);

        $modal.fadeIn(180);
    }

    $(function() {
        // Modern column show/hide Field Chooser with icons and pills
        function openColumnChooser(e) {
            if (e && e.stopPropagation) {
                e.stopPropagation();
            }
            var $menu = $("#colChooserMenu");
            if ($menu.length && $menu.is(":visible")) {
                $menu.removeClass("open").hide();
                return;
            }
            var grid = $("#gridContainer").dxDataGrid("instance");
            if (!grid) {
                alert("Grid not ready yet. Try again in a moment.");
                return;
            }
            var cols = (grid.option("columns") || []).filter(function(c) {
                return c && (c.name || c.dataField) &&
                    c.type !== "buttons" && c.dataField !== "action" &&
                    c.caption !== "Action";
            });
            if (!$menu.length) {
                $menu = $('<div class="col-chooser modern-chooser" id="colChooserMenu"></div>')
                    .appendTo("body");
            }
            $menu.html(
                '<div class="fc-modern">' +
                '  <div class="fc-header" style="display:flex; align-items:center; justify-content:space-between;">' +
                '    <div style="display:flex; align-items:center; gap:12px;">' +
                '      <div class="fc-icon-wrapper"><i class="fa-solid fa-layer-group"></i></div>' +
                '      <div class="fc-title-area"><h4>Field Chooser</h4></div>' +
                '    </div>' +
                '    <button type="button" class="close-btn" id="closeColChooserBtn" style="background:transparent; border:none; color:#71717a; font-size:18px; cursor:pointer;">&times;</button>' +
                '  </div>' +
                '  <div class="fc-search-wrap">' +
                '    <i class="fa-solid fa-search"></i>' +
                '    <input type="text" id="colChooserSearch" placeholder="Search">' +
                '  </div>' +
                '  <div id="colChooserList" class="fc-pill-container"></div>' +
                '</div>'
            );
            var $list = $menu.find("#colChooserList");

            function render() {
                var q = ($menu.find("#colChooserSearch").val() || "").toLowerCase().trim();
                var html = "";
                cols.forEach(function(c) {
                    var id = c.name || c.dataField;
                    var label = c.caption || id;
                    if (q && label.toLowerCase().indexOf(q) === -1) return;
                    var vis = grid.columnOption(id, "visible");
                    if (vis === undefined) vis = true;

                    var icon = "fa-hashtag";
                    var lowerId = id.toLowerCase();
                    var lowerLabel = label.toLowerCase();

                    if (lowerId.indexOf("code") !== -1) {
                        icon = "fa-barcode";
                    } else if (lowerId.indexOf("name") !== -1) {
                        icon = "fa-layer-group";
                    } else if (lowerId.indexOf("created") !== -1 || lowerLabel.indexOf("created") !== -
                        1) {
                        icon = (lowerId.indexOf("time") !== -1 || lowerLabel.indexOf("time") !== -1) ?
                            "fa-clock" : "fa-calendar-plus";
                    } else if (lowerId.indexOf("last") !== -1 || lowerId.indexOf("update") !== -1 ||
                        lowerLabel.indexOf("last") !== -1) {
                        icon = (lowerId.indexOf("time") !== -1 || lowerLabel.indexOf("time") !== -1) ?
                            "fa-history" : "fa-calendar-check";
                    } else if (lowerId.indexOf("date") !== -1 || lowerLabel.indexOf("date") !== -1) {
                        icon = "fa-calendar-day";
                    } else if (lowerId.indexOf("time") !== -1 || lowerLabel.indexOf("time") !== -1) {
                        icon = "fa-clock-rotate-left";
                    }

                    var stateClass = vis ? "active" : "";

                    html += '<div class="fc-pill ' + stateClass + '" data-id="' + id + '">' +
                        '<i class="fa-solid ' + icon + '"></i>' +
                        '<span>' + label + '</span>' +
                        '</div>';
                });
                if (!html) html = '<div class="fc-empty">No columns match.</div>';
                $list.html(html);
            }

            render();

            $menu.addClass("open").css({
                position: "fixed",
                top: "50%",
                left: "50%",
                transform: "translate(-50%, -50%)",
                "max-height": "85vh",
                "overflow-y": "auto",
                display: "block",
                width: "min(800px, calc(100vw - 24px))",
                padding: "20px"
            });

            $menu.off("click").on("click", function(e) {
                e.stopPropagation();
            });
            $menu.off("click", "#closeColChooserBtn").on("click", "#closeColChooserBtn", function() {
                $menu.removeClass("open").hide();
            });
            $menu.off("input", "#colChooserSearch").on("input", "#colChooserSearch", function() {
                render();
            });
            $menu.off("click", ".fc-pill").on("click", ".fc-pill", function() {
                var id = $(this).data("id");
                var isVis = $(this).hasClass("active");
                grid.columnOption(id, "visible", !isVis);
                $(this).toggleClass("active", !isVis);
            });

            setTimeout(function() {
                $(document).off("click.colChooser").on("click.colChooser", function(e) {
                    if (!$(e.target).closest("#colChooserMenu, #customFieldChooserBtn").length) {
                        $menu.removeClass("open").hide();
                        $(document).off("click.colChooser");
                    }
                });
                setTimeout(function() {
                    $menu.find("#colChooserSearch").trigger("focus");
                }, 50);
            }, 50);
        }

        // Clear any STALE saved grid state (old "all rows" view) from a previous
        // stateStoring session, so it can never re-apply after the data loads.
        function fixFormLabelsAccessibility(e) {
            var $popup = e && e.component ? $(e.component.content()) : $(document);
            $popup.find(".dx-field-item").each(function(idx) {
                var $item = $(this);
                var $label = $item.find("label.dx-field-item-label, label.dx-field-item-label-text, label");
                if ($label.length) {
                    var $input = $item.find("input, select, textarea").first();
                    if ($input.length) {
                        var inputId = $input.attr("id");
                        if (!inputId) {
                            inputId = "editor_field_" + idx + "_" + Date.now();
                            $input.attr("id", inputId);
                        }
                        $label.attr("for", inputId);
                    } else {
                        $label.removeAttr("for");
                    }
                }
            });
        }

        var isMobile = $(window).width() <= 768;
        $("#gridContainer").dxDataGrid({
            allowColumnReordering: true,
            allowColumnResizing: true,
            columnAutoWidth: true,
            columnResizingMode: localStorage.getItem("categoryGridResizeMode") || "widget",
            columnFixing: {
                enabled: true
            },
            // headerFilter disabled so no funnel icons show on column headers
            grouping: {
                contextMenuEnabled: true,
                autoExpandAll: false
            },
            // Fixed height so the GRID scrolls internally instead of the whole page.
            // Height is computed in JS (fitGridHeight) so the pager is always
            // inside the visible grid regardless of header/toolbar height.
            height: 400,

            // Plain internal row scrolling (no virtualization). The fixed height
            // makes ONLY the rows scroll inside the grid; the page itself stays put.
            renderAsync: true,
            scrolling: {
                mode: "standard",
                renderAsync: true
            },

            dataSource: new DevExpress.data.CustomStore({
                key: "id",
                loadMode: "raw",
                load: function() {
                    return new Promise(function(resolve, reject) {
                        $.ajax({
                                url: "products.php?action=read",
                                method: "GET",
                                cache: false,
                                dataType: "json"
                            })
                            .done(function(data) {
                                resolve(data);
                            })
                            .fail(function() {
                                reject(new Error("Failed to load products."));
                            });
                    });
                },
                insert: function(values) {
                    return new Promise(function(resolve, reject) {
                        var formData = new FormData();
                        $.each(values, function(k, v) {
                            if (k !== 'image' && k !== 'product_image_file' && v !== null && v !== undefined) {
                                formData.append(k, v);
                            }
                        });
                        if (window._currentSelectedImageFile) {
                            formData.append('product_image', window._currentSelectedImageFile);
                        }
                        $.ajax({
                            url: "create_product.php",
                            type: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                            dataType: "json"
                        }).done(function(data) {
                            window._currentSelectedImageFile = null;
                            window._currentImagePreviewDataUrl = null;
                            var grid = $("#gridContainer").dxDataGrid("instance");
                            if (grid) grid.refresh();
                            resolve(data);
                        }).fail(function(xhr) {
                            window._currentSelectedImageFile = null;
                            window._currentImagePreviewDataUrl = null;
                            var msg = "Failed to add product.";
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                msg = xhr.responseJSON.message;
                            }
                            reject(new Error(msg));
                        });
                    });
                },
                update: function(key, values) {
                    return new Promise(function(resolve, reject) {
                        var formData = new FormData();
                        $.each(values, function(k, v) {
                            if (k !== 'image' && k !== 'product_image_file' && v !== null && v !== undefined) {
                                formData.append(k, v);
                            }
                        });
                        if (window._currentSelectedImageFile) {
                            formData.append('product_image', window._currentSelectedImageFile);
                        }
                        $.ajax({
                            url: "edit_product.php?id=" + key,
                            type: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                            dataType: "json"
                        }).done(function(data) {
                            window._currentSelectedImageFile = null;
                            window._currentImagePreviewDataUrl = null;
                            var grid = $("#gridContainer").dxDataGrid("instance");
                            if (grid) grid.refresh();
                            resolve(data);
                        }).fail(function(xhr) {
                            window._currentSelectedImageFile = null;
                            window._currentImagePreviewDataUrl = null;
                            var msg = "Failed to update product.";
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                msg = xhr.responseJSON.message;
                            }
                            reject(new Error(msg));
                        });
                    });
                },
                remove: function(key) {
                    return new Promise(function(resolve, reject) {
                        $.post("delete_product.php?id=" + key)
                            .done(function(data) {
                                resolve(data);
                            })
                            .fail(function(xhr) {
                                var msg = "Failed to delete product.";
                                if (xhr.responseJSON && xhr.responseJSON
                                    .message) {
                                    msg = xhr.responseJSON.message;
                                }
                                reject(new Error(msg));
                            });
                    });
                }
            }),
            columns: [{
                    name: "product_code",
                    dataField: "product_code",
                    caption: "Product Code",
                    minWidth: isMobile ? 80 : 100,
                    width: isMobile ? 105 : 150,
                    maxWidth: isMobile ? 130 : 300,
                    validationRules: [{
                        type: "required",
                        message: "Product Code is required"
                    }],
                    cellTemplate: function(container, options) {
                        if (!options.value) return;
                        const colors = ['badge-purple', 'badge-green', 'badge-orange', 'badge-pink', 'badge-blue'];
                        let hash = 0;
                        for (let i = 0; i < options.value.length; i++) {
                            hash = options.value.charCodeAt(i) + ((hash << 5) - hash);
                        }
                        const colorClass = colors[Math.abs(hash) % colors.length];
                        $("<span>")
                            .addClass("category-badge " + colorClass)
                            .text(options.value)
                            .appendTo(container);
                    }
                },
                {
                    name: "product_name",
                    dataField: "product_name",
                    caption: "Product Name",
                    minWidth: isMobile ? 90 : 150,
                    width: isMobile ? 125 : 200,
                    maxWidth: isMobile ? 160 : 300,
                    validationRules: [{
                        type: "required",
                        message: "Product Name is required"
                    }]
                },
                {
                    name: "category_id",
                    dataField: "category_id",
                    caption: "Category",
                    minWidth: isMobile ? 85 : 150,
                    width: isMobile ? 110 : 200,
                    maxWidth: isMobile ? 150 : 300,
                    lookup: {
                        dataSource: new DevExpress.data.CustomStore({
                            key: "id",
                            loadMode: "raw",
                            load: function() {
                                return $.getJSON("products.php?action=get_categories");
                            }
                        }),
                        valueExpr: "id",
                        displayExpr: "category_name"
                    },
                    validationRules: [{
                        type: "required",
                        message: "Category is required"
                    }]
                },
                {
                    name: "price",
                    dataField: "price",
                    caption: "Price",
                    dataType: "number",
                    format: "$ #,##0.00",
                    minWidth: isMobile ? 70 : 100,
                    width: isMobile ? 85 : 120,
                    maxWidth: isMobile ? 110 : 200,
                    validationRules: [{
                        type: "required",
                        message: "Price is required"
                    }]
                },
                {
                    name: "quantity",
                    dataField: "quantity",
                    caption: "Quantity",
                    dataType: "number",
                    minWidth: isMobile ? 65 : 100,
                    width: isMobile ? 75 : 120,
                    maxWidth: isMobile ? 100 : 200,
                    validationRules: [{
                        type: "required",
                        message: "Quantity is required"
                    }, {
                        type: "range",
                        min: 0,
                        message: "Quantity must be 0 or greater"
                    }]
                },
                {
                    name: "image",
                    dataField: "image",
                    caption: "Image",
                    minWidth: isMobile ? 55 : 70,
                    width: isMobile ? 65 : 80,
                    allowSorting: false,
                    allowFiltering: false,
                    allowHeaderFiltering: false,
                    cellTemplate: function(container, options) {
                        if (options.data && options.data.image) {
                            $("<img>")
                                .attr("src", options.data.image)
                                .attr("alt", options.data.product_name || "Product Image")
                                .attr("loading", "lazy")
                                .attr("decoding", "async")
                                .css({
                                    width: "40px",
                                    height: "40px",
                                    objectFit: "cover",
                                    borderRadius: "6px",
                                    border: "1px solid rgba(255, 255, 255, 0.15)",
                                    cursor: "pointer",
                                    verticalAlign: "middle",
                                    transition: "transform 0.15s ease"
                                })
                                .hover(
                                    function() { $(this).css({ transform: "scale(1.15)" }); },
                                    function() { $(this).css({ transform: "scale(1)" }); }
                                )
                                .on("click", function(e) {
                                    e.stopPropagation();
                                    showImageLightbox(options.data.image, options.data.product_name, options.data.product_code);
                                })
                                .appendTo(container);
                        } else {
                            $("<span>")
                                .text("No image")
                                .css({ color: "#94a3b8", fontSize: "11px", fontStyle: "italic" })
                                .appendTo(container);
                        }
                    }
                },
                {
                    name: "created_date",
                    dataField: "created_at",
                    caption: "Created Date",
                    dataType: "date",
                    format: "dd/MM/yyyy",
                    minWidth: isMobile ? 80 : 100,
                    width: isMobile ? 95 : 260,
                    maxWidth: isMobile ? 130 : 300,
                    allowEditing: false
                },
                {
                    name: "created_time",
                    dataField: "created_at",
                    caption: "Created Time",
                    dataType: "string",
                    calculateCellValue: function(rowData) {
                        if (!rowData.created_at) return "";
                        const d = new Date(rowData.created_at);
                        return d.toLocaleTimeString('en-GB');
                    },
                    calculateSortValue: function(rowData) {
                        if (!rowData.created_at) return 0;
                        const d = new Date(rowData.created_at);
                        return d.getHours() * 3600 + d.getMinutes() * 60 + d.getSeconds();
                    },
                    minWidth: isMobile ? 75 : 100,
                    width: isMobile ? 85 : 260,
                    maxWidth: isMobile ? 120 : 300,
                    allowEditing: false
                },
                {
                    name: "date_created",
                    dataField: "created_at",
                    caption: "Date Created",
                    dataType: "datetime",
                    format: "dd/MM/yyyy HH:mm:ss",
                    minWidth: isMobile ? 90 : 100,
                    width: isMobile ? 120 : 260,
                    maxWidth: isMobile ? 160 : 300,
                    allowEditing: false
                },
                {
                    name: "formatted_date",
                    dataField: "created_at",
                    caption: "Formatted Date",
                    dataType: "date",
                    format: "dd-MMMM-yyyy",
                    minWidth: isMobile ? 80 : 100,
                    width: isMobile ? 100 : 100,
                    maxWidth: isMobile ? 140 : 250,
                    allowEditing: false
                },
                {
                    name: "formatted_time",
                    dataField: "created_at",
                    caption: "Formatted Time",
                    dataType: "datetime",
                    format: "hh:mm:ss a",
                    minWidth: isMobile ? 75 : 100,
                    width: isMobile ? 85 : 260,
                    maxWidth: isMobile ? 120 : 300,
                    allowEditing: false
                },
                {
                    name: "formatted_datetime",
                    dataField: "created_at",
                    caption: "Formatted Date & Time",
                    dataType: "datetime",
                    format: "dd-MMMM-yyyy hh:mm:ss a",
                    minWidth: isMobile ? 100 : 100,
                    width: isMobile ? 130 : 260,
                    maxWidth: isMobile ? 170 : 300,
                    allowEditing: false
                },
                {
                    name: "last_updated",
                    dataField: "lastupdate",
                    caption: "Last Updated",
                    dataType: "datetime",
                    minWidth: isMobile ? 95 : 100,
                    width: isMobile ? 120 : 260,
                    maxWidth: isMobile ? 160 : 300,
                    allowEditing: false,
                    cellTemplate: function(container, options) {
                        if (!options.value) {
                            $("<span>").text("-").appendTo(container);
                            return;
                        }
                        const date = new Date(options.value);
                        const formatted = formatDateTime(date);
                        const ago = timeAgo(date);
                        $("<span>")
                            .text(formatted + " (" + ago + ")")
                            .appendTo(container);
                    }
                },
                {
                    name: "last_date",
                    dataField: "lastupdate",
                    caption: "Last Date",
                    dataType: "date",
                    format: "dd/MM/yyyy",
                    minWidth: isMobile ? 80 : 100,
                    width: isMobile ? 95 : 260,
                    maxWidth: isMobile ? 130 : 300,
                    allowEditing: false
                },
                {
                    name: "last_time",
                    dataField: "lastupdate",
                    caption: "Last Time",
                    dataType: "string",
                    calculateCellValue: function(rowData) {
                        if (!rowData.lastupdate) return "";
                        const d = new Date(rowData.lastupdate);
                        return d.toLocaleTimeString('en-GB');
                    },
                    calculateSortValue: function(rowData) {
                        if (!rowData.lastupdate) return 0;
                        const d = new Date(rowData.lastupdate);
                        return d.getHours() * 3600 + d.getMinutes() * 60 + d.getSeconds();
                    },
                    minWidth: isMobile ? 75 : 100,
                    width: isMobile ? 85 : 260,
                    maxWidth: isMobile ? 120 : 300,
                    allowEditing: false
                },
                {
                    name: "time_ago",
                    dataField: "lastupdate",
                    caption: "Time Ago",
                    dataType: "datetime",
                    minWidth: isMobile ? 70 : 100,
                    width: isMobile ? 80 : 260,
                    maxWidth: isMobile ? 120 : 300,
                    allowEditing: false,
                    cellTemplate: function(container, options) {
                        if (!options.value) {
                            $("<span>").text("-").appendTo(container);
                            return;
                        }
                        const date = new Date(options.value);
                        $("<span>")
                            .text(timeAgo(date))
                            .appendTo(container);
                    }
                },
                {
                    type: "buttons",
                    caption: "Action",
                    width: isMobile ? 52 : 140,
                    minWidth: isMobile ? 48 : 130,
                    allowExporting: false,
                    allowColumnResizing: true,
                    allowFiltering: false,
                    allowSorting: false,
                    allowFixing: true,
                    fixed: true,
                    fixedPosition: "right",
                    allowReordering: false,
                    cellTemplate: function(container, options) {
                        container.addClass("actions-cell");

                        var telegramSvg = '<i class="fa-solid fa-paper-plane" style="font-size: 14px; color: #38bdf8;"></i>';
                        var $telegramBtn = $("<a>")
                            .addClass("dx-link dx-link-telegram")
                            .attr("title", "Push to Telegram")
                            .append(telegramSvg)
                            .on("click", function(e) {
                                e.preventDefault();
                                if (options.data && options.data.id) {
                                    var $btn = $(this);
                                    if ($btn.data("loading")) return;
                                    $btn.data("loading", true);
                                    $btn.addClass("is-loading").html('<i class="fa-solid fa-spinner fa-spin" style="font-size: 14px; color: #38bdf8;"></i>');

                                    function resetBtn() {
                                        $btn.data("loading", false);
                                        $btn.removeClass("is-loading").html(telegramSvg);
                                    }

                                    window.checkTelegramConnectionAndExecute(function() {
                                        $.ajax({
                                            url: "manual_push.php",
                                            type: "POST",
                                            dataType: "json",
                                            data: { action: "push_single_product", id: options.data.id },
                                            success: function(res) {
                                                if (res && (res.ok === true || res.ok === "true")) {
                                                    DevExpress.ui.notify("✅ Product notification pushed to Telegram successfully!", "success", 3500);
                                                } else {
                                                    var errMsg = (res && (res.description || res.message)) ? (res.description || res.message) : "Failed to push notification.";
                                                    if (errMsg.toLowerCase().includes("not connected")) {
                                                        window.isTelegramConnected = false;
                                                        if (typeof fetchTelegramStatus === "function") fetchTelegramStatus();
                                                        $("#pushModal").css("display", "flex").hide().fadeIn(200);
                                                        DevExpress.ui.notify("⚠️ Telegram is not connected. Please click 'Connect Telegram' to link your account.", "warning", 4000);
                                                    } else {
                                                        DevExpress.ui.notify("❌ Telegram Push Unsuccessful: " + errMsg, "error", 5000);
                                                    }
                                                }
                                            },
                                            error: function(xhr) {
                                                var errMsg = (xhr && xhr.responseJSON && (xhr.responseJSON.description || xhr.responseJSON.message)) 
                                                             ? (xhr.responseJSON.description || xhr.responseJSON.message) 
                                                             : "Could not push notification to Telegram.";
                                                if (errMsg.toLowerCase().includes("not connected")) {
                                                    window.isTelegramConnected = false;
                                                    if (typeof fetchTelegramStatus === "function") fetchTelegramStatus();
                                                    $("#pushModal").css("display", "flex").hide().fadeIn(200);
                                                    DevExpress.ui.notify("⚠️ Telegram is not connected. Please click 'Connect Telegram' to link your account.", "warning", 4000);
                                                } else {
                                                    DevExpress.ui.notify("❌ Telegram Push Failed: " + errMsg, "error", 5000);
                                                }
                                            },
                                            complete: resetBtn
                                        });
                                    }, resetBtn);
                                }
                            });

                        var editSvg =
                            '<svg fill="none" height="18" viewBox="0 0 24 24" width="18" xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle;"><g fill-rule="evenodd"><path d="m20.6 2c-.3639 0-.7001.11429-.9929.40712l-9.3188 9.31888-.81771 2.8034 2.80351-.8177 9.3188-9.3188c.2146-.2146.4071-.66113.4071-.99291 0-.74771-.6523-1.39999-1.4-1.39999zm-2.4071-1.007095c.7072-.707166 1.571-.992905 2.4071-.992905 1.8523 0 3.4 1.54771 3.4 3.39999 0 .86822-.4075 1.82172-.9929 2.40712l-9.5 9.49999c-.1188.1189-.2657.2058-.4271.2529l-4.8 1.4c-.35053.1022-.72892.0053-.98711-.2529s-.35513-.6366-.25289-.9871l1.39999-4.8c.04707-.1613.13404-.3082.2529-.4271z" fill="currentColor" /><path d="m0 7c0-2.75228 2.24772-5 5-5h6c.5523 0 1 .44772 1 1s-.4477 1-1 1h-6c-1.64772 0-3 1.35228-3 3v12c0 1.6477 1.35228 3 3 3h12c1.6477 0 3-1.3523 3-3v-6c0-.5523.4477-1 1-1s1 .4477 1 1v6c0 2.7523-2.2477 5-5 5h-12c-2.75228 0-5-2.2477-5-5z" fill="currentColor" /></g></svg>';
                        var $editBtn = $("<a>")
                            .addClass("dx-link dx-link-edit")
                            .attr("title", "Edit")
                            .append(editSvg)
                            .on("click", function(e) {
                                options.component.editRow(options.rowIndex);
                                e.preventDefault();
                            });

                        var deleteSvg =
                            '<svg id="Capa_1" enable-background="new 0 0 440 440" height="18" viewBox="0 0 440 440" width="18" xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle;"><g><g id="delete_1_"><path d="m412 88h-384c-6.627 0-12-5.373-12-12s5.373-12 12-12h384c6.627 0 12 5.373 12 12s-5.373 12-12 12z" fill="currentColor" /><path d="m316 88h-192c-3.693-.012-7.175-1.723-9.44-4.64-2.234-2.91-3.055-6.663-2.24-10.24l9.92-39.84c4.969-19.547 22.551-33.244 42.72-33.28h110.08c20.169.036 37.751 13.733 42.72 33.28l9.92 39.84c.815 3.577-.006 7.33-2.24 10.24-2.265 2.917-5.747 4.628-9.44 4.64zm-176-24h160l-5.6-24.8c-1.882-9.226-9.945-15.889-19.36-16h-110.08c-9.415.111-17.478 6.774-19.36 16z" fill="currentColor" /><path d="m286.4 440h-132.8c-46.24 0-84.8-29.12-89.76-67.68l-16-231.52c-.442-6.627 4.573-12.358 11.2-12.8s12.358 4.573 12.8 11.2l16 230.72c3.36 25.92 32 46.08 65.92 46.08h132.8c34.24 0 62.56-20.16 65.92-46.88l16-229.92c.442-6.627 6.173-11.642 12.8-11.2s11.642 6.173 11.2 12.8l-16 230.72c-5.28 39.36-44.48 68.48-90.08 68.48z" fill="currentColor" /></g></g></svg>';
                        var $deleteBtn = $("<a>")
                            .addClass("dx-link dx-link-delete")
                            .attr("title", "Delete")
                            .append(deleteSvg)
                            .on("click", function(e) {
                                options.component.deleteRow(options.rowIndex);
                                e.preventDefault();
                            });

                        var $desktopWrapper = $("<div>")
                            .addClass("actions-wrapper desktop-actions-wrapper")
                            .append($telegramBtn)
                            .append($editBtn)
                            .append($deleteBtn);

                        var donerSvg = '<svg class="doner-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">' +
                            '<line class="doner-line doner-line-1" x1="3" y1="6" x2="21" y2="6"></line>' +
                            '<line class="doner-line doner-line-2" x1="6" y1="12" x2="18" y2="12"></line>' +
                            '<line class="doner-line doner-line-3" x1="9" y1="18" x2="15" y2="18"></line>' +
                            '</svg>';

                        var $kebabBtn = $("<button>")
                            .attr("type", "button")
                            .addClass("kebab-trigger-btn doner-trigger-btn")
                            .attr("title", "More Actions")
                            .html(donerSvg);

                        var $kebabMenu = $("<div>")
                            .addClass("kebab-dropdown-menu")
                            .html(
                                '<button type="button" class="kebab-menu-item kebab-push-item"><i class="fa-solid fa-paper-plane" style="color: #38bdf8;"></i> <span>Report Push</span></button>' +
                                '<button type="button" class="kebab-menu-item kebab-edit-item"><i class="fa-solid fa-pen-to-square" style="color: #34d399;"></i> <span>Edit</span></button>' +
                                '<button type="button" class="kebab-menu-item kebab-delete-item"><i class="fa-solid fa-trash-can" style="color: #f87171;"></i> <span>Delete</span></button>'
                            );

                        $kebabBtn.on("click", function(e) {
                            e.stopPropagation();
                            var wasShown = $kebabMenu.hasClass("show");
                            $(".kebab-dropdown-menu").removeClass("show");
                            $(".kebab-trigger-btn").removeClass("open");
                            if (!wasShown) {
                                $kebabMenu.addClass("show");
                                $kebabBtn.addClass("open");
                            }
                        });

                        $kebabMenu.find(".kebab-push-item").on("click", function(e) {
                            e.stopPropagation();
                            $kebabMenu.removeClass("show");
                            $kebabBtn.removeClass("open");
                            $telegramBtn.trigger("click");
                        });

                        $kebabMenu.find(".kebab-edit-item").on("click", function(e) {
                            e.stopPropagation();
                            $kebabMenu.removeClass("show");
                            $kebabBtn.removeClass("open");
                            options.component.editRow(options.rowIndex);
                        });

                        $kebabMenu.find(".kebab-delete-item").on("click", function(e) {
                            e.stopPropagation();
                            $kebabMenu.removeClass("show");
                            $kebabBtn.removeClass("open");
                            options.component.deleteRow(options.rowIndex);
                        });

                        var $mobileKebabWrapper = $("<div>")
                            .addClass("mobile-kebab-wrapper")
                            .append($kebabBtn)
                            .append($kebabMenu);

                        container.append($desktopWrapper).append($mobileKebabWrapper);
                    }
                }
            ],
            editing: {
                mode: "popup",
                repaintChangesOnly: true,
                allowUpdating: true,
                allowDeleting: true,
                useIcons: true,
                popup: {
                    title: "Product Details",
                    showTitle: true,
                    width: function() {
                        return Math.min(500, $(window).width() - 24);
                    },
                    height: "auto",
                    wrapperAttr: {
                        class: "dark-popup"
                    },
                    onShown: function(e) {
                        fixFormLabelsAccessibility(e);
                    },
                    onShowing: function(e) {
                        window._currentSelectedImageFile = null;
                        window._currentImagePreviewDataUrl = null;
                    },
                    onHiding: function(e) {
                        window._currentSelectedImageFile = null;
                        window._currentImagePreviewDataUrl = null;
                    }
                },
                form: {
                    colCount: 1,
                    items: [{
                            dataField: "product_code",
                            editorType: "dxTextBox",
                            editorOptions: {
                                placeholder: "Enter product code",
                                inputAttr: { id: "product_code_input" }
                            }
                        },
                        {
                            dataField: "product_name",
                            editorType: "dxTextBox",
                            editorOptions: {
                                placeholder: "Enter product name",
                                inputAttr: { id: "product_name_input" }
                            }
                        },
                        {
                            dataField: "category_id",
                            editorType: "dxSelectBox",
                            editorOptions: {
                                placeholder: "Select category",
                                inputAttr: { id: "category_id_input" }
                            }
                        },
                        {
                            dataField: "price",
                            editorType: "dxNumberBox",
                            editorOptions: {
                                placeholder: "Enter price",
                                inputAttr: { id: "price_input" }
                            }
                        },
                        {
                            dataField: "quantity",
                            editorType: "dxNumberBox",
                            editorOptions: {
                                placeholder: "Enter quantity",
                                format: "#",
                                min: 0,
                                showSpinButtons: true,
                                inputAttr: { id: "quantity_input" }
                            }
                        },
                        {
                            dataField: "image",
                            label: { text: "Product Image" },
                            template: function(data, itemElement) {
                                var $container = $("<div>").addClass("custom-file-upload-wrapper").css({ display: "flex", flexDirection: "column", gap: "10px", marginTop: "4px", width: "100%" });
                                var formData = data.formData || (data.component && data.component.option ? data.component.option("formData") : {}) || {};
                                var currentImg = formData.image;

                                var $hiddenInput = $("<input>")
                                    .attr("id", "product_image_input")
                                    .attr("type", "file")
                                    .attr("accept", "image/jpeg,image/png,image/gif,image/webp")
                                    .css({ display: "none" });

                                var $dropzone = $("<div>")
                                    .addClass("custom-upload-dropzone")
                                    .html(
                                        '<div class="upload-dropzone-inner">' +
                                            '<div class="upload-icon-wrapper">' +
                                                '<i class="fa-solid fa-cloud-arrow-up"></i>' +
                                            '</div>' +
                                            '<div class="upload-text-content">' +
                                                '<span class="upload-title">Choose image or drag & drop</span>' +
                                                '<span class="upload-subtext">JPG, PNG, WEBP or GIF (Auto 300x300 canvas framing)</span>' +
                                            '</div>' +
                                            '<button type="button" class="btn-browse-file"><i class="fa-solid fa-folder-open me-1"></i> Browse File</button>' +
                                        '</div>'
                                    );

                                var $prevBox = $("<div>")
                                    .addClass("form-preview-container")
                                    .css({ display: "flex", alignItems: "center", gap: "12px", background: "rgba(15, 23, 42, 0.6)", border: "1px solid rgba(52, 211, 153, 0.3)", borderRadius: "8px", padding: "10px 14px" });

                                var $statusMsg = $("<div>").addClass("upload-status-msg").css({ fontSize: "11px", display: "none" });

                                if (window._currentImagePreviewDataUrl) {
                                    var $img = $("<img>").attr("src", window._currentImagePreviewDataUrl).css({
                                        width: "52px", height: "52px", objectFit: "contain", background: "#ffffff", borderRadius: "6px", border: "2px solid #34d399", padding: "2px", boxShadow: "0 4px 10px rgba(0,0,0,0.3)"
                                    });
                                    $prevBox.append($img).append($("<div>").html('<div style="font-size:12px; font-weight:600; color:#34d399;">New Image Ready</div><div style="font-size:11px; color:#94a3b8;">Resized & Border Framed (300x300)</div>')).show();
                                    $statusMsg.text("✨ Image framed & standardized (300x300)").css({ color: "#34d399", display: "block" });
                                } else if (currentImg && typeof currentImg === "string" && (currentImg.startsWith("http://") || currentImg.startsWith("https://") || currentImg.startsWith("/") || currentImg.startsWith("assets/") || currentImg.startsWith("data:"))) {
                                    var $img = $("<img>").attr("src", currentImg).css({
                                        width: "52px", height: "52px", objectFit: "contain", background: "#ffffff", borderRadius: "6px", border: "2px solid #38bdf8", padding: "2px", boxShadow: "0 4px 10px rgba(0,0,0,0.3)"
                                    });
                                    $prevBox.append($img).append($("<div>").html('<div style="font-size:12px; font-weight:600; color:#f8fafc;">Current Image</div><div style="font-size:11px; color:#94a3b8;">Uniform 300x300 canvas frame</div>')).show();
                                } else {
                                    $prevBox.hide();
                                }

                                $dropzone.on("click", function(e) {
                                    e.preventDefault();
                                    $hiddenInput.trigger("click");
                                });

                                $dropzone.on("dragover dragenter", function(e) {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    $dropzone.addClass("is-dragover");
                                });

                                $dropzone.on("dragleave dragend drop", function(e) {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    $dropzone.removeClass("is-dragover");
                                });

                                $dropzone.on("drop", function(e) {
                                    var files = e.originalEvent && e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files;
                                    if (files && files.length > 0) {
                                        handleFileSelect(files[0]);
                                    }
                                });

                                function handleFileSelect(file) {
                                    if (!file) {
                                        window._currentSelectedImageFile = null;
                                        window._currentImagePreviewDataUrl = null;
                                        if (formData) formData.image = null;
                                        if (typeof data.setValue === "function") data.setValue(null);
                                        $(".dx-overlay-content .form-preview-container, .form-preview-container").hide();
                                        $(".dx-overlay-content .upload-status-msg, .upload-status-msg").hide();
                                        return;
                                    }

                                    window._currentSelectedImageFile = file;
                                    var pendingVal = "pending_upload_" + Date.now();
                                    if (formData) formData.image = pendingVal;

                                    // 1. Immediately create local preview URL and set global state BEFORE any grid re-render
                                    try {
                                        var immediateUrl = URL.createObjectURL(file);
                                        window._currentImagePreviewDataUrl = immediateUrl;
                                    } catch(e) {}

                                    // 2. Set DevExtreme form value
                                    if (typeof data.setValue === "function") {
                                        data.setValue(pendingVal);
                                    }

                                    // 3. Register change in DevExtreme Grid editing option
                                    var grid = $("#gridContainer").dxDataGrid("instance");
                                    if (grid) {
                                        var editRowKey = grid.option("editing.editRowKey");
                                        var changes = $.extend(true, [], grid.option("editing.changes") || []);
                                        if (changes.length === 0) {
                                            if (editRowKey !== null && editRowKey !== undefined) {
                                                changes = [{ key: editRowKey, type: "update", data: { image: pendingVal } }];
                                            } else {
                                                changes = [{ type: "insert", data: { image: pendingVal } }];
                                            }
                                        } else {
                                            changes[0].data = changes[0].data || {};
                                            changes[0].data.image = pendingVal;
                                        }
                                        grid.option("editing.changes", changes);
                                    }

                                    // 4. Render immediate preview thumbnail safely in active DOM element
                                    if (window._currentImagePreviewDataUrl) {
                                        var $livePrev = $(".dx-overlay-content .form-preview-container");
                                        var $liveStatus = $(".dx-overlay-content .upload-status-msg");
                                        var $targetBox = $livePrev.length ? $livePrev : (itemElement.find(".form-preview-container").length ? itemElement.find(".form-preview-container") : $prevBox);
                                        var $targetStatus = $liveStatus.length ? $liveStatus : (itemElement.find(".upload-status-msg").length ? itemElement.find(".upload-status-msg") : $statusMsg);

                                        $targetBox.empty().css({ display: "flex" }).show();
                                        var $img = $("<img>").attr("src", window._currentImagePreviewDataUrl).css({
                                            width: "52px", height: "52px", objectFit: "contain", background: "#ffffff", borderRadius: "6px", border: "2px solid #34d399", padding: "2px", boxShadow: "0 4px 10px rgba(0,0,0,0.3)"
                                        });
                                        var fileSizeKb = (file.size / 1024).toFixed(1);
                                        $targetBox.append($img).append($("<div>").html('<div style="font-size:12px; font-weight:600; color:#34d399;">' + file.name + ' (' + fileSizeKb + ' KB)</div><div style="font-size:11px; color:#94a3b8;">Processing 300x300 canvas frame...</div>'));
                                        $targetStatus.text("🎨 Standardizing image to 300x300 framed square box...").css({ color: "#38bdf8", display: "block" });
                                    }

                                    // 5. Standardize image on canvas asynchronously
                                    standardizeImageFile(file, function(standardizedFile, previewDataUrl) {
                                        if (standardizedFile) {
                                            window._currentSelectedImageFile = standardizedFile;
                                        }
                                        if (previewDataUrl) {
                                            window._currentImagePreviewDataUrl = previewDataUrl;
                                            var $livePrev = $(".dx-overlay-content .form-preview-container");
                                            var $liveStatus = $(".dx-overlay-content .upload-status-msg");
                                            var $targetBox = $livePrev.length ? $livePrev : (itemElement.find(".form-preview-container").length ? itemElement.find(".form-preview-container") : $prevBox);
                                            var $targetStatus = $liveStatus.length ? $liveStatus : (itemElement.find(".upload-status-msg").length ? itemElement.find(".upload-status-msg") : $statusMsg);

                                            $targetBox.empty().css({ display: "flex" }).show();
                                            var $img = $("<img>").attr("src", previewDataUrl).css({
                                                width: "52px", height: "52px", objectFit: "contain", background: "#ffffff", borderRadius: "6px", border: "2px solid #34d399", padding: "2px", boxShadow: "0 4px 10px rgba(0,0,0,0.3)"
                                            });
                                            var fileSizeKb = (file.size / 1024).toFixed(1);
                                            $targetBox.append($img).append($("<div>").html('<div style="font-size:12px; font-weight:600; color:#34d399;">' + file.name + ' (' + fileSizeKb + ' KB)</div><div style="font-size:11px; color:#94a3b8;">Resized & Border Framed (300x300)</div>'));
                                            $targetStatus.text("✨ Image framed & standardized (300x300)").css({ color: "#34d399", display: "block" });
                                        }
                                    });
                                }

                                $hiddenInput.on("change", function() {
                                    var file = this.files && this.files[0];
                                    handleFileSelect(file);
                                });

                                $container.append($dropzone).append($hiddenInput).append($prevBox).append($statusMsg);
                                itemElement.append($container);

                                setTimeout(function() {
                                    itemElement.closest('.dx-field-item').find('label').attr('for', 'product_image_input');
                                }, 0);
                            }
                        }
                    ]
                }
            },
            onEditingStart: function(e) {
                // Handled in popup.onShowing
            },
            onInitNewRow: function(e) {
                // Handled in popup.onShowing
            },
            onSaving: function(e) {
                if (window._currentSelectedImageFile) {
                    var pendingVal = "pending_upload_" + Date.now();
                    if (!e.changes || e.changes.length === 0) {
                        var grid = $("#gridContainer").dxDataGrid("instance");
                        var editRowKey = grid ? grid.option("editing.editRowKey") : null;
                        if (editRowKey !== null && editRowKey !== undefined) {
                            e.changes = [{ key: editRowKey, type: "update", data: { image: pendingVal } }];
                        } else {
                            e.changes = [{ type: "insert", data: { image: pendingVal } }];
                        }
                    } else {
                        e.changes[0].data = e.changes[0].data || {};
                        e.changes[0].data.image = pendingVal;
                    }
                }
            },
            onRowUpdating: function(e) {
                if (window._currentSelectedImageFile) {
                    if (!e.newData) e.newData = {};
                    e.newData.image = "pending_upload_" + Date.now();
                }
            },
            onRowInserting: function(e) {
                if (window._currentSelectedImageFile) {
                    if (!e.data) e.data = {};
                    e.data.image = "pending_upload_" + Date.now();
                }
            },
            showBorders: true,
            rowAlternationEnabled: true,
            hoverStateEnabled: true,
            pager: {
                visible: true,
                showPageSizeSelector: true,
                allowedPageSizes: [5, 10, 20, 50],
                showInfo: true,
                showNavigationButtons: true,
                displayMode: 'full'
            },
            paging: {
                enabled: true,
                pageSize: 10
            },
            searchPanel: {
                visible: false
            },
            onCellPrepared: function(e) {
                if (e.rowType === "data") {
                    var field = e.column.dataField;
                    var name = e.column.name;
                    if (field === "product_code" || field === "category_code" || 
                        field === "created_at" || field === "lastupdate" || 
                        e.column.dataType === "date" || e.column.dataType === "datetime" ||
                        (name && (name.includes("time") || name.includes("date") || name.includes("ago")))) {
                        e.cellElement.addClass("font-mono");
                    }
                }
            },
            onSaved: function(e) {
                // Force a TRUE reload from the server (reload() re-runs the
                // CustomStore load; refresh() alone can keep stale data with a
                // remote store). Then reset the view so the new row is visible.
                var grid = e.component;
                grid.getDataSource().reload().done(function() {
                    grid.pageIndex(0);
                    grid.clearFilter();
                    grid.searchByText("");
                    $("#searchInput").val("");
                    $("#searchInput").trigger("input");
                });
            },
            // ===== Field Chooser (show / hide columns) =====
            columnChooser: {
                enabled: true,
                mode: "select", // clickable checkboxes to toggle column visibility
                title: "Field Chooser",
                height: 380,
                width: 280,
                emptyPanelText: "Drag a column here to hide it",
                search: {
                    enabled: true
                }
            },
            onToolbarPreparing: function(e) {
                // Add a "Columns" button to the grid toolbar that opens the chooser.
                e.toolbarOptions.items.push({
                    widget: "dxButton",
                    location: "after",
                    locateInMenu: "never",
                    options: {
                        icon: "columnchooser",
                        text: "Columns",
                        hint: "Show / hide columns",
                        onClick: function() {
                            openColumnChooser();
                        }
                    }
                });
            },
            onContentReady: function(e) {
                // Default to 10 rows on initial load so grid fills nicely.
                var grid = e.component;
                if (!grid._initDone && grid.option("paging.pageSize") !== 10) {
                    grid.option("paging.pageSize", 10);
                }
                grid._initDone = true;
            },
            onOptionChanged: function(e) {
                // Re-fit the grid height when page size changes so the pager
                // (Next / page numbers) stays visible without scrolling.
                if (e.name === "pageSize" || e.name === "paging.pageSize") {
                    setTimeout(fitGridHeight, 0);
                }
            },
            onPageSizeChanged: function(e) {
                // Guaranteed handler for page-size selection (5/10/20).
                // Apply the chosen size, reset to page 1 (so you don't land on an
                // out-of-range page when coming from a later page), and refit height.
                var grid = e.component;
                grid.option("paging.pageSize", e.pageSize);
                grid.option("paging.pageIndex", 0); // 0-based: go back to first page
                setTimeout(fitGridHeight, 0);
            },
            onContextMenuPreparing: function(e) {
                if (e.target === "header") {
                    var column = e.column;

                    if (!e.items) e.items = [];

                    // Clear default context menu items to avoid duplicates
                    e.items = (e.items || []).filter(function(item) {
                        return !item.text || (item.text !== "Fix" && item.text !==
                            "Unfix" && item.text !== "Sticky");
                    });

                    // Allow fixing options if column exists and allowFixing is not false
                    if (column && column.allowFixing !== false) {
                        var colIdentifier = column.index !== undefined ? column.index : column.name;

                        // Direct "Fix Left" option
                        e.items.push({
                            text: "Freeze Left",
                            icon: "lock",
                            disabled: column.fixed && column.fixedPosition === "left",
                            onItemClick: function() {
                                e.component.columnOption(colIdentifier, {
                                    fixed: true,
                                    fixedPosition: "left"
                                });
                            }
                        });

                        // Direct "Fix Right" option
                        e.items.push({
                            text: "Freeze Right",
                            icon: "lock",
                            disabled: column.fixed && column.fixedPosition === "right",
                            onItemClick: function() {
                                e.component.columnOption(colIdentifier, {
                                    fixed: true,
                                    fixedPosition: "right"
                                });
                            }
                        });

                        // Direct "Unfix" option
                        e.items.push({
                            text: "Unfreeze",
                            icon: "unlock",
                            disabled: !column.fixed,
                            onItemClick: function() {
                                e.component.columnOption(colIdentifier, "fixed", false);
                            }
                        });
                    }

                    // "View Created As" submenu — ONLY on the Date Created column header.
                    // Must match by name (date_created) because several columns share
                    // dataField "created_at"; we want this feature on Date Created alone.
                    if (column && column.name === "date_created") {
                        e.items.push({
                            text: "View Created As",
                            icon: "calendar",
                            items: [{
                                    text: "Date",
                                    onItemClick: function() {
                                        applyCreatedView("date");
                                    }
                                },
                                {
                                    text: "Time",
                                    onItemClick: function() {
                                        applyCreatedView("time");
                                    }
                                },
                                {
                                    text: "Month",
                                    onItemClick: function() {
                                        applyCreatedView("month");
                                    }
                                },
                                {
                                    text: "Year",
                                    onItemClick: function() {
                                        applyCreatedView("year");
                                    }
                                },
                                {
                                    text: "Day of Week",
                                    onItemClick: function() {
                                        applyCreatedView("weekday");
                                    }
                                },
                                {
                                    text: "Date & Time",
                                    onItemClick: function() {
                                        applyCreatedView("datetime");
                                    }
                                },
                                {
                                    text: "Relative",
                                    onItemClick: function() {
                                        applyCreatedView("relative");
                                    }
                                }
                            ]
                        });
                    }

                }
            }
        });

        function fitGridHeight() {
            var wrapper = document.querySelector('.table-wrapper');
            if (!wrapper) return;
            var rect = wrapper.getBoundingClientRect();
            var vh = window.innerHeight;
            if (window.visualViewport && window.visualViewport.height) {
                vh = window.visualViewport.height;
            }
            var available = vh - rect.top - 16; // Extend container almost to bottom edge / Safari bar
            if (available < 350) available = 350;
            var grid = $("#gridContainer").dxDataGrid("instance");
            if (!grid) return;

            grid.option("height", available);
        }
        $(window).on("resize orientationchange", fitGridHeight);
        if (window.visualViewport) {
            window.visualViewport.addEventListener("resize", fitGridHeight);
        }
        setTimeout(fitGridHeight, 300);
        $(window).on("load", fitGridHeight);

        // Wire up custom Search Input to DevExtreme DataGrid search
        $("#searchInput").on("input", function() {
            var grid = $("#gridContainer").dxDataGrid("instance");
            grid.searchByText($(this).val());
        });

        // Applies "View Created As: Date / Time / Month" to the Date Created column.
        // Swaps calculateCellValue + calculateSortValue so the column shows AND sorts
        // purely by date, time-of-day, or month — triggered by right-click on the header.
        function applyCreatedView(mode) {
            var grid = $("#gridContainer").dxDataGrid("instance");
            var cell, sort;
            if (mode === "date") {
                cell = function(r) {
                    if (!r.created_at) return "";
                    return new Date(r.created_at).toLocaleDateString('en-GB');
                };
                sort = function(r) {
                    return r.created_at ? new Date(r.created_at).getTime() : 0;
                };
            } else if (mode === "time") {
                cell = function(r) {
                    if (!r.created_at) return "";
                    return new Date(r.created_at).toLocaleTimeString('en-GB');
                };
                sort = function(r) {
                    if (!r.created_at) return 0;
                    var d = new Date(r.created_at);
                    return d.getHours() * 3600 + d.getMinutes() * 60 + d.getSeconds();
                };
            } else if (mode === "month") {
                cell = function(r) {
                    if (!r.created_at) return "";
                    return new Date(r.created_at).toLocaleDateString('en-GB', {
                        month: 'long',
                        year: 'numeric'
                    });
                };
                sort = function(r) {
                    if (!r.created_at) return 0;
                    var d = new Date(r.created_at);
                    return d.getFullYear() * 12 + d.getMonth();
                };
            } else if (mode === "year") {
                cell = function(r) {
                    if (!r.created_at) return "";
                    return String(new Date(r.created_at).getFullYear());
                };
                sort = function(r) {
                    if (!r.created_at) return 0;
                    return new Date(r.created_at).getFullYear();
                };
            } else if (mode === "weekday") {
                cell = function(r) {
                    if (!r.created_at) return "";
                    return new Date(r.created_at).toLocaleDateString('en-GB', {
                        weekday: 'long'
                    });
                };
                sort = function(r) {
                    if (!r.created_at) return 0;
                    return new Date(r.created_at).getDay();
                };
            } else if (mode === "datetime") {
                cell = function(r) {
                    if (!r.created_at) return "";
                    var d = new Date(r.created_at);
                    return d.toLocaleDateString('en-GB') + " " + d.toLocaleTimeString('en-GB');
                };
                sort = function(r) {
                    return r.created_at ? new Date(r.created_at).getTime() : 0;
                };
            } else { // relative
                cell = function(r) {
                    if (!r.created_at) return "";
                    return timeAgo(new Date(r.created_at));
                };
                sort = function(r) {
                    return r.created_at ? new Date(r.created_at).getTime() : 0;
                };
            }
            grid.columnOption("date_created", {
                calculateCellValue: cell,
                calculateSortValue: sort,
                dataType: "string",
                format: null,
                sortOrder: undefined
            });
        }

        $("#openAddModalBtn").on("click", function() {
            var grid = $("#gridContainer").dxDataGrid("instance");
            grid.addRow();
        });

        // Touchpad horizontal scrolling
        $(".table-wrapper").each(function() {
            this.addEventListener("wheel", function(e) {
                if (Math.abs(e.deltaX) <= Math.abs(e.deltaY)) return;

                var $inner = $(this).find(
                    ".dx-datagrid-rowsview .dx-scrollable-container"
                ).first();
                if (!$inner.length) return;

                var el = $inner[0];
                if (el.scrollWidth > el.clientWidth) {
                    el.scrollLeft += e.deltaX;
                    e.preventDefault();
                }
            }, {
                passive: false
            });
        });

        // PDF page orientation: "p" = Portrait (default, highlighted in menu),
        // "l" = Landscape. Set by the Orientation toggle in the PDF dropdown.
        var pdfOrientation = "p";
        // PDF paper size: "a4" (default), "a3", "letter".
        var pdfPaper = "a4";

        async function generateCleanGridPdfBlob(pageOnly) {
            var gridInstance = $("#gridContainer").dxDataGrid("instance");
            if (!gridInstance) throw new Error("Grid instance not found");

            var exportData;
            if (pageOnly) {
                exportData = gridInstance.getVisibleRows()
                    .filter(function(r) { return r.rowType === "data"; })
                    .map(function(r) { return r.data; });
            } else {
                exportData = await gridInstance.getDataSource().store().load();
            }

            if (!exportData || exportData.length === 0) {
                throw new Error("No data available to export.");
            }

            var visibleColumns = gridInstance.option("columns").filter(function(col) {
                return col.type !== "buttons" && col.caption !== "Action" && col.dataField !== "action";
            });

            var thead = "<thead><tr>";
            visibleColumns.forEach(function(col) {
                var cap = col.caption || col.dataField || "";
                thead += "<th>" + cap + "</th>";
            });
            thead += "</tr></thead>";

            var tbody = "<tbody>";
            exportData.forEach(function(row) {
                tbody += "<tr>";
                visibleColumns.forEach(function(col) {
                    var val = row[col.dataField];
                    if (val === null || val === undefined) val = "";
                    if ((col.dataField === "created_at" || col.dataField === "lastupdate" || col.dataField === "date_created") && val) {
                        try { val = formatDateTime(new Date(val)); } catch(e) {}
                    }
                    if (col.dataField === "price" && val !== "") {
                        val = "$" + parseFloat(val).toFixed(2);
                    }
                    tbody += "<td>" + String(val).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;") + "</td>";
                });
                tbody += "</tr>";
            });
            tbody += "</tbody>";

            var scopeTag = pageOnly ? " (Current Page)" : " (All Pages)";

            var overlay = $(
                '<div id="pdfCaptureOverlay">' +
                '<style>' +
                '#pdfCaptureOverlay { position: fixed; left: -10000px; top: 0; z-index: -1; background: #ffffff !important; padding: 24px; box-sizing: border-box; }' +
                '#pdfTable { font-family: "KhmerOSWeb", "Khmer OS Siemreap", Arial, sans-serif; border-collapse: collapse; width: 100%; color: #000000; font-size: 10px; font-weight: normal; background: #ffffff !important; }' +
                '#pdfTable th { background: #f1f5f9 !important; color: #0f172a; padding: 7px 8px; text-align: left; border: 1px solid #64748b !important; font-weight: 700; font-size: 10px; }' +
                '#pdfTable td { padding: 6px 8px; border: 1px solid #64748b !important; color: #0f172a; font-weight: normal; font-size: 9.5px; background: #ffffff !important; }' +
                '#pdfTable tr:nth-child(even) td { background: #f8fafc !important; }' +
                '</style>' +
                '<div style="font-family: Arial, sans-serif; font-size: 15px; font-weight: bold; margin-bottom: 12px; color: #0f172a;">Products Inventory Report' + scopeTag + '</div>' +
                '<table id="pdfTable">' + thead + tbody + '</table>' +
                '</div>'
            ).appendTo("body");

            if (document.fonts && document.fonts.ready) {
                await document.fonts.ready;
            }
            await new Promise(function(r) { setTimeout(r, 400); });

            const canvas = await html2canvas(overlay[0], {
                scale: 2,
                useCORS: true,
                backgroundColor: "#ffffff",
                logging: false,
                onclone: function(clonedDoc) {
                    try {
                        if (window.__khmerB64) {
                            var s = clonedDoc.createElement("style");
                            s.textContent = '@font-face{font-family:"KhmerOSWeb";src:url(data:font/ttf;base64,' + window.__khmerB64 + ') format("truetype");font-weight:normal;font-style:normal;}';
                            clonedDoc.head.appendChild(s);
                        }
                    } catch (e) {}
                }
            });
            overlay.remove();

            const { jsPDF } = window.jspdf;
            var $menu = document.getElementById("masterExportMenu");
            var $actPaper = $menu ? $menu.querySelector("[data-paper].active") : null;
            var $actOrient = $menu ? $menu.querySelector("[data-orientation].active") : null;
            var pdfPaper = $actPaper ? $actPaper.dataset.paper : "a4";
            var pdfOrientation = $actOrient ? ($actOrient.dataset.orientation === "landscape" ? "l" : "p") : (visibleColumns.length > 5 ? "l" : "p");

            const pdf = new jsPDF(pdfOrientation, "pt", pdfPaper);
            const pageW = pdf.internal.pageSize.getWidth();
            const pageH = pdf.internal.pageSize.getHeight();
            const margin = 20;

            const imgData = canvas.toDataURL("image/jpeg", 0.95);
            const imgProps = pdf.getImageProperties(imgData);
            let imgW = pageW - margin * 2;
            let imgH = (imgProps.height * imgW) / imgProps.width;
            if (imgH > pageH - margin * 2) {
                imgH = pageH - margin * 2;
                imgW = (imgProps.width * imgH) / imgProps.height;
            }
            pdf.addImage(imgData, "JPEG", margin, margin, imgW, imgH);

            return { pdf: pdf, blob: pdf.output("blob") };
        }

        async function exportPDF(pageOnly) {
            const $btn = $("#pdfExportTrigger");
            try {
                $btn.prop("disabled", true).css("opacity", "0.6");
                var res = await generateCleanGridPdfBlob(pageOnly);
                var filename = "Products_" + (pageOnly ? "CurrentPage" : "AllPages") + "_" + new Date().toISOString().slice(0, 10) + ".pdf";
                res.pdf.save(filename);
            } catch (err) {
                console.error("PDF Export Error:", err);
                alert("Export failed: " + err.message);
            } finally {
                $btn.prop("disabled", false).css("opacity", "1");
            }
        }

        function exportGrid(pageOnly) {
            try {
                var gridInstance = $("#gridContainer").dxDataGrid("instance");
                var workbook = new ExcelJS.Workbook();
                var worksheet = workbook.addWorksheet('Category');

                if (pageOnly) {
                    var visibleData = gridInstance.getVisibleRows()
                        .filter(function(row) {
                            return row.rowType === "data";
                        })
                        .map(function(row) {
                            return row.data;
                        });

                    var tempDiv = $("<div>").appendTo("body").css({
                        position: "absolute",
                        left: "-9999px",
                        top: "-9999px",
                        width: "1000px",
                        height: "600px"
                    });
                    var exported = false;

                    tempDiv.dxDataGrid({
                        dataSource: visibleData,
                        columns: gridInstance.option("columns"),
                        onContentReady: function(e) {
                            if (exported) return;
                            exported = true;

                            DevExpress.excelExporter.exportDataGrid({
                                component: e.component,
                                worksheet: worksheet,
                                autoFilterEnabled: true
                            }).then(function() {
                                return workbook.xlsx.writeBuffer();
                            }).then(function(buffer) {
                                const today = new Date();
                                const yyyy = today.getFullYear();
                                const mm = String(today.getMonth() + 1).padStart(2, '0');
                                const dd = String(today.getDate()).padStart(2, '0');
                                const formattedDate = `${yyyy}-${mm}-${dd}`;
                                const fileName = `Categories_${formattedDate}_Page.xlsx`;
                                saveAs(new Blob([buffer], {
                                    type: 'application/octet-stream'
                                }), fileName);
                            }).then(function() {
                                tempDiv.remove();
                            }).catch(function(err) {
                                tempDiv.remove();
                                alert("Export Error: " + err.message);
                            });
                        }
                    });
                } else {
                    DevExpress.excelExporter.exportDataGrid({
                        component: gridInstance,
                        worksheet: worksheet,
                        autoFilterEnabled: true
                    }).then(function() {
                        return workbook.xlsx.writeBuffer();
                    }).then(function(buffer) {
                        const today = new Date();
                        const yyyy = today.getFullYear();
                        const mm = String(today.getMonth() + 1).padStart(2, '0');
                        const dd = String(today.getDate()).padStart(2, '0');
                        const formattedDate = `${yyyy}-${mm}-${dd}`;
                        const fileName = `Categories_${formattedDate}.xlsx`;
                        saveAs(new Blob([buffer], {
                            type: 'application/octet-stream'
                        }), fileName);
                    }).catch(function(err) {
                        alert("Export Error: " + err.message);
                    });
                }
            } catch (err) {
                alert("Export Handler Error: " + err.message);
            }
        }

        // CSV export (client-side, no server script). Exports the chosen
        // rows (current page or all) as a downloaded .csv file.
        function exportCSV(pageOnly) {
            try {
                var gridInstance = $("#gridContainer").dxDataGrid("instance");
                var rows, cols;

                if (pageOnly) {
                    rows = gridInstance.getVisibleRows()
                        .filter(function(r) {
                            return r.rowType === "data";
                        })
                        .map(function(r) {
                            return r.data;
                        });
                } else {
                    rows = gridInstance.getDataSource().store().load();
                }

                var finish = function(data) {
                    if (!data || data.length === 0) {
                        alert("No data to export.");
                        return;
                    }
                    cols = gridInstance.option("columns").filter(function(col) {
                        return col.type !== "buttons" && col.caption !== "Action" &&
                            col.dataField !== "action";
                    });

                    // Build CSV (quote fields that contain comma/quote/newline)
                    var escape = function(v) {
                        if (v === null || v === undefined) v = "";
                        v = String(v);
                        if (v.search(/[",n]/) !== -1) {
                            v = '"' + v.replace(/"/g, '""') + '"';
                        }
                        return v;
                    };
                    var header = cols.map(function(c) {
                        return escape(c.caption || c.dataField || "");
                    }).join(",");
                    var body = data.map(function(row) {
                        return cols.map(function(c) {
                            return escape(row[c.dataField]);
                        }).join(",");
                    }).join("n");
                    var csv = "﻿" + header + "n" + body;

                    var today = new Date();
                    var yyyy = today.getFullYear();
                    var mm = String(today.getMonth() + 1).padStart(2, '0');
                    var dd = String(today.getDate()).padStart(2, '0');
                    var fileName = "Categories_" + yyyy + "-" + mm + "-" + dd +
                        (pageOnly ? "_Page" : "") + ".csv";

                    var blob = new Blob([csv], {
                        type: "text/csv;charset=utf-8;"
                    });
                    if (navigator.msSaveOrOpenBlob) {
                        navigator.msSaveOrOpenBlob(blob, fileName);
                    } else {
                        var url = URL.createObjectURL(blob);
                        var a = document.createElement("a");
                        a.href = url;
                        a.download = fileName;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(url);
                    }
                };

                if (pageOnly) {
                    finish(rows);
                } else {
                    Promise.resolve(rows).then(finish).catch(function(err) {
                        alert("Export Error: " + err.message);
                    });
                }
            } catch (err) {
                alert("Export Handler Error." + err.message);
            }
        }
        // Dedicated PDF Export DropDownButton (matches Export Excel style)
        // ===== Custom Export PDF dropdown (plain HTML + CSS, no DevExtreme) =====
        (function() {
            var trigger = document.getElementById("masterExportTrigger");
            var menu = document.getElementById("masterExportMenu");
            if (!trigger || !menu) return;

            trigger.addEventListener("click", function(e) {
                e.stopPropagation();
                menu.classList.toggle("open");
            });
            document.addEventListener("click", function() {
                menu.classList.remove("open");
            });
            menu.addEventListener("click", function(e) {
                e.stopPropagation();
            });

            // Orientation toggle
            menu.querySelectorAll("[data-orientation]").forEach(function(btn) {
                btn.addEventListener("click", function() {
                    pdfOrientation = (btn.dataset.orientation === "landscape") ? "l" : "p";
                    menu.querySelectorAll("[data-orientation]").forEach(function(b) {
                        b.classList.toggle("active", b === btn);
                    });
                });
            });

            // Paper size toggle (A4 / A3 / Letter)
            menu.querySelectorAll("[data-paper]").forEach(function(btn) {
                btn.addEventListener("click", function() {
                    pdfPaper = btn.dataset.paper; // "a4" | "a3" | "letter"
                    menu.querySelectorAll("[data-paper]").forEach(function(b) {
                        b.classList.toggle("active", b === btn);
                    });
                });
            });

            // Export action
            menu.querySelectorAll("[data-action]").forEach(function(btn) {
                btn.addEventListener("click", function() {
                    var format = btn.dataset.format;
                    var scope = btn.dataset.action; // "all" | "current"
                    menu.classList.remove("open");
                    
                    if (format === "pdf") {
                        exportPDF(scope === "current");
                    } else if (format === "excel") {
                        exportGrid(scope === "current");
                    } else if (format === "csv") {
                        exportCSV(scope === "current");
                    }
                });
            });
        })();


        // Initialize custom Field Chooser Button
        $("#customFieldChooserBtn").dxButton({
            text: "",
            icon: "columnchooser",
            hint: "Field Chooser",
            elementAttr: {
                'data-tooltip': 'Field Chooser',
                'aria-label': 'Field Chooser'
            },
            onClick: function(e) {
                openColumnChooser(e ? e.event : null);
            }
        });

        $("#gridContainer").dxDataGrid({
            onToolbarPreparing: function(e) {
                var grid = e.component;
                e.toolbarOptions.items.push({
                    widget: "dxButton",
                    location: "after",
                    locateInMenu: "never",
                    options: {
                        icon: "bell",
                        text: "Push Selected",
                        hint: "Push selected products to Telegram",
                        onClick: function() {
                            var selectedKeys = grid.getSelectedRowKeys();
                            if (!selectedKeys || selectedKeys.length === 0) {
                                DevExpress.ui.notify("Please select one or more products using checkboxes first.", "warning", 3000);
                                return;
                            }
                            window.checkTelegramConnectionAndExecute(function() {
                                $.ajax({
                                    url: "manual_push.php",
                                    type: "POST",
                                    dataType: "json",
                                    data: { action: "push_batch_products", ids: selectedKeys },
                                    success: function(res) {
                                        if (res && res.ok) {
                                            DevExpress.ui.notify(selectedKeys.length + " selected product(s) pushed to Telegram!", "success", 3000);
                                        } else {
                                            DevExpress.ui.notify(res.description || "Failed to push selected products.", "error", 4000);
                                        }
                                    },
                                    error: function() {
                                        DevExpress.ui.notify("Network error pushing selected products.", "error", 4000);
                                    }
                                });
                            });
                        }
                    }
                });
                // Add a "Columns" button to the grid toolbar that opens the chooser.
                e.toolbarOptions.items.push({
                    widget: "dxButton",
                    location: "after",
                    locateInMenu: "never",
                    options: {
                        icon: "columnchooser",
                        text: "Columns",
                        hint: "Show / hide columns",
                        onClick: function() {
                            openColumnChooser();
                        }
                    }
                });
            },
            onContentReady: function(e) {
                // Default to 10 rows on initial load so grid fills nicely.
                var grid = e.component;
                if (!grid._initDone && grid.option("paging.pageSize") !== 10) {
                    grid.option("paging.pageSize", 10);
                }
                grid._initDone = true;
            },
            onOptionChanged: function(e) {
                // Re-fit the grid height when page size changes so the pager
                // (Next / page numbers) stays visible without scrolling.
                if (e.name === "pageSize" || e.name === "paging.pageSize") {
                    setTimeout(fitGridHeight, 0);
                }
            },
            onPageSizeChanged: function(e) {
                // Guaranteed handler for page-size selection (5/10/20).
                var grid = e.component;
                grid.option("paging.pageSize", e.pageSize);
                grid.option("paging.pageIndex", 0); // 0-based: go back to first page
                setTimeout(fitGridHeight, 0);
            },
            onContextMenuPreparing: function(e) {
                if (e.target === "header") {
                    var column = e.column;
                    if (!e.items) e.items = [];

                    // Clear default context menu items to avoid duplicates
                    e.items = [];

                    // 1. Rename Label Item
                    e.items.push({
                        text: "Rename Label",
                        icon: "rename",
                        onItemClick: function() {
                            openRenameDialog(column);
                        }
                    });

                    // 2. Hide / Show Column Toggle
                    var isVisible = column.visible !== false;
                    e.items.push({
                        text: isVisible ? "Hide Column" : "Show Column",
                        icon: isVisible ? "eyeclose" : "eyeopen",
                        onItemClick: function() {
                            e.component.columnOption(column.dataField || column.name, "visible", !isVisible);
                        }
                    });

                    // 3. Freeze / Pin Column Submenu
                    var isFixed = column.fixed === true;
                    var currentPos = column.fixedPosition || "left";
                    
                    e.items.push({
                        text: "Freeze Column",
                        icon: "pin",
                        items: [
                            {
                                text: "Left",
                                icon: (isFixed && currentPos === "left") ? "check" : "",
                                onItemClick: function() {
                                    e.component.columnOption(column.dataField || column.name, {
                                        fixed: true,
                                        fixedPosition: "left"
                                    });
                                }
                            },
                            {
                                text: "Right",
                                icon: (isFixed && currentPos === "right") ? "check" : "",
                                onItemClick: function() {
                                    e.component.columnOption(column.dataField || column.name, {
                                        fixed: true,
                                        fixedPosition: "right"
                                    });
                                }
                            },
                            {
                                text: "Sticky",
                                icon: (isFixed && currentPos === "sticky") ? "check" : "",
                                onItemClick: function() {
                                    e.component.columnOption(column.dataField || column.name, {
                                        fixed: true,
                                        fixedPosition: "sticky"
                                    });
                                }
                            },
                            {
                                text: "Unfreeze",
                                icon: !isFixed ? "check" : "",
                                onItemClick: function() {
                                    e.component.columnOption(column.dataField || column.name, "fixed", false);
                                }
                            }
                        ]
                    });

                    // 4. Alignment Submenu
                    var currentAlign = column.alignment || "left";
                    e.items.push({
                        text: "Alignment",
                        icon: "alignleft",
                        items: [
                            {
                                text: "Left",
                                icon: currentAlign === "left" ? "check" : "",
                                onItemClick: function() {
                                    e.component.columnOption(column.dataField || column.name, "alignment", "left");
                                }
                            },
                            {
                                text: "Center",
                                icon: currentAlign === "center" ? "check" : "",
                                onItemClick: function() {
                                    e.component.columnOption(column.dataField || column.name, "alignment", "center");
                                }
                            },
                            {
                                text: "Right",
                                icon: currentAlign === "right" ? "check" : "",
                                onItemClick: function() {
                                    e.component.columnOption(column.dataField || column.name, "alignment", "right");
                                }
                            }
                        ]
                    });

                    // 5. Open Field Chooser
                    e.items.push({
                        text: "Column Chooser...",
                        icon: "columnchooser",
                        onItemClick: function() {
                            openColumnChooser();
                        }
                    });
                }
            }
        });

        gridInstance = $("#gridContainer").dxDataGrid("instance");

        // Load Push Notification Settings on page load
        function loadPushSettings() {
            $.ajax({
                url: "manual_push.php",
                type: "GET",
                data: { action: "get_settings" },
                dataType: "json",
                success: function(res) {
                    if (res && res.ok) {
                        var isAuto = (res.auto_telegram_notify === "1");
                        $("#toggleAutoPush").prop("checked", isAuto);
                        updatePushModeUI(isAuto);
                    }
                }
            });
        }

        function updatePushModeUI(isAuto) {
            if (isAuto) {
                $("#pushModeBadge").removeClass("auto-off").addClass("auto-on").text("Auto Active");
                $("#pushModeSubtitle").text("Automatically sends Telegram alerts on Add, Edit, & Delete.");
            } else {
                $("#pushModeBadge").removeClass("auto-on").addClass("auto-off").text("Manual Only (Auto Closed)");
                $("#pushModeSubtitle").text("Auto push closed. CRUD operations will NOT trigger automatic alerts.");
            }
        }

        loadPushSettings();

        // Manual Push Modal Handlers & Tab Switching
        $(document).on("click", "#openPushModalBtn", function(e) {
            e.preventDefault();
            $("#customPushMessageCustom").val("");
            loadPushSettings();
            $("#pushModal").css("display", "flex").hide().fadeIn(120);
        });

        $(document).on("click", "#closePushModalBtn", function() {
            $("#pushModal").fadeOut(100);
        });

        $(document).on("click", "#closeDocumentPushModalBtn", function() {
            $("#documentPushModal").fadeOut(100);
        });

        $(window).on("click", function(e) {
            if ($(e.target).is("#pushModal")) {
                $("#pushModal").fadeOut(100);
            }
            if ($(e.target).is("#documentPushModal")) {
                $("#documentPushModal").fadeOut(100);
            }
        });

        // 2-Tab Navigation Switcher
        $(document).on("click", ".push-tab-btn", function() {
            var targetTab = $(this).data("tab");
            $(".push-tab-btn").removeClass("active");
            $(this).addClass("active");
            $(".push-tab-content").removeClass("active").hide();
            $("#" + targetTab).addClass("active").css("display", "flex");
        });

        // Toggle Auto / Manual Push Setting
        $("#toggleAutoPush").on("change", function() {
            var isChecked = $(this).is(":checked");
            var enabledVal = isChecked ? "1" : "0";
            updatePushModeUI(isChecked);

            $.ajax({
                url: "manual_push.php",
                type: "POST",
                dataType: "json",
                data: { action: "toggle_auto", enabled: enabledVal },
                success: function(res) {
                    if (res && res.ok) {
                        var msg = isChecked ? "Auto Telegram notifications turned ON!" : "Auto Telegram notifications turned OFF (Manual Mode active).";
                        DevExpress.ui.notify(msg, isChecked ? "success" : "warning", 3000);
                    } else {
                        DevExpress.ui.notify("Failed to update setting.", "error", 3000);
                        $("#toggleAutoPush").prop("checked", !isChecked);
                        updatePushModeUI(!isChecked);
                    }
                },
                error: function() {
                    DevExpress.ui.notify("Network error updating notification setting.", "error", 3000);
                    $("#toggleAutoPush").prop("checked", !isChecked);
                    updatePushModeUI(!isChecked);
                }
            });
        });

        // Push Added Items Action
        $("#btnPushAdded").on("click", function() {
            var $btn = $(this);
            $btn.addClass("btn-loading").prop("disabled", true);
            LoadingOverlay.show("Pushing Added Items...");
            
            $.ajax({
                url: "manual_push.php",
                type: "POST",
                dataType: "json",
                data: { action: "push_added" },
                success: function(res) {
                    $btn.prop("disabled", false).html('<i class="fa-solid fa-paper-plane"></i> Push Added Items Report');
                    if (res && res.ok) {
                        DevExpress.ui.notify("Recently added items pushed to Telegram!", "success", 3000);
                        $("#pushModal").fadeOut(150);
                    } else {
                        DevExpress.ui.notify(res.description || "Failed to push added items.", "error", 4000);
                    }
                },
                error: function() {
                    $btn.prop("disabled", false).html('<i class="fa-solid fa-paper-plane"></i> Push Added Items Report');
                    DevExpress.ui.notify("Network error sending push.", "error", 4000);
                }
            });
        });

        // Push Updated Items Action
        $("#btnPushUpdated").on("click", function() {
            var $btn = $(this);
            $btn.addClass("btn-loading").prop("disabled", true);
            LoadingOverlay.show("Pushing Updated Items...");
            
            $.ajax({
                url: "manual_push.php",
                type: "POST",
                dataType: "json",
                data: { action: "push_updated" },
                success: function(res) {
                    $btn.prop("disabled", false).html('<i class="fa-solid fa-paper-plane"></i> Push Updated Items Report');
                    if (res && res.ok) {
                        DevExpress.ui.notify("Recently updated items pushed to Telegram!", "success", 3000);
                        $("#pushModal").fadeOut(150);
                    } else {
                        DevExpress.ui.notify(res.description || "Failed to push updated items.", "error", 4000);
                    }
                },
                error: function() {
                    $btn.prop("disabled", false).html('<i class="fa-solid fa-paper-plane"></i> Push Updated Items Report');
                    DevExpress.ui.notify("Network error sending push.", "error", 4000);
                }
            });
        });

        // Push Low Stock Warning Action
        $("#btnPushLowStock").on("click", function() {
            var $btn = $(this);
            $btn.addClass("btn-loading").prop("disabled", true);
            LoadingOverlay.show("Pushing Low Stock Report...");
            
            $.ajax({
                url: "manual_push.php",
                type: "POST",
                dataType: "json",
                data: { action: "push_low_stock" },
                success: function(res) {
                    $btn.prop("disabled", false).html('<i class="fa-solid fa-paper-plane"></i> Push Low Stock Warning');
                    if (res && res.ok) {
                        DevExpress.ui.notify("Low stock warning report pushed to Telegram!", "success", 3000);
                        $("#pushModal").fadeOut(150);
                    } else {
                        DevExpress.ui.notify(res.description || "Failed to push low stock report.", "error", 4000);
                    }
                },
                error: function() {
                    $btn.prop("disabled", false).html('<i class="fa-solid fa-paper-plane"></i> Push Low Stock Warning');
                    DevExpress.ui.notify("Network error sending push.", "error", 4000);
                }
            });
        });

        // Push Out of Stock Action
        $("#btnPushOutOfStock").on("click", function() {
            var $btn = $(this);
            $btn.addClass("btn-loading").prop("disabled", true);
            LoadingOverlay.show("Pushing Out of Stock...");
            
            $.ajax({
                url: "manual_push.php",
                type: "POST",
                dataType: "json",
                data: { action: "push_out_of_stock" },
                success: function(res) {
                    $btn.prop("disabled", false).html('<i class="fa-solid fa-paper-plane"></i> Push Out of Stock');
                    if (res && res.ok) {
                        DevExpress.ui.notify("Out of stock report pushed to Telegram!", "success", 3000);
                        $("#pushModal").fadeOut(150);
                    } else {
                        DevExpress.ui.notify(res.description || "Failed to push out of stock report.", "error", 4000);
                    }
                },
                error: function() {
                    $btn.prop("disabled", false).html('<i class="fa-solid fa-paper-plane"></i> Push Out of Stock');
                    DevExpress.ui.notify("Network error sending push.", "error", 4000);
                }
            });
        });

        // Push Valuation Report Action
        $("#btnPushValuation").on("click", function() {
            var $btn = $(this);
            $btn.addClass("btn-loading").prop("disabled", true);
            LoadingOverlay.show("Pushing Financial Report...");
            
            $.ajax({
                url: "manual_push.php",
                type: "POST",
                dataType: "json",
                data: { action: "push_valuation" },
                success: function(res) {
                    $btn.prop("disabled", false).html('<i class="fa-solid fa-paper-plane"></i> Push Valuation Report');
                    if (res && res.ok) {
                        DevExpress.ui.notify("Valuation report pushed to Telegram!", "success", 3000);
                        $("#pushModal").fadeOut(150);
                    } else {
                        DevExpress.ui.notify(res.description || "Failed to push valuation report.", "error", 4000);
                    }
                },
                error: function() {
                    $btn.prop("disabled", false).html('<i class="fa-solid fa-paper-plane"></i> Push Valuation Report');
                    DevExpress.ui.notify("Network error sending push.", "error", 4000);
                }
            });
        });

        // Push Summary Action
        $("#btnPushSummary").on("click", function() {
            var $btn = $(this);
            $btn.addClass("btn-loading").prop("disabled", true);
            LoadingOverlay.show("Pushing Summary...");
            
            $.ajax({
                url: "manual_push.php",
                type: "POST",
                dataType: "json",
                data: { action: "summary" },
                success: function(res) {
                    $btn.prop("disabled", false).html('<i class="fa-solid fa-paper-plane"></i> Push Summary Report');
                    if (res && res.ok) {
                        DevExpress.ui.notify("Summary report successfully pushed to Telegram!", "success", 3000);
                        $("#pushModal").fadeOut(150);
                    } else {
                        DevExpress.ui.notify(res.description || "Failed to push summary to Telegram.", "error", 4000);
                    }
                },
                error: function(xhr) {
                    $btn.prop("disabled", false).html('<i class="fa-solid fa-paper-plane"></i> Push Summary Report');
                    DevExpress.ui.notify("Network or server error sending push.", "error", 4000);
                }
            });
        });

        // Push Custom Action
        $("#btnPushCustom").on("click", function() {
            var msg = $.trim($("#customPushMessageCustom").val());
            if (!msg) {
                DevExpress.ui.notify("Please enter a custom message to push.", "warning", 3000);
                return;
            }

            var $btn = $(this);
            $btn.addClass("btn-loading").prop("disabled", true);
            LoadingOverlay.show("Sending...");

            $.ajax({
                url: "manual_push.php",
                type: "POST",
                dataType: "json",
                data: { action: "custom", message: msg },
                success: function(res) {
                    $btn.prop("disabled", false).html('<i class="fa-solid fa-paper-plane"></i> Send Custom Push');
                    if (res && res.ok) {
                        DevExpress.ui.notify("Custom notification pushed to Telegram!", "success", 3000);
                        $("#customPushMessageCustom").val("");
                        $("#pushModal").fadeOut(150);
                    } else {
                        DevExpress.ui.notify(res.description || "Failed to push custom message.", "error", 4000);
                    }
                },
                error: function(xhr) {
                    $btn.prop("disabled", false).html('<i class="fa-solid fa-paper-plane"></i> Send Custom Push');
                    DevExpress.ui.notify("Network or server error sending push.", "error", 4000);
                }
            });
        });

        function getCurrentGridPageItemIds() {
            try {
                var grid = $("#gridContainer").dxDataGrid("instance");
                if (grid) {
                    return grid.getVisibleRows()
                        .filter(function(r) { return r.rowType === "data" && r.data && r.data.id; })
                        .map(function(r) { return r.data.id; });
                }
            } catch(e) {}
            return [];
        }

        function executePushExcelDocument(scope) {
            var $btn = $("#btnPushExcelFile");
            setCardLoading($btn, true, "Generating Excel & Pushing to Telegram...");

            var pageOnly = (scope === "current");
            var rowIds = pageOnly ? getCurrentGridPageItemIds() : [];

            if (pageOnly && rowIds.length === 0) {
                DevExpress.ui.notify("No items visible on current grid page.", "warning", 3000);
                setCardLoading($btn, false);
                return;
            }

            window.checkTelegramConnectionAndExecute(function() {
                try {
                    var gridInstance = $("#gridContainer").dxDataGrid("instance");
                    var workbook = new ExcelJS.Workbook();
                    var worksheet = workbook.addWorksheet('Products');

                    DevExpress.excelExporter.exportDataGrid({
                        component: gridInstance,
                        worksheet: worksheet,
                        autoFilterEnabled: true
                    }).then(function() {
                        return workbook.xlsx.writeBuffer();
                    }).then(function(buffer) {
                        setCardLoading($btn, true, "Pushing Excel to Telegram...");
                        var blob = new Blob([buffer], { type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" });
                        var formData = new FormData();
                        var today = new Date();
                        var yyyy = today.getFullYear();
                        var mm = String(today.getMonth() + 1).padStart(2, '0');
                        var dd = String(today.getDate()).padStart(2, '0');
                        var filename = "Products_Report_" + yyyy + "-" + mm + "-" + dd + (pageOnly ? "_CurrentPage" : "") + ".xlsx";

                        formData.append("action", "push_excel_file");
                        formData.append("scope", scope);
                        if (pageOnly && rowIds.length > 0) {
                            formData.append("ids", rowIds.join(','));
                        }
                        formData.append("file", blob, filename);

                        $.ajax({
                            url: "manual_push.php",
                            type: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                            dataType: "json",
                            success: function(res) {
                                if (res && res.ok) {
                                    var tag = pageOnly ? " (Current Page)" : " (All Pages)";
                                    DevExpress.ui.notify("✅ Real Excel (.xlsx) file" + tag + " pushed to Telegram!", "success", 4000);
                                } else {
                                    DevExpress.ui.notify(res.message || res.description || "Failed to push Excel file.", "error", 4000);
                                }
                            },
                            error: function() {
                                DevExpress.ui.notify("Network error pushing Excel file.", "error", 4000);
                            },
                            complete: function() {
                                setCardLoading($btn, false);
                            }
                        });
                    }).catch(function() {
                        $.ajax({
                            url: "manual_push.php",
                            type: "POST",
                            dataType: "json",
                            data: { action: "push_excel_file", scope: scope, ids: rowIds },
                            success: function(res) {
                                if (res && res.ok) {
                                    var tag = pageOnly ? " (Current Page)" : " (All Pages)";
                                    DevExpress.ui.notify("✅ Excel Report Document" + tag + " pushed to Telegram!", "success", 4000);
                                } else {
                                    DevExpress.ui.notify(res.message || res.description || "Failed to push Excel file.", "error", 4000);
                                }
                            },
                            error: function() {
                                DevExpress.ui.notify("Network error pushing Excel file.", "error", 4000);
                            },
                            complete: function() {
                                setCardLoading($btn, false);
                            }
                        });
                    });
                } catch(e) {
                    setCardLoading($btn, false);
                }
            }, function() {
                setCardLoading($btn, false);
            });
        }

        function executePushCsvDocument(scope) {
            var $btn = $("#btnPushCsvFile");
            setCardLoading($btn, true, "Generating CSV & Pushing to Telegram...");

            var pageOnly = (scope === "current");
            var rowIds = pageOnly ? getCurrentGridPageItemIds() : [];

            if (pageOnly && rowIds.length === 0) {
                DevExpress.ui.notify("No items visible on current grid page.", "warning", 3000);
                setCardLoading($btn, false);
                return;
            }

            window.checkTelegramConnectionAndExecute(function() {
                try {
                    var gridInstance = $("#gridContainer").dxDataGrid("instance");
                    var fetchRows;
                    if (pageOnly) {
                        var visibleRows = gridInstance.getVisibleRows()
                            .filter(function(r) { return r.rowType === "data"; })
                            .map(function(r) { return r.data; });
                        fetchRows = Promise.resolve(visibleRows);
                    } else {
                        fetchRows = Promise.resolve(gridInstance.getDataSource().store().load());
                    }

                    fetchRows.then(function(rows) {
                        if (!rows || rows.length === 0) {
                            DevExpress.ui.notify("No data to export.", "warning", 3000);
                            setCardLoading($btn, false);
                            return;
                        }

                        var cols = gridInstance.option("columns").filter(function(col) {
                            return col.type !== "buttons" && col.caption !== "Action" && col.dataField !== "action";
                        });

                        var escapeCsv = function(v) {
                            if (v === null || v === undefined) v = "";
                            v = String(v);
                            if (v.search(/[",\n]/) !== -1) {
                                v = '"' + v.replace(/"/g, '""') + '"';
                            }
                            return v;
                        };

                        var header = cols.map(function(c) {
                            return escapeCsv(c.caption || c.dataField || "");
                        }).join(",") + "\n";

                        var body = rows.map(function(row) {
                            return cols.map(function(c) {
                                var val = row[c.dataField];
                                if (c.calculateCellValue) {
                                    val = c.calculateCellValue(row);
                                }
                                return escapeCsv(val);
                            }).join(",");
                        }).join("\n");

                        var csvContent = "\uFEFF" + header + body;
                        var blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });

                        var formData = new FormData();
                        var today = new Date();
                        var yyyy = today.getFullYear();
                        var mm = String(today.getMonth() + 1).padStart(2, '0');
                        var dd = String(today.getDate()).padStart(2, '0');
                        var filename = "Products_Report_" + yyyy + "-" + mm + "-" + dd + (pageOnly ? "_CurrentPage" : "") + ".csv";

                        formData.append("action", "push_csv_file");
                        formData.append("scope", scope);
                        if (pageOnly && rowIds.length > 0) {
                            formData.append("ids", rowIds.join(','));
                        }
                        formData.append("file", blob, filename);

                        setCardLoading($btn, true, "Pushing CSV to Telegram...");

                        $.ajax({
                            url: "manual_push.php",
                            type: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                            dataType: "json",
                            success: function(res) {
                                if (res && res.ok) {
                                    var tag = pageOnly ? " (Current Page)" : " (All Pages)";
                                    DevExpress.ui.notify("✅ CSV Report Document" + tag + " pushed to Telegram!", "success", 4000);
                                } else {
                                    DevExpress.ui.notify(res.message || res.description || "Failed to push CSV file.", "error", 4000);
                                }
                            },
                            error: function() {
                                DevExpress.ui.notify("Network error pushing CSV file.", "error", 4000);
                            },
                            complete: function() {
                                setCardLoading($btn, false);
                            }
                        });
                    }).catch(function(err) {
                        DevExpress.ui.notify("CSV Generation Error: " + (err.message || err), "error", 4000);
                        setCardLoading($btn, false);
                    });
                } catch(e) {
                    setCardLoading($btn, false);
                }
            }, function() {
                setCardLoading($btn, false);
            });
        }

        function executePushPdfDocument(scope) {
            var $btn = $("#btnPushPdfFile");
            setCardLoading($btn, true, "Generating PDF & Pushing to Telegram...");

            window.checkTelegramConnectionAndExecute(function() {
                var pageOnly = (scope === "current");
                generateCleanGridPdfBlob(pageOnly).then(function(res) {
                    var formData = new FormData();
                    var today = new Date();
                    var yyyy = today.getFullYear();
                    var mm = String(today.getMonth() + 1).padStart(2, '0');
                    var dd = String(today.getDate()).padStart(2, '0');
                    var filename = "Products_Report_" + (pageOnly ? "CurrentPage" : "AllPages") + "_" + yyyy + "-" + mm + "-" + dd + ".pdf";

                    formData.append("action", "push_pdf_file");
                    formData.append("scope", scope);
                    formData.append("file", res.blob, filename);

                    $.ajax({
                        url: "manual_push.php",
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        dataType: "json",
                        success: function(res) {
                            if (res && res.ok) {
                                var tag = (scope === "current") ? " (Current Page)" : " (All Pages)";
                                DevExpress.ui.notify("✅ PDF Report Document" + tag + " pushed to Telegram successfully!", "success", 4000);
                            } else {
                                DevExpress.ui.notify(res.message || res.description || "Failed to push PDF file.", "error", 4000);
                            }
                        },
                        error: function() {
                            DevExpress.ui.notify("Network error pushing PDF file.", "error", 4000);
                        },
                        complete: function() {
                            setCardLoading($btn, false);
                        }
                    });
                }).catch(function(err) {
                    DevExpress.ui.notify("PDF Generation Error: " + err.message, "error", 4000);
                    setCardLoading($btn, false);
                });
            }, function() {
                setCardLoading($btn, false);
            });
        }

        function executePushHtmlDocument(scope) {
            var $btn = $("#btnPushHtmlFile");
            setCardLoading($btn, true, "Generating HTML Report & Pushing to Telegram...");

            window.checkTelegramConnectionAndExecute(function() {
                var pageOnly = (scope === "current");
                var idsArr = [];
                if (pageOnly) {
                    var gridInstance = $("#gridContainer").dxDataGrid("instance");
                    if (gridInstance) {
                        idsArr = gridInstance.getVisibleRows()
                            .filter(function(r) { return r.rowType === "data" && r.data && r.data.id; })
                            .map(function(r) { return r.data.id; });
                    }
                }

                $.ajax({
                    url: "manual_push.php",
                    type: "POST",
                    data: {
                        action: "push_html_file",
                        scope: scope,
                        ids: idsArr.join(",")
                    },
                    dataType: "json",
                    success: function(res) {
                        if (res && res.ok) {
                            var tag = pageOnly ? " (Current Page)" : " (All Pages)";
                            DevExpress.ui.notify("✅ HTML Report Document" + tag + " pushed to Telegram successfully!", "success", 4000);
                        } else {
                            DevExpress.ui.notify(res.message || res.description || "Failed to push HTML file.", "error", 4000);
                        }
                    },
                    error: function() {
                        DevExpress.ui.notify("Network error pushing HTML file.", "error", 4000);
                    },
                    complete: function() {
                        setCardLoading($btn, false);
                    }
                });
            }, function() {
                setCardLoading($btn, false);
            });
        }

        function buildProductsTableCanvas(scope) {
            return new Promise(function(resolve, reject) {
                var gridInstance = $("#gridContainer").dxDataGrid("instance");
                if (!gridInstance) {
                    return reject(new Error("Grid instance not found"));
                }

                var fetchRows;
                if (scope === "current") {
                    var visibleRows = gridInstance.getVisibleRows()
                        .filter(function(r) { return r.rowType === "data"; })
                        .map(function(r) { return r.data; });
                    fetchRows = Promise.resolve(visibleRows);
                } else {
                    fetchRows = Promise.resolve(gridInstance.getDataSource().store().load());
                }

                fetchRows.then(function(rows) {
                    if (!rows || rows.length === 0) {
                        return reject(new Error("No table rows found to render picture."));
                    }

                    var totalValuation = 0;
                    var totalQty = 0;

                    var tableRowsHtml = rows.map(function(item, idx) {
                        var price = parseFloat(item.price || 0);
                        var qty = parseInt(item.quantity || 0, 10);
                        var val = price * qty;
                        totalValuation += val;
                        totalQty += qty;

                        var bg = (idx % 2 === 0) ? "background: rgba(30, 41, 59, 0.45);" : "background: rgba(15, 23, 42, 0.45);";
                        return '<tr style="' + bg + ' border-bottom: 1px solid rgba(255, 255, 255, 0.08);">' +
                            '<td style="padding: 10px 12px; font-family: monospace; color: #94a3b8;">#' + (item.id || '') + '</td>' +
                            '<td style="padding: 10px 12px; font-weight: 600; color: #38bdf8;">' + (item.product_code || '') + '</td>' +
                            '<td style="padding: 10px 12px; font-weight: 600; color: #f8fafc;">' + (item.product_name || '') + '</td>' +
                            '<td style="padding: 10px 12px; color: #cbd5e1;">' + (item.category_name || 'N/A') + '</td>' +
                            '<td style="padding: 10px 12px; text-align: right; color: #f8fafc;">$' + price.toFixed(2) + '</td>' +
                            '<td style="padding: 10px 12px; text-align: center; font-weight: 600; color: #38bdf8;">' + qty + '</td>' +
                            '<td style="padding: 10px 12px; text-align: right; font-weight: 700; color: #4ade80;">$' + val.toFixed(2) + '</td>' +
                            '</tr>';
                    }).join('');

                    var nowStr = new Date().toLocaleString();
                    var scopeStr = (scope === "current") ? "CURRENT PAGE" : "ALL PAGES";

                    var html = '<div id="tempTableReportCanvas" style="position: absolute; left: -9999px; top: 0; width: 1100px; background: #0f172a; color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif; padding: 28px; border-radius: 12px; box-sizing: border-box; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">' +
                        '<div style="display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 2px solid #38bdf8; padding-bottom: 14px; margin-bottom: 20px;">' +
                            '<div>' +
                                '<div style="font-size: 22px; font-weight: 800; color: #38bdf8; letter-spacing: 0.5px;">INVENTORY PRODUCTS DATA TABLE</div>' +
                                '<div style="font-size: 12px; color: #94a3b8; margin-top: 4px;">Scope: <strong>' + scopeStr + '</strong> | Generated: ' + nowStr + '</div>' +
                            '</div>' +
                            '<div style="text-align: right;">' +
                                '<div style="font-size: 13px; color: #94a3b8;">Total Products: <strong style="color: #f8fafc;">' + rows.length + '</strong></div>' +
                                '<div style="font-size: 15px; font-weight: 700; color: #4ade80; margin-top: 2px;">Total Valuation: $' + totalValuation.toFixed(2) + '</div>' +
                            '</div>' +
                        '</div>' +
                        '<table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;">' +
                            '<thead>' +
                                '<tr style="background: #1e293b; color: #38bdf8; border-bottom: 2px solid #334155;">' +
                                    '<th style="padding: 12px; font-weight: 700;">ID</th>' +
                                    '<th style="padding: 12px; font-weight: 700;">Code</th>' +
                                    '<th style="padding: 12px; font-weight: 700;">Product Name</th>' +
                                    '<th style="padding: 12px; font-weight: 700;">Category</th>' +
                                    '<th style="padding: 12px; font-weight: 700; text-align: right;">Price</th>' +
                                    '<th style="padding: 12px; font-weight: 700; text-align: center;">Qty</th>' +
                                    '<th style="padding: 12px; font-weight: 700; text-align: right;">Valuation</th>' +
                                '</tr>' +
                            '</thead>' +
                            '<tbody>' +
                                tableRowsHtml +
                            '</tbody>' +
                            '<tfoot>' +
                                '<tr style="background: #1e293b; border-top: 2px solid #38bdf8; font-weight: 700; color: #f8fafc;">' +
                                    '<td colspan="4" style="padding: 14px 12px; color: #38bdf8;">TOTAL SUMMARY (' + rows.length + ' Items)</td>' +
                                    '<td style="padding: 14px 12px; text-align: right; color: #94a3b8;">-</td>' +
                                    '<td style="padding: 14px 12px; text-align: center; color: #38bdf8;">' + totalQty + '</td>' +
                                    '<td style="padding: 14px 12px; text-align: right; color: #4ade80; font-size: 14px;">$' + totalValuation.toFixed(2) + '</td>' +
                                '</tr>' +
                            '</tfoot>' +
                        '</table>' +
                    '</div>';

                    var $wrapper = $(html).appendTo("body");

                    html2canvas($wrapper[0], {
                        scale: 2,
                        useCORS: true,
                        backgroundColor: "#0f172a",
                        logging: false
                    }).then(function(canvas) {
                        $wrapper.remove();
                        resolve(canvas);
                    }).catch(function(err) {
                        $wrapper.remove();
                        reject(err);
                    });
                }).catch(reject);
            });
        }

        function executePushImageDocument(scope) {
            var $btn = $("#btnPushImageFile");
            setCardLoading($btn, true, "Rendering Picture & Pushing to Telegram...");

            window.checkTelegramConnectionAndExecute(function() {
                buildProductsTableCanvas(scope).then(function(canvas) {
                    canvas.toBlob(function(blob) {
                        if (!blob) {
                            DevExpress.ui.notify("Failed to render table picture.", "error", 3000);
                            setCardLoading($btn, false);
                            return;
                        }

                        var formData = new FormData();
                        var today = new Date();
                        var yyyy = today.getFullYear();
                        var mm = String(today.getMonth() + 1).padStart(2, '0');
                        var dd = String(today.getDate()).padStart(2, '0');
                        var filename = "Products_Table_Report_" + yyyy + "-" + mm + "-" + dd + ".jpg";

                        formData.append("action", "push_image_file");
                        formData.append("scope", scope);
                        formData.append("file", blob, filename);

                        $.ajax({
                            url: "manual_push.php",
                            type: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                            dataType: "json",
                            success: function(res) {
                                if (res && res.ok) {
                                    var tag = (scope === "current") ? " (Current Page)" : " (All Pages)";
                                    DevExpress.ui.notify("✅ Table Picture Report (.JPG)" + tag + " pushed to Telegram!", "success", 4000);
                                } else {
                                    DevExpress.ui.notify(res.message || res.description || "Failed to push table picture.", "error", 4000);
                                }
                            },
                            error: function() {
                                DevExpress.ui.notify("Network error pushing table picture.", "error", 4000);
                            },
                            complete: function() {
                                setCardLoading($btn, false);
                            }
                        });
                    }, "image/jpeg", 0.95);
                }).catch(function(err) {
                    DevExpress.ui.notify("Table Picture Render Error: " + (err.message || err), "error", 4000);
                    setCardLoading($btn, false);
                });
            }, function() {
                setCardLoading($btn, false);
            });
        }

        // Open Dedicated Document Push Modal from purple document icon button
        $("#openPushExcelPdfBtn").on("click", function(e) {
            e.preventDefault();
            window.checkTelegramConnectionAndExecute(function() {
                $("#documentPushModal").css("display", "flex").hide().fadeIn(120);
            });
        });

        // Push Modal Card Clicks
        $("#btnPushExcelFile").on("click", function(e) {
            e.preventDefault();
            var scope = $("input[name='pushDocumentScope']:checked").val() || "all";
            executePushExcelDocument(scope);
        });

        $("#btnPushCsvFile").on("click", function(e) {
            e.preventDefault();
            var scope = $("input[name='pushDocumentScope']:checked").val() || "all";
            executePushCsvDocument(scope);
        });

        $("#btnPushPdfFile").on("click", function(e) {
            e.preventDefault();
            var scope = $("input[name='pushDocumentScope']:checked").val() || "all";
            executePushPdfDocument(scope);
        });

        $("#btnPushHtmlFile").on("click", function(e) {
            e.preventDefault();
            var scope = $("input[name='pushDocumentScope']:checked").val() || "all";
            executePushHtmlDocument(scope);
        });

        $("#btnPushImageFile").on("click", function(e) {
            e.preventDefault();
            var scope = $("input[name='pushDocumentScope']:checked").val() || "all";
            executePushImageDocument(scope);
        });

        // Export Menu Item Clicks
        $("#btnExportPushExcelAll, #btnExportPushCsvAll").on("click", function(e) {
            e.preventDefault();
            $("#masterExportMenu").removeClass("open");
            executePushExcelDocument("all");
        });
        $("#btnExportPushExcelCurrent, #btnExportPushCsvCurrent").on("click", function(e) {
            e.preventDefault();
            $("#masterExportMenu").removeClass("open");
            executePushExcelDocument("current");
        });
        $("#btnExportPushPdfAll").on("click", function(e) {
            e.preventDefault();
            $("#masterExportMenu").removeClass("open");
            executePushPdfDocument("all");
        });
        $("#btnExportPushPdfCurrent").on("click", function(e) {
            e.preventDefault();
            $("#masterExportMenu").removeClass("open");
            executePushPdfDocument("current");
        });
        $("#btnExportPushImageAll").on("click", function(e) {
            e.preventDefault();
            $("#masterExportMenu").removeClass("open");
            executePushImageDocument("all");
        });
        $("#btnExportPushImageCurrent").on("click", function(e) {
            e.preventDefault();
            $("#masterExportMenu").removeClass("open");
            executePushImageDocument("current");
        });

        $("#btnDownloadImageJpg").on("click", function(e) {
            e.preventDefault();
            $("#masterExportMenu").removeClass("open");
            DevExpress.ui.notify("📸 Rendering Table Data Picture (JPG)...", "info", 2000);
            var scope = $("input[name='pushDocumentScope']:checked").val() || "all";
            buildProductsTableCanvas(scope).then(function(canvas) {
                var link = document.createElement("a");
                link.download = "Products_Table_Report_" + Date.now() + ".jpg";
                link.href = canvas.toDataURL("image/jpeg", 0.95);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }).catch(function(err) {
                DevExpress.ui.notify("Error rendering table picture: " + err.message, "error", 3000);
            });
        });

        // Tab Switch Handlers
        $(".push-tab-btn").on("click", function() {
            var targetTab = $(this).data("tab");
            $(".push-tab-btn").removeClass("active");
            $(this).addClass("active");
            $(".push-tab-content").removeClass("active");
            $("#" + targetTab).addClass("active");
        });

        // Close Kebab Dropdown Menu on Outside Click
        $(document).on("click", function(e) {
            if (!$(e.target).closest(".mobile-kebab-wrapper").length) {
                $(".kebab-dropdown-menu").removeClass("show");
                $(".kebab-trigger-btn").removeClass("open");
            }
        });
    });
    </script>

    <!-- Manual Telegram Push Modal -->
    <div id="pushModal" class="custom-modal-backdrop" style="display: none;">
        <div class="custom-modal-content" style="max-width: 580px; max-height: 85vh; overflow-y: auto;">
            <div class="custom-modal-header">
                <h3><i class="fa-brands fa-telegram telegram-icon"></i> Telegram Hub <a href="https://t.me/enginebi_bot" target="_blank" style="color: #38bdf8; text-decoration: none; font-size: 14px; margin-left: 6px;">@enginebi_bot <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 11px;"></i></a></h3>
                <button type="button" class="custom-modal-close" id="closePushModalBtn">&times;</button>
            </div>
            <div class="custom-modal-body">
                <!-- Dynamic Telegram Account Connection Banner -->
                <div class="join-bot-banner" style="background: linear-gradient(135deg, rgba(56, 189, 248, 0.12), rgba(99, 102, 241, 0.18)); border: 1px solid rgba(99, 102, 241, 0.35); border-radius: 14px; padding: 18px 20px; margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 12px;">
                        <div style="display: flex; align-items: center; gap: 14px;">
                            <div style="width: 44px; height: 44px; background: rgba(56, 189, 248, 0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #38bdf8; font-size: 22px; border: 1px solid rgba(56, 189, 248, 0.3);">
                                <i class="fa-brands fa-telegram"></i>
                            </div>
                            <div>
                                <div style="font-weight: 700; color: #f8fafc; font-size: 15px; display: flex; align-items: center; gap: 8px;">
                                    Connect Telegram Account
                                    <span id="tgConnectStatusBadge" style="background: rgba(239, 68, 68, 0.2); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.3); font-size: 10px; font-weight: 700; text-transform: uppercase; padding: 2px 8px; border-radius: 10px;">Not Connected</span>
                                </div>
                                <div style="font-size: 12px; color: #94a3b8; margin-top: 3px;" id="tgConnectDescText">
                                    Click <strong>Connect Telegram</strong> to link your Telegram account to this user account.
                                </div>
                            </div>
                        </div>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;" id="tgConnectActionsGroup">
                            <button type="button" id="btnQuickRefreshTelegram" title="Quick Refresh Status" style="background: rgba(255, 255, 255, 0.08); color: #cbd5e1; border: 1px solid rgba(255, 255, 255, 0.15); font-size: 12px; font-weight: 600; padding: 8px 12px; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s ease;">
                                <i class="fa-solid fa-arrows-rotate" style="font-size: 12px;"></i> Refresh
                            </button>
                            <button type="button" id="btnConnectTelegramAccount" style="background: #38bdf8; color: #0f172a; font-weight: 700; font-size: 13px; padding: 10px 18px; border-radius: 8px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25); transition: all 0.2s ease;">
                                <i class="fa-brands fa-telegram" style="font-size: 16px;"></i> Connect Telegram
                            </button>
                            <button type="button" id="btnDisconnectTelegramAccount" style="display: none; background: rgba(239, 68, 68, 0.15); color: #fca5a5; font-weight: 600; font-size: 12px; padding: 8px 14px; border-radius: 8px; border: 1px solid rgba(239, 68, 68, 0.3); cursor: pointer;">
                                <i class="fa-solid fa-plug-circle-xmark me-1"></i> Disconnect
                            </button>
                        </div>
                    </div>

                    <!-- Connection Code Banner (Hidden until generated) -->
                    <div id="tgCodeBox" style="display: none; background: rgba(15, 20, 28, 0.7); border: 1px dashed rgba(56, 189, 248, 0.4); border-radius: 10px; padding: 12px 16px; margin-top: 10px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
                            <div>
                                <span style="font-size: 12px; color: #94a3b8;">One-Time Connection Code:</span>
                                <span style="font-size: 20px; font-weight: 800; color: #38bdf8; font-family: monospace; letter-spacing: 2px; margin-left: 8px;" id="tgCodeDisplay">------</span>
                            </div>
                            <a href="#" id="tgDeepLinkBtn" target="_blank" style="background: #6366f1; color: #ffffff; font-weight: 600; font-size: 12px; padding: 8px 14px; border-radius: 6px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                                Open Bot & Press START <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 10px;"></i>
                            </a>
                        </div>
                    </div>

                    </div>
                </div>

                <!-- 2-Tab Navigation Bar -->
                <div class="push-modal-tabs">
                    <button type="button" class="push-tab-btn active" data-tab="tab-mode-setup">
                        <i class="fa-solid fa-sliders"></i> Mode Setup
                    </button>
                    <button type="button" class="push-tab-btn" data-tab="tab-reports-data">
                        <i class="fa-solid fa-paper-plane"></i> Push Reports
                    </button>
                </div>

                <!-- TAB 1: Auto & Manual Setup -->
                <div id="tab-mode-setup" class="push-tab-content active">
                    <!-- Auto/Manual Toggle Card -->
                    <div class="push-mode-card">
                        <div class="push-mode-left">
                            <div class="push-mode-icon auto-icon">
                                <i class="fa-solid fa-bolt"></i>
                            </div>
                            <div class="push-mode-info">
                                <div class="push-mode-title">
                                    Auto Telegram Notifications
                                    <span id="pushModeBadge" class="mode-badge auto-on">Active</span>
                                </div>
                                <div class="push-mode-subtitle" id="pushModeSubtitle">
                                    Real-time alerts on Add, Update & Delete
                                </div>
                            </div>
                        </div>
                        <label class="toggle-switch-container" for="toggleAutoPush">
                            <input type="checkbox" id="toggleAutoPush" class="toggle-switch-checkbox" checked>
                            <span class="toggle-switch-slider"></span>
                        </label>
                    </div>

                    <!-- Mode Comparison -->
                    <div class="mode-comparison">
                        <div class="mode-card auto-mode active">
                            <div class="mode-card-header">
                                <i class="fa-solid fa-bolt"></i> Auto Mode
                            </div>
                            <ul class="mode-card-list">
                                <li><i class="fa-solid fa-check"></i> Instant alerts on create</li>
                                <li><i class="fa-solid fa-check"></i> Instant alerts on update</li>
                                <li><i class="fa-solid fa-check"></i> Instant alerts on delete</li>
                            </ul>
                        </div>
                        <div class="mode-card manual-mode">
                            <div class="mode-card-header">
                                <i class="fa-solid fa-hand-pointer"></i> Manual Mode
                            </div>
                            <ul class="mode-card-list">
                                <li><i class="fa-solid fa-xmark"></i> No automatic alerts</li>
                                <li><i class="fa-solid fa-check"></i> Push via Reports tab</li>
                                <li><i class="fa-solid fa-check"></i> Push via row actions</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: Push Data Reports -->
                <div id="tab-reports-data" class="push-tab-content">
                    <div class="push-reports-grid">
                        <!-- Report Button 1 -->
                        <button type="button" class="push-report-card" id="btnPushAdded">
                            <div class="report-icon bg-green"><i class="fa-solid fa-plus"></i></div>
                            <div class="report-info">
                                <span class="report-title">Recently Added</span>
                                <span class="report-desc">New products & categories</span>
                            </div>
                            <i class="fa-solid fa-chevron-right report-arrow"></i>
                        </button>

                        <!-- Report Button 2 -->
                        <button type="button" class="push-report-card" id="btnPushUpdated">
                            <div class="report-icon bg-amber"><i class="fa-solid fa-pen"></i></div>
                            <div class="report-info">
                                <span class="report-title">Recently Updated</span>
                                <span class="report-desc">Modified products & categories</span>
                            </div>
                            <i class="fa-solid fa-chevron-right report-arrow"></i>
                        </button>

                        <!-- Report Button 3 -->
                        <button type="button" class="push-report-card" id="btnPushLowStock">
                            <div class="report-icon bg-red"><i class="fa-solid fa-triangle-exclamation"></i></div>
                            <div class="report-info">
                                <span class="report-title">Low Stock Warning</span>
                                <span class="report-desc">Items with quantity &le; 5</span>
                            </div>
                            <i class="fa-solid fa-chevron-right report-arrow"></i>
                        </button>

                        <!-- Report Button 3b: Out of Stock -->
                        <button type="button" class="push-report-card" id="btnPushOutOfStock">
                            <div class="report-icon bg-black"><i class="fa-solid fa-circle-xmark"></i></div>
                            <div class="report-info">
                                <span class="report-title">Out of Stock</span>
                                <span class="report-desc">Items with 0 units</span>
                            </div>
                            <i class="fa-solid fa-chevron-right report-arrow"></i>
                        </button>

                        <!-- Report Button 4 -->
                        <button type="button" class="push-report-card" id="btnPushValuation">
                            <div class="report-icon bg-purple"><i class="fa-solid fa-sack-dollar"></i></div>
                            <div class="report-info">
                                <span class="report-title">Valuation Report</span>
                                <span class="report-desc">Asset value & pricing</span>
                            </div>
                            <i class="fa-solid fa-chevron-right report-arrow"></i>
                        </button>

                        <!-- Report Button 5 -->
                        <button type="button" class="push-report-card" id="btnPushSummary">
                            <div class="report-icon bg-blue"><i class="fa-solid fa-chart-pie"></i></div>
                            <div class="report-info">
                                <span class="report-title">Full Summary</span>
                                <span class="report-desc">Complete inventory overview</span>
                            </div>
                            <i class="fa-solid fa-chevron-right report-arrow"></i>
                        </button>

                        <!-- Report Button 6: Custom -->
                        <div class="push-report-card custom-report" style="grid-column: 1 / -1;">
                            <div class="report-icon bg-cyan"><i class="fa-solid fa-comment-dots"></i></div>
                            <div class="report-info">
                                <span class="report-title">Custom Message</span>
                                <input type="text" id="customPushMessageCustom" class="custom-report-input" placeholder="Type message & click push...">
                            </div>
                            <button type="button" class="custom-push-btn" id="btnPushCustom">
                                <i class="fa-solid fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dedicated Push Documents to Telegram Modal -->
    <div id="documentPushModal" class="custom-modal-backdrop" style="display: none;">
        <div class="custom-modal-content" style="max-width: 580px; max-height: 85vh; overflow-y: auto;">
            <div class="custom-modal-header">
                <h3><i class="fa-solid fa-file-arrow-up" style="color: #38bdf8;"></i> Push Documents to Telegram</h3>
                <button type="button" class="custom-modal-close" id="closeDocumentPushModalBtn">&times;</button>
            </div>
            <div class="custom-modal-body">
                <!-- Document Push Scope Selector -->
                <div class="push-report-card scope-selector-card" style="background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255, 255, 255, 0.12); padding: 14px 18px; border-radius: 12px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 16px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div class="report-icon" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8; width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center;"><i class="fa-solid fa-filter"></i></div>
                        <div>
                            <span style="display: block; font-weight: 700; color: #f8fafc; font-size: 14px;">Document Push Scope</span>
                            <span style="display: block; font-size: 12px; color: #94a3b8;">Choose whether to send all items or active grid page items</span>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 14px;">
                        <label style="font-size: 13px; color: #f8fafc; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-weight: 600;">
                            <input type="radio" name="pushDocumentScope" value="all" checked style="accent-color: #38bdf8;"> All Pages
                        </label>
                        <label style="font-size: 13px; color: #f8fafc; cursor: pointer; display: inline-flex; align-items: gap: 6px; font-weight: 600;">
                            <input type="radio" name="pushDocumentScope" value="current" style="accent-color: #38bdf8;"> Current Page
                        </label>
                    </div>
                </div>

                <!-- Document Push Grid (Excel, CSV, PDF, Picture) -->
                <div class="push-reports-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 12px;">
                    <!-- Push Excel File -->
                    <button type="button" class="push-report-card" id="btnPushExcelFile" style="border-left: 4px solid #107c41;">
                        <div class="report-icon" style="background: rgba(16, 124, 65, 0.15); color: #107c41;"><i class="fa-solid fa-file-excel"></i></div>
                        <div class="report-info">
                            <span class="report-title">Push Excel Document File</span>
                            <span class="report-desc">Send formatted .xlsx spreadsheet file to Telegram</span>
                        </div>
                        <i class="fa-solid fa-chevron-right report-arrow"></i>
                    </button>

                    <!-- Push PDF File -->
                    <button type="button" class="push-report-card" id="btnPushPdfFile" style="border-left: 4px solid #e3242b;">
                        <div class="report-icon" style="background: rgba(227, 36, 43, 0.15); color: #e3242b;"><i class="fa-solid fa-file-pdf"></i></div>
                        <div class="report-info">
                            <span class="report-title">Push PDF Document File</span>
                            <span class="report-desc">Send formatted .pdf report file to Telegram</span>
                        </div>
                        <i class="fa-solid fa-chevron-right report-arrow"></i>
                    </button>

                    <!-- Push HTML File -->
                    <button type="button" class="push-report-card" id="btnPushHtmlFile" style="border-left: 4px solid #2563eb;">
                        <div class="report-icon" style="background: rgba(37, 99, 235, 0.15); color: #2563eb;"><i class="fa-solid fa-code"></i></div>
                        <div class="report-info">
                            <span class="report-title">Push HTML Document File</span>
                            <span class="report-desc">Send interactive .html report file to Telegram</span>
                        </div>
                        <i class="fa-solid fa-chevron-right report-arrow"></i>
                    </button>

                    <!-- Push Page Picture File (JPG) -->
                    <button type="button" class="push-report-card" id="btnPushImageFile" style="border-left: 4px solid #f59e0b;">
                        <div class="report-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;"><i class="fa-solid fa-file-image"></i></div>
                        <div class="report-info">
                            <span class="report-title">Push Page Picture File</span>
                            <span class="report-desc">Send high-resolution .jpg screenshot picture to Telegram</span>
                        </div>
                        <i class="fa-solid fa-chevron-right report-arrow"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create User Modal Dialog -->
    <div class="auth-modal-overlay" id="createUserModal">
        <div class="auth-modal-card">
            <div class="auth-modal-header">
                <h3><i class="fa-solid fa-user-plus"></i> Create New User Account</h3>
                <button type="button" class="auth-modal-close" id="closeCreateUserModalBtn">&times;</button>
            </div>
            <div class="auth-modal-body">
                <div class="modal-alert modal-alert-error" id="createUserModalError"></div>
                <div class="modal-alert modal-alert-success" id="createUserModalSuccess"></div>

                <form id="createUserModalForm">
                    <div class="modal-form-group">
                        <label for="modal_user_name">Full Name</label>
                        <div class="modal-input-wrapper">
                            <input type="text" class="modal-form-control" id="modal_user_name" name="name" placeholder="e.g. Jane Doe" required>
                            <i class="fa-solid fa-id-card input-icon"></i>
                        </div>
                    </div>

                    <div class="modal-form-group">
                        <label for="modal_user_username">Username</label>
                        <div class="modal-input-wrapper">
                            <input type="text" class="modal-form-control" id="modal_user_username" name="username" placeholder="e.g. janedoe" required>
                            <i class="fa-solid fa-user input-icon"></i>
                        </div>
                    </div>

                    <div class="modal-form-group">
                        <label for="modal_user_email">Email Address</label>
                        <div class="modal-input-wrapper">
                            <input type="email" class="modal-form-control" id="modal_user_email" name="email" placeholder="e.g. jane@example.com" required>
                            <i class="fa-solid fa-envelope input-icon"></i>
                        </div>
                    </div>

                    <div class="modal-form-group">
                        <label for="modal_user_password">Password</label>
                        <div class="modal-input-wrapper">
                            <input type="password" class="modal-form-control" id="modal_user_password" name="password" placeholder="Min 4 characters" required minlength="4">
                            <i class="fa-solid fa-lock input-icon"></i>
                        </div>
                    </div>

                    <button type="submit" class="modal-btn-submit">
                        <i class="fa-solid fa-user-check"></i> Save & Create Account
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('createUserModal');
        const openBtn = document.getElementById('openCreateUserModalBtn');
        const closeBtn = document.getElementById('closeCreateUserModalBtn');
        const form = document.getElementById('createUserModalForm');
        const errDiv = document.getElementById('createUserModalError');
        const succDiv = document.getElementById('createUserModalSuccess');

        if (openBtn && modal) {
            openBtn.addEventListener('click', function() {
                modal.classList.add('active');
                errDiv.style.display = 'none';
                succDiv.style.display = 'none';
                form.reset();
            });
        }

        if (closeBtn && modal) {
            closeBtn.addEventListener('click', function() {
                modal.classList.remove('active');
            });
        }

        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.classList.remove('active');
                }
            });
        }

        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                errDiv.style.display = 'none';
                succDiv.style.display = 'none';

                const submitBtn = form.querySelector('button[type="submit"]');
                const origHtml = submitBtn ? submitBtn.innerHTML : '';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.classList.add("btn-loading");
            LoadingOverlay.show("Saving...");
                }

                const formData = new FormData(form);
                fetch('create_user.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        succDiv.textContent = data.message || 'User created successfully!';
                        succDiv.style.display = 'block';
                        form.reset();
                        setTimeout(() => {
                            modal.classList.remove('active');
                        }, 1500);
                    } else {
                        errDiv.textContent = data.message || 'Error creating user.';
                        errDiv.style.display = 'block';
                    }
                })
                .catch(err => {
                    errDiv.textContent = 'Server error or invalid response.';
                    errDiv.style.display = 'block';
                })
                .finally(() => {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = origHtml;
                    }
                });
            });
        }

        // --- TELEGRAM BOT DYNAMIC CONNECTION HANDLERS ---
        const btnConnect = document.getElementById('btnConnectTelegramAccount');
        const btnDisconnect = document.getElementById('btnDisconnectTelegramAccount');
        const btnRefresh = document.getElementById('btnQuickRefreshTelegram');
        const statusBadge = document.getElementById('tgConnectStatusBadge');
        const descText = document.getElementById('tgConnectDescText');
        const codeBox = document.getElementById('tgCodeBox');
        const codeDisplay = document.getElementById('tgCodeDisplay');
        const deepLinkBtn = document.getElementById('tgDeepLinkBtn');
        const customBotForm = document.getElementById('customBotConfigForm');
        const cfgToken = document.getElementById('cfg_bot_token');
        const cfgUsername = document.getElementById('cfg_bot_username');
        let tgPollTimer = null;
        let wasConnectingTelegram = false;

        window.lastTelegramCheckTime = 0;
        window.isTelegramConnected = false;

        window.checkTelegramConnectionAndExecute = function(onConnected, onNotConnected, forceRefresh) {
            var now = Date.now();
            if (!forceRefresh && window.isTelegramConnected && (now - window.lastTelegramCheckTime < 25000)) {
                if (typeof onConnected === 'function') onConnected();
                return Promise.resolve({ success: true, is_connected: true });
            }

            return fetch('telegram_settings.php?action=get&_t=' + now)
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data && data.success && data.is_connected) {
                        window.isTelegramConnected = true;
                        window.lastTelegramCheckTime = Date.now();
                        if (typeof onConnected === 'function') {
                            onConnected();
                        }
                    } else {
                        window.isTelegramConnected = false;
                        window.lastTelegramCheckTime = 0;
                        if (typeof onNotConnected === 'function') {
                            onNotConnected();
                        }
                        if (typeof fetchTelegramStatus === 'function') {
                            fetchTelegramStatus();
                        }
                        if (typeof loadPushSettings === 'function') {
                            loadPushSettings();
                        }
                        $("#pushModal").css("display", "flex").hide().fadeIn(120);
                        if (window.DevExpress && DevExpress.ui && DevExpress.ui.notify) {
                            DevExpress.ui.notify("⚠️ Telegram is not connected. Please click 'Connect Telegram' to link your account.", "info", 4000);
                        }
                    }
                })
                .catch(function() {
                    if (window.isTelegramConnected) {
                        if (typeof onConnected === 'function') onConnected();
                    } else {
                        window.isTelegramConnected = false;
                        window.lastTelegramCheckTime = 0;
                        if (typeof onNotConnected === 'function') {
                            onNotConnected();
                        }
                        if (typeof fetchTelegramStatus === 'function') {
                            fetchTelegramStatus();
                        }
                        $("#pushModal").css("display", "flex").hide().fadeIn(120);
                        if (window.DevExpress && DevExpress.ui && DevExpress.ui.notify) {
                            DevExpress.ui.notify("⚠️ Telegram is not connected. Please click 'Connect Telegram' to link your account.", "info", 4000);
                        }
                    }
                });
        };

        function fetchTelegramStatus() {
            const icon = btnRefresh ? btnRefresh.querySelector('i') : null;
            if (icon) icon.classList.add('fa-spin');

            return fetch('telegram_settings.php?action=get&_t=' + Date.now())
                .then(r => r.json())
                .then(data => {
                    if (icon) icon.classList.remove('fa-spin');
                    if (data.success) {
                        if (cfgToken) cfgToken.value = data.bot_token || '';
                        if (cfgUsername) cfgUsername.value = data.bot_username || '';

                        if (data.is_connected) {
                            window.isTelegramConnected = true;
                            statusBadge.textContent = 'CONNECTED';
                            statusBadge.style.background = 'rgba(34, 197, 94, 0.2)';
                            statusBadge.style.color = '#86efac';
                            statusBadge.style.borderColor = 'rgba(34, 197, 94, 0.3)';
                            descText.innerHTML = 'Linked to Telegram Chat ID: <code>' + data.chat_id + '</code> (@' + data.bot_username + ')';
                            
                            if (btnConnect) btnConnect.style.display = 'none';
                            if (btnDisconnect) btnDisconnect.style.display = 'inline-flex';
                            if (codeBox) codeBox.style.display = 'none';

                            if (wasConnectingTelegram || tgPollTimer !== null) {
                                wasConnectingTelegram = false;
                                if ($("#pushModal").is(":visible")) {
                                    $("#pushModal").fadeOut(200);
                                }
                                if (window.DevExpress && DevExpress.ui && DevExpress.ui.notify) {
                                    DevExpress.ui.notify("✅ Telegram account linked successfully!", "success", 4000);
                                }
                            }

                            if (tgPollTimer) {
                                clearInterval(tgPollTimer);
                                tgPollTimer = null;
                            }
                        } else {
                            window.isTelegramConnected = false;
                            statusBadge.textContent = 'NOT CONNECTED';
                            statusBadge.style.background = 'rgba(239, 68, 68, 0.2)';
                            statusBadge.style.color = '#fca5a5';
                            statusBadge.style.borderColor = 'rgba(239, 68, 68, 0.3)';
                            descText.innerHTML = 'Click <strong>Connect Telegram</strong> to link your Telegram account.';
                            
                            if (btnConnect) btnConnect.style.display = 'inline-flex';
                            if (btnDisconnect) btnDisconnect.style.display = 'none';
                        }
                    }
                })
                .catch(err => {
                    if (icon) icon.classList.remove('fa-spin');
                });
        }

        function startAutoPollingTelegram() {
            if (tgPollTimer) clearInterval(tgPollTimer);
            tgPollTimer = setInterval(function() {
                fetchTelegramStatus();
            }, 2000);
        }

        // Fetch immediately on page load
        fetchTelegramStatus();

        if (btnRefresh) {
            btnRefresh.addEventListener('click', function() {
                fetchTelegramStatus();
            });
        }

        const openPushBtn = document.getElementById('openPushModalBtn');
        if (openPushBtn) {
            openPushBtn.addEventListener('click', fetchTelegramStatus);
        }

        if (btnConnect) {
            btnConnect.addEventListener('click', function() {
                btnConnect.classList.add("btn-loading");
                if (window.LoadingOverlay && LoadingOverlay.show) LoadingOverlay.show("Connecting Telegram...");
                fetch('telegram_settings.php?action=generate_code', { method: 'POST' })
                    .then(r => r.json())
                    .then(data => {
                        if (window.LoadingOverlay && LoadingOverlay.hide) LoadingOverlay.hide();
                        btnConnect.classList.remove("btn-loading");
                        btnConnect.innerHTML = '<i class="fa-brands fa-telegram" style="font-size: 16px;"></i> Connect Telegram';
                        if (data.success && data.deep_link) {
                            wasConnectingTelegram = true;
                            if (codeDisplay) codeDisplay.textContent = data.code;
                            if (deepLinkBtn) deepLinkBtn.href = data.deep_link;
                            if (codeBox) codeBox.style.display = 'block';
                            window.open(data.deep_link, '_blank');
                            startAutoPollingTelegram();
                        } else {
                            alert("Error: " + (data.message || "Failed to generate connection code."));
                        }
                    })
                    .catch(err => {
                        if (window.LoadingOverlay && LoadingOverlay.hide) LoadingOverlay.hide();
                        btnConnect.classList.remove("btn-loading");
                        btnConnect.innerHTML = '<i class="fa-brands fa-telegram" style="font-size: 16px;"></i> Connect Telegram';
                        console.error("Telegram Connect Error:", err);
                        alert("Connection failed. Please refresh and try again.");
                    });
            });
        }

        window.addEventListener('focus', function() {
            if (wasConnectingTelegram || tgPollTimer !== null) {
                fetchTelegramStatus();
            }
        });
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden && (wasConnectingTelegram || tgPollTimer !== null)) {
                fetchTelegramStatus();
            }
        });

        if (btnDisconnect) {
            btnDisconnect.addEventListener('click', function() {
                showCustomConfirmDialog({
                    title: "Disconnect Telegram Account",
                    message: "Are you sure you want to disconnect your Telegram account? You will stop receiving real-time stock alerts.",
                    confirmText: "Disconnect",
                    cancelText: "Cancel",
                    icon: "fa-plug-circle-xmark",
                    iconColor: "#ef4444",
                    confirmBg: "linear-gradient(135deg, #ef4444, #dc2626)",
                    onConfirm: function() {
                        fetch('telegram_settings.php?action=disconnect', { method: 'POST' })
                            .then(r => r.json())
                            .then(data => {
                                if (data.success) {
                                    fetchTelegramStatus();
                                    if (window.DevExpress && DevExpress.ui && DevExpress.ui.notify) {
                                        DevExpress.ui.notify("Telegram account disconnected.", "info", 3000);
                                    }
                                }
                            });
                    }
                });
            });
        }

        if (customBotForm) {
            customBotForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const token = cfgToken.value.trim();
                const username = cfgUsername.value.trim();

                fetch('telegram_settings.php?action=save_bot', {
                    method: 'POST',
                    body: JSON.stringify({ bot_token: token, bot_username: username }),
                    headers: { 'Content-Type': 'application/json' }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert('Custom Telegram Bot saved successfully!');
                        fetchTelegramStatus();
                    }
                });
            });
        }
    });
    </script>

<script>
// Disabled Loading Overlay per user request
var LoadingOverlay = window.LoadingOverlay || { show() {}, hide() {} };

window.setCardLoading = window.setCardLoading || function($btn, isLoading, statusText) {
    if (!$btn || !$btn.length) return;
    if (isLoading) {
        if (!$btn.data("orig-html")) {
            $btn.data("orig-html", $btn.html());
        }
        $btn.addClass("is-loading btn-loading").prop("disabled", true);
        var $arrow = $btn.find(".report-arrow");
        if ($arrow.length) {
            $arrow.removeClass("fa-chevron-right").addClass("fa-spinner fa-spin").css({ "color": "#38bdf8", "font-size": "16px" });
        }
        if (statusText) {
            $btn.find(".report-desc").text(statusText);
        }
    } else {
        var origHtml = $btn.data("orig-html");
        if (origHtml) {
            $btn.html(origHtml);
            $btn.removeData("orig-html");
        }
        $btn.removeClass("is-loading btn-loading").prop("disabled", false);
    }
};

// Button loading for push buttons
$(document).on('click', '[id^="btnPush"]', function() {
    const btn = $(this);
    if (btn.hasClass('push-report-card')) return;
    const originalText = btn.html();
    btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Pushing...');
    
    setTimeout(() => {
        btn.prop('disabled', false).html(originalText);
    }, 2500);
});
</script>

</body>

</html>