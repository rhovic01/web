<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Get filter parameters from POST
$reportType = $_POST['report_type'] ?? 'both';
$startDate = $_POST['start_date'] ?? '';
$endDate = $_POST['end_date'] ?? '';
$inventoryStatus = $_POST['inventory_status'] ?? 'all';
$transactionStatus = $_POST['transaction_status'] ?? 'all';

// Build inventory query
$inventoryWhere = [];
$inventoryParams = [];
if ($inventoryStatus !== 'all') {
    $inventoryWhere[] = "item_availability = ?";
    $inventoryParams[] = $inventoryStatus;
}
$inventorySql = "SELECT * FROM inventory";
if (!empty($inventoryWhere)) {
    $inventorySql .= " WHERE " . implode(" AND ", $inventoryWhere);
}

// Build transactions query
$transactionWhere = [];
$transactionParams = [];
if ($transactionStatus !== 'all') {
    $transactionWhere[] = "status = ?";
    $transactionParams[] = $transactionStatus;
}
if (!empty($startDate)) {
    $transactionWhere[] = "transaction_date >= ?";
    $transactionParams[] = $startDate . ' 00:00:00';
}
if (!empty($endDate)) {
    $transactionWhere[] = "transaction_date <= ?";
    $transactionParams[] = $endDate . ' 23:59:59';
}

$transactionSql = "SELECT * FROM transactions";
if (!empty($transactionWhere)) {
    $transactionSql .= " WHERE " . implode(" AND ", $transactionWhere);
}
$transactionSql .= " ORDER BY transaction_date DESC";

// Execute queries based on selected report type
$inventoryData = [];
$transactionData = [];

if ($reportType === 'inventory' || $reportType === 'both') {
    $inventoryStmt = $conn->prepare($inventorySql);
    if (!empty($inventoryParams)) {
        $inventoryStmt->bind_param(str_repeat('s', count($inventoryParams)), ...$inventoryParams);
    }
    $inventoryStmt->execute();
    $inventoryResult = $inventoryStmt->get_result();
    $inventoryData = $inventoryResult->fetch_all(MYSQLI_ASSOC);
    $inventoryStmt->close();
}

if ($reportType === 'transactions' || $reportType === 'both') {
    $transactionStmt = $conn->prepare($transactionSql);
    if (!empty($transactionParams)) {
        $transactionStmt->bind_param(str_repeat('s', count($transactionParams)), ...$transactionParams);
    }
    $transactionStmt->execute();
    $transactionResult = $transactionStmt->get_result();
    $transactionData = $transactionResult->fetch_all(MYSQLI_ASSOC);
    $transactionStmt->close();
}

$conn->close();

// Set headers for PDF
header('Content-Type: application/json');

// Prepare data for PDF generation
$data = [
    'reportInfo' => [
        'generatedOn' => date('Y-m-d H:i:s'),
        'generatedBy' => $_SESSION['username'],
        'reportType' => ucfirst($reportType),
        'dateRange' => [
            'start' => !empty($startDate) ? $startDate : 'Start',
            'end' => !empty($endDate) ? $endDate : 'End'
        ]
    ],
    'inventory' => [],
    'transactions' => []
];

// Format inventory data
if (!empty($inventoryData)) {
    foreach ($inventoryData as $item) {
        $data['inventory'][] = [
            'id' => $item['id'],
            'itemName' => htmlspecialchars($item['item_name']),
            'quantity' => $item['item_quantity'],
            'availability' => ucfirst($item['item_availability']),
            'lastUpdated' => !empty($item['last_updated']) ? 
                date('Y-m-d H:i', strtotime($item['last_updated'])) : 'N/A'
        ];
    }
}

// Format transaction data
if (!empty($transactionData)) {
    foreach ($transactionData as $transaction) {
        $data['transactions'][] = [
            'id' => $transaction['id'],
            'itemId' => $transaction['item_id'] ?? 'N/A',
            'itemName' => htmlspecialchars($transaction['item_name'] ?? 'N/A'),
            'studentId' => htmlspecialchars($transaction['student_id']),
            'studentName' => htmlspecialchars($transaction['student_name']),
            'type' => ucfirst($transaction['transaction_type'] ?? 'N/A'),
            'date' => date('Y-m-d H:i', strtotime($transaction['transaction_date'])),
            'status' => ucfirst($transaction['status']),
            'verifiedBy' => htmlspecialchars($transaction['verified_by'])
        ];
    }
}

// Return JSON data
echo json_encode($data);
?> 