-- =====================================================================
--  Portal Akademik - Skema & Seed Data
--  Versi: BEFORE HARDENING (sengaja rentan untuk keperluan demo UTS)
-- =====================================================================
--  Catatan keamanan (lihat VULN.md):
--   * Password disimpan sebagai MD5 tanpa salt  -> lemah (BAB IV)
--   * Tidak ada kolom untuk MFA / rate-limit    -> ditambah saat hardening
-- =====================================================================

CREATE DATABASE IF NOT EXISTS portal_akademik
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE portal_akademik;

-- ---------------------------------------------------------------------
-- Tabel akun login
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    username  VARCHAR(50)  NOT NULL UNIQUE,
    password  VARCHAR(64)  NOT NULL,          -- MD5 hex (rentan, diganti Argon2id saat hardening)
    role      ENUM('mahasiswa','admin') NOT NULL DEFAULT 'mahasiswa',
    nim       VARCHAR(15)  NULL,              -- diisi utk role mahasiswa
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------------------------------
-- Tabel data mahasiswa
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS mahasiswa (
    nim       VARCHAR(15) PRIMARY KEY,
    nama      VARCHAR(100) NOT NULL,
    prodi     VARCHAR(60)  NOT NULL,
    angkatan  YEAR         NOT NULL,
    email     VARCHAR(100) NOT NULL,
    ipk       DECIMAL(3,2) NOT NULL DEFAULT 0.00,
    -- data "sensitif" untuk menunjukkan dampak kebocoran (STRIDE: Information Disclosure)
    no_telp   VARCHAR(20)  NULL,
    alamat    VARCHAR(200) NULL
);

-- ---------------------------------------------------------------------
-- Tabel nilai / KHS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS nilai (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nim         VARCHAR(15) NOT NULL,
    kode_mk     VARCHAR(10) NOT NULL,
    nama_mk     VARCHAR(100) NOT NULL,
    sks         TINYINT      NOT NULL,
    semester    VARCHAR(10)  NOT NULL,
    nilai_huruf CHAR(2)      NOT NULL,
    FOREIGN KEY (nim) REFERENCES mahasiswa(nim)
);

-- =====================================================================
--  SEED DATA
-- =====================================================================

-- Akun: 1 admin + 3 mahasiswa.
-- Password (MD5):
--   admin      -> Admin@123        (21232f... bukan; dihitung di bawah)
--   budi        -> budi123
--   sari        -> sari123
--   andi        -> P@ssw0rd
INSERT INTO users (username, password, role, nim) VALUES
  ('admin', MD5('Admin@123'), 'admin',     NULL),
  ('budi',  MD5('budi123'),   'mahasiswa', '160423001'),
  ('sari',  MD5('sari123'),   'mahasiswa', '160423002'),
  ('andi',  MD5('P@ssw0rd'),  'mahasiswa', '160423003');

INSERT INTO mahasiswa (nim, nama, prodi, angkatan, email, ipk, no_telp, alamat) VALUES
  ('160423001','Budi Santoso',   'Teknik Informatika', 2023, 'budi@student.ac.id', 3.45, '081200000001', 'Jl. Melati No. 1, Surabaya'),
  ('160423002','Sari Wulandari',  'Sistem Informasi',   2023, 'sari@student.ac.id', 3.72, '081200000002', 'Jl. Mawar No. 7, Surabaya'),
  ('160423003','Andi Pratama',    'Teknik Informatika', 2023, 'andi@student.ac.id', 3.10, '081200000003', 'Jl. Kenanga No. 3, Sidoarjo');

INSERT INTO nilai (nim, kode_mk, nama_mk, sks, semester, nilai_huruf) VALUES
  ('160423001','IF3110','Pemrograman Web',            3, '2024/1', 'A'),
  ('160423001','IF3120','Basis Data Lanjut',          3, '2024/1', 'AB'),
  ('160423001','IF4110','Advanced Info Security Sys',  3, '2024/2', 'B'),
  ('160423002','SI3210','Analisis Proses Bisnis',     3, '2024/1', 'A'),
  ('160423002','SI3220','Data Warehouse',             3, '2024/1', 'A'),
  ('160423002','IF4110','Advanced Info Security Sys',  3, '2024/2', 'AB'),
  ('160423003','IF3110','Pemrograman Web',            3, '2024/1', 'BC'),
  ('160423003','IF3120','Basis Data Lanjut',          3, '2024/1', 'B'),
  ('160423003','IF4110','Advanced Info Security Sys',  3, '2024/2', 'B');
