<?php
// Security check - this file should only be included from officer_dashboard.php
if (!defined('INCLUDED_FROM_DASHBOARD') && !isset($_SESSION['username'])) {
    // Direct access not allowed
    header("Location: login.php");
    exit();
}

// Handle updating item availability
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_availability'])) {
    $item_id = filter_input(INPUT_POST, 'item_id', FILTER_SANITIZE_NUMBER_INT);
    $new_availability = filter_input(INPUT_POST, 'new_availability', FILTER_SANITIZE_STRING);
    
    if ($item_id && in_array($new_availability, ['available', 'unavailable'])) {
        $sql = "UPDATE inventory SET item_availability = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $new_availability, $item_id);
        
        if ($stmt->execute()) {
            $_SESSION['flash_message'] = "Item availability updated successfully!";
            $_SESSION['flash_type'] = "success";
        } else {
            $_SESSION['flash_message'] = "Error updating availability: " . $stmt->error;
            $_SESSION['flash_type'] = "danger";
        }
        $stmt->close();
        
        // Redirect to refresh the page and prevent form resubmission
        header("Location: officer_dashboard.php?tab=borrow");
        exit();
    }
}

// Handle borrowing items
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['borrow_item'])) {
    // Check if current time is within allowed borrowing hours (7:00 AM to 5:00 PM)
    $current_hour = (int)date('H');
    if ($current_hour < 7 || $current_hour >= 17) {
        $_SESSION['flash_message'] = "Borrowing is only allowed between 7:00 AM and 5:00 PM.";
        $_SESSION['flash_type'] = "danger";
        header("Location: officer_dashboard.php?tab=borrow");
        exit();
    }

    $item_id = filter_input(INPUT_POST, 'item_id', FILTER_SANITIZE_NUMBER_INT);
    $student_id = filter_input(INPUT_POST, 'student_id', FILTER_SANITIZE_STRING);
    $student_name = filter_input(INPUT_POST, 'student_name', FILTER_SANITIZE_STRING);
    $student_section = filter_input(INPUT_POST, 'student_section', FILTER_SANITIZE_STRING);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    $verified_by = $_SESSION['username'];

    // Set due date to 5:00 PM today
    $due_date = new DateTime();
    $due_date->setTime(17, 0, 0);
    $due_date_formatted = $due_date->format('Y-m-d H:i:s');

    if ($item_id && $student_id && $student_name && $student_section && $quantity > 0) {
        $conn->begin_transaction();
        
        try {
            // Check for duplicate student ID with different name
            $check_id_sql = "SELECT DISTINCT student_name FROM transactions WHERE student_id = ? AND student_name != ? LIMIT 1";
            $check_id_stmt = $conn->prepare($check_id_sql);
            $check_id_stmt->bind_param("ss", $student_id, $student_name);
            $check_id_stmt->execute();
            $id_result = $check_id_stmt->get_result();
            $check_id_stmt->close();
            
            if ($id_result->num_rows > 0) {
                $existing_student = $id_result->fetch_assoc();
                $conn->rollback();
                $_SESSION['flash_message'] = "Error: Student ID {$student_id} is already associated with {$existing_student['student_name']}. Each Student ID must be unique.";
                $_SESSION['flash_type'] = "danger";
                header("Location: officer_dashboard.php?tab=borrow");
                exit();
            }

            // Check for duplicate student name with different ID
            $check_name_sql = "SELECT DISTINCT student_id FROM transactions WHERE student_name = ? AND student_id != ? LIMIT 1";
            $check_name_stmt = $conn->prepare($check_name_sql);
            $check_name_stmt->bind_param("ss", $student_name, $student_id);
            $check_name_stmt->execute();
            $name_result = $check_name_stmt->get_result();
            $check_name_stmt->close();
            
            if ($name_result->num_rows > 0) {
                $existing_id = $name_result->fetch_assoc();
                $conn->rollback();
                $_SESSION['flash_message'] = "Error: Student name '{$student_name}' is already associated with ID {$existing_id['student_id']}. Each student name must be unique.";
                $_SESSION['flash_type'] = "danger";
                header("Location: officer_dashboard.php?tab=borrow");
                exit();
            }
            
            // Check item availability
            $check_sql = "SELECT item_quantity, item_availability FROM inventory WHERE id = ? FOR UPDATE";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("i", $item_id);
            $check_stmt->execute();
            $item = $check_stmt->get_result()->fetch_assoc();
            $check_stmt->close();
            
            if ($item && $item['item_quantity'] >= $quantity) {
                // Insert the borrow transaction
                $sql = "INSERT INTO transactions (item_id, student_id, student_name, section, quantity, transaction_type, verified_by, status, due_date, return_date) 
                        VALUES (?, ?, ?, ?, ?, 'borrowed', ?, 'borrowed', ?, NULL)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isssiss", $item_id, $student_id, $student_name, $student_section, $quantity, $verified_by, $due_date_formatted);
                $stmt->execute();
                $stmt->close();

                // Update item availability
                $update_sql = "UPDATE inventory SET 
                                item_quantity = item_quantity - ?, 
                                item_availability = IF(item_quantity - ? <= 0, 'unavailable', 'available') 
                                WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("iii", $quantity, $quantity, $item_id);
                $update_stmt->execute();
                $update_stmt->close();

                $conn->commit();
                $_SESSION['flash_message'] = "Item borrowed successfully!";
                $_SESSION['flash_type'] = "success";
            } else {
                $conn->rollback();
                $_SESSION['flash_message'] = "Item is not available in sufficient quantity for borrowing.";
                $_SESSION['flash_type'] = "danger";
            }
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['flash_message'] = "Error processing borrow: " . $e->getMessage();
            $_SESSION['flash_type'] = "danger";
        }
        
        header("Location: officer_dashboard.php?tab=borrow");
        exit();
    }
}

// Fetch all items
$sql = "SELECT * FROM inventory";
$result = $conn->query($sql);
$items = $result->fetch_all(MYSQLI_ASSOC);

// Function to get student details by ID for auto-filling
function getStudentDetails($conn, $student_id) {
    $sql = "SELECT DISTINCT student_name, section FROM transactions WHERE student_id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-hand-holding me-2"></i>Borrow Items</h5>
        <div class="text-muted">
            <small>Borrowing hours: 7:00 AM - 5:00 PM only</small>
        </div>
    </div>
    <div class="card-body">
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show mb-4">
                <?php echo $_SESSION['flash_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <form method="POST" class="needs-validation" novalidate>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="itemSelect" class="form-label">Select Item</label>
                    <select class="form-select" id="itemSelect" name="item_id" required>
                        <option value="" selected disabled>Select an item</option>
                        <?php foreach ($items as $item): ?>
                            <?php if ($item['item_availability'] === 'available'): ?>
                                <option value="<?php echo $item['id']; ?>" data-quantity="<?php echo $item['item_quantity']; ?>">
                                    <?php echo htmlspecialchars($item['item_name']); ?> 
                                    (Available: <?php echo $item['item_quantity']; ?>)
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Please select an item</div>
                </div>
                <div class="col-md-6">
                    <label for="quantity" class="form-label">Quantity</label>
                    <div class="input-group">
                        <button class="btn btn-outline-secondary" type="button" id="decrement-qty">-</button>
                        <input type="number" class="form-control text-center" id="quantity" name="quantity" min="1" value="1" required>
                        <button class="btn btn-outline-secondary" type="button" id="increment-qty">+</button>
                    </div>
                    <div class="invalid-feedback">Please enter a valid quantity</div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label for="studentId" class="form-label">Student ID</label>
                    <input type="text" class="form-control" id="studentId" name="student_id" required>
                    <div class="invalid-feedback">Please enter student ID</div>
                </div>
                <div class="col-md-4">
                    <label for="studentName" class="form-label">Student Name</label>
                    <input type="text" class="form-control" id="studentName" name="student_name" required>
                    <div class="invalid-feedback">Please enter student name</div>
                </div>
                <div class="col-md-4">
                    <label for="studentSection" class="form-label">Section</label>
                    <input type="text" class="form-control" id="studentSection" name="student_section" required>
                    <div class="invalid-feedback">Please enter section</div>
                </div>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="submit" name="borrow_item" class="btn btn-primary px-4 py-2">
                    <i class="fas fa-hand-holding me-2"></i>Process Borrow
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-boxes me-2"></i>Available Inventory</h5>
        <div class="small text-muted"><?php echo date('F j, Y'); ?></div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr class="bg-light">
                        <th class="border-0">ID</th>
                        <th class="border-0">Item Name</th>
                        <th class="border-0">Quantity</th>
                        <th class="border-0">Status</th>
                        <th class="border-0 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                <i class="fas fa-box-open fa-2x mb-2 text-muted"></i>
                                <p class="text-muted">No items available</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo $item['id']; ?></td>
                                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                <td><?php echo $item['item_quantity']; ?></td>
                                <td>
                                    <span class="badge rounded-pill bg-<?php echo $item['item_availability'] === 'available' ? 'success' : 'danger'; ?> bg-opacity-10 text-<?php echo $item['item_availability'] === 'available' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($item['item_availability']); ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <input type="hidden" name="new_availability" value="<?php echo $item['item_availability'] === 'available' ? 'unavailable' : 'available'; ?>">
                                        <button type="submit" name="update_availability" class="btn btn-sm btn-<?php echo $item['item_availability'] === 'available' ? 'warning' : 'success'; ?>">
                                            <i class="fas fa-power-off me-1"></i>
                                            <?php echo $item['item_availability'] === 'available' ? 'Unavailable' : 'Available'; ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Quantity controls
    const quantityInput = document.getElementById('quantity');
    if (quantityInput) {
        document.getElementById('increment-qty').addEventListener('click', () => {
            const max = parseInt(quantityInput.max) || Infinity;
            quantityInput.value = Math.min(parseInt(quantityInput.value || 0) + 1, max);
        });
        
        document.getElementById('decrement-qty').addEventListener('click', () => {
            quantityInput.value = Math.max(parseInt(quantityInput.value || 1) - 1, 1);
        });
    }

    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // Auto-fill student details when ID is entered
    const studentIdInput = document.getElementById('studentId');
    if (studentIdInput) {
        studentIdInput.addEventListener('blur', function() {
            if (this.value.trim() !== '') {
                fetch(`get_student.php?student_id=${encodeURIComponent(this.value)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.data) {
                            document.getElementById('studentName').value = data.data.student_name || '';
                            document.getElementById('studentSection').value = data.data.section || '';
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }
        });
    }

    // Update max quantity when item is selected
    const itemSelect = document.getElementById('itemSelect');
    if (itemSelect) {
        itemSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const maxQty = parseInt(selectedOption.getAttribute('data-quantity')) || 0;
            quantityInput.max = maxQty;
            quantityInput.value = Math.min(parseInt(quantityInput.value) || 1, maxQty);
        });
    }

    // Form validation for borrowing hours
    const borrowForm = document.querySelector('form.needs-validation');
    if (borrowForm) {
        borrowForm.addEventListener('submit', function(event) {
            const currentHour = new Date().getHours();
            if (currentHour < 7 || currentHour >= 17) {
                event.preventDefault();
                alert('Borrowing is only allowed between 7:00 AM and 5:00 PM.');
                return false;
            }
        });
    }
});
</script>
