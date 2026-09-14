<?php
/**
 * Pure PHP Excel (SpreadsheetML) Report Generator
 * Compatible with Microsoft Excel, Apple Numbers, WPS Office & Mobile Viewers
 * Output: Real formatted Excel spreadsheet document (.xls / .xlsx)
 */

class InventoryExcel {
    /**
     * Generate Products Excel Spreadsheet
     */
    public function generateProductsExcel($title, $rows, $totalValuation) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        $xml .= ' xmlns:o="urn:schemas-microsoft-com:office:office"' . "\n";
        $xml .= ' xmlns:x="urn:schemas-microsoft-com:office:excel"' . "\n";
        $xml .= ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        $xml .= ' xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";

        // Styles
        $xml .= ' <Styles>' . "\n";
        // Title Style
        $xml .= '  <Style ss:ID="TitleStyle">' . "\n";
        $xml .= '   <Font ss:Bold="1" ss:Color="#0F172A" ss:Size="14" ss:FontName="Calibri"/>' . "\n";
        $xml .= '   <Alignment ss:Horizontal="Left" ss:Vertical="Center"/>' . "\n";
        $xml .= '  </Style>' . "\n";
        // Header Style (Dark Blue Navy)
        $xml .= '  <Style ss:ID="HeaderStyle">' . "\n";
        $xml .= '   <Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="11" ss:FontName="Calibri"/>' . "\n";
        $xml .= '   <Interior ss:Color="#1E293B" ss:Pattern="Solid"/>' . "\n";
        $xml .= '   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>' . "\n";
        $xml .= '   <Borders>' . "\n";
        $xml .= '    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#0F172A"/>' . "\n";
        $xml .= '   </Borders>' . "\n";
        $xml .= '  </Style>' . "\n";
        // Data Normal
        $xml .= '  <Style ss:ID="DataStyle">' . "\n";
        $xml .= '   <Font ss:Color="#1E293B" ss:Size="10" ss:FontName="Calibri"/>' . "\n";
        $xml .= '   <Alignment ss:Vertical="Center"/>' . "\n";
        $xml .= '  </Style>' . "\n";
        // Data Center
        $xml .= '  <Style ss:ID="DataCenter">' . "\n";
        $xml .= '   <Font ss:Color="#1E293B" ss:Size="10" ss:FontName="Calibri"/>' . "\n";
        $xml .= '   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>' . "\n";
        $xml .= '  </Style>' . "\n";
        // Currency Format
        $xml .= '  <Style ss:ID="CurrencyStyle">' . "\n";
        $xml .= '   <Font ss:Color="#1E293B" ss:Size="10" ss:FontName="Calibri"/>' . "\n";
        $xml .= '   <NumberFormat ss:Format="$#,##0.00"/>' . "\n";
        $xml .= '   <Alignment ss:Horizontal="Right" ss:Vertical="Center"/>' . "\n";
        $xml .= '  </Style>' . "\n";
        // Number Format
        $xml .= '  <Style ss:ID="NumberStyle">' . "\n";
        $xml .= '   <Font ss:Color="#1E293B" ss:Size="10" ss:FontName="Calibri"/>' . "\n";
        $xml .= '   <NumberFormat ss:Format="#,##0"/>' . "\n";
        $xml .= '   <Alignment ss:Horizontal="Right" ss:Vertical="Center"/>' . "\n";
        $xml .= '  </Style>' . "\n";
        // Total Summary Footer
        $xml .= '  <Style ss:ID="TotalFooter">' . "\n";
        $xml .= '   <Font ss:Bold="1" ss:Color="#0F172A" ss:Size="11" ss:FontName="Calibri"/>' . "\n";
        $xml .= '   <Interior ss:Color="#E2E8F0" ss:Pattern="Solid"/>' . "\n";
        $xml .= '   <Alignment ss:Vertical="Center"/>' . "\n";
        $xml .= '  </Style>' . "\n";
        $xml .= ' </Styles>' . "\n";

        // Worksheet
        $xml .= ' <Worksheet ss:Name="Inventory Products">' . "\n";
        $xml .= '  <Table>' . "\n";
        // Column Widths
        $xml .= '   <Column ss:Width="50"/>' . "\n";  // ID
        $xml .= '   <Column ss:Width="110"/>' . "\n"; // Code
        $xml .= '   <Column ss:Width="200"/>' . "\n"; // Name
        $xml .= '   <Column ss:Width="150"/>' . "\n"; // Category
        $xml .= '   <Column ss:Width="90"/>' . "\n";  // Price
        $xml .= '   <Column ss:Width="80"/>' . "\n";  // Qty
        $xml .= '   <Column ss:Width="110"/>' . "\n"; // Valuation
        $xml .= '   <Column ss:Width="140"/>' . "\n"; // Created At

        // Title Row
        $xml .= '   <Row ss:Height="30" ss:StyleID="TitleStyle">' . "\n";
        $xml .= '    <Cell ss:MergeAcross="7"><Data ss:Type="String">' . htmlspecialchars($title) . ' (Generated: ' . date('Y-m-d H:i:s') . ')</Data></Cell>' . "\n";
        $xml .= '   </Row>' . "\n";
        $xml .= '   <Row ss:Height="10"></Row>' . "\n"; // Empty spacer

        // Header Row
        $xml .= '   <Row ss:Height="26" ss:StyleID="HeaderStyle">' . "\n";
        $xml .= '    <Cell><Data ss:Type="String">ID</Data></Cell>' . "\n";
        $xml .= '    <Cell><Data ss:Type="String">Product Code</Data></Cell>' . "\n";
        $xml .= '    <Cell><Data ss:Type="String">Product Name</Data></Cell>' . "\n";
        $xml .= '    <Cell><Data ss:Type="String">Category</Data></Cell>' . "\n";
        $xml .= '    <Cell><Data ss:Type="String">Price ($)</Data></Cell>' . "\n";
        $xml .= '    <Cell><Data ss:Type="String">Quantity</Data></Cell>' . "\n";
        $xml .= '    <Cell><Data ss:Type="String">Valuation ($)</Data></Cell>' . "\n";
        $xml .= '    <Cell><Data ss:Type="String">Created At</Data></Cell>' . "\n";
        $xml .= '   </Row>' . "\n";

        // Data Rows
        $totalQty = 0;
        $totalVal = 0;
        foreach ($rows as $r) {
            $id = (int)($r['id'] ?? 0);
            $code = htmlspecialchars($r['product_code'] ?? '');
            $name = htmlspecialchars($r['product_name'] ?? '');
            $cat = htmlspecialchars($r['category_name'] ?? 'N/A');
            $price = (float)($r['price'] ?? 0);
            $qty = (int)($r['quantity'] ?? 0);
            $val = $price * $qty;
            $created = htmlspecialchars($r['created_at'] ?? '');

            $totalQty += $qty;
            $totalVal += $val;

            $xml .= '   <Row ss:Height="22" ss:StyleID="DataStyle">' . "\n";
            $xml .= '    <Cell ss:StyleID="DataCenter"><Data ss:Type="Number">' . $id . '</Data></Cell>' . "\n";
            $xml .= '    <Cell ss:StyleID="DataCenter"><Data ss:Type="String">' . $code . '</Data></Cell>' . "\n";
            $xml .= '    <Cell><Data ss:Type="String">' . $name . '</Data></Cell>' . "\n";
            $xml .= '    <Cell><Data ss:Type="String">' . $cat . '</Data></Cell>' . "\n";
            $xml .= '    <Cell ss:StyleID="CurrencyStyle"><Data ss:Type="Number">' . number_format($price, 2, '.', '') . '</Data></Cell>' . "\n";
            $xml .= '    <Cell ss:StyleID="NumberStyle"><Data ss:Type="Number">' . $qty . '</Data></Cell>' . "\n";
            $xml .= '    <Cell ss:StyleID="CurrencyStyle"><Data ss:Type="Number">' . number_format($val, 2, '.', '') . '</Data></Cell>' . "\n";
            $xml .= '    <Cell ss:StyleID="DataCenter"><Data ss:Type="String">' . $created . '</Data></Cell>' . "\n";
            $xml .= '   </Row>' . "\n";
        }

        // Total Summary Row
        $xml .= '   <Row ss:Height="25" ss:StyleID="TotalFooter">' . "\n";
        $xml .= '    <Cell ss:MergeAcross="3"><Data ss:Type="String">TOTAL SUMMARY (' . count($rows) . ' Products Listed)</Data></Cell>' . "\n";
        $xml .= '    <Cell><Data ss:Type="String"></Data></Cell>' . "\n";
        $xml .= '    <Cell ss:StyleID="NumberStyle"><Data ss:Type="Number">' . $totalQty . '</Data></Cell>' . "\n";
        $xml .= '    <Cell ss:StyleID="CurrencyStyle"><Data ss:Type="Number">' . number_format($totalVal, 2, '.', '') . '</Data></Cell>' . "\n";
        $xml .= '    <Cell><Data ss:Type="String"></Data></Cell>' . "\n";
        $xml .= '   </Row>' . "\n";

        $xml .= '  </Table>' . "\n";
        $xml .= ' </Worksheet>' . "\n";
        $xml .= '</Workbook>';

        return $xml;
    }
}
