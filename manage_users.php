<?php
// manage_users.php (or the relevant section of your admin dashboard)
require 'db_connect.php';

// Pagination
$limit = 10;
$page = isset($_GET['user_page']) ? (int)$_GET['user_page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch total number of users
$sql = "SELECT COUNT(*) AS total FROM users";
$result = $conn->query($sql);
$totalItems = $result->fetch_assoc()['total'];
$totalPages = ceil($totalItems / $limit);

// Fetch users for the current page
$sql = "SELECT id, username, role, first_name, last_name, email, contact_number, status 
        FROM users 
        ORDER BY role, last_name 
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();

// Build query string for pagination links
function buildUserQueryString($page, $currentParams) {
    $params = $currentParams;
    $params['user_page'] = $page;
    
    // Ensure the tab parameter is always included
    if (!isset($params['tab'])) {
        $params['tab'] = 'users';
    }
    
    return http_build_query($params);
}

$currentParams = $_GET;
unset($currentParams['user_page']); // Remove page parameter so we can add it ourselves
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Manage Users</h5>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addAdminModal">
            <i class="fas fa-plus me-1"></i> Add Admin
        </button>
    </div>
    <div class="card-body">
        <!-- Status Change Alert -->
        <div id="statusChangeAlert" class="mb-3" style="display: none;"></div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr class="bg-light">
                        <th class="border-0">ID</th>
                        <th class="border-0">User</th>
                        <th class="border-0">Contact</th>
                        <th class="border-0">Role</th>
                        <th class="border-0">Status</th>
                        <th class="border-0 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <i class="fas fa-users fa-2x mb-2 text-muted"></i>
                                <p class="text-muted">No users found</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr id="user-row-<?php echo $user['id']; ?>">
                                <td><?php echo $user['id']; ?></td>
                                <td>
                                    <div class="fw-bold"><?php echo $user['username']; ?></div>
                                    <div class="text-muted small"><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></div>
                                    <div class="text-muted small"><?php echo $user['email']; ?></div>
                                </td>
                                <td><?php echo $user['contact_number']; ?></td>
                                <td>
                                    <span class="badge rounded-pill bg-<?php echo $user['role'] === 'admin' ? 'primary' : 'info'; ?> bg-opacity-10 text-<?php echo $user['role'] === 'admin' ? 'primary' : 'info'; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge rounded-pill bg-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?> bg-opacity-10 text-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?>" id="status-badge-<?php echo $user['id']; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-<?php echo $user['status'] === 'active' ? 'danger' : 'success'; ?> status-change-btn" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#statusChangeModal"
                                            data-user-id="<?php echo $user['id']; ?>"
                                            data-current-status="<?php echo $user['status']; ?>">
                                        <i class="fas fa-power-off"></i> <?php echo $user['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center mt-4">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" 
                           href="?<?php echo buildUserQueryString($page - 1, $currentParams); ?>" 
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
                // Show limited page numbers with ellipsis
                $maxVisiblePages = 5;
                $startPage = max(1, $page - floor($maxVisiblePages / 2));
                $endPage = min($totalPages, $startPage + $maxVisiblePages - 1);
                
                if ($startPage > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo buildUserQueryString(1, $currentParams); ?>">1</a>
                    </li>
                    <?php if ($startPage > 2): ?>
                        <li class="page-item disabled">
                            <span class="page-link">...</span>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" 
                           href="?<?php echo buildUserQueryString($i, $currentParams); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($endPage < $totalPages): ?>
                    <?php if ($endPage < $totalPages - 1): ?>
                        <li class="page-item disabled">
                            <span class="page-link">...</span>
                        </li>
                    <?php endif; ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo buildUserQueryString($totalPages, $currentParams); ?>">
                            <?php echo $totalPages; ?>
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" 
                           href="?<?php echo buildUserQueryString($page + 1, $currentParams); ?>" 
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
    </div>
</div>

<!-- Status Change Modal -->
<div class="modal fade" id="statusChangeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title">Change User Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="statusChangeForm" method="POST">
                <input type="hidden" name="user_id" id="modal_user_id">
                <input type="hidden" name="new_status" id="modal_new_status">
                <div class="modal-body">
                    <p>Are you sure you want to <span id="statusActionText" class="fw-bold"></span> this account?</p>
                    <p class="text-muted small">This will <?php echo $user['status'] === 'active' ? 'prevent' : 'allow'; ?> the user from accessing the system.</p>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Confirm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Admin Modal -->
<div class="modal fade" id="addAdminModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title">Add New Admin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="register_process.php" method="POST" onsubmit="return validateAdminForm()">
                <input type="hidden" name="role" value="admin">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="admin_first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="admin_first_name" name="first_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="admin_last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="admin_last_name" name="last_name" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="admin_username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="admin_username" name="username" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="admin_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="admin_email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="admin_contact_number" class="form-label">Phone Number</label>
                        <div class="phone-input">
                            <span class="phone-prefix">+63</span>
                            <input type="text" class="form-control phone-number" id="admin_contact_number" name="contact_number" 
                                maxlength="10" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="admin_password" class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="admin_password" name="password" required>
                            <span class="input-group-text toggle-password">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="admin_confirm_password" class="form-label">Confirm Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="admin_confirm_password" name="confirm_password" required>
                            <span class="input-group-text toggle-password">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Register Admin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
     // Initialize modal with user data
    const statusChangeModal = document.getElementById('statusChangeModal');
    
    statusChangeModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const userId = button.getAttribute('data-user-id');
        const currentStatus = button.getAttribute('data-current-status');
        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
        const actionText = currentStatus === 'active' ? 'deactivate' : 'activate';
        
        document.getElementById('modal_user_id').value = userId;
        document.getElementById('modal_new_status').value = newStatus;
        document.getElementById('statusActionText').textContent = actionText;
    });

    // Handle form submission with AJAX
    const statusChangeForm = document.getElementById('statusChangeForm');
    if (statusChangeForm) {
        statusChangeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const userId = formData.get('user_id');
            const submitButton = this.querySelector('button[type="submit"]');
            
            // Disable button during request
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
            
            fetch('update_status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Update the status badge
                    const statusBadge = document.getElementById(`status-badge-${userId}`);
                    if (statusBadge) {
                        statusBadge.className = `badge rounded-pill ${data.status_badge_class}`;
                        statusBadge.textContent = data.new_status.charAt(0).toUpperCase() + data.new_status.slice(1);
                    }
                    
                    // Update the button's data attribute
                    const statusButton = document.querySelector(`.status-change-btn[data-user-id="${userId}"]`);
                    if (statusButton) {
                        statusButton.setAttribute('data-current-status', data.new_status);
                        statusButton.className = `btn btn-sm btn-outline-${data.new_status === 'active' ? 'danger' : 'success'} status-change-btn`;
                        statusButton.innerHTML = `<i class="fas fa-power-off"></i> ${data.new_status === 'active' ? 'Deactivate' : 'Activate'}`;
                    }
                    
                    showAlert('Status updated successfully!', 'success');
                } else {
                    throw new Error(data.error || 'Unknown error occurred');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Failed to update status: ' + error.message, 'danger');
            })
            .finally(() => {
                // Re-enable button
                submitButton.disabled = false;
                submitButton.textContent = 'Confirm';
                
                // Close modal
                bootstrap.Modal.getInstance(statusChangeModal).hide();
            });
        });
    }
    
    // Improved alert function
    function showAlert(message, type) {
        const alertDiv = document.getElementById('statusChangeAlert');
        alertDiv.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        alertDiv.style.display = 'block';
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            const alert = alertDiv.querySelector('.alert');
            if (alert) {
                alert.classList.remove('show');
                setTimeout(() => {
                    alertDiv.style.display = 'none';
                }, 150);
            }
        }, 5000);
    }
});

// Form validation
function validateAdminForm() {
    const password = document.getElementById("admin_password").value;
    const confirmPassword = document.getElementById("admin_confirm_password").value;

    if (password !== confirmPassword) {
        showAlert("Passwords do not match!", "danger");
        return false;
    }
    return true;
}

// Toggle password visibility
document.querySelectorAll('.toggle-password').forEach(function(element) {
    element.addEventListener('click', function() {
        const input = this.parentElement.querySelector('input');
        const icon = this.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
});

// Phone number validation
document.querySelectorAll('.phone-number').forEach(function(element) {
    element.addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
});
</script>