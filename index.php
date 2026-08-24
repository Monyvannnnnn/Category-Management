<?php

require_once "database.php";

// API Endpoint to read category list
if (isset($_GET["action"]) && $_GET["action"] === "read") {
    header("Content-Type: application/json");
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

    <title>Category Management (DevExtreme)</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- Required for DevExtreme Excel Export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.4.0/exceljs.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>

    <!-- jQuery -->


    <link rel="stylesheet" href="https://cdn3.devexpress.com/jslib/23.1.6/css/dx.dark.css" />

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn3.devexpress.com/jslib/23.1.6/js/dx.all.js"></script>

    <!-- App CSS Styles -->
    <link rel="stylesheet" href="style.css?v=1.0.5">

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
            columnResizingMode: localStorage.getItem("categoryGridResizeMode") || "widget",
            columnFixing: {
                enabled: true
            },
            stateStoring: {
                enabled: true,
                type: "localStorage",
                storageKey: "categoryGridStateV9"
            },

            dataSource: new DevExpress.data.CustomStore({
                key: "id",
                loadMode: "raw",
                load: function() {
                    return new Promise(function(resolve, reject) {
                        $.getJSON("index.php?action=read")
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
                    dataField: "category_code",
                    caption: "Category Code",
                    fixed: true,
                    fixedPosition: "left",
                    minWidth: 120,
                    validationRules: [{
                        type: "required",
                        message: "Category Code is required"
                    }]
                },
                {
                    dataField: "category_name",
                    caption: "Category Name",
                    minWidth: 150,
                    validationRules: [{
                        type: "required",
                        message: "Category Name is required"
                    }]
                },
                {
                    dataField: "created_at",
                    caption: "Date Created",
                    dataType: "datetime",
                    format: "dd/MM/yyyy HH:mm:ss",
                    minWidth: 180,
                    allowEditing: false
                },
                {
                    dataField: "created_at",
                    caption: "Created Date",
                    dataType: "date",
                    format: "dd/MM/yyyy",
                    minWidth: 120,
                    allowEditing: false
                },
                {
                    dataField: "created_at",
                    caption: "Created Time",
                    dataType: "datetime",
                    format: "HH:mm:ss",
                    minWidth: 120,
                    allowEditing: false
                },
                {
                    dataField: "created_at",
                    caption: "Formatted Date",
                    dataType: "date",
                    format: "dd-MMMM-yyyy",
                    minWidth: 160,
                    allowEditing: false
                },
                {
                    dataField: "created_at",
                    caption: "Formatted Time",
                    dataType: "datetime",
                    format: "hh:mm:ss a",
                    minWidth: 140,
                    allowEditing: false
                },
                {
                    dataField: "created_at",
                    caption: "Formatted Date & Time",
                    dataType: "datetime",
                    format: "dd-MMMM-yyyy hh:mm:ss a",
                    minWidth: 260,
                    allowEditing: false
                },
                {
                    dataField: "lastupdate",
                    caption: "Last Updated",
                    dataType: "datetime",
                    minWidth: 280,
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
                    dataField: "lastupdate",
                    caption: "Last Date",
                    dataType: "date",
                    format: "dd/MM/yyyy",
                    minWidth: 120,
                    allowEditing: false
                },
                {
                    dataField: "lastupdate",
                    caption: "Last Time",
                    dataType: "datetime",
                    format: "HH:mm:ss",
                    minWidth: 120,
                    allowEditing: false
                },
                {
                    dataField: "lastupdate",
                    caption: "Time Ago",
                    dataType: "datetime",
                    minWidth: 120,
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
                mode: "popup", // edit/add opens in popup modal
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
                visible: false // Hidden as we use our custom search input
            },
            onSaved: function(e) {
                e.component.refresh();
            },
            onContextMenuPreparing: function(e) {
                if (e.target === "header") {
                    var column = e.column;

                    if (!e.items) e.items = [];

                    // Remove default Fix/Unfix items to prevent duplicates
                    e.items = (e.items || []).filter(function(item) {
                        return !item.text || (item.text !== "Fix" &&
                            item.text !== "Unfix" && item.text !== "Sticky");
                    });

                    // Don't show options for command/action columns
                    if (column && column.dataField) {
                        e.items.push({
                            text: "Move Left",
                            icon: "arrowleft",
                            onItemClick: function() {
                                var currentIndex = column.visibleIndex;
                                var columns = e.component.getVisibleColumns();
                                var targetColumn = null;
                                for (var i = 0; i < columns.length; i++) {
                                    if (columns[i].visibleIndex === currentIndex - 1) {
                                        targetColumn = columns[i];
                                        break;
                                    }
                                }

                                // Swap only if target is a valid, reorderable data column
                                if (targetColumn && targetColumn.dataField &&
                                    targetColumn.allowReordering !== false) {
                                    e.component.beginUpdate();
                                    e.component.columnOption(
                                        column.index,
                                        "visibleIndex",
                                        currentIndex - 1
                                    );
                                    e.component.columnOption(
                                        targetColumn.index,
                                        "visibleIndex",
                                        currentIndex
                                    );
                                    e.component.endUpdate();
                                }
                            }
                        });

                        e.items.push({
                            text: "Move Right",
                            icon: "arrowright",
                            onItemClick: function() {
                                var currentIndex = column.visibleIndex;
                                var columns = e.component.getVisibleColumns();
                                var targetColumn = null;
                                for (var i = 0; i < columns.length; i++) {
                                    if (columns[i].visibleIndex === currentIndex + 1) {
                                        targetColumn = columns[i];
                                        break;
                                    }
                                }

                                // Swap only if target is a valid, reorderable data column
                                if (targetColumn && targetColumn.dataField &&
                                    targetColumn.allowReordering !== false) {
                                    e.component.beginUpdate();
                                    e.component.columnOption(
                                        column.index,
                                        "visibleIndex",
                                        currentIndex + 1
                                    );
                                    e.component.columnOption(
                                        targetColumn.index,
                                        "visibleIndex",
                                        currentIndex
                                    );
                                    e.component.endUpdate();
                                }
                            }
                        });

                        // Set fix position left, right, sticky, and unfix options.
                        // Skip command columns (buttons) and columns that
                        // disallow fixing - fixing them breaks the layout.
                        var isCommandColumn = column.type === "buttons" ||
                            column.command !== undefined;
                        if (!isCommandColumn && column.allowFixing !== false) {
                            e.items.push({
                                text: "Fix Left",
                                icon: "pinleft",
                                onItemClick: function() {
                                    e.component.columnOption(column.index, {
                                        fixed: true,
                                        fixedPosition: "left"
                                    });
                                }
                            });

                            e.items.push({
                                text: "Fix Right",
                                icon: "pinright",
                                onItemClick: function() {
                                    e.component.columnOption(column.index, {
                                        fixed: true,
                                        fixedPosition: "right"
                                    });
                                }
                            });

                            e.items.push({
                                text: "Fix Sticky",
                                icon: "pin",
                                onItemClick: function() {
                                    e.component.columnOption(column.index, {
                                        fixed: true,
                                        fixedPosition: "sticky"
                                    });
                                }
                            });

                            e.items.push({
                                text: "Unfix",
                                icon: "unpin",
                                onItemClick: function() {
                                    e.component.columnOption(column.index, "fixed",
                                        false);
                                }
                            });
                        } // end allowFixing guard
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

        // Touchpad horizontal scrolling for Windows laptops (ASUS TUF etc.)
        // The grid's own internal scroller is the only horizontal scroll now.
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