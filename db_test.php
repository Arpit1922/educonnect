<?php
// db_test.php - quick test
require_once __DIR__ . '/db.php';
try {
    $stmt = $pdo->query("SELECT 'OK' AS ok");
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['success'=>true,'db'=>$r]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
