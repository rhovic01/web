<?php
require 'db_connect.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$isAdmin = ($_SESSION['role'] === 'admin');

// Initialize filter variables
$reportType = $_POST['report_type'] ?? $_GET['report_type'] ?? 'both';
$startDate = $_POST['start_date'] ?? $_GET['start_date'] ?? '';
$endDate = $_POST['end_date'] ?? $_GET['end_date'] ?? '';
$inventoryStatus = $_POST['inventory_status'] ?? $_GET['inventory_status'] ?? 'all';
$transactionStatus = $_POST['transaction_status'] ?? $_GET['transaction_status'] ?? 'all';

// Pagination settings
$itemsPerPage = isset($_POST['print_all']) || isset($_GET['print_all']) ? PHP_INT_MAX : 10;
$inventoryPage = isset($_GET['invent_page']) ? (int)$_GET['invent_page'] : 1;
$transactionPage = isset($_GET['transact_page']) ? (int)$_GET['transact_page'] : 1;

// Build inventory query with pagination
$inventoryWhere = [];
$inventoryParams = [];
$inventoryTypes = '';

if ($inventoryStatus !== 'all') {
    $inventoryWhere[] = "item_availability = ?";
    $inventoryParams[] = $inventoryStatus;
    $inventoryTypes .= 's';
}

$inventoryWhereClause = '';
if (!empty($inventoryWhere)) {
    $inventoryWhereClause = " WHERE " . implode(" AND ", $inventoryWhere);
}

// Count total inventory items
$inventoryCountSql = "SELECT COUNT(*) as total FROM inventory" . $inventoryWhereClause;
$inventoryCountStmt = $conn->prepare($inventoryCountSql);
if (!empty($inventoryParams)) {
    $inventoryCountStmt->bind_param($inventoryTypes, ...$inventoryParams);
}
$inventoryCountStmt->execute();
$inventoryTotalItems = $inventoryCountStmt->get_result()->fetch_assoc()['total'];
$inventoryTotalPages = ceil($inventoryTotalItems / $itemsPerPage);
$inventoryCountStmt->close();

// Get inventory data with pagination
$inventoryData = [];
if ($reportType === 'inventory' || $reportType === 'both') {
    $inventoryOffset = ($inventoryPage - 1) * $itemsPerPage;
    $inventorySql = "SELECT * FROM inventory" . $inventoryWhereClause . " LIMIT ? OFFSET ?";
    
    $inventoryStmt = $conn->prepare($inventorySql);
    $allInventoryParams = $inventoryParams;
    $allInventoryParams[] = $itemsPerPage;
    $allInventoryParams[] = $inventoryOffset;
    $allInventoryTypes = $inventoryTypes . 'ii';
    
    if (!empty($allInventoryParams)) {
        $inventoryStmt->bind_param($allInventoryTypes, ...$allInventoryParams);
    }
    
    $inventoryStmt->execute();
    $inventoryResult = $inventoryStmt->get_result();
    $inventoryData = $inventoryResult->fetch_all(MYSQLI_ASSOC);
    $inventoryStmt->close();
}

// Build transaction query with pagination
$transactionWhere = [];
$transactionParams = [];
$transactionTypes = '';

if ($transactionStatus !== 'all') {
    $transactionWhere[] = "status = ?";
    $transactionParams[] = $transactionStatus;
    $transactionTypes .= 's';
}
if (!empty($startDate)) {
    $transactionWhere[] = "transaction_date >= ?";
    $transactionParams[] = $startDate . ' 00:00:00';
    $transactionTypes .= 's';
}
if (!empty($endDate)) {
    $transactionWhere[] = "transaction_date <= ?";
    $transactionParams[] = $endDate . ' 23:59:59';
    $transactionTypes .= 's';
}

$transactionWhereClause = '';
if (!empty($transactionWhere)) {
    $transactionWhereClause = " WHERE " . implode(" AND ", $transactionWhere);
}

// Count total transactions
$transactionCountSql = "SELECT COUNT(*) as total FROM transactions" . $transactionWhereClause;
$transactionCountStmt = $conn->prepare($transactionCountSql);
if (!empty($transactionParams)) {
    $transactionCountStmt->bind_param($transactionTypes, ...$transactionParams);
}
$transactionCountStmt->execute();
$transactionTotalItems = $transactionCountStmt->get_result()->fetch_assoc()['total'];
$transactionTotalPages = ceil($transactionTotalItems / $itemsPerPage);
$transactionCountStmt->close();

// Get transaction data with pagination
$transactionData = [];
if ($reportType === 'transactions' || $reportType === 'both') {
    $transactionOffset = ($transactionPage - 1) * $itemsPerPage;
    $transactionSql = "SELECT t.*, i.item_name FROM transactions t 
                       LEFT JOIN inventory i ON t.item_id = i.id" . 
                       $transactionWhereClause . 
                       " ORDER BY transaction_date DESC LIMIT ? OFFSET ?";
    
    $transactionStmt = $conn->prepare($transactionSql);
    $allTransactionParams = $transactionParams;
    $allTransactionParams[] = $itemsPerPage;
    $allTransactionParams[] = $transactionOffset;
    $allTransactionTypes = $transactionTypes . 'ii';
    
    if (!empty($allTransactionParams)) {
        $transactionStmt->bind_param($allTransactionTypes, ...$allTransactionParams);
    }
    
    $transactionStmt->execute();
    $transactionResult = $transactionStmt->get_result();
    $transactionData = $transactionResult->fetch_all(MYSQLI_ASSOC);
    $transactionStmt->close();
}

$conn->close();

// Function to build query string for pagination
function buildReportQueryString($pageType, $pageNum, $reportType, $inventoryStatus, $transactionStatus, $startDate, $endDate, $tab = 'reports') {
    $params = [
        'report_type' => $reportType,
        'inventory_status' => $inventoryStatus,
        'transaction_status' => $transactionStatus,
        'start_date' => $startDate,
        'end_date' => $endDate,
        $pageType => $pageNum,
        'tab' => $tab
    ];
    
    // Remove empty parameters
    $params = array_filter($params, function($value) {
        return $value !== '' && $value !== null;
    });
    
    return http_build_query($params);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .filter-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .badge-available {
            background-color: #28a745;
        }
        .badge-unavailable {
            background-color: #dc3545;
        }
        .badge-borrowed {
            background-color: #ffc107;
            color: #212529;
        }
        .badge-returned {
            background-color: #17a2b8;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                width: 100%;
                font-size: 11px; /* Slightly smaller font for landscape */
                padding: 15px;
                font-family: 'Helvetica', 'Arial', sans-serif;
                color: #000;
                background: #fff;
            }
            .container {
                max-width: 100% !important;
                width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            h1 {
                text-align: center;
                font-size: 24px;
                color: #03396c;
                margin-bottom: 10px;
                font-weight: bold;
                text-transform: uppercase;
            }
            .card {
                border: none !important;
                box-shadow: none !important;
                margin-bottom: 30px !important;
            }
            .card-header {
                background-color: #fff !important;
                border-bottom: 2px solid #03396c !important;
                padding: 15px 0 !important;
            }
            .card-header h5 {
                color: #03396c;
                font-size: 18px;
                font-weight: bold;
                margin: 0;
            }
            .card-body {
                padding: 20px 0 !important;
            }
            .table {
                width: 100% !important;
                margin-bottom: 1rem;
                page-break-inside: auto;
                border-collapse: collapse !important;
            }
            .table th {
                background-color: #03396c !important;
                color: #fff !important;
                font-weight: bold;
                font-size: 10px;
                padding: 8px 6px !important;
                border: 1px solid #dee2e6 !important;
            }
            .table td {
                padding: 6px !important;
                font-size: 10px;
                border: 1px solid #dee2e6 !important;
                vertical-align: middle !important;
            }
            .table tr:nth-child(even) {
                background-color: #f8f9fa !important;
            }
            .badge {
                font-size: 11px !important;
                padding: 5px 10px !important;
                font-weight: normal !important;
                border-radius: 4px !important;
            }
            .badge.bg-success, .badge.bg-success.bg-opacity-10 {
                background-color: #d4edda !important;
                color: #155724 !important;
                border: 1px solid #c3e6cb !important;
            }
            .badge.bg-danger, .badge.bg-danger.bg-opacity-10 {
                background-color: #f8d7da !important;
                color: #721c24 !important;
                border: 1px solid #f5c6cb !important;
            }
            .badge.bg-info, .badge.bg-info.bg-opacity-10 {
                background-color: #d1ecf1 !important;
                color: #0c5460 !important;
                border: 1px solid #bee5eb !important;
            }
            .badge.bg-primary {
                background-color: #cce5ff !important;
                color: #004085 !important;
                border: 1px solid #b8daff !important;
            }
            .pagination-info {
                display: none !important;
            }
            .report-section {
                margin-bottom: 30px;
                page-break-inside: avoid;
            }
            /* Report Summary Styles */
            .card.mb-4 {
                margin-bottom: 30px !important;
            }
            .card.mb-4 .card-header {
                background-color: #f8f9fa !important;
                border-bottom: 2px solid #03396c !important;
            }
            .card.mb-4 .card-body {
                padding: 15px 0 !important;
            }
            .card.mb-4 .card-body p {
                margin-bottom: 8px;
                font-size: 12px;
            }
            .card.mb-4 .card-body strong {
                color: #03396c;
            }
            /* Footer */
            @page {
                size: landscape;
                margin: 1.5cm;
            }
            @page :first {
                margin-top: 1.5cm;
            }
            .print-footer {
                position: fixed;
                bottom: 0;
                width: 100%;
                text-align: center;
                font-size: 10px;
                color: #6c757d;
                padding: 10px 0;
                border-top: 1px solid #dee2e6;
            }
        }
        .count-number {
            font-size: 35px;
            font-weight: bold;
            margin-left: 5px;
        }
        .pagination-info {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1 class="mb-4">Reports</h1>

        <!-- Filter Section -->
        <div class="filter-section no-print">
            <h4><i class="fas fa-filter"></i> Filter Options</h4>
            <form method="GET" id="reportForm">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label for="report_type" class="form-label">Report Type</label>
                        <select class="form-select" id="report_type" name="report_type">
                            <option value="both" <?= $reportType === 'both' ? 'selected' : '' ?>>Both Reports</option>
                            <option value="inventory" <?= $reportType === 'inventory' ? 'selected' : '' ?>>Inventory Only</option>
                            <option value="transactions" <?= $reportType === 'transactions' ? 'selected' : '' ?>>Transactions Only</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3 inventory-field" <?= $reportType === 'transactions' ? 'style="display:none"' : '' ?>>
                        <label for="inventory_status" class="form-label">Inventory Status</label>
                        <select class="form-select" id="inventory_status" name="inventory_status">
                            <option value="all" <?= $inventoryStatus === 'all' ? 'selected' : '' ?>>All Items</option>
                            <option value="available" <?= $inventoryStatus === 'available' ? 'selected' : '' ?>>Available Only</option>
                            <option value="unavailable" <?= $inventoryStatus === 'unavailable' ? 'selected' : '' ?>>Unavailable Only</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3 transaction-field" <?= $reportType === 'inventory' ? 'style="display:none"' : '' ?>>
                        <label for="transaction_status" class="form-label">Transaction Status</label>
                        <select class="form-select" id="transaction_status" name="transaction_status">
                            <option value="all" <?= $transactionStatus === 'all' ? 'selected' : '' ?>>All Transactions</option>
                            <option value="borrowed" <?= $transactionStatus === 'borrowed' ? 'selected' : '' ?>>Borrowed</option>
                            <option value="returned" <?= $transactionStatus === 'returned' ? 'selected' : '' ?>>Returned</option>
                        </select>
                    </div> 
                </div>
                
                <div class="row mb-3 transaction-field" <?= $reportType === 'inventory' ? 'style="display:none"' : '' ?>>
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control datepicker" id="start_date" name="start_date" value="<?= $startDate ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control datepicker" id="end_date" name="end_date" value="<?= $endDate ?>">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Apply Filters</button>
                <button type="button" class="btn btn-secondary" onclick="resetFilters()"><i class="fas fa-undo"></i> Reset</button>
            </form>
        </div>

        <!-- Action Buttons -->
        <div class="mb-3 no-print">
            <button class="btn btn-primary" onclick="printReport()"><i class="fas fa-print"></i> Print Report</button>
            <button class="btn btn-success" onclick="exportToExcel()"><i class="fas fa-file-excel"></i> Export to Excel</button>
            <button class="btn btn-danger" onclick="exportToPDF()"><i class="fas fa-file-pdf"></i> Export to PDF</button>
        </div>

        <!-- Report Summary -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Report Summary</h5>
            </div>
            <div class="card-body">
                <p><strong>Generated on:</strong> <?= date('Y-m-d H:i:s') ?></p>
                <p><strong>Generated by:</strong> <?= $_SESSION['username'] ?></p>
                <p><strong>Report Type:</strong> <?= ucfirst($reportType) ?> report</p>
                <?php if ($reportType !== 'inventory'): ?>
                <p><strong>Date Range:</strong> <?= !empty($startDate) ? $startDate : 'Start' ?> to <?= !empty($endDate) ? $endDate : 'End' ?></p>
                <?php endif; ?>
            </div>
        </div>

        <?php if (empty($inventoryData) && empty($transactionData)): ?>
            <div class="alert alert-warning" role="alert">
                No data available for the selected filters.
            </div>
        <?php else: ?>
            <!-- Inventory Report -->
            <?php if (($reportType === 'inventory' || $reportType === 'both') && ($inventoryTotalItems > 0)): ?>
                <div class="report-section">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Inventory Report</h5>
                            <span class="badge bg-primary rounded-pill">Total: <?= $inventoryTotalItems ?></span>
                        </div>
                        <div class="card-body">
                            <!-- Pagination Info -->
                            <?php if (!isset($_POST['print_all']) && !isset($_GET['print_all'])): ?>
                            <div class="pagination-info">
                                Showing <?= (($inventoryPage - 1) * $itemsPerPage) + 1 ?> to 
                                <?= min($inventoryPage * $itemsPerPage, $inventoryTotalItems) ?> of 
                                <?= $inventoryTotalItems ?> entries
                            </div>
                            <?php endif; ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr class="bg-light">
                                            <th class="border-0">ID</th>
                                            <th class="border-0">Item Name</th>
                                            <th class="border-0">Quantity</th>
                                            <th class="border-0">Availability</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($inventoryData)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-4">
                                                    <i class="fas fa-inbox fa-2x mb-2 text-muted"></i>
                                                    <p class="text-muted">No inventory items found</p>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($inventoryData as $item): ?>
                                                <tr>
                                                    <td><?= $item['id'] ?></td>
                                                    <td><?= htmlspecialchars($item['item_name']) ?></td>
                                                    <td><?= $item['item_quantity'] ?></td>
                                                    <td>
                                                        <span class="badge rounded-pill <?= $item['item_availability'] === 'available' ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger' ?>">
                                                            <?= ucfirst($item['item_availability']) ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Inventory Pagination -->
                <?php if ($inventoryTotalPages > 1 && !isset($_POST['print_all']) && !isset($_GET['print_all'])): ?>
                    <nav aria-label="Inventory pagination">
                        <ul class="pagination justify-content-center mt-4">
                            <?php if ($inventoryPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" 
                                       href="?<?= buildReportQueryString('invent_page', $inventoryPage - 1, $reportType, $inventoryStatus, $transactionStatus, $startDate, $endDate, 'reports') ?>" 
                                       aria-label="Previous">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <a class="page-link" href="#" aria-label="Previous">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php
                            $maxVisiblePages = 5;
                            $startPage = max(1, $inventoryPage - floor($maxVisiblePages / 2));
                            $endPage = min($inventoryTotalPages, $startPage + $maxVisiblePages - 1);

                            if ($startPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= buildReportQueryString('invent_page', 1, $reportType, $inventoryStatus, $transactionStatus, $startDate, $endDate, 'reports') ?>">1</a>
                                </li>
                                <?php if ($startPage > 2): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?= ($i == $inventoryPage) ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= buildReportQueryString('invent_page', $i, $reportType, $inventoryStatus, $transactionStatus, $startDate, $endDate, 'reports') ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($endPage < $inventoryTotalPages): ?>
                                <?php if ($endPage < $inventoryTotalPages - 1): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= buildReportQueryString('invent_page', $inventoryTotalPages, $reportType, $inventoryStatus, $transactionStatus, $startDate, $endDate, 'reports') ?>">
                                        <?= $inventoryTotalPages ?>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php if ($inventoryPage < $inventoryTotalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" 
                                       href="?<?= buildReportQueryString('invent_page', $inventoryPage + 1, $reportType, $inventoryStatus, $transactionStatus, $startDate, $endDate, 'reports') ?>" 
                                       aria-label="Next">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <a class="page-link" href="#" aria-label="Next">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Transactions Report -->
            <?php if (($reportType === 'transactions' || $reportType === 'both') && ($transactionTotalItems > 0)): ?>
                <div class="report-section">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Transactions Report</h5>
                            <span class="badge bg-primary rounded-pill">Total: <?= $transactionTotalItems ?></span>
                        </div>
                        <div class="card-body">
                            <!-- Pagination Info -->
                            <?php if (!isset($_POST['print_all']) && !isset($_GET['print_all'])): ?>
                            <div class="pagination-info">
                                Showing <?= (($transactionPage - 1) * $itemsPerPage) + 1 ?> to 
                                <?= min($transactionPage * $itemsPerPage, $transactionTotalItems) ?> of 
                                <?= $transactionTotalItems ?> entries
                            </div>
                            <?php endif; ?>
                            
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr class="bg-light">
                                            <th class="border-0">ID</th>
                                            <th class="border-0">Item ID</th>
                                            <th class="border-0">Item Name</th>
                                            <th class="border-0">Student ID</th>
                                            <th class="border-0">Student Name</th>
                                            <th class="border-0">Type</th>
                                            <th class="border-0">Date</th>
                                            <th class="border-0">Status</th>
                                            <th class="border-0">Verified By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($transactionData)): ?>
                                            <tr>
                                                <td colspan="9" class="text-center py-4">
                                                    <i class="fas fa-inbox fa-2x mb-2 text-muted"></i>
                                                    <p class="text-muted">No transactions found</p>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($transactionData as $transaction): ?>
                                                <tr>
                                                    <td><?= $transaction['id'] ?></td>
                                                    <td><?= $transaction['item_id'] ?? 'N/A' ?></td>
                                                    <td><?= htmlspecialchars($transaction['item_name'] ?? 'N/A') ?></td>
                                                    <td><?= htmlspecialchars($transaction['student_id']) ?></td>
                                                    <td><?= htmlspecialchars($transaction['student_name']) ?></td>
                                                    <td>
                                                        <?php 
                                                        $typeClass = ($transaction['transaction_type'] ?? '') === 'checkout' ? 'bg-primary' : 'bg-success';
                                                        ?>
                                                        <span class="badge <?= $typeClass ?>">
                                                            <?= ucfirst($transaction['transaction_type'] ?? 'N/A') ?>
                                                        </span>
                                                    </td>
                                                    <td><?= date('Y-m-d H:i', strtotime($transaction['transaction_date'])) ?></td>
                                                    <td>
                                                        <?php 
                                                        $statusClass = '';
                                                        switch($transaction['status']) {
                                                            case 'borrowed':
                                                                $statusClass = 'bg-info bg-opacity-10 text-info';
                                                                break;
                                                            case 'returned':
                                                                $statusClass = 'bg-success bg-opacity-10 text-success';
                                                                break;
                                                            default:
                                                                $statusClass = 'bg-secondary bg-opacity-10 text-secondary';
                                                        }
                                                        ?>
                                                        <span class="badge rounded-pill <?= $statusClass ?>">
                                                            <?= ucfirst($transaction['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= htmlspecialchars($transaction['verified_by'] ?? 'N/A') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Transaction Pagination -->
                            <?php if ($transactionTotalPages > 1 && !isset($_POST['print_all']) && !isset($_GET['print_all'])): ?>
                                <nav aria-label="Transaction pagination">
                                    <ul class="pagination justify-content-center mt-4">
                                        <?php if ($transactionPage > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" 
                                                   href="?<?= buildReportQueryString('transact_page', $transactionPage - 1, $reportType, $inventoryStatus, $transactionStatus, $startDate, $endDate) ?>" 
                                                   aria-label="Previous">
                                                    <i class="fas fa-chevron-left"></i>
                                                </a>
                                            </li>
                                        <?php else: ?>
                                            <li class="page-item disabled">
                                                <a class="page-link" href="#" aria-label="Previous">
                                                    <i class="fas fa-chevron-left"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>

                                        <?php
                                        $maxVisiblePages = 5;
                                        $startPage = max(1, $transactionPage - floor($maxVisiblePages / 2));
                                        $endPage = min($transactionTotalPages, $startPage + $maxVisiblePages - 1);

                                        if ($startPage > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?<?= buildReportQueryString('transact_page', 1, $reportType, $inventoryStatus, $transactionStatus, $startDate, $endDate) ?>">1</a>
                                            </li>
                                            <?php if ($startPage > 2): ?>
                                                <li class="page-item disabled">
                                                    <span class="page-link">...</span>
                                                </li>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                            <li class="page-item <?= ($i == $transactionPage) ? 'active' : '' ?>">
                                                <a class="page-link" href="?<?= buildReportQueryString('transact_page', $i, $reportType, $inventoryStatus, $transactionStatus, $startDate, $endDate) ?>">
                                                    <?= $i ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>

                                        <?php if ($endPage < $transactionTotalPages): ?>
                                            <?php if ($endPage < $transactionTotalPages - 1): ?>
                                                <li class="page-item disabled">
                                                    <span class="page-link">...</span>
                                                </li>
                                            <?php endif; ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?<?= buildReportQueryString('transact_page', $transactionTotalPages, $reportType, $inventoryStatus, $transactionStatus, $startDate, $endDate) ?>">
                                                    <?= $transactionTotalPages ?>
                                                </a>
                                            </li>
                                        <?php endif; ?>

                                        <?php if ($transactionPage < $transactionTotalPages): ?>
                                            <li class="page-item">
                                                <a class="page-link" 
                                                   href="?<?= buildReportQueryString('transact_page', $transactionPage + 1, $reportType, $inventoryStatus, $transactionStatus, $startDate, $endDate) ?>" 
                                                   aria-label="Next">
                                                    <i class="fas fa-chevron-right"></i>
                                                </a>
                                            </li>
                                        <?php else: ?>
                                            <li class="page-item disabled">
                                                <a class="page-link" href="#" aria-label="Next">
                                                    <i class="fas fa-chevron-right"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Print Footer -->
    <div class="print-footer">
        © <?= date('Y') ?> CICT Inventory Management System
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    <script>
        // Initialize date pickers
        flatpickr(".datepicker", {
            dateFormat: "Y-m-d",
            allowInput: true
        });

        // Show/hide fields based on report type
        document.getElementById('report_type').addEventListener('change', function() {
            const reportType = this.value;
            const inventoryFields = document.querySelectorAll('.inventory-field');
            const transactionFields = document.querySelectorAll('.transaction-field');

            if (reportType === 'both') {
                inventoryFields.forEach(field => field.style.display = '');
                transactionFields.forEach(field => field.style.display = '');
            } else if (reportType === 'inventory') {
                inventoryFields.forEach(field => field.style.display = '');
                transactionFields.forEach(field => field.style.display = 'none');
            } else if (reportType === 'transactions') {
                inventoryFields.forEach(field => field.style.display = 'none');
                transactionFields.forEach(field => field.style.display = '');
            }
        });

        function resetFilters() {
            // Get current URL to extract the active tab parameter
            const currentUrl = new URL(window.location.href);
            const currentTab = currentUrl.searchParams.get('tab') || 'inventory'; // Default to inventory if no tab specified
            
            // Build the reset URL while preserving the current tab
            const resetUrl = new URL('<?= $_SERVER['PHP_SELF'] ?>', window.location.origin);
            resetUrl.searchParams.set('report_type', 'both');
            resetUrl.searchParams.set('tab', currentTab);
            
            // Redirect while preserving the tab
            window.location.href = resetUrl.toString();
        }

        function printReport() {
            // Add print parameter to current URL
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('print_all', '1');
            
            // Remove pagination parameters if they exist
            currentUrl.searchParams.delete('invent_page');
            currentUrl.searchParams.delete('transact_page');
            
            // Open in new window and print
            const printWindow = window.open(currentUrl.toString(), '_blank');
            if (printWindow) {
                printWindow.onload = function() {
                    setTimeout(function() {
                        printWindow.print();
                        // Optional: Close after printing
                        printWindow.onafterprint = function() {
                            printWindow.close();
                        };
                    }, 1000); // Give it a second to properly load
                };
            } else {
                alert('Please allow popups for this website to print reports.');
            }
        }

        function exportToExcel() {
            // Create form with current filter values
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'export_to_excel.php';
            
            const reportTypeInput = document.createElement('input');
            reportTypeInput.type = 'hidden';
            reportTypeInput.name = 'report_type';
            reportTypeInput.value = '<?= $reportType ?>';
            form.appendChild(reportTypeInput);
            
            const inventoryStatusInput = document.createElement('input');
            inventoryStatusInput.type = 'hidden';
            inventoryStatusInput.name = 'inventory_status';
            inventoryStatusInput.value = '<?= $inventoryStatus ?>';
            form.appendChild(inventoryStatusInput);
            
            const transactionStatusInput = document.createElement('input');
            transactionStatusInput.type = 'hidden';
            transactionStatusInput.name = 'transaction_status';
            transactionStatusInput.value = '<?= $transactionStatus ?>';
            form.appendChild(transactionStatusInput);
            
            const startDateInput = document.createElement('input');
            startDateInput.type = 'hidden';
            startDateInput.name = 'start_date';
            startDateInput.value = '<?= $startDate ?>';
            form.appendChild(startDateInput);
            
            const endDateInput = document.createElement('input');
            endDateInput.type = 'hidden';
            endDateInput.name = 'end_date';
            endDateInput.value = '<?= $endDate ?>';
            form.appendChild(endDateInput);
            
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }

        function exportToPDF() {
            // Create form to get all data
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'export_to_pdf.php';
            
            const reportTypeInput = document.createElement('input');
            reportTypeInput.type = 'hidden';
            reportTypeInput.name = 'report_type';
            reportTypeInput.value = '<?= $reportType ?>';
            form.appendChild(reportTypeInput);
            
            const inventoryStatusInput = document.createElement('input');
            inventoryStatusInput.type = 'hidden';
            inventoryStatusInput.name = 'inventory_status';
            inventoryStatusInput.value = '<?= $inventoryStatus ?>';
            form.appendChild(inventoryStatusInput);
            
            const transactionStatusInput = document.createElement('input');
            transactionStatusInput.type = 'hidden';
            transactionStatusInput.name = 'transaction_status';
            transactionStatusInput.value = '<?= $transactionStatus ?>';
            form.appendChild(transactionStatusInput);
            
            const startDateInput = document.createElement('input');
            startDateInput.type = 'hidden';
            startDateInput.name = 'start_date';
            startDateInput.value = '<?= $startDate ?>';
            form.appendChild(startDateInput);
            
            const endDateInput = document.createElement('input');
            endDateInput.type = 'hidden';
            endDateInput.name = 'end_date';
            endDateInput.value = '<?= $endDate ?>';
            form.appendChild(endDateInput);

            // Fetch data and generate PDF
            fetch('export_to_pdf.php', {
                method: 'POST',
                body: new FormData(form)
            })
            .then(response => response.json())
            .then(data => {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF({
                    orientation: 'landscape',
                    unit: 'mm',
                    format: 'a4'
                });
                
                // Set primary color
                const primaryColor = '#03396c';
                const lightColor = '#f5f9fc';
                
                // Add title with modern styling
                doc.setFontSize(18);
                doc.setTextColor(primaryColor);
                doc.setFont('helvetica', 'bold');
                doc.text('CICT INVENTORY MANAGEMENT SYSTEM', 149, 20, { align: 'center' });
                
                // Add subtitle
                doc.setFontSize(14);
                doc.setTextColor(40);
                doc.setFont('helvetica', 'normal');
                doc.text('Report Summary', 149, 28, { align: 'center' });
                
                // Add divider line
                doc.setDrawColor(200);
                doc.line(15, 32, 283, 32);
                
                // Add report details
                doc.setFontSize(10);
                doc.setTextColor(100);
                doc.text(`Generated on: ${data.reportInfo.generatedOn}`, 15, 40);
                doc.text(`Generated by: ${data.reportInfo.generatedBy}`, 149, 40, { align: 'center' });
                doc.text(`Report Type: ${data.reportInfo.reportType}`, 283, 40, { align: 'right' });
                
                if (data.reportInfo.reportType !== 'Inventory') {
                    doc.text(`Date Range: ${data.reportInfo.dateRange.start} to ${data.reportInfo.dateRange.end}`, 15, 45);
                }
                
                let yPosition = 55;
                
                // Add inventory data if available
                if (data.inventory && data.inventory.length > 0) {
                    doc.setFontSize(12);
                    doc.setTextColor(primaryColor);
                    doc.setFont('helvetica', 'bold');
                    doc.text('INVENTORY REPORT', 15, yPosition);
                    doc.setFontSize(10);
                    doc.text(`Total Items: ${data.inventory.length}`, 283, yPosition, { align: 'right' });
                    yPosition += 8;
                    
                    const inventoryHeaders = [['ID', 'Item Name', 'Quantity', 'Availability']];
                    const inventoryTableData = data.inventory.map(item => [
                        item.id,
                        item.itemName,
                        item.quantity,
                        item.availability
                    ]);
                    
                    doc.autoTable({
                        startY: yPosition,
                        head: inventoryHeaders,
                        body: inventoryTableData,
                        margin: { left: 15, right: 15 },
                        headStyles: {
                            fillColor: primaryColor,
                            textColor: lightColor,
                            fontStyle: 'bold',
                            fontSize: 10
                        },
                        bodyStyles: {
                            textColor: 40,
                            fontSize: 9,
                            cellPadding: 3
                        },
                        alternateRowStyles: {
                            fillColor: 245
                        },
                        styles: {
                            cellPadding: 4,
                            lineWidth: 0.1,
                            lineColor: 200
                        },
                        theme: 'grid'
                    });
                    
                    yPosition = doc.lastAutoTable.finalY + 15;
                }
                
                // Add transaction data if available
                if (data.transactions && data.transactions.length > 0) {
                    // Check if we need a new page
                    if (yPosition > 160) {
                        doc.addPage('landscape');
                        yPosition = 20;
                    }
                    
                    doc.setFontSize(12);
                    doc.setTextColor(primaryColor);
                    doc.setFont('helvetica', 'bold');
                    doc.text('TRANSACTIONS REPORT', 15, yPosition);
                    doc.setFontSize(10);
                    doc.text(`Total Transactions: ${data.transactions.length}`, 283, yPosition, { align: 'right' });
                    yPosition += 8;
                    
                    const transactionHeaders = [['ID', 'Item ID', 'Item Name', 'Student ID', 'Student Name', 'Type', 'Date', 'Status', 'Verified By']];
                    const transactionTableData = data.transactions.map(trans => [
                        trans.id,
                        trans.itemId,
                        trans.itemName,
                        trans.studentId,
                        trans.studentName,
                        trans.type,
                        trans.date,
                        trans.status,
                        trans.verifiedBy
                    ]);
                    
                    doc.autoTable({
                        startY: yPosition,
                        head: transactionHeaders,
                        body: transactionTableData,
                        margin: { left: 15, right: 15 },
                        headStyles: {
                            fillColor: primaryColor,
                            textColor: lightColor,
                            fontStyle: 'bold',
                            fontSize: 9
                        },
                        bodyStyles: {
                            textColor: 40,
                            fontSize: 8,
                            cellPadding: 2
                        },
                        alternateRowStyles: {
                            fillColor: 245
                        },
                        styles: {
                            cellPadding: 3,
                            lineWidth: 0.1,
                            lineColor: 200
                        },
                        theme: 'grid',
                        columnStyles: {
                            0: { cellWidth: 'auto' },
                            1: { cellWidth: 'auto' },
                            2: { cellWidth: 'auto' },
                            3: { cellWidth: 'auto' },
                            4: { cellWidth: 'auto' },
                            5: { cellWidth: 'auto' },
                            6: { cellWidth: 'auto' },
                            7: { cellWidth: 'auto' },
                            8: { cellWidth: 'auto' }
                        }
                    });
                }
                
                // Add footer on each page
                const pageCount = doc.internal.getNumberOfPages();
                for (let i = 1; i <= pageCount; i++) {
                    doc.setPage(i);
                    doc.setFontSize(8);
                    doc.setTextColor(150);
                    doc.setFont('helvetica', 'normal');
                    doc.text('© CICT Inventory Management System', 149, 200, { align: 'center' });
                    doc.text(`Page ${i} of ${pageCount}`, 283, 200, { align: 'right' });
                }
                
                // Save the PDF
                doc.save(`CICT_IMS_Report_${data.reportInfo.generatedOn.replace(/[^0-9]/g, '')}.pdf`);
            })
            .catch(error => {
                console.error('Error generating PDF:', error);
                alert('There was an error generating the PDF. Please try again.');
            });
        }

        // Handle form submission to preserve pagination
        document.getElementById('reportForm').addEventListener('submit', function(e) {
            // Reset pagination when applying new filters
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.delete('invent_page');
            currentUrl.searchParams.delete('transact_page');
            
            // Add form data to URL
            const formData = new FormData(this);
            for (let [key, value] of formData.entries()) {
                if (value) {
                    currentUrl.searchParams.set(key, value);
                }
            }
            
            window.location.href = currentUrl.toString();
            e.preventDefault();
        });
        
    </script>
</body>
</html>