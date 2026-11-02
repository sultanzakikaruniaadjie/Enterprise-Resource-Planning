CREATE DATABASE IF NOT EXISTS erp_sim CHARACTER SET utf8mb4;
USE erp_sim;

-- Persediaan: PRODUK & RAW (Praktikum 1), A & B (Praktikum 5)
CREATE TABLE inventory (
  code VARCHAR(10) PRIMARY KEY,
  name VARCHAR(60) NOT NULL,
  stock INT NOT NULL DEFAULT 0,
  incoming INT NOT NULL DEFAULT 0,
  lead INT NOT NULL DEFAULT 0
);
INSERT INTO inventory VALUES
 ('PRODUK','Komponen Otomotif Tipe A',120,0,0),
 ('RAW','Bahan baku produksi',500,0,0),
 ('A','Bahan A',400,100,4),
 ('B','Bahan B',250,0,7);

CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer VARCHAR(100) NOT NULL,
  qty INT NOT NULL,
  total BIGINT NOT NULL,
  kondisi VARCHAR(20) NOT NULL,
  status VARCHAR(20) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE accounts (
  code VARCHAR(10) PRIMARY KEY,
  name VARCHAR(60) NOT NULL,
  type VARCHAR(20) NOT NULL,
  normal ENUM('debit','kredit') NOT NULL
);
INSERT INTO accounts VALUES
 ('1101','Kas','Aset','debit'),
 ('1102','Piutang Usaha','Aset','debit'),
 ('1103','Persediaan Bahan Baku','Aset','debit'),
 ('2101','Utang Usaha','Kewajiban','kredit'),
 ('4101','Penjualan','Pendapatan','kredit'),
 ('5101','Beban Operasional','Beban','debit');

CREATE TABLE journal (
  id INT AUTO_INCREMENT PRIMARY KEY,
  descr VARCHAR(200) NOT NULL,
  debit VARCHAR(60) NOT NULL,
  credit VARCHAR(60) NOT NULL,
  amt BIGINT NOT NULL,
  tag VARCHAR(20) NOT NULL DEFAULT '',
  ref_id INT NULL,                       -- jurnal koreksi merujuk ke transaksi asal
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO journal (descr,debit,credit,amt) VALUES
 ('Pembelian bahan baku (kredit)','Persediaan Bahan Baku','Utang Usaha',8000000),
 ('Pembayaran utang pemasok','Utang Usaha','Kas',3000000),
 ('Penjualan produk (kredit)','Piutang Usaha','Penjualan',12000000),
 ('Penerimaan pembayaran pelanggan','Kas','Piutang Usaha',5000000),
 ('Pembayaran beban operasional (tunai)','Beban Operasional','Kas',1500000);

CREATE TABLE purchase_orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  no_po VARCHAR(20) NOT NULL,
  item CHAR(1) NOT NULL,
  qty INT NOT NULL,
  recv INT NOT NULL DEFAULT 0,
  eta INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO purchase_orders (no_po,item,qty,recv,eta) VALUES ('PO-001','A',100,0,4);

CREATE TABLE backups (
  id INT AUTO_INCREMENT PRIMARY KEY,
  label VARCHAR(100) NOT NULL,
  snapshot LONGTEXT NOT NULL,            -- JSON: inventory, journal, purchase_orders
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE sim_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  module VARCHAR(20) NOT NULL,
  input TEXT,
  result TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
