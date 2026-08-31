<?php
require __DIR__ . '/../bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error' => 'Method not allowed'], 405);
$data = json_decode(file_get_contents('php://input'), true) ?: [];
$email = filter_var(trim($data['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$password = (string)($data['password'] ?? '');
if (!$email || $password === '') json_response(['error' => 'Enter a valid email and password.'], 422);
try {
  $user = db()->prepare('SELECT id,name,email,password_hash,avatar_url FROM users WHERE email = ? LIMIT 1');
  $user->execute([$email]); $user = $user->fetch();
  $stored = $user['password_hash'] ?? '';
  $valid = password_matches($password, $stored);
  if (!$user || empty($stored) || !$valid) json_response(['error' => 'Incorrect email or password.'], 401);
  $_SESSION['user']=['name'=>$user['name'],'email'=>$user['email'],'avatar'=>$user['avatar_url']];
  json_response(['ok'=>true]);
} catch (Throwable $e) {
  $store = demo_data();
  foreach ($store['users'] as $user) if (strtolower($user['email']) === strtolower($email) && password_matches($password, $user['password_hash'])) {
    $_SESSION['user']=['name'=>$user['name'],'email'=>$user['email'],'avatar'=>null,'demo'=>true]; json_response(['ok'=>true,'mode'=>'demo']);
  }
  $demoUsers = ['ali@gmail.com'=>'Ali', 'aatif@gmail.com'=>'Aatif'];
  if ($password === '123456' && isset($demoUsers[strtolower($email)])) {
    $_SESSION['user']=['name'=>$demoUsers[strtolower($email)],'email'=>strtolower($email),'avatar'=>null,'demo'=>true];
    json_response(['ok'=>true,'mode'=>'demo']);
  }
  json_response(['error' => 'Incorrect email or password.'], 401);
}
