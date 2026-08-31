<?php
require __DIR__ . '/bootstrap.php';
require_api_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error' => 'POST required'], 405);
$data = json_decode(file_get_contents('php://input'), true) ?: [];
if (empty($data['company_id']) || empty($data['evidence']) || !in_array($data['decision'] ?? '', ['verified','flagged'], true)) json_response(['error' => 'Company, evidence and decision are required.'], 422);
try {
  $db = db(); $find = $db->prepare('SELECT id, arr FROM companies WHERE id = ?'); $find->execute([(int)$data['company_id']]); $company = $find->fetch();
  if (!$company) json_response(['error' => 'Company not found'], 404);
  $insert = $db->prepare('INSERT INTO revenue_submissions (company_id, period_label, reported_revenue, evidence_reference, source_type, review_status, reviewed_at, reviewer_note) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)');
  $insert->execute([$company['id'], date('F Y'), $company['arr'] / 12, $data['evidence'], 'manual review', $data['decision'], $data['note'] ?? null]);
  $status = $data['decision'] === 'verified' ? 'validated' : 'risk'; $update = $db->prepare('UPDATE companies SET status=? WHERE id=?'); $update->execute([$status, $company['id']]);
  json_response(['ok' => true, 'status' => $status]);
} catch (Throwable $e) {
  $store = demo_data(); $companyId = (int)$data['company_id']; $found = false;
  foreach ($store['companies'] as &$company) if ($company['id'] === $companyId) { $company['status'] = $data['decision'] === 'verified' ? 'validated' : 'risk'; $company['updated_at'] = date('Y-m-d H:i:s'); $found = true; }
  unset($company); if (!$found) json_response(['error'=>'Company not found'],404);
  foreach ($store['submissions'] as &$submission) if ($submission['company_id'] === $companyId && $submission['review_status'] === 'pending') { $submission['review_status'] = $data['decision']; $submission['evidence_reference'] = $data['evidence']; $submission['reviewer_note'] = $data['note'] ?? null; }
  unset($submission); demo_save($store); json_response(['ok'=>true,'status'=>$data['decision'] === 'verified' ? 'validated' : 'risk','mode'=>'demo']);
}
