<?php
require 'db_connect.php';

// Initialize search and filter variables
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$whereClause = [];
$params = [];
$paramTypes = '';

// Define all available statuses
$allStatuses = ['borrowed', 'returned'];

// Handle status filter
$statusFilter = isset($_GET['status']) ? $_GET['status'] : [];
if (!empty($statusFilter) && is_array($statusFilter)) {
    // Only add filter if not all statuses are selected
    if (count($statusFilter) < count($allStatuses)) {
        $placeholders = implode(',', array_fill(0, count($statusFilter), '?'));
        $whereClause[] = "status IN ($placeholders)";
        $params = array_merge($params, $statusFilter);
        $paramTypes .= str_repeat('s', count($statusFilter));
    }
}

// Handle date range filter
$startDate = isset($_GET['start_date']) && !empty($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) && !empty($_GET['end_date']) ? $_GET['end_date'] : '';

if (!empty($startDate)) {
    $whereClause[] = "transaction_date >= ?";
    $params[] = $startDate . ' 00:00:00';
    $paramTypes .= 's';
}

if (!empty($endDate)) {
    $whereClause[] = "transaction_date <= ?";
    $params[] = $endDate . ' 23:59:59';
    $paramTypes .= 's';
}

// Add search term filter
if (!empty($searchTerm)) {
    $whereClause[] = "(student_id LIKE ? OR student_name LIKE ? OR status LIKE ? OR verified_by LIKE ?)";
    $searchParam = "%$searchTerm%";
    $params = array_merge($params, array_fill(0, 4, $searchParam));
    $paramTypes .= str_repeat('s', 4);
}

// Build the final WHERE clause
$whereSQL = '';
if (!empty($whereClause)) {
    $whereSQL = 'WHERE ' . implode(' AND ', $whereClause);
}

// Pagination
$limit = 10;
$page = isset($_GET['trans_page']) ? (int)$_GET['trans_page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch total number of transactions (with filters if applicable)
$sql = "SELECT COUNT(*) AS total FROM transactions $whereSQL";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($paramTypes, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$totalItems = $result->fetch_assoc()['total'];
$totalPages = ceil($totalItems / $limit);
$stmt->close();

// Fetch transactions for the current page (with filters if applicable)
$sql = "SELECT * FROM transactions $whereSQL ORDER BY transaction_date DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);

// If there are filter parameters, bind them along with pagination parameters
if (!empty($params)) {
    $params[] = $limit;
    $params[] = $offset;
    $stmt->bind_param($paramTypes . 'ii', ...$params);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}

$stmt->execute();
$result = $stmt->get_result();
$transactions = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();

// Get current filter values for form
$selectedStatuses = $statusFilter;

// Helper function to build query string for pagination links
function buildQueryString($page, $currentParams) {
    $params = $currentParams;
    $params['trans_page'] = $page;
    
    // Remove empty values and tab parameter (we'll add it separately)
    $params = array_filter($params, function($value) {
        return $value !== '' && $value !== [] && !is_null($value);
    });
    
    // Always include the tab parameter for dashboard integration
    if (!isset($params['tab'])) {
        $params['tab'] = 'history';
    }
    
    return http_build_query($params);
}

// Store all current GET parameters for pagination links
$currentParams = $_GET;
unset($currentParams['trans_page']); // Remove page parameter so we can add it ourselves
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Transaction History</h5>
        <div>
            <button class="btn btn-sm btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#filterModal">
                <i class="fas fa-filter me-1"></i> Filters
                <?php if (!empty($statusFilter) || !empty($startDate) || !empty($endDate)): ?>
                <span class="badge rounded-pill bg-primary"><?php echo count(array_filter([$statusFilter, $startDate, $endDate])); ?></span>
                <?php endif; ?>
            </button>
        </div>
    </div>
    <div class="card-body">
        <!-- Search Form -->
        <div class="row mb-4">
            <div class="col-md-6">
                <form method="GET" action="">
                    <input type="hidden" name="tab" value="history">
                    <?php foreach ($statusFilter as $status): ?>
                    <input type="hidden" name="status[]" value="<?php echo htmlspecialchars($status); ?>">
                    <?php endforeach; ?>
                    <?php if (!empty($startDate)): ?>
                    <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
                    <?php endif; ?>
                    <?php if (!empty($endDate)): ?>
                    <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
                    <?php endif; ?>
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search transactions..." 
                               value="<?php echo htmlspecialchars($searchTerm); ?>">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                        <?php if (!empty($searchTerm) || !empty($statusFilter) || !empty($startDate) || !empty($endDate)): ?>
                            <a href="officer_dashboard.php?tab=history" class="btn btn-outline-danger">
                                <i class="fas fa-times"></i> Clear All
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            <?php if (!empty($statusFilter) || !empty($startDate) || !empty($endDate)): ?>
            <div class="col-md-6">
                <div class="d-flex flex-wrap gap-2 justify-content-md-end align-items-center">
                    <?php if (!empty($statusFilter)): foreach ($statusFilter as $status): ?>
                    <span class="badge rounded-pill bg-info bg-opacity-10 text-info">
                        Status: <?php echo ucfirst($status); ?>
                        <a href="officer_dashboard.php?tab=history&<?php echo http_build_query(array_merge(
                            array_diff_key($_GET, ['status' => '']), 
                            ['status' => array_diff($statusFilter, [$status])]
                        )); ?>" class="text-info text-decoration-none ms-1">×</a>
                    </span>
                    <?php endforeach; endif; ?>
                    
                    <?php if (!empty($startDate)): ?>
                    <span class="badge rounded-pill bg-info bg-opacity-10 text-info">
                        From: <?php echo date('M d, Y', strtotime($startDate)); ?>
                        <a href="officer_dashboard.php?tab=history&<?php echo http_build_query(array_diff_key($_GET, ['start_date' => ''])); ?>" 
                           class="text-info text-decoration-none ms-1">×</a>
                    </span>
                    <?php endif; ?>
                    
                    <?php if (!empty($endDate)): ?>
                    <span class="badge rounded-pill bg-info bg-opacity-10 text-info">
                        To: <?php echo date('M d, Y', strtotime($endDate)); ?>
                        <a href="officer_dashboard.php?tab=history&<?php echo http_build_query(array_diff_key($_GET, ['end_date' => ''])); ?>" 
                           class="text-info text-decoration-none ms-1">×</a>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr class="bg-light">
                        <th class="border-0">ID</th>
                        <th class="border-0">Student</th>
                        <th class="border-0">Status</th>
                        <th class="border-0">Verified By</th>
                        <th class="border-0 ">Borrowed</th>
                        <th class="border-0 ">Returned</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="fas fa-inbox fa-2x mb-2 text-muted"></i>
                                <p class="text-muted">No transactions found</p>
                                <?php if (!empty($searchTerm) || !empty($statusFilter) || !empty($startDate) || !empty($endDate)): ?>
                                    <a href="officer_dashboard.php?tab=history" class="btn btn-sm btn-outline-primary">
                                        Clear all filters
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($transaction['id']); ?></td>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($transaction['student_id']); ?></div>
                                    <div class="text-muted small"><?php echo htmlspecialchars($transaction['student_name']); ?></div>
                                </td>
                                <td>
                                    <?php 
                                    $badgeClass = '';
                                    if ($transaction['status'] === 'borrowed') {
                                        $badgeClass = 'bg-success bg-opacity-10 text-success';
                                    } elseif ($transaction['status'] === 'returned') {
                                        $badgeClass = 'bg-danger bg-opacity-10 text-danger';
                                    } else {
                                        $badgeClass = $transaction['status'] === 'pending' ? 'bg-warning bg-opacity-10 text-warning' : 'bg-secondary bg-opacity-10 text-secondary';
                                    }
                                    ?>
                                    <span class="badge rounded-pill <?php echo $badgeClass; ?>">
                                        <?php echo ucfirst(htmlspecialchars($transaction['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo $transaction['verified_by'] ? htmlspecialchars($transaction['verified_by']) : 'N/A'; ?></td>
                                <td class="">
                                    <?php if ($transaction['transaction_date'] && $transaction['transaction_date'] != '0000-00-00 00:00:00'): ?>
                                        <div class="fw-bold"><?php echo date('M d, Y', strtotime($transaction['transaction_date'])); ?></div>
                                        <div class="text-muted small"><?php echo date('h:i A', strtotime($transaction['transaction_date'])); ?></div>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td class="">
                                    <?php if ($transaction['return_date'] && $transaction['return_date'] != '0000-00-00 00:00:00'): ?>
                                        <div class="fw-bold"><?php echo date('M d, Y', strtotime($transaction['return_date'])); ?></div>
                                        <div class="text-muted small"><?php echo date('h:i A', strtotime($transaction['return_date'])); ?></div>
                                    <?php else: ?>
                                        <span class="text-muted">Not returned</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center mt-4">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" 
                           href="officer_dashboard.php?<?php echo buildQueryString($page - 1, $currentParams); ?>" 
                           aria-label="Previous">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" 
                           href="officer_dashboard.php?<?php echo buildQueryString($i, $currentParams); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" 
                           href="officer_dashboard.php?<?php echo buildQueryString($page + 1, $currentParams); ?>" 
                           aria-label="Next">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Filter Modal -->
<div class="modal fade" id="filterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title">Filter Transactions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="filterForm" method="GET" action="">
                <div class="modal-body">
                    <input type="hidden" name="tab" value="history">
                    <?php if (!empty($searchTerm)): ?>
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Status</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="status[]" 
                                value="borrowed" id="borrowedCheck"
                                <?php echo (empty($selectedStatuses) || in_array('borrowed', $selectedStatuses)) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="borrowedCheck">
                                Borrowed
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="status[]" 
                                value="returned" id="returnedCheck"
                                <?php echo (empty($selectedStatuses) || in_array('returned', $selectedStatuses)) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="returnedCheck">
                                Returned
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Date Range</label>
                        <div class="input-group mb-2">
                            <span class="input-group-text bg-light">From</span>
                            <input type="date" class="form-control" name="start_date" 
                                   value="<?php echo htmlspecialchars($startDate); ?>">
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-light">To</span>
                            <input type="date" class="form-control" name="end_date"
                                   value="<?php echo htmlspecialchars($endDate); ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <a href="officer_dashboard.php?tab=history<?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?>" 
                       class="btn btn-outline-secondary me-auto">Reset Filters</a>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize filter modal checkboxes
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        // Handle checkbox groups to ensure at least one is selected
        const handleCheckboxGroup = (name) => {
            const checkboxes = filterForm.querySelectorAll(`input[name="${name}[]"]`);
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
                    if (!anyChecked) {
                        // If none checked, check all (acts like "select all")
                        checkboxes.forEach(cb => cb.checked = true);
                    }
                });
            });
        };

        handleCheckboxGroup('status');
    }
});
</script>