<?php
require __DIR__ . '/../bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error' => 'Method not allowed.'], 405);
$data = json_decode(file_get_contents('php://input'), true) ?: [];
$name = trim($data['name'] ?? '');
$email = filter_var(trim($data['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$password = (string)($data['password'] ?? '');
if (strlen($name) < 2 || strlen($name) > 120) json_response(['error' => 'Enter a name between 2 and 120 characters.'], 422);
if (!$email) json_response(['error' => 'Enter a valid email address.'], 422);
if (strlen($password) < 6) json_response(['error' => 'Use a password with at least 6 characters.'], 422);
$hash = password_hash($password, PASSWORD_DEFAULT);
try {
  $db = db();
  $exists = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1'); $exists->execute([$email]);
  if ($exists->fetch()) json_response(['error' => 'An account with this email already exists.'], 409);
  $add = $db->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)'); $add->execute([$name, $email, $hash]);
  $_SESSION['user']=['name'=>$name,'email'=>$email,'avatar'=>null]; json_response(['ok'=>true], 201);
} catch (PDOException $e) {
  if ($e->getCode() === '23000') json_response(['error' => 'An account with this email already exists.'], 409);
  $store = demo_data();
  foreach ($store['users'] as $user) if (strtolower($user['email']) === strtolower($email)) json_response(['error' => 'An account with this email already exists.'], 409);
  $store['users'][]=['id'=>count($store['users'])+1,'name'=>$name,'email'=>strtolower($email),'password_hash'=>$hash,'created_at'=>date('c')]; demo_save($store);
  $_SESSION['user']=['name'=>$name,'email'=>strtolower($email),'avatar'=>null,'demo'=>true]; json_response(['ok'=>true,'mode'=>'demo'], 201);
}
