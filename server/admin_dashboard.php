<?php
// Server-side API for admin dashboard
// Do not display errors in production
error_reporting(E_ALL);
ini_set('display_errors', 0);
session_start();

header('Content-Type: application/json');

// Authenticate: if not logged in, return 401 JSON response
if (!isset($_SESSION['username']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'local')) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$host = "localhost";
$username = "root";
$password = "root";
$database = "sensor_data";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed."]);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'fetch') {
    $result = $conn->query("SELECT * FROM sensor_id");
    $data = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    echo json_encode($data);
} elseif ($action === 'update') {
    $input = json_decode(file_get_contents("php://input"), true);
    if (!$input) { echo json_encode(["error" => "Invalid input"]); exit; }

    $stmt = $conn->prepare("UPDATE sensor_id SET lab_name=?, esp8266_id=?, sensor_short_name=?, data_interval=? WHERE id=?");
    $lab = $input['lab_name'] ?? '';
    $esp = intval($input['esp8266_id'] ?? 0);
    $short = $input['sensor_short_name'] ?? '';
    $interval = intval($input['data_interval'] ?? 60);
    $id = intval($input['id'] ?? 0);
    $stmt->bind_param("sisii", $lab, $esp, $short, $interval, $id);
    $stmt->execute();
    echo json_encode(["message" => "Sensor updated successfully"]);
} elseif ($action === 'add') {
    $input = json_decode(file_get_contents("php://input"), true);
    if (!isset($input['lab_name'], $input['esp8266_id'], $input['sensor_short_name'], $input['data_interval'])) {
        echo json_encode(["error" => "Invalid input. Missing required fields."]);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO sensor_id (lab_name, esp8266_id, sensor_short_name, data_interval) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sisi", $input['lab_name'], $input['esp8266_id'], $input['sensor_short_name'], $input['data_interval']);
    if ($stmt->execute()) {
        echo json_encode(["message" => "New sensor added successfully"]);
    } else {
        echo json_encode(["error" => "Failed to add sensor: " . $stmt->error]);
    }
} elseif ($action === 'delete') {
    $id = intval($_GET['id'] ?? 0);
    $stmt = $conn->prepare("DELETE FROM sensor_id WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    echo json_encode(["message" => "Sensor deleted successfully"]);
} elseif ($action === 'get_admin_name') {
    echo json_encode(["admin_name" => $_SESSION['username'] ?? 'Admin']);
}

$conn->close();
?>
