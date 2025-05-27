<?php
// Include database connection
require_once '../config/db_connect.php';

// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security check - verify the user is logged in
if (!isset($_SESSION['username'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit();
}

// Process AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id'])) {
    $student_id = filter_input(INPUT_POST, 'student_id', FILTER_SANITIZE_STRING);
    
    if (empty($student_id)) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Student ID is required'
        ]);
        exit();
    }
    
    // Query to get the student details
    $sql = "SELECT DISTINCT student_name, section FROM transactions WHERE student_id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $student_data = $result->fetch_assoc();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $student_data
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Student not found'
        ]);
    }
    
    $stmt->close();
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}

// Close database connection
$conn->close();
?>