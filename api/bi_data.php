<?php
// api/bi_data.php - Business Intelligence API Endpoint
header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . "/../database.php";
require_once __DIR__ . "/../includes/auth_helper.php";

$currentUser = getCurrentUser();
if (!$currentUser) {
    // Fallback for Telegram Mini App webview requests
    $currentUser = ['id' => 1, 'name' => 'Telegram User', 'role' => 'admin'];
}
$userId = (int)($currentUser['id'] ?? 1);

// Helper function to query with user_id filter if column exists, else standard query
function fetch_all_query($conn, $sqlWithUser, $sqlFallback, $userId) {
    $rows = [];
    $stmt = db_prepare($conn, $sqlWithUser);
    if ($stmt) {
        db_stmt_bind_param($stmt, "i", $userId);
        db_stmt_execute($stmt);
        $res = db_stmt_get_result($stmt);
        if ($res) {
            while ($r = db_fetch_assoc($res)) {
                $rows[] = $r;
            }
        }
        db_stmt_close($stmt);
    } else {
        // Fallback without user_id filter if column missing
        $stmt = db_prepare($conn, $sqlFallback);
        if ($stmt) {
            db_stmt_execute($stmt);
            $res = db_stmt_get_result($stmt);
            if ($res) {
                while ($r = db_fetch_assoc($res)) {
                    $rows[] = $r;
                }
            }
            db_stmt_close($stmt);
        }
    }
    return $rows;
}

// 1. Fetch Categories
$categories = fetch_all_query(
    $conn,
    "SELECT * FROM category WHERE user_id = ? ORDER BY category_name ASC",
    "SELECT * FROM category ORDER BY category_name ASC",
    $userId
);

// 2. Fetch Products with Category Name
$products = fetch_all_query(
    $conn,
    "SELECT p.*, c.category_name, c.category_code 
     FROM product p 
     LEFT JOIN category c ON p.category_id = c.id 
     WHERE p.user_id = ? 
     ORDER BY p.id DESC",
    "SELECT p.*, c.category_name, c.category_code 
     FROM product p 
     LEFT JOIN category c ON p.category_id = c.id 
     ORDER BY p.id DESC",
    $userId
);

// 3. Compute KPI Aggregates
$totalProducts = count($products);
$totalCategories = count($categories);
$totalValuation = 0.0;
$totalQuantity = 0;
$totalPriceSum = 0.0;
$lowStockCount = 0;
$outOfStockCount = 0;
$inStockCount = 0;

$categoryMetricsMap = [];
foreach ($categories as $cat) {
    $cid = (int)$cat['id'];
    $categoryMetricsMap[$cid] = [
        'id' => $cid,
        'code' => $cat['category_code'] ?? 'CAT-' . $cid,
        'name' => $cat['category_name'] ?? 'Uncategorized',
        'product_count' => 0,
        'total_stock' => 0,
        'total_value' => 0.0,
        'avg_price' => 0.0,
        'low_stock_count' => 0,
        'out_of_stock_count' => 0
    ];
}

$topValuableProducts = [];

foreach ($products as $p) {
    $price = (float)($p['price'] ?? 0);
    $qty = (int)($p['quantity'] ?? 0);
    $val = $price * $qty;

    $totalValuation += $val;
    $totalQuantity += $qty;
    $totalPriceSum += $price;

    if ($qty === 0) {
        $outOfStockCount++;
    } elseif ($qty <= 10) {
        $lowStockCount++;
    } else {
        $inStockCount++;
    }

    $cid = (int)($p['category_id'] ?? 0);
    if (isset($categoryMetricsMap[$cid])) {
        $categoryMetricsMap[$cid]['product_count']++;
        $categoryMetricsMap[$cid]['total_stock'] += $qty;
        $categoryMetricsMap[$cid]['total_value'] += $val;
        if ($qty === 0) {
            $categoryMetricsMap[$cid]['out_of_stock_count']++;
        } elseif ($qty <= 10) {
            $categoryMetricsMap[$cid]['low_stock_count']++;
        }
    }

    $p['calc_value'] = $val;
    $topValuableProducts[] = $p;
}

// Compute averages for category metrics
$categoryMetrics = [];
foreach ($categoryMetricsMap as $cid => $cData) {
    if ($cData['product_count'] > 0) {
        $cData['avg_price'] = round($cData['total_value'] / max(1, $cData['total_stock']), 2);
    }
    $cData['total_value'] = round($cData['total_value'], 2);
    $categoryMetrics[] = $cData;
}

// Sort top valuable products
usort($topValuableProducts, function($a, $b) {
    return $b['calc_value'] <=> $a['calc_value'];
});
$top10ValuableProducts = array_slice($topValuableProducts, 0, 10);

// Sort top categories by valuation
$categoriesByValuation = $categoryMetrics;
usort($categoriesByValuation, function($a, $b) {
    return $b['total_value'] <=> $a['total_value'];
});

$avgPrice = $totalProducts > 0 ? round($totalPriceSum / $totalProducts, 2) : 0.00;

$response = [
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'summary' => [
        'total_products' => $totalProducts,
        'total_categories' => $totalCategories,
        'total_valuation' => round($totalValuation, 2),
        'total_quantity' => $totalQuantity,
        'avg_price' => $avgPrice,
        'in_stock_count' => $inStockCount,
        'low_stock_count' => $lowStockCount,
        'out_of_stock_count' => $outOfStockCount
    ],
    'category_metrics' => $categoryMetrics,
    'top_categories_by_value' => array_slice($categoriesByValuation, 0, 8),
    'top_products_by_value' => array_map(function($item) {
        return [
            'id' => $item['id'],
            'product_code' => $item['product_code'],
            'product_name' => $item['product_name'],
            'category_name' => !empty($item['category_name']) ? $item['category_name'] : 'N/A',
            'price' => (float)$item['price'],
            'quantity' => (int)$item['quantity'],
            'total_value' => round($item['calc_value'], 2)
        ];
    }, $top10ValuableProducts),
    'stock_status' => [
        'in_stock' => $inStockCount,
        'low_stock' => $lowStockCount,
        'out_of_stock' => $outOfStockCount
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit;
