<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Check if user is admin
$isAdmin = ($_SESSION['role'] === 'admin');
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | CICT Inventory</title>
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
                <a href="#" class="tablink" onclick="openTab(event, 'ManageInventory', 'manage_inventory.php')">
                    <i class="fas fa-boxes"></i>
                    <span>Inventory</span>
                </a>
            </li>
            <li>
                <a href="#" class="tablink" onclick="openTab(event, 'Transactions', 'transaction_history.php')">
                    <i class="fas fa-exchange-alt"></i>
                    <span>Transactions</span>
                </a>
            </li>
            <li>
                <a href="#" class="tablink" onclick="openTab(event, 'Reports', 'reports.php')">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
            </li>
            <?php if ($isAdmin): ?>
            <li>
                <a href="#" class="tablink" onclick="openTab(event, 'ManageUsers', 'manage_users.php')">
                    <i class="fas fa-users-cog"></i>
                    <span>Manage Users</span>
                </a>
            </li>
            <?php endif; ?>
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
                    <span class="navbar-brand mb-0">Welcome, <?php echo $_SESSION['name']; ?></span>
                </div>
                <div>
                    <a href="logout.php" class="btn btn-outline-danger">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </nav>

        <!-- Tab Content -->
        <div id="ManageInventory" class="tabcontent" style="display: none;">
            <?php include 'manage_inventory.php'; ?>
        </div>
        <div id="Transactions" class="tabcontent" style="display: none;">
            <?php include 'transaction_history.php'; ?>
        </div>
        <div id="Reports" class="tabcontent" style="display: none;">
            <?php include 'reports.php'; ?>
        </div>
        <?php if ($isAdmin): ?>
        <div id="ManageUsers" class="tabcontent" style="display: none;">
            <?php include 'manage_users.php'; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // [Keep all your existing JavaScript exactly the same]
        function openTab(event, tabName, targetFile) {
            // Hide all tab content
            const tabcontent = document.querySelectorAll(".tabcontent");
            tabcontent.forEach(tab => tab.style.display = "none");

            // Remove the "active" class from all tab links
            const tablinks = document.querySelectorAll(".tablink");
            tablinks.forEach(tab => tab.classList.remove("active"));

            // Show the current tab
            const targetPane = document.getElementById(tabName);
            targetPane.style.display = "block";
            if (event) event.currentTarget.classList.add("active");

            // Update URL with tab parameter
            const tabParam = tabName.toLowerCase().replace('manage', '');
            updateUrlParameter('tab', tabParam);
        }

        // Function to update URL parameters
        function updateUrlParameter(key, value) {
            const url = new URL(window.location.href);
            url.searchParams.set(key, value);
            window.history.pushState({}, '', url);
        }

        document.addEventListener('DOMContentLoaded', function () {
            // Get the active tab from URL parameter
            const urlParams = new URLSearchParams(window.location.search);
            const activeTab = urlParams.get('tab') || 'inventory';
            
            // Map URL parameter to tab names
            const tabMap = {
                'inventory': 'ManageInventory',
                'transactions': 'Transactions',
                'reports': 'Reports',
                'users': 'ManageUsers'
            };
            
            const tabName = tabMap[activeTab] || 'ManageInventory';
            
            // Find and click the corresponding tab
            const tabLink = Array.from(document.querySelectorAll('.tablink'))
                .find(link => link.onclick.toString().includes(`'${tabName}'`));
                
            if (tabLink) tabLink.click();
        });
    </script>
</body> 
</html>