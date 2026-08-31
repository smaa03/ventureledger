<?php
session_start();
function env_value(string $key): ?string {
  static $vars = null;
  if ($vars === null) {
    $vars = [];
    $path = dirname(__DIR__, 2) . '/.env';
    if (is_file($path)) foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
      if ($line[0] !== '#' && str_contains($line, '=')) { [$k, $v] = explode('=', $line, 2); $vars[trim($k)] = trim($v); }
    }
  }
  return $_ENV[$key] ?? getenv($key) ?: ($vars[$key] ?? null);
}
function db(): PDO {
  static $db = null;
  if ($db) return $db;
  $dsn = 'mysql:host=' . (env_value('DB_HOST') ?: '127.0.0.1') . ';dbname=' . env_value('DB_NAME') . ';charset=utf8mb4';
  $db = new PDO($dsn, env_value('DB_USER'), env_value('DB_PASS'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
  return $db;
}
function json_response($data, int $code = 200): never { http_response_code($code); header('Content-Type: application/json'); echo json_encode($data); exit; }
function require_api_login(): void {
  if (empty($_SESSION['user'])) json_response(['error' => 'Your session has ended. Please log in again.'], 401);
}
function require_page_login(): void {
  if (empty($_SESSION['user'])) { header('Location: login.php'); exit; }
}
function demo_store_path(): string { return dirname(__DIR__, 2) . '/data/demo-store.json'; }
function demo_seed(): array {
  return [
    'companies' => [
      ['id'=>1,'name'=>'Lattice Health','sector'=>'Healthtech','stage'=>'Series A','arr'=>2800000,'previous_arr'=>2100000,'runway_months'=>18,'status'=>'validated','updated_at'=>'2026-07-16 09:00:00'],
      ['id'=>2,'name'=>'ParcelPilot','sector'=>'Logistics','stage'=>'Seed','arr'=>920000,'previous_arr'=>710000,'runway_months'=>14,'status'=>'review','updated_at'=>'2026-07-16 10:00:00'],
      ['id'=>3,'name'=>'Aurora Grid','sector'=>'Climate','stage'=>'Series B','arr'=>5100000,'previous_arr'=>4700000,'runway_months'=>22,'status'=>'validated','updated_at'=>'2026-07-16 11:00:00'],
      ['id'=>4,'name'=>'Kite Finance','sector'=>'Fintech','stage'=>'Pre-seed','arr'=>380000,'previous_arr'=>260000,'runway_months'=>9,'status'=>'risk','updated_at'=>'2026-07-16 12:00:00'],
    ],
    'submissions' => [
      ['id'=>1,'company_id'=>2,'period_label'=>'July 2026 revenue','reported_revenue'=>920000,'evidence_reference'=>'Stripe report — July 2026','source_type'=>'Stripe','review_status'=>'pending'],
      ['id'=>2,'company_id'=>4,'period_label'=>'July 2026 revenue','reported_revenue'=>380000,'evidence_reference'=>'Bank statement — July 2026','source_type'=>'Bank statement','review_status'=>'pending'],
    ],
    'users' => [],
  ];
}
function demo_data(): array {
  $path = demo_store_path();
  $data = is_file($path) ? json_decode((string)file_get_contents($path), true) : null;
  if (!is_array($data) || !isset($data['companies'], $data['submissions'])) { $data = demo_seed(); demo_save($data); }
  if (!isset($data['users']) || !is_array($data['users'])) { $data['users'] = []; demo_save($data); }
  return $data;
}
function demo_save(array $data): void {
  $dir = dirname(demo_store_path());
  if (!is_dir($dir)) mkdir($dir, 0775, true);
  file_put_contents(demo_store_path(), json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
}
function demo_metrics(array $data): array {
  $companies = $data['companies']; $total = array_sum(array_column($companies, 'arr')); $previous = array_sum(array_column($companies, 'previous_arr'));
  return ['total_arr'=>$total, 'previous_arr'=>$previous, 'average_runway'=>round(array_sum(array_column($companies, 'runway_months')) / max(count($companies), 1), 1), 'at_risk'=>count(array_filter($companies, fn($company) => $company['status'] === 'risk')), 'growth'=>$previous ? round((($total-$previous)/$previous)*100, 1) : 0];
}
function demo_queue(array $data): array {
  $companies = []; foreach ($data['companies'] as $company) $companies[$company['id']] = $company;
  return array_values(array_map(function($submission) use ($companies) { $company = $companies[$submission['company_id']] ?? []; return array_merge(['company_id'=>$submission['company_id'],'name'=>$company['name'] ?? 'Unknown company','sector'=>$company['sector'] ?? '','status'=>$company['status'] ?? 'review'], $submission); }, array_filter($data['submissions'], fn($submission) => $submission['review_status'] === 'pending')));
}
function password_matches(string $password, string $stored): bool {
  if (str_starts_with($stored, 'pbkdf2-sha256$')) {
    [, $iterations, $salt, $expected] = explode('$', $stored, 4);
    return hash_equals($expected, base64_encode(hash_pbkdf2('sha256', $password, $salt, (int)$iterations, 32, true)));
  }
  return password_verify($password, $stored);
}
