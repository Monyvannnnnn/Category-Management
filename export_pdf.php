<?php
// export_pdf.php - exports your REAL project table (category) to PDF.
// It reuses the SAME connection as index.php (db_config.php + database.php)
// and reads the SAME rows, so it is never mock/demo data.
// The table is rendered VISIBLY on the page, then html2canvas captures it
// (capturing a visible element avoids the "blank PDF" problem).

require_once __DIR__ . "/database.php";

// Read the category table - identical query to index.php's read endpoint.
$sql = "SELECT * FROM category ORDER BY id DESC";
$res = mysqli_query($conn, $sql);
if (!$res) {
    die("Query failed: " . mysqli_error($conn));
}
$rows = [];
while ($r = mysqli_fetch_assoc($res)) {
    $rows[] = $r;
}
mysqli_free_result($res);

// Column definitions (caption + dataField) matching your grid.
$columns = [
    ["caption" => "ID",            "dataField" => "id"],
    ["caption" => "Category Code", "dataField" => "category_code"],
    ["caption" => "Category Name", "dataField" => "category_name"],
    ["caption" => "Created At",    "dataField" => "created_at"],
    ["caption" => "Last Update",   "dataField" => "lastupdate"]
];

// Helper: format a datetime like your JS formatDateTime (dd/MM/yyyy HH:mm:ss)
function fmt($dt) {
    if (!$dt) return "";
    $t = strtotime($dt);
    return $t ? date("d/m/Y H:i:s", $t) : $dt;
}
?>
<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Categories PDF</title>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <style>
        body { font-family: Arial, sans-serif; padding: 24px; }
        #exportBtn {
            background: #ef4444; color: #fff; border: 0; padding: 10px 18px;
            border-radius: 6px; cursor: pointer; font-size: 15px; margin-bottom: 16px;
        }
        /* VISIBLE table (not hidden) so html2canvas captures real content */
        #htmlData {
            font-family: "KhmerOSWeb", "Khmer OS Siemreap", Arial, sans-serif;
            color: #0f172a;
            width: 100%;
            max-width: 900px;
            border-collapse: collapse;
            background: #fff;
        }
        #htmlData th {
            background: #ef4444; color: #fff; padding: 8px 10px; text-align: left;
            border: 1px solid #cbd5e1;
        }
        #htmlData td {
            padding: 7px 10px; border: 1px solid #e2e8f0;
        }
        #htmlData tr:nth-child(even) td { background: #fef2f2; }
        #rowCount { color: #64748b; margin: 8px 0; font-size: 13px; }
    </style>
</head>
<body>

    <button id="exportBtn">Export PDF (<?php echo count($rows); ?> rows)</button>
    <div id="rowCount">Real data from table `category`: <?php echo count($rows); ?> row(s).</div>

    <table id="htmlData">
        <thead>
            <tr>
                <?php foreach ($columns as $c): ?>
                    <th><?php echo htmlspecialchars($c["caption"], ENT_QUOTES, "UTF-8"); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (count($rows) === 0): ?>
                <tr><td colspan="<?php echo count($columns); ?>">No categories found.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <?php foreach ($columns as $c):
                            $df = $c["dataField"];
                            $val = $row[$df] ?? "";
                            if ($df === "created_at" || $df === "lastupdate") {
                                $val = fmt($val);
                            }
                        ?>
                            <td><?php echo htmlspecialchars($val, ENT_QUOTES, "UTF-8"); ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Khmer OS Siemreap as a real webfont (set window.__khmerB64) -->
    <script>
    window.title = "Categories_Export";
    window.__khmerB64 = "<?php
        $js = @file_get_contents(__DIR__ . '/js/KhmerOSSiemreap.js');
        if ($js && preg_match('/window\.KhmerOsSeimreapBase64\s*=\s*["\']([A-Za-z0-9+\/=]+)["\']/', $js, $m)) {
            echo $m[1];
        }
    ?>";
    (function () {
        if (!window.__khmerB64) { console.warn("Khmer font base64 missing - using fallback."); return; }
        var s = document.createElement('style');
        s.textContent = '@font-face{font-family:"KhmerOSWeb";src:url(data:font/ttf;base64,'
            + window.__khmerB64 + ') format("truetype");font-weight:normal;font-style:normal;font-display:swap;}';
        document.head.appendChild(s);
    })();
    </script>

    <script>
    $(document).ready(function () {
        function openPDF() {
            const { jsPDF } = window.jspdf;
            const el = document.getElementById('htmlData');
            if (!el) { alert("Table #htmlData not found!"); return; }

            // Wait for fonts (Khmer shaping) then capture the VISIBLE table.
            const go = function () {
                html2canvas(el, {
                    scale: 2,
                    useCORS: true,
                    backgroundColor: "#ffffff",
                    logging: false,
                    onclone: function (clonedDoc) {
                        try {
                            if (window.__khmerB64) {
                                var s = clonedDoc.createElement("style");
                                s.textContent = '@font-face{font-family:"KhmerOSWeb";src:url(data:font/ttf;base64,'
                                    + window.__khmerB64 + ') format("truetype");font-weight:normal;font-style:normal;}';
                                clonedDoc.head.appendChild(s);
                            }
                        } catch (e) {}
                    }
                }).then(function (canvas) {
                    if (!canvas || canvas.width === 0) { alert("Capture failed: empty canvas."); return; }
                    const fileWidth = 208;
                    const fileHeight = (canvas.height * fileWidth) / canvas.width;
                    const fileUri = canvas.toDataURL('image/png');
                    const pdf = new jsPDF('p', 'mm', 'a4');
                    pdf.addImage(fileUri, 'PNG', 0, 0, fileWidth, fileHeight);
                    pdf.save((window.title || 'Export') + '.pdf');
                }).catch(function (error) {
                    alert("html2canvas export failed: " + (error && error.message ? error.message : error));
                });
            };

            if (document.fonts && document.fonts.ready) {
                document.fonts.ready.then(go).catch(go);
            } else {
                setTimeout(go, 400);
            }
        }
        $('#exportBtn').on('click', openPDF);
    });
    </script>

</body>
</html>
