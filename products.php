<?php

// Prevent the browser from caching this HTML page, so edits to the grid
// config (paging/scrolling) always take effect on reload instead of running
// a stale cached version. Safe for the JSON API too (no harmful side-effects).
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

require_once "database.php";

// API Endpoint to read product list
if (isset($_GET["action"]) && $_GET["action"] === "read") {
    header("Content-Type: application/json");
    // Prevent caching so newly added/edited/deleted rows always show up live
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: 0");
    $sql = "SELECT product.*, category.category_name FROM product LEFT JOIN category ON product.category_id = category.id ORDER BY product.id DESC";
    $result = mysqli_query($conn, $sql);
    $products = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $products[] = $row;
        }
    }
    echo json_encode($products);
    exit;
}

// API Endpoint to read categories for lookup
if (isset($_GET["action"]) && $_GET["action"] === "get_categories") {
    header("Content-Type: application/json");
    $sql = "SELECT id, category_name FROM category ORDER BY category_name ASC";
    $result = mysqli_query($conn, $sql);
    $categories = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $categories[] = $row;
        }
    }
    echo json_encode($categories);
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Product Management</title>

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
    <script src="js/app.js"></script>
    <link rel="stylesheet" href="css/style.css?v=<?php echo date('Y-m-d-H-i-s', filemtime(__DIR__ . '/css/style.css')); ?>">
</head>

<body>
    <div class="page">
        <div class="category-card">
            <div class="header">
                <h1>
                    <i class="fa-solid fa-boxes-stacked" style="font-size: 22px;"></i>
                    Products
                </h1>
                <div style="display: flex; gap: 10px;">
                    <button type="button" class="add-btn telegram-push-btn" id="openPushModalBtn">
                        <i class="fa-brands fa-telegram"></i>
                        Manual Push
                    </button>
                    <button type="button" class="add-btn" onclick="window.location.href='index.php'">
                        <i class="fa-solid fa-list"></i>
                        Manage Categories
                    </button>
                    <button type="button" class="add-btn" id="openAddModalBtn">
                        <i class="fa-solid fa-plus"></i>
                        Add Product
                    </button>
                </div>
            </div>
            <div class="options-container">
                <div class="option-item">
                    <span class="option-label">Resize Mode:</span>
                    <div id="select-resizing"></div>
                </div>
                <div class="search-and-export">
                    <div class="search-wrapper">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="searchInput" placeholder="Search...">
                    </div>
                    <div class="action-buttons-group">
                        <div class="export-wrapper" id="masterExportWrapper">
                            <button class="export-btn" id="masterExportTrigger" type="button">
                                <i class="fa-solid fa-download"></i>
                                Export
                                <i class="fa-solid fa-chevron-down" style="font-size: 11px; margin-left: 2px;"></i>
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
                        <div id="customAdvancedSearchBtn"></div>
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

    <!-- Popup container for Advanced Search -->
    <div id="advancedSearchPopup"></div>

    <script>
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

    function formatDateTime(date) {
        if (!date) return "-";
        const d = new Date(date);
        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();
        const hours = String(d.getHours()).padStart(2, '0');
        const minutes = String(d.getMinutes()).padStart(2, '0');
        const seconds = String(d.getSeconds()).padStart(2, '0');
        return `${day}/${month}/${year} ${hours}:${minutes}:${seconds}`;
    }

    $(function() {
        const resizingModes = [
            { text: "Widget", value: "widget" },
            { text: "Next Column", value: "nextColumn" }
        ];

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
        try {
            localStorage.removeItem("categoryGridStateV13");
        } catch (e) {}

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
            scrolling: {
                mode: "standard"
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
                        $.post("create_product.php", values)
                            .done(function(data) {
                                resolve(data);
                            })
                            .fail(function(xhr) {
                                var msg = "Failed to add product.";
                                if (xhr.responseJSON && xhr.responseJSON
                                    .message) {
                                    msg = xhr.responseJSON.message;
                                }
                                reject(new Error(msg));
                            });
                    });
                },
                update: function(key, values) {
                    return new Promise(function(resolve, reject) {
                        $.post("edit_product.php?id=" + key, values)
                            .done(function(data) {
                                resolve(data);
                            })
                            .fail(function(xhr) {
                                var msg = "Failed to update product.";
                                if (xhr.responseJSON && xhr.responseJSON
                                    .message) {
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
                    minWidth: 100,
                    width: 150,
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
                    minWidth: 150,
                    width: 200,
                    validationRules: [{
                        type: "required",
                        message: "Product Name is required"
                    }]
                },
                {
                    name: "category_id",
                    dataField: "category_id",
                    caption: "Category",
                    minWidth: 150,
                    width: 200,
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
                    minWidth: 100,
                    width: 120,
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
                    minWidth: 100,
                    width: 120,
                    validationRules: [{
                        type: "required",
                        message: "Quantity is required"
                    }]
                },
                {
                    name: "created_date",
                    dataField: "created_at",
                    caption: "Created Date",
                    dataType: "date",
                    format: "dd/MM/yyyy",
                    minWidth: 100,
                    width: 260,
                    maxWidth: 300,
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
                        return d.toLocaleTimeString('en-GB'); // "13:45:00"
                    },
                    calculateSortValue: function(rowData) {
                        if (!rowData.created_at) return 0;
                        const d = new Date(rowData.created_at);
                        // Convert to total seconds from midnight to sort properly
                        return d.getHours() * 3600 + d.getMinutes() * 60 + d.getSeconds();
                    },
                    minWidth: 100,
                    width: 260,
                    maxWidth: 300,
                    allowEditing: false
                },
                {
                    name: "date_created",
                    dataField: "created_at",
                    caption: "Date Created",
                    dataType: "datetime",
                    format: "dd/MM/yyyy HH:mm:ss",
                    minWidth: 100,
                    width: 260,
                    maxWidth: 300,
                    allowEditing: false
                },
                {
                    name: "formatted_date",
                    dataField: "created_at",
                    caption: "Formatted Date",
                    dataType: "date",
                    format: "dd-MMMM-yyyy",
                    width: 100,
                    minWidth: 100,
                    maxWidth: 250,
                    allowEditing: false
                },
                {
                    name: "formatted_time",
                    dataField: "created_at",
                    caption: "Formatted Time",
                    dataType: "datetime",
                    format: "hh:mm:ss a",
                    minWidth: 100,
                    width: 260,
                    maxWidth: 300,
                    allowEditing: false
                },
                {
                    name: "formatted_datetime",
                    dataField: "created_at",
                    caption: "Formatted Date & Time",
                    dataType: "datetime",
                    format: "dd-MMMM-yyyy hh:mm:ss a",
                    minWidth: 100,
                    width: 260,
                    maxWidth: 300,
                    allowEditing: false
                },
                {
                    name: "last_updated",
                    dataField: "lastupdate",
                    caption: "Last Updated",
                    dataType: "datetime",
                    minWidth: 100,
                    width: 260,
                    maxWidth: 300,
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
                    minWidth: 100,
                    width: 260,
                    maxWidth: 300,
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
                        return d.toLocaleTimeString('en-GB'); // "13:45:00"
                    },
                    calculateSortValue: function(rowData) {
                        if (!rowData.lastupdate) return 0;
                        const d = new Date(rowData.lastupdate);
                        return d.getHours() * 3600 + d.getMinutes() * 60 + d.getSeconds();
                    },
                    minWidth: 100,
                    width: 260,
                    maxWidth: 300,
                    allowEditing: false
                },
                {
                    name: "time_ago",
                    dataField: "lastupdate",
                    caption: "Time Ago",
                    dataType: "datetime",
                    minWidth: 100,
                    width: 260,
                    maxWidth: 300,
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
                    width: 115,
                    minWidth: 105,
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

                        var telegramSvg = '<i class="fa-brands fa-telegram" style="font-size: 15px;"></i>';
                        var $telegramBtn = $("<a>")
                            .addClass("dx-link dx-link-telegram")
                            .attr("title", "Push Product to Telegram")
                            .append(telegramSvg)
                            .on("click", function(e) {
                                e.preventDefault();
                                var rowData = options.data;
                                if (!rowData || !rowData.id) return;
                                var $btn = $(this);
                                $btn.css("opacity", "0.4");
                                $.ajax({
                                    url: "manual_push.php",
                                    type: "POST",
                                    dataType: "json",
                                    data: { action: "push_single_product", id: rowData.id },
                                    success: function(res) {
                                        $btn.css("opacity", "1");
                                        if (res && res.ok) {
                                            DevExpress.ui.notify("Product '" + (rowData.product_name || rowData.id) + "' pushed to Telegram!", "success", 3000);
                                        } else {
                                            DevExpress.ui.notify(res.description || "Failed to push product.", "error", 4000);
                                        }
                                    },
                                    error: function() {
                                        $btn.css("opacity", "1");
                                        DevExpress.ui.notify("Network error pushing product.", "error", 4000);
                                    }
                                });
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

                        var $wrapper = $("<div>")
                            .addClass("actions-wrapper")
                            .append($telegramBtn)
                            .append($editBtn)
                            .append($deleteBtn);

                        container.append($wrapper);
                    }
                }
            ],
            editing: {
                mode: "popup",
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
                    }
                },
                form: {
                    colCount: 1,
                    items: [{
                            dataField: "product_code",
                            editorType: "dxTextBox",
                            editorOptions: {
                                placeholder: "Enter product code"
                            }
                        },
                        {
                            dataField: "product_name",
                            editorType: "dxTextBox",
                            editorOptions: {
                                placeholder: "Enter product name"
                            }
                        },
                        {
                            dataField: "category_id",
                            editorType: "dxSelectBox",
                            editorOptions: {
                                placeholder: "Select category"
                            }
                        },
                        {
                            dataField: "price",
                            editorType: "dxNumberBox",
                            editorOptions: {
                                placeholder: "Enter price"
                            }
                        },
                        {
                            dataField: "quantity",
                            editorType: "dxNumberBox",
                            editorOptions: {
                                placeholder: "Enter quantity",
                                format: "#"
                            }
                        }
                    ]
                }
            },
            showBorders: true,
            rowAlternationEnabled: true,
            hoverStateEnabled: true,
            pager: {
                visible: true,
                showPageSizeSelector: true,
                allowedPageSizes: [5, 10, 20],
                showInfo: true,
                showNavigationButtons: true,
                displayMode: 'full'
            },
            paging: {
                enabled: true,
                pageSize: 5
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
                // Force 5 rows ONLY on the very first load (so refresh defaults to 5).
                // Do NOT re-force on later renders, or clicking 10/20 would snap back to 5.
                var grid = e.component;
                if (!grid._initDone && grid.option("paging.pageSize") !== 5) {
                    grid.option("paging.pageSize", 5);
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
            var available = window.innerHeight - rect.top - 12; // 12px bottom margin
            if (available < 520) available = 520; // taller minimum (~15 rows)
            var grid = $("#gridContainer").dxDataGrid("instance");
            if (!grid) return;

            grid.option("height", available);
        }
        $(window).on("resize", fitGridHeight);
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

        async function exportPDF(pageOnly) {
            const $btn = $("#pdfExportTrigger");
            let overlay = null;
            try {
                $btn.prop("disabled", true).css("opacity", "0.6");

                var gridInstance = $("#gridContainer").dxDataGrid("instance");

                // 1) Read rows from the LIVE grid (same data shown on this page)
                var exportData;
                if (pageOnly) {
                    exportData = gridInstance.getVisibleRows()
                        .filter(function(r) {
                            return r.rowType === "data";
                        })
                        .map(function(r) {
                            return r.data;
                        });
                } else {
                    exportData = await gridInstance.getDataSource().store().load();
                }
                if (!exportData || exportData.length === 0) {
                    alert("No data to export.");
                    return;
                }

                // 2) Columns from the grid (skip the Action/buttons column)
                var visibleColumns = gridInstance.option("columns").filter(function(col) {
                    return col.type !== "buttons" && col.caption !== "Action" &&
                        col.dataField !== "action";
                });

                // 3) Build a plain table styled like index.php (red header, white body)
                var thead = "<thead><tr>";
                visibleColumns.forEach(function(col) {
                    thead += "<th>" + (col.caption || col.dataField || "") + "</th>";
                });
                thead += "</tr></thead>";

                var tbody = "<tbody>";
                exportData.forEach(function(row) {
                    tbody += "<tr>";
                    visibleColumns.forEach(function(col) {
                        var val = row[col.dataField];
                        if (val === null || val === undefined) val = "";
                        if ((col.dataField === "created_at" || col.dataField ===
                                "lastupdate") && val) {
                            val = formatDateTime(new Date(val));
                        }
                        if (col.dataField === "price" && val !== "") {
                            val = "$" + parseFloat(val).toFixed(2);
                        }
                        tbody += "<td>" + String(val)
                            .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g,
                                "&gt;") + "</td>";
                    });
                    tbody += "</tr>";
                });
                tbody += "</tbody>";

                // 4) Render off-screen (NOT visible on screen) but still painted by the
                //    browser, so html2canvas can capture real pixels without a flash.
                overlay = $(
                    '<div id="pdfCaptureOverlay">' +
                    '<style>' +
                    '#pdfCaptureOverlay{position:fixed;left:-10000px;top:0;z-index:-1;background:#fff !important;padding:24px;}' +
                    '#pdfTable{font-family:"KhmerOSWeb","Khmer OS Siemreap",Arial,sans-serif;' +
                    'border-collapse:collapse;width:100%;color:#000;font-size:12px;font-weight:normal;background:#fff !important;}' +
                    '#pdfTable th{background:#fff !important;color:#000;padding:8px 10px;text-align:left;' +
                    'border:1px solid #999;font-weight:normal;}' +
                    '#pdfTable td{padding:7px 10px;border:1px solid #999;color:#000;font-weight:normal;background:#fff !important;}' +
                    '#pdfTable tr:nth-child(even) td{background:#fff !important;}' +
                    '</style>' +
                    '<table id="pdfTable">' + thead + tbody + '</table>' +
                    '</div>'
                ).appendTo("body");

                // Wait for the Khmer webfont to shape text
                if (document.fonts && document.fonts.ready) {
                    await document.fonts.ready;
                }
                await new Promise(function(r) {
                    setTimeout(r, 500);
                });

                // 5) Capture the (painted, off-screen) table, then move it into jsPDF.
                //    scale:2 + high-quality JPEG keeps text sharp (small file thanks to JPEG).
                const canvas = await html2canvas(overlay[0], {
                    scale: 2,
                    useCORS: true,
                    backgroundColor: "#ffffff",
                    logging: false,
                    onclone: function(clonedDoc) {
                        try {
                            if (window.__khmerB64) {
                                var s = clonedDoc.createElement("style");
                                s.textContent = '@font-face{font-family:"KhmerOSWeb";' +
                                    'src:url(data:font/ttf;base64,' + window.__khmerB64 +
                                    ') format("truetype");font-weight:normal;font-style:normal;}';
                                clonedDoc.head.appendChild(s);
                            }
                        } catch (e) {}
                    }
                });
                overlay.remove();
                overlay = null;

                const {
                    jsPDF
                } = window.jspdf;
                // Read the ACTIVE paper + orientation straight from the menu so the
                // latest selection is always used (not a stale closure value).
                var $menu = document.getElementById("masterExportMenu");
                var $actPaper = $menu ? $menu.querySelector("[data-paper].active") : null;
                var $actOrient = $menu ? $menu.querySelector("[data-orientation].active") : null;
                pdfPaper = $actPaper ? $actPaper.dataset.paper : "a4";
                pdfOrientation = $actOrient ? ($actOrient.dataset.orientation === "landscape" ? "l" : "p") :
                    "p";
                const pdf = new jsPDF(pdfOrientation, "pt", pdfPaper);
                const pageW = pdf.internal.pageSize.getWidth();
                const pageH = pdf.internal.pageSize.getHeight();
                const margin = 20;

                const imgData = canvas.toDataURL("image/jpeg", 0.92);
                const imgProps = pdf.getImageProperties(imgData);
                let imgW = pageW - margin * 2;
                let imgH = (imgProps.height * imgW) / imgProps.width;
                if (imgH > pageH - margin * 2) {
                    imgH = pageH - margin * 2;
                    imgW = (imgProps.width * imgH) / imgProps.height;
                }
                pdf.addImage(imgData, "JPEG", margin, margin, imgW, imgH);
                pdf.save("Categories_" + (pageOnly ? "Page" : "All") + "_" + new Date().toISOString().slice(
                    0, 10) + ".pdf");
            } catch (err) {
                console.error("PDF Export Error:", err);
                alert("Export failed: " + err.message);
            } finally {
                if (overlay && overlay.length) {
                    try {
                        overlay.remove();
                    } catch (e) {}
                }
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
                        if (v.search(/[",\n]/) !== -1) {
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
                    }).join("\n");
                    var csv = "﻿" + header + "\n" + body;

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

        // Initialize Column Resizing Mode dxSelectBox
        $('#select-resizing').dxSelectBox({
            items: resizingModes,
            valueExpr: 'value',
            displayExpr: 'text',
            value: localStorage.getItem("categoryGridResizeMode") || 'widget',
            inputAttr: {
                'aria-label': 'Resize Mode'
            },
            width: 160,
            onValueChanged(data) {
                localStorage.setItem("categoryGridResizeMode", data.value);
                var grid = $("#gridContainer").dxDataGrid("instance");
                grid.option('columnResizingMode', data.value);
            },
        });
        // Initialize custom Field Chooser Button
        $("#customFieldChooserBtn").dxButton({
            text: $(window).width() <= 768 ? "Fields" : "Field Chooser",
            icon: "columnchooser",
            onClick: function(e) {
                openColumnChooser(e ? e.event : null);
            }
        });

        // Initialize custom Advanced Search Button
        $("#customAdvancedSearchBtn").dxButton({
            text: $(window).width() <= 768 ? "Search All" : "Search All Records",
            icon: "fa fa-sliders",
            onClick: function() {
                advancedSearchPopup.show();
            }
        });

        // ==========================================
        // Advanced Search (Search All Records) Logic
        // ==========================================
        var advancedSearchPopup = $("#advancedSearchPopup").dxPopup({
            title: "Search All Records",
            width: 900,
            height: "auto",
            showTitle: true,
            visible: false,
            dragEnabled: false,
            hideOnOutsideClick: true,
            showCloseButton: true,
            wrapperAttr: {
                class: "dark-popup"
            },
            titleTemplate: function (titleElement) {
                titleElement.append(
                    $("<div style='display: flex; align-items: center; justify-content: space-between; width: 100%;'>")
                        .append("<div style='display: flex; align-items: center; gap: 10px; font-size: 17px; color: #ffffff;'><i class='fa-solid fa-magnifying-glass' style='color: #ffffff; font-size: 18px;'></i> <b>Search All Records</b></div>")
                );
            },
            contentTemplate: function (contentElement) {
                var formHtml = $("<div>").append(
                    $("<div style='margin-bottom: 25px;'>")
                        .append("<p style='margin: 0 0 0 5px; color: #71717a; font-size: 13px;'>Full database search.</p>")
                );

                var $formContainer = $("<div id='advancedSearchForm'>").appendTo(formHtml);
                
                contentElement.append(formHtml);

                var createLabel = function(iconClass, text) {
                    return function(data, $element) {
                        $element.append("<div style='display:flex; align-items:center; gap:8px;'><i class='" + iconClass + "' style='color:#71717a; width:16px; text-align:center;'></i> <span style='color:#e4e4e7; font-family:\"Poppins\", sans-serif;'>" + text + "</span></div>");
                    };
                };

                $formContainer.dxForm({
                    colCount: 4,
                    labelLocation: "top",
                    items: [
                        {
                            dataField: "product_code",
                            editorType: "dxTextBox",
                            label: { template: createLabel("fa-solid fa-barcode", "Product Code") },
                            editorOptions: { placeholder: "Enter code...", stylingMode: "outlined" }
                        },
                        {
                            dataField: "product_name",
                            editorType: "dxTextBox",
                            label: { template: createLabel("fa-solid fa-box", "Product Name") },
                            editorOptions: { placeholder: "Enter name...", stylingMode: "outlined" }
                        },
                        {
                            dataField: "category_id",
                            editorType: "dxSelectBox",
                            label: { template: createLabel("fa-solid fa-layer-group", "Category") },
                            editorOptions: {
                                dataSource: new DevExpress.data.CustomStore({
                                    key: "id",
                                    loadMode: "raw",
                                    load: function() {
                                        return $.getJSON("products.php?action=get_categories");
                                    }
                                }),
                                valueExpr: "id",
                                displayExpr: "category_name",
                                placeholder: "Select category...",
                                showClearButton: true,
                                stylingMode: "outlined"
                            }
                        },
                        {
                            dataField: "price_min",
                            editorType: "dxNumberBox",
                            label: { template: createLabel("fa-solid fa-hand-holding-dollar", "Min Price") },
                            editorOptions: { placeholder: "0.00", stylingMode: "outlined", format: "$ #,##0.00" }
                        },
                        {
                            dataField: "price_max",
                            editorType: "dxNumberBox",
                            label: { template: createLabel("fa-solid fa-sack-dollar", "Max Price") },
                            editorOptions: { placeholder: "0.00", stylingMode: "outlined", format: "$ #,##0.00" }
                        },
                        {
                            dataField: "quantity_min",
                            editorType: "dxNumberBox",
                            label: { template: createLabel("fa-solid fa-cubes", "Min Qty") },
                            editorOptions: { placeholder: "0", stylingMode: "outlined" }
                        },
                        {
                            dataField: "date_from",
                            editorType: "dxDateBox",
                            label: { template: createLabel("fa-regular fa-calendar", "Date From") },
                            editorOptions: { type: "date", showClearButton: true, placeholder: "Select date...", stylingMode: "outlined" }
                        },
                        {
                            dataField: "date_to",
                            editorType: "dxDateBox",
                            label: { template: createLabel("fa-regular fa-calendar-check", "Date To") },
                            editorOptions: { type: "date", showClearButton: true, placeholder: "Select date...", stylingMode: "outlined" }
                        }
                    ]
                });
                
                var $btnContainer = $("<div style='margin-top: 35px; display: flex; justify-content: flex-end; gap: 10px;'>");
                
                var $clearBtn = $("<div>").dxButton({
                    text: "Clear",
                    icon: "clear",
                    stylingMode: "outlined",
                    type: "normal",
                    onClick: function() {
                        $("#advancedSearchForm").dxForm("instance").resetValues();
                        $("#gridContainer").dxDataGrid("instance").clearFilter();
                    }
                });
                $clearBtn.addClass("secondary-modal-btn");
                $btnContainer.append($clearBtn);
                
                var $searchBtn = $("<div>").dxButton({
                    text: "Search",
                    icon: "search",
                    type: "success",
                    stylingMode: "contained",
                    stylingMode: "contained",
                    onClick: function() {
                        var formData = $("#advancedSearchForm").dxForm("instance").option("formData");
                        var filterExpr = [];
                        
                        if (formData.product_code) {
                            filterExpr.push(["product_code", "contains", formData.product_code]);
                        }
                        if (formData.product_name) {
                            filterExpr.push(["product_name", "contains", formData.product_name]);
                        }
                        if (formData.category_id) {
                            filterExpr.push(["category_id", "=", formData.category_id]);
                        }
                        if (formData.price_min) {
                            filterExpr.push(["price", ">=", formData.price_min]);
                        }
                        if (formData.price_max) {
                            filterExpr.push(["price", "<=", formData.price_max]);
                        }
                        if (formData.quantity_min) {
                            filterExpr.push(["quantity", ">=", formData.quantity_min]);
                        }
                        if (formData.date_from) {
                            filterExpr.push(["created_at", ">=", formData.date_from]);
                        }
                        if (formData.date_to) {
                            var toDate = new Date(formData.date_to);
                            toDate.setDate(toDate.getDate() + 1);
                            filterExpr.push(["created_at", "<", toDate]);
                        }
                        
                        var finalFilter = [];
                        for(var i = 0; i < filterExpr.length; i++) {
                            if(i > 0) finalFilter.push("and");
                            finalFilter.push(filterExpr[i]);
                        }
                        
                        var grid = $("#gridContainer").dxDataGrid("instance");
                        if(finalFilter.length > 0) {
                            grid.filter(finalFilter);
                        } else {
                            grid.clearFilter();
                        }
                        
                        advancedSearchPopup.hide();
                    }
                });
                $searchBtn.addClass("primary-modal-btn");
                $btnContainer.append($searchBtn);
                
                contentElement.append($btnContainer);
            }
        }).dxPopup({
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
                // Force 5 rows ONLY on the very first load (so refresh defaults to 5).
                // Do NOT re-force on later renders, or clicking 10/20 would snap back to 5.
                var grid = e.component;
                if (!grid._initDone && grid.option("paging.pageSize") !== 5) {
                    grid.option("paging.pageSize", 5);
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
        }).dxPopup("instance");

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

        // Manual Push Modal Handlers
        $("#openPushModalBtn").on("click", function() {
            $("#customPushMessage").val("");
            loadPushSettings();
            $("#pushModal").fadeIn(200);
        });

        $("#closePushModalBtn").on("click", function() {
            $("#pushModal").fadeOut(150);
        });

        $(window).on("click", function(e) {
            if ($(e.target).is("#pushModal")) {
                $("#pushModal").fadeOut(150);
            }
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
            $btn.prop("disabled", true).html('<i class="fa-solid fa-spinner fa-spin"></i> Pushing Added Items...');
            
            $.ajax({
                url: "manual_push.php",
                type: "POST",
                dataType: "json",
                data: { action: "push_added" },
                success: function(res) {
                    $btn.prop("disabled", false).html('<i class="fa-paper-plane"></i> Push Added Items Report');
                    if (res && res.ok) {
                        DevExpress.ui.notify("Recently added items pushed to Telegram!", "success", 3000);
                        $("#pushModal").fadeOut(150);
                    } else {
                        DevExpress.ui.notify(res.description || "Failed to push added items.", "error", 4000);
                    }
                },
                error: function() {
                    $btn.prop("disabled", false).html('<i class="fa-paper-plane"></i> Push Added Items Report');
                    DevExpress.ui.notify("Network error sending push.", "error", 4000);
                }
            });
        });

        // Push Updated Items Action
        $("#btnPushUpdated").on("click", function() {
            var $btn = $(this);
            $btn.prop("disabled", true).html('<i class="fa-solid fa-spinner fa-spin"></i> Pushing Updated Items...');
            
            $.ajax({
                url: "manual_push.php",
                type: "POST",
                dataType: "json",
                data: { action: "push_updated" },
                success: function(res) {
                    $btn.prop("disabled", false).html('<i class="fa-paper-plane"></i> Push Updated Items Report');
                    if (res && res.ok) {
                        DevExpress.ui.notify("Recently updated items pushed to Telegram!", "success", 3000);
                        $("#pushModal").fadeOut(150);
                    } else {
                        DevExpress.ui.notify(res.description || "Failed to push updated items.", "error", 4000);
                    }
                },
                error: function() {
                    $btn.prop("disabled", false).html('<i class="fa-paper-plane"></i> Push Updated Items Report');
                    DevExpress.ui.notify("Network error sending push.", "error", 4000);
                }
            });
        });

        // Push Low Stock Warning Action
        $("#btnPushLowStock").on("click", function() {
            var $btn = $(this);
            $btn.prop("disabled", true).html('<i class="fa-solid fa-spinner fa-spin"></i> Pushing Low Stock Report...');
            
            $.ajax({
                url: "manual_push.php",
                type: "POST",
                dataType: "json",
                data: { action: "push_low_stock" },
                success: function(res) {
                    $btn.prop("disabled", false).html('<i class="fa-paper-plane"></i> Push Low Stock Warning');
                    if (res && res.ok) {
                        DevExpress.ui.notify("Low stock warning report pushed to Telegram!", "success", 3000);
                        $("#pushModal").fadeOut(150);
                    } else {
                        DevExpress.ui.notify(res.description || "Failed to push low stock report.", "error", 4000);
                    }
                },
                error: function() {
                    $btn.prop("disabled", false).html('<i class="fa-paper-plane"></i> Push Low Stock Warning');
                    DevExpress.ui.notify("Network error sending push.", "error", 4000);
                }
            });
        });

        // Push Valuation Report Action
        $("#btnPushValuation").on("click", function() {
            var $btn = $(this);
            $btn.prop("disabled", true).html('<i class="fa-solid fa-spinner fa-spin"></i> Pushing Financial Report...');
            
            $.ajax({
                url: "manual_push.php",
                type: "POST",
                dataType: "json",
                data: { action: "push_valuation" },
                success: function(res) {
                    $btn.prop("disabled", false).html('<i class="fa-paper-plane"></i> Push Valuation Report');
                    if (res && res.ok) {
                        DevExpress.ui.notify("Valuation report pushed to Telegram!", "success", 3000);
                        $("#pushModal").fadeOut(150);
                    } else {
                        DevExpress.ui.notify(res.description || "Failed to push valuation report.", "error", 4000);
                    }
                },
                error: function() {
                    $btn.prop("disabled", false).html('<i class="fa-paper-plane"></i> Push Valuation Report');
                    DevExpress.ui.notify("Network error sending push.", "error", 4000);
                }
            });
        });

        // Push Summary Action
        $("#btnPushSummary").on("click", function() {
            var $btn = $(this);
            $btn.prop("disabled", true).html('<i class="fa-solid fa-spinner fa-spin"></i> Pushing Summary...');
            
            $.ajax({
                url: "manual_push.php",
                type: "POST",
                dataType: "json",
                data: { action: "summary" },
                success: function(res) {
                    $btn.prop("disabled", false).html('<i class="fa-paper-plane"></i> Push Summary Report');
                    if (res && res.ok) {
                        DevExpress.ui.notify("Summary report successfully pushed to Telegram!", "success", 3000);
                        $("#pushModal").fadeOut(150);
                    } else {
                        DevExpress.ui.notify(res.description || "Failed to push summary to Telegram.", "error", 4000);
                    }
                },
                error: function(xhr) {
                    $btn.prop("disabled", false).html('<i class="fa-paper-plane"></i> Push Summary Report');
                    DevExpress.ui.notify("Network or server error sending push.", "error", 4000);
                }
            });
        });

        // Push Custom Action
        $("#btnPushCustom").on("click", function() {
            var msg = $.trim($("#customPushMessage").val());
            if (!msg) {
                DevExpress.ui.notify("Please enter a custom message to push.", "warning", 3000);
                return;
            }

            var $btn = $(this);
            $btn.prop("disabled", true).html('<i class="fa-solid fa-spinner fa-spin"></i> Sending...');

            $.ajax({
                url: "manual_push.php",
                type: "POST",
                dataType: "json",
                data: { action: "custom", message: msg },
                success: function(res) {
                    $btn.prop("disabled", false).html('<i class="fa-paper-plane"></i> Send Custom Push');
                    if (res && res.ok) {
                        DevExpress.ui.notify("Custom notification pushed to Telegram!", "success", 3000);
                        $("#customPushMessage").val("");
                        $("#pushModal").fadeOut(150);
                    } else {
                        DevExpress.ui.notify(res.description || "Failed to push custom message.", "error", 4000);
                    }
                },
                error: function(xhr) {
                    $btn.prop("disabled", false).html('<i class="fa-paper-plane"></i> Send Custom Push');
                    DevExpress.ui.notify("Network or server error sending push.", "error", 4000);
                }
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
    });
    </script>

    <!-- Manual Telegram Push Modal -->
    <div id="pushModal" class="custom-modal-backdrop" style="display: none;">
        <div class="custom-modal-content" style="max-width: 560px; max-height: 85vh; overflow-y: auto;">
            <div class="custom-modal-header">
                <h3><i class="fa-brands fa-telegram" style="color: #0088cc;"></i> Telegram Notification Hub</h3>
                <button type="button" class="custom-modal-close" id="closePushModalBtn">&times;</button>
            </div>
            <div class="custom-modal-body">
                <!-- 2-Tab Navigation Bar -->
                <div class="push-modal-tabs">
                    <button type="button" class="push-tab-btn active" data-tab="tab-mode-setup">
                        <i class="fa-solid fa-sliders"></i> Mode Setup (Auto/Manual)
                    </button>
                    <button type="button" class="push-tab-btn" data-tab="tab-reports-data">
                        <i class="fa-solid fa-chart-column"></i> Push Data Reports
                    </button>
                </div>

                <!-- TAB 1: Auto & Manual Setup -->
                <div id="tab-mode-setup" class="push-tab-content active">
                    <div class="push-mode-banner" style="margin-bottom: 0;">
                        <div class="push-mode-info">
                            <div class="push-mode-title">
                                Auto Telegram Notifications
                                <span id="pushModeBadge" class="mode-badge auto-on">Auto Active</span>
                            </div>
                            <div class="push-mode-subtitle" id="pushModeSubtitle">
                                Automatically sends alert on Add, Update, & Delete operations.
                            </div>
                        </div>
                        <label class="toggle-switch-container">
                            <input type="checkbox" id="toggleAutoPush" class="toggle-switch-checkbox" checked>
                            <span class="toggle-switch-slider"></span>
                        </label>
                    </div>

                    <div class="push-option-card">
                        <h4><i class="fa-solid fa-gear" style="color: #6366f1;"></i> Notification Triggers & Configuration</h4>
                        <p style="margin-bottom: 8px;">
                            • <b>Auto Mode (Active)</b>: Real-time instant alerts dispatched whenever a Category or Product is created, updated, or removed.<br>
                            • <b>Manual Mode (Auto Closed)</b>: System operations remain quiet. Notifications are dispatched only when manually triggered from the Data Reports tab or row actions.
                        </p>
                    </div>
                </div>

                <!-- TAB 2: Push Data Reports -->
                <div id="tab-reports-data" class="push-tab-content">
                    <!-- Action Card 1: Push Recently Added -->
                    <div class="push-option-card">
                        <h4><i class="fa-solid fa-plus-circle" style="color: #10b981;"></i> Push Recently Added Items</h4>
                        <p>Manually trigger a Telegram update listing newly created products and categories.</p>
                        <button type="button" class="add-btn" id="btnPushAdded" style="width: 100%; justify-content: center; background: #10b981;">
                            <i class="fa-paper-plane"></i> Push Added Items Report
                        </button>
                    </div>

                    <!-- Action Card 2: Push Recently Updated -->
                    <div class="push-option-card">
                        <h4><i class="fa-solid fa-pen-to-square" style="color: #f59e0b;"></i> Push Recently Updated Items</h4>
                        <p>Manually trigger a Telegram update listing recently modified products and categories.</p>
                        <button type="button" class="add-btn" id="btnPushUpdated" style="width: 100%; justify-content: center; background: #f59e0b; color: #1e293b;">
                            <i class="fa-paper-plane"></i> Push Updated Items Report
                        </button>
                    </div>

                    <!-- Action Card 3: Low Stock Warning -->
                    <div class="push-option-card">
                        <h4><i class="fa-solid fa-triangle-exclamation" style="color: #ef4444;"></i> Push Low Stock Warning</h4>
                        <p>Manually send a low stock alert listing items with quantity &le; 5.</p>
                        <button type="button" class="add-btn" id="btnPushLowStock" style="width: 100%; justify-content: center; background: #ef4444;">
                            <i class="fa-paper-plane"></i> Push Low Stock Warning
                        </button>
                    </div>

                    <!-- Action Card 4: Financial Valuation -->
                    <div class="push-option-card">
                        <h4><i class="fa-solid fa-sack-dollar" style="color: #8b5cf6;"></i> Push Financial & Valuation Report</h4>
                        <p>Send asset valuation report, total stock count, and average pricing breakdown.</p>
                        <button type="button" class="add-btn" id="btnPushValuation" style="width: 100%; justify-content: center; background: #8b5cf6;">
                            <i class="fa-paper-plane"></i> Push Valuation Report
                        </button>
                    </div>

                    <!-- Action Card 5: Inventory Summary -->
                    <div class="push-option-card">
                        <h4><i class="fa-solid fa-chart-pie" style="color: #6366f1;"></i> Full Inventory Summary Report</h4>
                        <p>Send real-time overall summary of total categories, products, stock quantity, and valuation.</p>
                        <button type="button" class="add-btn" id="btnPushSummary" style="width: 100%; justify-content: center; background: #6366f1;">
                            <i class="fa-paper-plane"></i> Push Summary Report
                        </button>
                    </div>
                    
                    <!-- Action Card 6: Custom Message -->
                    <div class="push-option-card">
                        <h4><i class="fa-solid fa-comment-dots" style="color: #38bdf8;"></i> Custom Message Push</h4>
                        <p>Type a custom alert or note to push directly to your Telegram chat.</p>
                        <textarea id="customPushMessage" class="custom-modal-input" rows="2" placeholder="Enter custom notification message here..."></textarea>
                        <button type="button" class="add-btn" id="btnPushCustom" style="width: 100%; justify-content: center; background: #0284c7; margin-top: 10px;">
                            <i class="fa-paper-plane"></i> Send Custom Push
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>

</html>