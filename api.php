<?php
// api.php - robust JSON + form handler
ini_set('display_errors', 0); // Hide errors from the user's screen
ini_set('log_errors', 1);
// Ensure the log file is writable by your web server
ini_set('error_log', __DIR__ . '/api_debug.log'); 
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

// --- DATABASE CONNECTION & ERROR HANDLING START ---
$pdo = null;
try {
    // Include the connection file, which returns the PDO object
    $pdo = require_once __DIR__ . '/db_connection.php'; 
} catch (\Exception $e) {
    // If the database connection fails (due to bad credentials, server down, etc.)
    http_response_code(500);
    // Return a clean JSON error response immediately
    echo json_encode([
        'success' => false,
        'error' => 'Server Error: Database connection unavailable.',
        'debug' => $e->getMessage() // Only include for internal debugging, remove in production
    ]);
    exit;
}
// --- DATABASE CONNECTION & ERROR HANDLING END ---

session_start();

$route = $_GET['route'] ?? '';

function json_out($data) { echo json_encode($data); exit; }

function get_request_data() {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $contentType = strtolower(trim(explode(';', $contentType)[0] ?? ''));

    if ($contentType === 'application/json') {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['__parse_error' => json_last_error_msg(), '__raw' => $raw];
        }
        return $json ?: [];
    }

    // If normal form POST (x-www-form-urlencoded / multipart)
    if (!empty($_POST)) return $_POST;

    // Try parse urlencoded raw
    $raw = file_get_contents('php://input');
    parse_str($raw, $parsed);
    return $parsed ?: [];
}

// ROUTES
if ($route === 'register') {
    $input = get_request_data();
    if (isset($input['__parse_error'])) {
        http_response_code(400);
        json_out(['success'=>false,'error'=>'Invalid JSON: '.$input['__parse_error'],'raw'=>$input['__raw']]);
    }
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $full_name = trim($input['full_name'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_out(['success'=>false,'error'=>'Invalid email']);
    if (strlen($password) < 6) json_out(['success'=>false,'error'=>'Password too short (min 6)']);
    if ($full_name === '') json_out(['success'=>false,'error'=>'Full name required']);

    // check existing
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) json_out(['success'=>false,'error'=>'Email already registered']);

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, full_name) VALUES (?, ?, ?)');
    $stmt->execute([$email, $hash, $full_name]);

    // auto-login
    $_SESSION['user_id'] = $pdo->lastInsertId();
    json_out(['success'=>true,'message'=>'Registered','data'=>['id'=>$_SESSION['user_id'],'email'=>$email,'full_name'=>$full_name]]);
}

if ($route === 'login') {
    $input = get_request_data();
    if (isset($input['__parse_error'])) {
        http_response_code(400);
        json_out(['success'=>false,'error'=>'Invalid JSON: '.$input['__parse_error'],'raw'=>$input['__raw']]);
    }
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_out(['success'=>false,'error'=>'Invalid email']);
    if ($password === '') json_out(['success'=>false,'error'=>'Password required']);

    $stmt = $pdo->prepare('SELECT id, password_hash, full_name FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || !password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        json_out(['success'=>false,'error'=>'Invalid credentials']);
    }

    $_SESSION['user_id'] = $user['id'];
    json_out(['success'=>true,'message'=>'Logged in','data'=>['id'=>$user['id'],'email'=>$email,'full_name'=>$user['full_name']]]);
}

if ($route === 'me') {
    if (empty($_SESSION['user_id'])) json_out(['success'=>false,'error'=>'Not authenticated']);
    $stmt = $pdo->prepare('SELECT id, email, full_name FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) json_out(['success'=>false,'error'=>'Not found']);
    json_out(['success'=>true,'data'=>$user]);
}

if ($route === 'logout') {
    session_unset();
    session_destroy();
    json_out(['success'=>true,'message'=>'Logged out']);
}

if ($route === 'courses') {
    $stmt = $pdo->query('SELECT id, slug, title, short_description FROM courses ORDER BY id');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    json_out(['success'=>true,'data'=>$rows]);
}

if ($route === 'enroll') {
    if (empty($_SESSION['user_id'])) { http_response_code(401); json_out(['success'=>false,'error'=>'Not authenticated']); }
    $input = get_request_data();
    $course_id = $input['course_id'] ?? null;
    if (!$course_id) json_out(['success'=>false,'error'=>'Missing course_id']);
    // Demo: return success. Implement actual enrollment table if desired.
    json_out(['success'=>true,'message'=>'Enrolled (demo)']);
}

json_out(['success'=>false,'error'=>'Unknown route']);