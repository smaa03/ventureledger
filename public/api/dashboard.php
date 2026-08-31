<?php
require __DIR__ . '/bootstrap.php';
require_api_login();
try {
  $db = db();
  $metrics = $db->query("SELECT COALESCE(SUM(arr),0) total_arr, COALESCE(SUM(previous_arr),0) previous_arr, COALESCE(ROUND(AVG(runway_months),1),0) average_runway, SUM(status='risk') at_risk FROM companies")->fetch();
  $metrics['growth'] = $metrics['previous_arr'] > 0 ? round((($metrics['total_arr']-$metrics['previous_arr'])/$metrics['previous_arr'])*100, 1) : 0;
  $queue = $db->query("SELECT c.id company_id,c.name,c.arr,c.sector,c.status,rs.id submission_id,rs.period_label,rs.reported_revenue,rs.evidence_reference,rs.review_status FROM companies c LEFT JOIN revenue_submissions rs ON rs.id=(SELECT r.id FROM revenue_submissions r WHERE r.company_id=c.id ORDER BY r.submitted_at DESC LIMIT 1) WHERE c.status='review' OR rs.review_status='pending' ORDER BY c.updated_at DESC LIMIT 8")->fetchAll();
  json_response(['metrics'=>$metrics,'queue'=>$queue,'updated_at'=>gmdate('c')]);
} catch (Throwable $e) { $data = demo_data(); json_response(['metrics'=>demo_metrics($data),'queue'=>demo_queue($data),'updated_at'=>gmdate('c'),'mode'=>'demo']); }
