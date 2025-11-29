<?php
// local.php: server-side endpoints for local dashboard
error_reporting(E_ALL);
ini_set('display_errors', 0);
session_start();

header('Content-Type: application/json');

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
    echo json_encode(["error" => "Database connection failed: " . $conn->connect_error]);
    exit;
}

// Fetch labs
if (isset($_GET['labs'])) {
    $sql = "SELECT DISTINCT lab_name FROM sensor_id";
    $result = $conn->query($sql);
    if (!$result) {
        echo json_encode(["error" => "Query failed: " . $conn->error]);
        exit;
    }
    $labs = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($labs);
    exit;
}

// Fetch device IDs for a lab
if (isset($_GET['lab'])) {
    $lab = $_GET['lab'];
    $sql = "SELECT esp8266_id FROM sensor_id WHERE lab_name = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(["error" => "Prepare failed: " . $conn->error]);
        exit;
    }
    $stmt->bind_param("s", $lab);
    $stmt->execute();
    $result = $stmt->get_result();
    $devices = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($devices);
    exit;
}

// Fetch readings for selected device(s)
if (isset($_GET['deviceIds'], $_GET['fromDate'], $_GET['toDate'])) {
    $deviceIds = json_decode($_GET['deviceIds'], true);
    $fromDate = $_GET['fromDate'];
    $toDate = $_GET['toDate'];

    if (empty($deviceIds)) {
        echo json_encode(["error" => "No devices found."]);
        exit;
    }

    $placeholders = implode(',', array_fill(0, count($deviceIds), '?'));
    $types = str_repeat('i', count($deviceIds)) . 'ss';
    $query = "SELECT * FROM readings WHERE esp8266_id IN ($placeholders) AND DATE(recorded_at) BETWEEN ? AND ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        echo json_encode(["error" => "Prepare failed: " . $conn->error]);
        exit;
    }

    $params = array_merge($deviceIds, [$fromDate, $toDate]);
    $stmtParams = [];
    $stmtParams[] = & $types;
    foreach ($params as $key => $value) {
        $stmtParams[] = & $params[$key];
    }
    call_user_func_array([$stmt, 'bind_param'], $stmtParams);

    $stmt->execute();
    $result = $stmt->get_result();
    if ($result === false) {
        echo json_encode(["error" => "SQL Execution failed: " . $conn->error]);
        exit;
    }

    $readings = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($readings);
    exit;
}

echo json_encode([]);
exit;
?>
