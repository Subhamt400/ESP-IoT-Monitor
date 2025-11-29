<?php
// Login endpoint with password_hash migration support
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');
session_start();

$host = "localhost";
$username = "root";
$password = "root";
$database = "login_system";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Database connection failed."]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['username']) || !isset($input['password'])) {
    echo json_encode(["success" => false, "error" => "Username and Password are required."]);
    exit;
}

$u = $input['username'];
$p = $input['password'];

$stmt = $conn->prepare('SELECT * FROM users WHERE username = ?');
$stmt->bind_param('s', $u);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    echo json_encode(["success" => false, "error" => "Invalid username or password."]);
    exit;
}

$user = $result->fetch_assoc();
$stored = $user['password'];
$role = $user['role'] ?? 'local';

$authenticated = false;
// Prefer password_hash verification; support legacy MD5 and rehash on success
if (password_verify($p, $stored)) {
    $authenticated = true;
} elseif (md5($p) === $stored) {
    $authenticated = true;
    // Rehash and store using password_hash
    $newHash = password_hash($p, PASSWORD_DEFAULT);
    $update = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
    $update->bind_param('si', $newHash, $user['id']);
    $update->execute();
}

if ($authenticated) {
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $role;
    $redirect = ($role === 'admin') ? '../public/admin_dashboard.html' : '../public/local_dashboard.html';
    echo json_encode(["success" => true, "message" => "Login successful.", "role" => $role, "redirect_url" => $redirect]);
} else {
    echo json_encode(["success" => false, "error" => "Invalid username or password."]);
}

$stmt->close();
$conn->close();
?>
