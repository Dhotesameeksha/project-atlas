SET NAMES utf8mb4;
USE atlas;

CREATE TABLE reports (
  id VARCHAR(10) PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  publisher VARCHAR(100) NOT NULL,
  published DATE NOT NULL,
  sector VARCHAR(60) NOT NULL,
  access ENUM('paid','public_excerpt') NOT NULL
);

CREATE TABLE market_metrics (
  id INT AUTO_INCREMENT PRIMARY KEY,
  report_id VARCHAR(10) NOT NULL,
  metric VARCHAR(60) NOT NULL,
  region VARCHAR(40) NOT NULL,
  period VARCHAR(20) NOT NULL,
  value DECIMAL(12,2) NOT NULL,
  unit VARCHAR(20) NOT NULL,
  scope_note VARCHAR(200) NOT NULL,
  FOREIGN KEY (report_id) REFERENCES reports(id)
);

-- contact_email is confidential. The read-only SQL user gets NO access to this table.
CREATE TABLE clients (
  id VARCHAR(10) PRIMARY KEY,
  company VARCHAR(100) NOT NULL,
  contact_email VARCHAR(150) NOT NULL
);

CREATE TABLE client_purchases (
  client_id VARCHAR(10) NOT NULL,
  report_id VARCHAR(10) NOT NULL,
  PRIMARY KEY (client_id, report_id),
  FOREIGN KEY (client_id) REFERENCES clients(id),
  FOREIGN KEY (report_id) REFERENCES reports(id)
);

CREATE TABLE escalations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  client_id VARCHAR(10) NOT NULL,
  question TEXT NOT NULL,
  reason VARCHAR(200) NOT NULL,
  confidence DECIMAL(4,2) NULL,
  status ENUM('open','closed') NOT NULL DEFAULT 'open',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE audit_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  client_id VARCHAR(10) NULL,
  event_type VARCHAR(40) NOT NULL,
  detail JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO reports VALUES
('R-101','Global Smart Water Meter Market 2025','CMR','2025-03-10','Utilities','paid'),
('R-102','Smart Water Metering: Industry Outlook 2024','Aquila Insights (third party)','2024-06-18','Utilities','public_excerpt'),
('R-103','Residential Water Purifier Market 2025','CMR','2025-01-22','Consumer Durables','paid'),
('R-104','Global EV Charging Infrastructure Market 2025','CMR','2025-04-05','Energy','paid'),
('R-105','Smart Grid Analytics Market 2025','CMR','2025-05-14','Utilities','paid'),
('R-106','Industrial IoT Sensors Market 2025','CMR','2025-02-18','Industrial Automation','paid');

INSERT INTO market_metrics (report_id,metric,region,period,value,unit,scope_note) VALUES
('R-101','Market size','Global','2024',4.21,'USD B','Includes hardware, software and services'),
('R-101','CAGR','Global','2025-2030',11.20,'%','Forecast period stated as 2025-2030'),
('R-101','Regional share','APAC','2024',38.00,'%','Share of global market size'),
('R-102','Market size','Global','2024',3.87,'USD B','AMR / AMI hardware only'),
('R-102','CAGR','Global','2024-2029',9.80,'%','Different forecast window from R-101'),
('R-102','Regional share','APAC','2024',41.00,'%','Share of hardware market'),
('R-103','Market size','Global','2024',38.40,'USD B','Residential point-of-use and whole-house purifiers'),
('R-103','CAGR','Global','2025-2030',7.10,'%','Forecast period stated as 2025-2030'),
('R-103','Regional share','APAC','2024',47.00,'%','Share of global market size'),
('R-104','Market size','Global','2024',25.30,'USD B','Public and private chargers incl. installation'),
('R-104','CAGR','Global','2025-2030',22.40,'%','Forecast period stated as 2025-2030'),
('R-105','Market size','Global','2024',9.60,'USD B','Software and analytics services only'),
('R-105','CAGR','Global','2025-2030',12.50,'%','Forecast period stated as 2025-2030'),
('R-106','Market size','Global','2024',17.80,'USD B','Sensors for manufacturing and process industries'),
('R-106','CAGR','Global','2025-2030',8.90,'%','Forecast period stated as 2025-2030');

INSERT INTO clients VALUES
('C-01','Northwind Utilities','ops@example.com'),
('C-02','Blue Delta Infra','procurement@example.com');

INSERT INTO client_purchases VALUES ('C-01','R-101'),('C-01','R-103'),('C-02','R-103');
