<?php
// Simple API endpoint for ESP devices to post readings (moved from local/api.php)
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

date_default_timezone_set('Asia/Kolkata');

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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = $_GET['id'] ?? null;
    $sql = $id ? "SELECT * FROM readings WHERE id = ?" : "SELECT * FROM readings";
    $stmt = $conn->prepare($sql);
    if ($id) { $stmt->bind_param('i', $id); }
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) { $data[] = $row; }
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);
    if (!isset($input['id']) || !isset($input['temp']) || !isset($input['rh'])) {
        echo json_encode(["error" => "ID, Temperature, and Humidity are required."]);
        exit;
    }
    $esp8266_id = intval($input['id']);
    $temperature = floatval($input['temp']);
    $humidity = floatval($input['rh']);

    $sql = "SELECT data_interval FROM sensor_id WHERE esp8266_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $esp8266_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) { echo json_encode(["error" => "Sensor ID not found."]); exit; }
    $row = $result->fetch_assoc();
    $data_interval = intval($row['data_interval']);
    $stmt->close();

    $sql = "SELECT recorded_at FROM readings WHERE esp8266_id = ? ORDER BY recorded_at DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $esp8266_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $last_recorded_at = strtotime($row['recorded_at']);
        $current_time = time();
        $time_difference = $current_time - $last_recorded_at;
        if ($time_difference < $data_interval) {
            $remaining_time = $data_interval - $time_difference;
            echo json_encode(["error" => "Wait $remaining_time seconds before sending data again."]);
            exit;
        }
    }

    $sql = "INSERT INTO readings (esp8266_id, temperature, humidity) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('idd', $esp8266_id, $temperature, $humidity);
    if ($stmt->execute()) {
        echo json_encode(["message" => "Data saved successfully."]);
    } else {
        echo json_encode(["error" => "Failed to save data."]);
    }
    $stmt->close();
    $conn->close();
    exit;
}

echo json_encode(["error" => "Unsupported request method."]);
?>
