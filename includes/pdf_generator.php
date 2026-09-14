<?php
/**
 * Simple Pure PHP PDF Generator for Inventory Reports
 * Standard PDF 1.4 compliance with zero external dependencies
 */

class InventoryPDF {
    private $page = 0;
    private $n = 0;
    private $offsets = [];
    private $buffer = '';
    private $w = 210; // A4 width mm
    private $h = 297; // A4 height mm
    private $k = 2.834646; // pt per mm
    private $defOrientation = 'P';
    private $fontSizePt = 10;
    private $fontFamily = 'Helvetica';

    public function __construct() {
        $this->buffer = '';
    }

    private function _out($s) {
        $this->buffer .= $s . "\n";
    }

    private function _newobj() {
        $this->n++;
        $this->offsets[$this->n] = strlen($this->buffer);
        $this->_out($this->n . ' 0 obj');
    }

    public function generateProductsPDF($title, $rows, $totalValuation = 0) {
        $now = date('d/m/Y H:i:s');
        $totalItems = count($rows);

        // Header and layout content streams
        $content = "BT /F1 16 Tf 20 800 Td (" . $this->_escape($title) . ") Tj ET\n";
        $content .= "BT /F1 9 Tf 20 784 Td (Generated: " . $this->_escape($now) . " | Total Items: {$totalItems} | Valuation: $" . number_format($totalValuation, 2) . ") Tj ET\n";
        $content .= "1 0 0 rg 0.2 0.4 0.8 rg\n"; // Header color bar accent

        // Table Header
        $y = 750;
        $content .= "0.15 0.2 0.3 RG 0.15 0.2 0.3 rg\n";
        $content .= "20 " . ($y - 5) . " 555 22 re f\n"; // Header background
        $content .= "BT /F1 10 Tf 1 1 1 rg\n";
        $content .= "25 " . ($y) . " Td (ID) Tj\n";
        $content .= "55 0 Td (PRODUCT CODE) Tj\n";
        $content .= "120 0 Td (PRODUCT NAME) Tj\n";
        $content .= "220 0 Td (CATEGORY) Tj\n";
        $content .= "90 0 Td (PRICE) Tj\n";
        $content .= "40 0 Td (QTY) Tj\n";
        $content .= "30 0 Td (VALUATION) Tj\n";
        $content .= "ET\n";

        $y -= 25;
        $rowIdx = 0;

        foreach ($rows as $row) {
            if ($y < 50) {
                // Future multi-page support can break here
                break;
            }
            $bg = ($rowIdx % 2 === 0) ? "0.96 0.97 0.98 rg" : "1 1 1 rg";
            $content .= "20 " . ($y - 4) . " 555 18 re f\n";
            $content .= "BT /F1 9 Tf 0.1 0.1 0.1 rg\n";

            $id = $this->_escape('#' . ($row['id'] ?? ''));
            $code = $this->_escape(substr($row['product_code'] ?? '', 0, 16));
            $name = $this->_escape(substr($row['product_name'] ?? '', 0, 26));
            $cat = $this->_escape(substr($row['category_name'] ?? 'N/A', 0, 20));
            $price = '$' . number_format((float)($row['price'] ?? 0), 2);
            $qty = (string)($row['quantity'] ?? 0);
            $val = '$' . number_format(((float)($row['price'] ?? 0) * (int)($row['quantity'] ?? 0)), 2);

            $content .= "25 {$y} Td ({$id}) Tj\n";
            $content .= "55 0 Td ({$code}) Tj\n";
            $content .= "120 0 Td ({$name}) Tj\n";
            $content .= "220 0 Td ({$cat}) Tj\n";
            $content .= "90 0 Td ({$price}) Tj\n";
            $content .= "40 0 Td ({$qty}) Tj\n";
            $content .= "30 0 Td ({$val}) Tj\n";
            $content .= "ET\n";

            $y -= 20;
            $rowIdx++;
        }

        // Summary footer bar
        $content .= "0.1 0.15 0.25 rg\n";
        $content .= "20 " . ($y - 5) . " 555 22 re f\n";
        $content .= "BT /F1 10 Tf 1 1 1 rg\n";
        $content .= "25 {$y} Td (TOTAL SUMMARY: {$totalItems} Products Listed | Total Stock Value: $" . number_format($totalValuation, 2) . ") Tj ET\n";

        return $this->_buildPDFDocument($content);
    }

    private function _escape($s) {
        $s = str_replace('\\', '\\\\', $s);
        $s = str_replace('(', '\\(', $s);
        $s = str_replace(')', '\\)', $s);
        return preg_replace('/[^\x20-\x7E]/', '?', $s);
    }

    private function _buildPDFDocument($streamContent) {
        $this->buffer = "%PDF-1.4\n";

        // Catalog (obj 1)
        $this->_newobj();
        $this->_out('<</Type /Catalog /Pages 2 0 R>>');
        $this->_out('endobj');

        // Pages (obj 2)
        $this->_newobj();
        $this->_out('<</Type /Pages /Kids [3 0 R] /Count 1>>');
        $this->_out('endobj');

        // Page 1 (obj 3)
        $this->_newobj();
        $this->_out('<</Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources <</Font <</F1 4 0 R>>>> /Contents 5 0 R>>');
        $this->_out('endobj');

        // Font Helvetica (obj 4)
        $this->_newobj();
        $this->_out('<</Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding>>');
        $this->_out('endobj');

        // Stream (obj 5)
        $this->_newobj();
        $len = strlen($streamContent);
        $this->_out("<</Length {$len}>>");
        $this->_out('stream');
        $this->_out($streamContent);
        $this->_out('endstream');
        $this->_out('endobj');

        // Cross-reference table
        $o = strlen($this->buffer);
        $this->_out('xref');
        $this->_out('0 ' . ($this->n + 1));
        $this->_out('0000000000 65535 f ');
        for ($i = 1; $i <= $this->n; $i++) {
            $this->_out(sprintf('%010d 00000 n ', $this->offsets[$i]));
        }

        // Trailer
        $this->_out('trailer');
        $this->_out("<</Size " . ($this->n + 1) . " /Root 1 0 R>>");
        $this->_out('startxref');
        $this->_out($o);
        $this->_out('%%EOF');

        return $this->buffer;
    }
}
