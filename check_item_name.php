<?php
require 'db_connect.php';

header('Content-Type: application/json');

if (isset($_GET['name'])) {
    $item_name = trim($_GET['name']);
    
    $sql = "SELECT id FROM inventory WHERE item_name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $item_name);
    $stmt->execute();
    $stmt->store_result();
    
    echo json_encode(['exists' => $stmt->num_rows > 0]);
    $stmt->close();
} else {
    echo json_encode(['exists' => false]);
}

$conn->close();
?>