-- Run this once for an existing VentureLedger database.
ALTER TABLE users ADD COLUMN password_hash VARCHAR(255) NULL AFTER google_id;

INSERT INTO users (name, email, password_hash) VALUES
('Ali', 'ali@gmail.com', 'pbkdf2-sha256$210000$ventureledger-ali$Sq4+Ku34VuFYwPNdFQi5bfWTVI5SyqUBluIgxO8YuWg='),
('Aatif', 'aatif@gmail.com', 'pbkdf2-sha256$210000$ventureledger-aatif$jCrCq5fIFplN3h97JwP8HDT3cz4fWZiEG1jgG1y6lZ8=')
ON DUPLICATE KEY UPDATE name = VALUES(name), password_hash = VALUES(password_hash);

INSERT INTO revenue_submissions (company_id, period_label, reported_revenue, evidence_reference, source_type, review_status)
SELECT id, 'July 2026 revenue', arr, 'Stripe report — July 2026', 'Stripe', 'pending'
FROM companies WHERE name = 'ParcelPilot' LIMIT 1;
INSERT INTO revenue_submissions (company_id, period_label, reported_revenue, evidence_reference, source_type, review_status)
SELECT id, 'July 2026 revenue', arr, 'Bank statement — July 2026', 'Bank statement', 'pending'
FROM companies WHERE name = 'Kite Finance' LIMIT 1;
