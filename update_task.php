<?php
include 'db_connect.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($_SESSION['user_id']) || empty($data['id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$user_id = $_SESSION['user_id'];
$set = [];
$params = [];
$types = '';

if (isset($data['name'])) {
    $set[] = 'name = ?';
    $params[] = $data['name'];
    $types .= 's';
}
if (isset($data['priority'])) {
    $set[] = 'priority = ?';
    $params[] = $data['priority'];
    $types .= 's';
}
if (isset($data['deadline'])) {
    $set[] = 'deadline = ?';
    $params[] = $data['deadline'];
    $types .= 's';
}
if (isset($data['description'])) {
    $set[] = 'description = ?';
    $params[] = $data['description'];
    $types .= 's';
}
if (isset($data['status'])) {
    $set[] = 'status = ?';
    $params[] = $data['status'];
    $types .= 's';
}

if (empty($set)) {
    echo json_encode(['success' => false]);
    exit;
}

$sql = "UPDATE tasks SET " . implode(', ', $set) . " WHERE id = ? AND user_id = ?";
$types .= 'ii';
$params[] = $data['id'];
$params[] = $user_id;

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}
?>