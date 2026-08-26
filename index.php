<?php

require_once "database.php";

// API Endpoint to read category list
if (isset($_GET["action"]) && $_GET["action"] === "read") {
    header("Content-Type: application/json");
    // Prevent caching so newly added/edited/deleted rows always show up live
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: 0");
    $sql = "SELECT * FROM category ORDER BY id DESC";
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

    <title>Category Management</title>

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
        (function () {
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
    <!-- App CSS Styles -->
    <!-- Update the version number whenever you upload new CSS -->
    <link rel="stylesheet" href="style.css?v=1.1">


</head>

<body>

    <div class="page">

        <div class="category-card">


            <div class="header">

                <h1>
                    Category-Management
                </h1>

                <button type="button" class="add-btn" id="openAddModalBtn">

                    <i class="fa-solid fa-plus"></i>

                    Add Category

                </button>

            </div>



            <div class="options-container">
                <div class="option-item">
                    <span class="option-label">Resize Mode:</span>
                    <div id="select-resizing"></div>
                </div>
                <div class="search-and-export">
                    <div id="customPdfExportBtn"></div>
                    <div id="customExportBtn"></div>
                    <div class="search-wrapper">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="searchInput" placeholder="Search...">
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
        const resizingModes = ['widget', 'nextColumn'];

        $("#gridContainer").dxDataGrid({
            allowColumnReordering: true,
            allowColumnResizing: true,
            columnAutoWidth: true,
            columnResizingMode: localStorage.getItem("categoryGridResizeMode") || "widget",
            columnFixing: {
                enabled: true
            },
            stateStoring: {
                enabled: true,
                type: "localStorage",
                storageKey: "categoryGridStateV13"
            },

            dataSource: new DevExpress.data.CustomStore({
                key: "id",
                loadMode: "raw",
                load: function() {
                    return new Promise(function(resolve, reject) {
                        // cache:false forces jQuery to append ?_=timestamp so the
                        // browser / shared host (InfinityFree) never serves a stale
                        // GET response. Without this, newly added rows won't show
                        // after refresh() because the read is cached.
                        $.ajax({
                            url: "index.php?action=read",
                            method: "GET",
                            cache: false,
                            dataType: "json"
                        })
                            .done(function(data) {
                                resolve(data);
                            })
                            .fail(function() {
                                reject(new Error("Failed to load categories."));
                            });
                    });
                },
                insert: function(values) {
                    return new Promise(function(resolve, reject) {
                        $.post("create.php", values)
                            .done(function(data) {
                                resolve(data);
                            })
                            .fail(function(xhr) {
                                var msg = "Failed to add category.";
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
                        $.post("edit.php?id=" + key, values)
                            .done(function(data) {
                                resolve(data);
                            })
                            .fail(function(xhr) {
                                var msg = "Failed to update category.";
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
                        $.post("delete.php?id=" + key)
                            .done(function(data) {
                                resolve(data);
                            })
                            .fail(function(xhr) {
                                var msg = "Failed to delete category.";
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
                    name: "category_code",
                    dataField: "category_code",
                    caption: "Category Code",
                    fixed: true,
                    fixedPosition: "left",
                    minWidth: 100,
                    width: 260,
                    maxWidth: 300,
                    validationRules: [{
                        type: "required",
                        message: "Category Code is required"
                    }]
                },
                {
                    name: "category_name",
                    dataField: "category_name",
                    caption: "Category Name",
                    minWidth: 100,
                    width: 260,
                    maxWidth: 300,
                    validationRules: [{
                        type: "required",
                        message: "Category Name is required"
                    }]
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
                    dataType: "datetime",
                    format: "HH:mm:ss",
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
                    width: 100, // Default initial width
                    minWidth: 100, // Prevents dragging smaller than 100px
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
                    dataType: "datetime",
                    format: "HH:mm:ss",
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
                    width: 130,
                    minWidth: 100,
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
                    title: "Category Details",
                    showTitle: true,
                    width: 500,
                    height: 350,
                    wrapperAttr: {
                        class: "dark-popup"
                    }
                },
                form: {
                    colCount: 1,
                    items: [{
                            dataField: "category_code",
                            editorType: "dxTextBox",
                            editorOptions: {
                                placeholder: "Enter category code"
                            }
                        },
                        {
                            dataField: "category_name",
                            editorType: "dxTextBox",
                            editorOptions: {
                                placeholder: "Enter category name"
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
                pageSize: 10
            },
            searchPanel: {
                visible: false
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
                }
            }
        });

        // Wire up custom Search Input to DevExtreme DataGrid search
        $("#searchInput").on("input", function() {
            var grid = $("#gridContainer").dxDataGrid("instance");
            grid.searchByText($(this).val());
        });

        // Wire up custom Add Category Button to DevExtreme DataGrid addRow
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

        async function exportPDF(pageOnly) {
            const $btn = $("#customPdfExportBtn");
            let overlay = null;
            try {
                $btn.prop("disabled", true).css("opacity", "0.6");

                var gridInstance = $("#gridContainer").dxDataGrid("instance");

                // 1) Read rows from the LIVE grid (same data shown on this page)
                var exportData;
                if (pageOnly) {
                    exportData = gridInstance.getVisibleRows()
                        .filter(function(r) { return r.rowType === "data"; })
                        .map(function(r) { return r.data; });
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
                        if ((col.dataField === "created_at" || col.dataField === "lastupdate") && val) {
                            val = formatDateTime(new Date(val));
                        }
                        tbody += "<td>" + String(val)
                            .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;") + "</td>";
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
                if (document.fonts && document.fonts.ready) { await document.fonts.ready; }
                await new Promise(function(r) { setTimeout(r, 500); });

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

                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF("l", "pt", "a4");
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
                pdf.save("Categories_" + (pageOnly ? "Page" : "All") + "_" + new Date().toISOString().slice(0, 10) + ".pdf");
            } catch (err) {
                console.error("PDF Export Error:", err);
                alert("Export failed: " + err.message);
            } finally {
                if (overlay && overlay.length) { try { overlay.remove(); } catch (e) {} }
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
        // Dedicated PDF Export DropDownButton (matches Export Excel style)
        $("#customPdfExportBtn").dxDropDownButton({
            text: "Export PDF",
            icon: "export",
            items: [{
                    id: "all",
                    text: "Export All Pages",
                    icon: "bulletlist"
                },
                {
                    id: "current",
                    text: "Export Current Page",
                    icon: "export"
                }
            ],
            displayExpr: "text",
            keyExpr: "id",
            dropDownOptions: {
                width: 200
            },
            onItemClick: function(e) {
                exportPDF(e.itemData.id === "current");
            }
        });
        // Initialize custom Export Excel DropDownButton
        $("#customExportBtn").dxDropDownButton({
            text: "Export Excel",
            icon: "xlsxfile",
            items: [{
                    id: "all",
                    text: "Export All Pages",
                    icon: "bulletlist"
                },
                {
                    id: "current",
                    text: "Export Current Page",
                    icon: "export"
                }
            ],
            displayExpr: "text",
            keyExpr: "id",
            dropDownOptions: {
                width: 200
            },
            onItemClick: function(e) {
                exportGrid(e.itemData.id === "current");
            }
        });

        // Initialize Column Resizing Mode dxSelectBox
        $('#select-resizing').dxSelectBox({
            items: resizingModes,
            value: localStorage.getItem("categoryGridResizeMode") || resizingModes[0],
            inputAttr: {
                'aria-label': 'Resize Mode'
            },
            width: 250,
            onValueChanged(data) {
                localStorage.setItem("categoryGridResizeMode", data.value);
                var grid = $("#gridContainer").dxDataGrid("instance");
                grid.option('columnResizingMode', data.value);
            },
        });
    });
    </script>


</body>

</html>