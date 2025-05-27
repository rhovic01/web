<?php
session_start();
require 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Fetch user from the database
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        // Check if the account is active
        if ($user['status'] === 'inactive') {
            echo "<script>alert('Your account is deactivated. Please contact the admin.');</script>";
        } else {
            // Verify the password
            if (password_verify($password, $user['password'])) {
                // Store session data
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];

                // Store the session ID in the database
                $sessionId = session_id();
                $updateSql = "UPDATE users SET session_id = ? WHERE id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("si", $sessionId, $user['id']);
                $updateStmt->execute();
                $updateStmt->close();

                // Redirect based on role
                if ($user['role'] == 'admin') {
                    header("Location: admin_dashboard.php#");
                } else {
                    header("Location: officer_dashboard.php");
                }
                exit();
            } else {
                echo "<script>alert('Invalid credentials!');</script>";
            }
        }
    } else {
        echo "<script>alert('Invalid credentials!');</script>";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | CICT Inventory</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Shared CSS -->
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
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Identical Navbar */
        .navbar {
            background-color: var(--dark-blue);
            padding: 1.5rem 0;
            box-shadow: 0 4px 12px rgba(1, 31, 75, 0.1);
        }
        
        .nav-link {
            color: var(--off-white) !important;
            padding: 0.5rem 1.5rem !important;
            font-weight: 600;
            position: relative;
        }
        
        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            background-color: var(--very-pale-blue);
            transition: width 0.3s ease;
        }
        
        .nav-link:hover::after {
            width: 60%;
        }
        
        /* Login Card (matches homepage hero) */
        .login-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .login-card {
            width: 100%;
            max-width: 450px;
            padding: 3rem;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(1, 31, 75, 0.1);
        }
        
        /* Form Elements */
        .form-control {
            border: 1px solid var(--very-pale-blue);
            padding: 0.75rem 1rem;
            border-radius: 8px;
        }
        
        .form-control:focus {
            border-color: var(--light-blue);
            box-shadow: 0 0 0 0.25rem rgba(0, 91, 150, 0.1);
        }
        
        /* Buttons (identical to homepage) */
        .btn-primary {
            background-color: var(--light-blue);
            border: none;
            padding: 0.75rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: var(--medium-blue);
            transform: translateY(-2px);
        }
        
        .text-link {
            color: var(--light-blue);
            text-decoration: none;
        }
        
        h2 {
            color: var(--dark-blue);
            font-weight: 700;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <!-- Identical Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">Signup</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Login Card -->
    <section class="login-container">
        <div class="login-card">
            <h2 class="text-center">Login to Your Account</h2>
            <form method="POST">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 mb-3">Login</button>
                <div class="text-center">
                    <a href="index.php" class="text-link">Back to Home</a>
                </div>
            </form>
        </div>
    </section>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>