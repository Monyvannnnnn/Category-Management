<?php
/**
 * Standalone HTML Report Generator for Inventory System
 * Produces modern, responsive, styled HTML document files for Telegram push and web viewing.
 */

class InventoryHTML {

    /**
     * Generate complete HTML report document string
     *
     * @param string $title          Report Title
     * @param array  $rows           Product rows data
     * @param float  $totalValuation Total valuation amount
     * @return string Complete HTML document
     */
    public function generateProductsHTML($title, $rows, $totalValuation = 0) {
        $generatedAt = date('d M Y, H:i:s');
        $totalItems = count($rows);
        $totalQty = 0;

        foreach ($rows as $r) {
            $totalQty += (int)($r['quantity'] ?? 0);
        }

        $formattedValuation = number_format((float)$totalValuation, 2);
        $formattedQty = number_format($totalQty);

        $html = '<!DOCTYPE html>' . "\n";
        $html .= '<html lang="en">' . "\n";
        $html .= '<head>' . "\n";
        $html .= '    <meta charset="UTF-8">' . "\n";
        $html .= '    <meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
        $html .= '    <title>' . htmlspecialchars($title) . '</title>' . "\n";
        $html .= '    <style>' . "\n";
        $html .= '        :root {' . "\n";
        $html .= '            --primary: #2563eb;' . "\n";
        $html .= '            --primary-dark: #1d4ed8;' . "\n";
        $html .= '            --bg: #f8fafc;' . "\n";
        $html .= '            --card-bg: #ffffff;' . "\n";
        $html .= '            --text-main: #0f172a;' . "\n";
        $html .= '            --text-muted: #64748b;' . "\n";
        $html .= '            --border: #e2e8f0;' . "\n";
        $html .= '            --header-bg: #0f172a;' . "\n";
        $html .= '            --header-text: #ffffff;' . "\n";
        $html .= '        }' . "\n";
        $html .= '        * { box-sizing: border-box; margin: 0; padding: 0; }' . "\n";
        $html .= '        body {' . "\n";
        $html .= '            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;' . "\n";
        $html .= '            background-color: var(--bg);' . "\n";
        $html .= '            color: var(--text-main);' . "\n";
        $html .= '            padding: 24px 16px;' . "\n";
        $html .= '            line-height: 1.5;' . "\n";
        $html .= '        }' . "\n";
        $html .= '        .container {' . "\n";
        $html .= '            max-width: 1000px;' . "\n";
        $html .= '            margin: 0 auto;' . "\n";
        $html .= '            background: var(--card-bg);' . "\n";
        $html .= '            border-radius: 12px;' . "\n";
        $html .= '            box-shadow: 0 4px 20px rgba(0,0,0,0.08);' . "\n";
        $html .= '            overflow: hidden;' . "\n";
        $html .= '            border: 1px solid var(--border);' . "\n";
        $html .= '        }' . "\n";
        $html .= '        .header {' . "\n";
        $html .= '            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);' . "\n";
        $html .= '            color: var(--header-text);' . "\n";
        $html .= '            padding: 28px 32px;' . "\n";
        $html .= '            position: relative;' . "\n";
        $html .= '        }' . "\n";
        $html .= '        .header-badge {' . "\n";
        $html .= '            display: inline-block;' . "\n";
        $html .= '            background: rgba(37, 99, 235, 0.25);' . "\n";
        $html .= '            color: #60a5fa;' . "\n";
        $html .= '            font-size: 12px;' . "\n";
        $html .= '            font-weight: 600;' . "\n";
        $html .= '            padding: 4px 12px;' . "\n";
        $html .= '            border-radius: 20px;' . "\n";
        $html .= '            text-transform: uppercase;' . "\n";
        $html .= '            letter-spacing: 0.5px;' . "\n";
        $html .= '            margin-bottom: 8px;' . "\n";
        $html .= '        }' . "\n";
        $html .= '        .header h1 {' . "\n";
        $html .= '            font-size: 24px;' . "\n";
        $html .= '            font-weight: 700;' . "\n";
        $html .= '            letter-spacing: -0.5px;' . "\n";
        $html .= '        }' . "\n";
        $html .= '        .header p {' . "\n";
        $html .= '            color: #94a3b8;' . "\n";
        $html .= '            font-size: 13px;' . "\n";
        $html .= '            margin-top: 4px;' . "\n";
        $html .= '        }' . "\n";
        $html .= '        .metrics-grid {' . "\n";
        $html .= '            display: grid;' . "\n";
        $html .= '            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));' . "\n";
        $html .= '            gap: 16px;' . "\n";
        $html .= '            padding: 24px 32px;' . "\n";
        $html .= '            background: #f1f5f9;' . "\n";
        $html .= '            border-bottom: 1px solid var(--border);' . "\n";
        $html .= '        }' . "\n";
        $html .= '        .metric-card {' . "\n";
        $html .= '            background: #ffffff;' . "\n";
        $html .= '            padding: 16px 20px;' . "\n";
        $html .= '            border-radius: 8px;' . "\n";
        $html .= '            border: 1px solid var(--border);' . "\n";
        $html .= '        }' . "\n";
        $html .= '        .metric-card .label {' . "\n";
        $html .= '            font-size: 12px;' . "\n";
        $html .= '            color: var(--text-muted);' . "\n";
        $html .= '            text-transform: uppercase;' . "\n";
        $html .= '            font-weight: 600;' . "\n";
        $html .= '            letter-spacing: 0.5px;' . "\n";
        $html .= '        }' . "\n";
        $html .= '        .metric-card .value {' . "\n";
        $html .= '            font-size: 20px;' . "\n";
        $html .= '            font-weight: 700;' . "\n";
        $html .= '            color: var(--text-main);' . "\n";
        $html .= '            margin-top: 4px;' . "\n";
        $html .= '        }' . "\n";
        $html .= '        .table-wrapper {' . "\n";
        $html .= '            overflow-x: auto;' . "\n";
        $html .= '            padding: 0 32px 32px 32px;' . "\n";
        $html .= '        }' . "\n";
        $html .= '        table {' . "\n";
        $html .= '            width: 100%;' . "\n";
        $html .= '            border-collapse: collapse;' . "\n";
        $html .= '            margin-top: 24px;' . "\n";
        $html .= '            font-size: 14px;' . "\n";
        $html .= '        }' . "\n";
        $html .= '        th {' . "\n";
        $html .= '            background: #f8fafc;' . "\n";
        $html .= '            color: #475569;' . "\n";
        $html .= '            font-weight: 600;' . "\n";
        $html .= '            text-align: left;' . "\n";
        $html .= '            padding: 12px 16px;' . "\n";
        $html .= '            border-bottom: 2px solid var(--border);' . "\n";
        $html .= '            font-size: 12px;' . "\n";
        $html .= '            text-transform: uppercase;' . "\n";
        $html .= '            letter-spacing: 0.5px;' . "\n";
        $html .= '        }' . "\n";
        $html .= '        td {' . "\n";
        $html .= '            padding: 12px 16px;' . "\n";
        $html .= '            border-bottom: 1px solid var(--border);' . "\n";
        $html .= '            color: var(--text-main);' . "\n";
        $html .= '        }' . "\n";
        $html .= '        tr:nth-child(even) td {' . "\n";
        $html .= '            background-color: #fdfdfd;' . "\n";
        $html .= '        }' . "\n";
        $html .= '        tr:hover td {' . "\n";
        $html .= '            background-color: #f1f5f9;' . "\n";
        $html .= '        }' . "\n";
        $html .= '        .text-center { text-align: center; }' . "\n";
        $html .= '        .text-right { text-align: right; }' . "\n";
        $html .= '        .badge-code {' . "\n";
        $html .= '            font-family: monospace;' . "\n";
        $html .= '            background: #f1f5f9;' . "\n";
        $html .= '            padding: 2px 6px;' . "\n";
        $html .= '            border-radius: 4px;' . "\n";
        $html .= '            font-size: 13px;' . "\n";
        $html .= '            color: #334155;' . "\n";
        $html .= '        }' . "\n";
        $html .= '        .badge-qty {' . "\n";
        $html .= '            display: inline-block;' . "\n";
        $html .= '            padding: 2px 8px;' . "\n";
        $html .= '            border-radius: 12px;' . "\n";
        $html .= '            font-weight: 600;' . "\n";
        $html .= '            font-size: 12px;' . "\n";
        $html .= '        }' . "\n";
        $html .= '        .qty-ok { background: #dcfce7; color: #166534; }' . "\n";
        $html .= '        .qty-low { background: #fef9c3; color: #854d0e; }' . "\n";
        $html .= '        .qty-zero { background: #fee2e2; color: #991b1b; }' . "\n";
        $html .= '        .footer {' . "\n";
        $html .= '            background: #f8fafc;' . "\n";
        $html .= '            padding: 16px 32px;' . "\n";
        $html .= '            border-top: 1px solid var(--border);' . "\n";
        $html .= '            font-size: 12px;' . "\n";
        $html .= '            color: var(--text-muted);' . "\n";
        $html .= '            display: flex;' . "\n";
        $html .= '            justify-content: space-between;' . "\n";
        $html .= '            align-items: center;' . "\n";
        $html .= '        }' . "\n";
        $html .= '    </style>' . "\n";
        $html .= '</head>' . "\n";
        $html .= '<body>' . "\n";
        $html .= '    <div class="container">' . "\n";
        $html .= '        <div class="header">' . "\n";
        $html .= '            <div class="header-badge">Telegram Push Report</div>' . "\n";
        $html .= '            <h1>' . htmlspecialchars($title) . '</h1>' . "\n";
        $html .= '            <p>Generated on ' . $generatedAt . ' | Inventory System</p>' . "\n";
        $html .= '        </div>' . "\n";
        $html .= '        <div class="metrics-grid">' . "\n";
        $html .= '            <div class="metric-card">' . "\n";
        $html .= '                <div class="label">Total Products</div>' . "\n";
        $html .= '                <div class="value">' . $totalItems . '</div>' . "\n";
        $html .= '            </div>' . "\n";
        $html .= '            <div class="metric-card">' . "\n";
        $html .= '                <div class="label">Total Stock Qty</div>' . "\n";
        $html .= '                <div class="value">' . $formattedQty . ' units</div>' . "\n";
        $html .= '            </div>' . "\n";
        $html .= '            <div class="metric-card">' . "\n";
        $html .= '                <div class="label">Total Valuation</div>' . "\n";
        $html .= '                <div class="value">$' . $formattedValuation . '</div>' . "\n";
        $html .= '            </div>' . "\n";
        $html .= '        </div>' . "\n";
        $html .= '        <div class="table-wrapper">' . "\n";
        $html .= '            <table>' . "\n";
        $html .= '                <thead>' . "\n";
        $html .= '                    <tr>' . "\n";
        $html .= '                        <th class="text-center">ID</th>' . "\n";
        $html .= '                        <th>Code</th>' . "\n";
        $html .= '                        <th>Product Name</th>' . "\n";
        $html .= '                        <th>Category</th>' . "\n";
        $html .= '                        <th class="text-right">Price</th>' . "\n";
        $html .= '                        <th class="text-center">Quantity</th>' . "\n";
        $html .= '                        <th class="text-right">Total Valuation</th>' . "\n";
        $html .= '                    </tr>' . "\n";
        $html .= '                </thead>' . "\n";
        $html .= '                <tbody>' . "\n";

        if (empty($rows)) {
            $html .= '                    <tr><td colspan="7" class="text-center" style="padding: 32px; color: #94a3b8;">No products found in report.</td></tr>' . "\n";
        } else {
            foreach ($rows as $r) {
                $id = (int)($r['id'] ?? 0);
                $code = htmlspecialchars($r['product_code'] ?? 'N/A');
                $name = htmlspecialchars($r['product_name'] ?? 'N/A');
                $cat = htmlspecialchars($r['category_name'] ?? 'N/A');
                $price = (float)($r['price'] ?? 0);
                $qty = (int)($r['quantity'] ?? 0);
                $val = $price * $qty;

                $qtyClass = ($qty > 5) ? 'qty-ok' : (($qty > 0) ? 'qty-low' : 'qty-zero');

                $html .= '                    <tr>' . "\n";
                $html .= '                        <td class="text-center">#' . $id . '</td>' . "\n";
                $html .= '                        <td><span class="badge-code">' . $code . '</span></td>' . "\n";
                $html .= '                        <td><strong>' . $name . '</strong></td>' . "\n";
                $html .= '                        <td>' . $cat . '</td>' . "\n";
                $html .= '                        <td class="text-right">$' . number_format($price, 2) . '</td>' . "\n";
                $html .= '                        <td class="text-center"><span class="badge-qty ' . $qtyClass . '">' . $qty . '</span></td>' . "\n";
                $html .= '                        <td class="text-right"><strong>$' . number_format($val, 2) . '</strong></td>' . "\n";
                $html .= '                    </tr>' . "\n";
            }
        }

        $html .= '                </tbody>' . "\n";
        $html .= '            </table>' . "\n";
        $html .= '        </div>' . "\n";
        $html .= '        <div class="footer">' . "\n";
        $html .= '            <div>Inventory Management System &bull; Telegram Report Service</div>' . "\n";
        $html .= '            <div>Page 1 of 1</div>' . "\n";
        $html .= '        </div>' . "\n";
        $html .= '    </div>' . "\n";
        $html .= '</body>' . "\n";
        $html .= '</html>';

        return $html;
    }
}
