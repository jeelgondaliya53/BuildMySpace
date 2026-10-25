CREATE DATABASE IF NOT EXISTS buildmyspace CHARACTER SET utf8mb4;
USE buildmyspace;

CREATE TABLE quality_rates (
  code VARCHAR(20) PRIMARY KEY,
  label VARCHAR(60) NOT NULL,
  note VARCHAR(160) NOT NULL,
  rate_per_sqft DECIMAL(10,2) NOT NULL
);
CREATE TABLE cost_breakdown (
  id INT AUTO_INCREMENT PRIMARY KEY,
  label VARCHAR(60) NOT NULL,
  percent DECIMAL(5,2) NOT NULL
);
CREATE TABLE extras (
  code VARCHAR(30) PRIMARY KEY,
  label VARCHAR(80) NOT NULL,
  cost DECIMAL(12,2) NOT NULL,
  type ENUM('fixed','per_sqft') NOT NULL DEFAULT 'fixed'
);
CREATE TABLE estimates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  email VARCHAR(120) NOT NULL,
  inputs JSON NOT NULL,
  result JSON NOT NULL,
  total DECIMAL(14,2) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sample rates (INR). Edit these rows to match your local market.
INSERT INTO quality_rates VALUES
 ('basic','Basic','Standard tiles, plain paint, basic fittings',1600),
 ('standard','Standard','Vitrified tiles, branded fittings, putty finish',2200),
 ('premium','Premium','Italian-style flooring, premium fittings, false ceiling',3200);
INSERT INTO cost_breakdown (label, percent) VALUES
 ('Foundation & structure',35),('Masonry & plaster',15),('Flooring & tiles',12),
 ('Electrical',8),('Plumbing & sanitary',8),('Doors & windows',8),
 ('Painting',6),('Contingency',8);
INSERT INTO extras VALUES
 ('modular_kitchen','Modular kitchen',250000,'fixed'),
 ('solar','Rooftop solar (3 kW)',180000,'fixed'),
 ('lift','Home lift',600000,'fixed'),
 ('compound_wall','Compound wall & gate',150000,'fixed'),
 ('interiors','Furniture & interiors',450,'per_sqft'),
 ('landscaping','Garden & landscaping',80000,'fixed');
