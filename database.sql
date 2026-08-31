CREATE DATABASE IF NOT EXISTS ventureledger CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ventureledger;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  google_id VARCHAR(255) UNIQUE NULL,
  password_hash VARCHAR(255) NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  avatar_url VARCHAR(500) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE companies (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  sector VARCHAR(80) NOT NULL,
  stage VARCHAR(40) NOT NULL,
  arr DECIMAL(14,2) NOT NULL DEFAULT 0,
  previous_arr DECIMAL(14,2) NOT NULL DEFAULT 0,
  runway_months SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('validated','review','risk') NOT NULL DEFAULT 'review',
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE revenue_submissions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id INT UNSIGNED NOT NULL,
  period_label VARCHAR(50) NOT NULL,
  reported_revenue DECIMAL(14,2) NOT NULL,
  evidence_reference VARCHAR(500) NOT NULL,
  source_type VARCHAR(60) NOT NULL,
  submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  reviewed_at TIMESTAMP NULL,
  review_status ENUM('pending','verified','flagged') DEFAULT 'pending',
  reviewer_note TEXT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

-- Run this after the initial import when upgrading an existing database:
-- ALTER TABLE revenue_submissions ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
-- ALTER TABLE users ADD COLUMN password_hash VARCHAR(255) NULL AFTER google_id;

INSERT INTO companies (name, sector, stage, arr, previous_arr, runway_months, status) VALUES
('Lattice Health', 'Healthtech', 'Series A', 2800000, 2100000, 18, 'validated'),
('ParcelPilot', 'Logistics', 'Seed', 920000, 710000, 14, 'review'),
('Aurora Grid', 'Climate', 'Series B', 5100000, 4700000, 22, 'validated'),
('Kite Finance', 'Fintech', 'Pre-seed', 380000, 260000, 9, 'risk');

-- Demo sign-in accounts. Both use password: 123456
INSERT INTO users (name, email, password_hash) VALUES
('Ali', 'ali@gmail.com', 'pbkdf2-sha256$210000$ventureledger-ali$Sq4+Ku34VuFYwPNdFQi5bfWTVI5SyqUBluIgxO8YuWg='),
('Aatif', 'aatif@gmail.com', 'pbkdf2-sha256$210000$ventureledger-aatif$jCrCq5fIFplN3h97JwP8HDT3cz4fWZiEG1jgG1y6lZ8=');

-- Revenue evidence creates the initial items in the validation queue.
INSERT INTO revenue_submissions (company_id, period_label, reported_revenue, evidence_reference, source_type, review_status) VALUES
((SELECT id FROM companies WHERE name = 'ParcelPilot' LIMIT 1), 'July 2026 revenue', 920000, 'Stripe report — July 2026', 'Stripe', 'pending'),
((SELECT id FROM companies WHERE name = 'Kite Finance' LIMIT 1), 'July 2026 revenue', 380000, 'Bank statement — July 2026', 'Bank statement', 'pending');
