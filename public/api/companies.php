<?php
require __DIR__ . '/bootstrap.php';
require_api_login();
try {
  $db = db();
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    foreach (['name','sector','stage','arr','runway_months'] as $field) if (!isset($data[$field]) || $data[$field] === '') json_response(['error' => "$field is required."], 422);
    $statement = $db->prepare('INSERT INTO companies (name,sector,stage,arr,previous_arr,runway_months,status) VALUES (?,?,?,?,?,?,?)');
    $statement->execute([trim($data['name']),trim($data['sector']),trim($data['stage']),(float)$data['arr'],(float)($data['previous_arr'] ?? 0),(int)$data['runway_months'],'review']);
    json_response(['ok'=>true,'id'=>(int)$db->lastInsertId()], 201);
  }
  if ($_SERVER['REQUEST_METHOD'] !== 'GET') json_response(['error'=>'Method not allowed'],405);
  json_response($db->query('SELECT id,name,sector,stage,arr,previous_arr,runway_months,status,updated_at FROM companies ORDER BY arr DESC')->fetchAll());
} catch (Throwable $e) {
  $data = demo_data();
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    foreach (['name','sector','stage','arr','runway_months'] as $field) if (!isset($data[$field]) || $data[$field] === '') json_response(['error' => "$field is required."], 422);
    $store = demo_data(); $id = max(array_column($store['companies'], 'id')) + 1;
    $store['companies'][] = ['id'=>$id,'name'=>trim($data['name']),'sector'=>trim($data['sector']),'stage'=>trim($data['stage']),'arr'=>(float)$data['arr'],'previous_arr'=>(float)($data['previous_arr'] ?? 0),'runway_months'=>(int)$data['runway_months'],'status'=>'review','updated_at'=>date('Y-m-d H:i:s')];
    demo_save($store); json_response(['ok'=>true,'id'=>$id,'mode'=>'demo'], 201);
  }
  usort($data['companies'], fn($a, $b) => $b['arr'] <=> $a['arr']); json_response($data['companies']);
}
