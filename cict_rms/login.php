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
                    header("Location: admin_dashboard.php");
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
    <title>Login</title>
</head>
<body>
    <form method="POST">
        Username: <input type="text" name="username" required><br>
        Password: <input type="password" name="password" required><br>
        <button type="submit">Login</button>
    </form>
</body>
</html>