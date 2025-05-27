<?php
session_start();
ob_start();
require 'db_connect.php';

// Enhanced security check
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'officer') {
    header("Location: login.php");
    exit();
}

// Determine which tab is active
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'borrow';

// Set tab file based on active tab
$tab_file = '';
switch ($active_tab) {
    case 'return':
        $tab_file = 'return_item.php';
        break;
    case 'history':
        $tab_file = 'officer_transaction_history.php';
        break;
    default:
        $tab_file = 'borrow_item.php';
        break;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Officer Dashboard | CICT Inventory</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        :root {
            --dark-blue: #011f4b;
            --medium-blue: #03396c;
            --light-blue: #005b96;
            --pale-blue: #6497b1;
            --very-pale-blue: #b3cde0;
            --off-white: #eeeeee;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--off-white);
            color: var(--dark-blue);
        }
        
        /* Sidebar */
        .sidebar {
            background-color: var(--dark-blue);
            color: white;
            height: 100vh;
            position: fixed;
            width: 250px;
            transition: all 0.3s;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-menu {
            padding: 0;
            list-style: none;
        }
        
        .sidebar-menu li {
            padding: 0.75rem 1.5rem;
            transition: all 0.3s;
        }
        
        .sidebar-menu li:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-menu li a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        
        .sidebar-menu li i {
            margin-right: 0.75rem;
            width: 1.25rem;
            text-align: center;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 250px;
            padding: 1.5rem;
            transition: all 0.3s;
        }
        
        /* Navbar */
        .navbar-custom {
            background-color: white;
            padding: 0.75rem 1.5rem;
            box-shadow: 0 2px 10px rgba(1, 31, 75, 0.05);
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        /* Cards */
        .card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(1, 31, 75, 0.05);
            margin-bottom: 1.5rem;
            background-color: white;
        }
        
        .card-header {
            background-color: var(--dark-blue);
            color: white;
            border-radius: 8px 8px 0 0 !important;
            padding: 1rem 1.5rem;
            font-weight: 600;
        }
        
        /* Buttons */
        .btn-primary {
            background-color: var(--light-blue);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 600;
        }
        
        .btn-outline-danger {
            border-color: var(--light-blue);
            color: var(--light-blue);
        }
        
        .btn-outline-danger:hover {
            background-color: var(--light-blue);
            color: white;
        }
        
        /* User Avatar */
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: var(--light-blue);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 0.75rem;
        }
        
        /* Status Badges */
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-available {
            background-color: rgba(40, 167, 69, 0.2);
            color: #28a745;
        }
        
        .status-borrowed {
            background-color: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }
        
        .status-returned {
            background-color: rgba(23, 162, 184, 0.2);
            color: #17a2b8;
        }
        
        .status-unavailable {
            background-color: rgba(220, 53, 69, 0.2);
            color: #dc3545;
        }
        
        /* Form Controls */
        .form-control:focus {
            border-color: var(--light-blue);
            box-shadow: 0 0 0 0.25rem rgba(0, 91, 150, 0.25);
        }
        
        /* Alerts */
        .alert {
            border-radius: 8px;
        }
        
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 80px;
            }
            
            .sidebar-header h3,
            .sidebar-menu li span {
                display: none;
            }
            
            .sidebar-menu li {
                text-align: center;
                padding: 0.75rem;
            }
            
            .sidebar-menu li i {
                margin-right: 0;
                font-size: 1.25rem;
            }
            
            .main-content {
                margin-left: 80px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3 style="font-weight: bold;">CICT Inventory</h3>
        </div>
        <ul class="sidebar-menu">
            <li>
                <a href="officer_dashboard.php?tab=borrow" class="<?php echo $active_tab == 'borrow' ? 'active' : ''; ?>">
                    <i class="fas fa-hand-holding"></i>
                    <span>Borrow Item</span>
                </a>
            </li>
            <li>
                <a href="officer_dashboard.php?tab=return" class="<?php echo $active_tab == 'return' ? 'active' : ''; ?>">
                    <i class="fas fa-undo"></i>
                    <span>Return Item</span>
                </a>
            </li>
            <li>
                <a href="officer_dashboard.php?tab=history" class="<?php echo $active_tab == 'history' ? 'active' : ''; ?>">
                    <i class="fas fa-history"></i>
                    <span>Transaction History</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg navbar-custom">
            <div class="container-fluid">
                <div class="d-flex align-items-center">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
                    </div>
                    <span class="navbar-brand mb-0">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                </div>
                <div>
                    <a href="logout.php" class="btn btn-outline-danger">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </nav>

        <!-- Flash Messages -->
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show">
                <?php echo $_SESSION['flash_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <!-- Tab Content -->
        <div class="tab-content">
            <?php include $tab_file; ?>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php ob_end_flush(); ?>