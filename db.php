<?php
// db.php
$DB_HOST = '127.0.0.1';
$DB_NAME = 'educonnect_db';
$DB_USER = 'root';
$DB_PASS = ''; // XAMPP default is empty

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    // For debugging return JSON (but in production you might prefer logging)
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success'=>false, 'error'=>'DB connect failed', 'detail'=>$e->getMessage()]);
    exit;
}
