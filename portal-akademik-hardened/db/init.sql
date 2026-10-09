-- =====================================================================
--  Portal Akademik - Skema & Seed Data  (VERSI AFTER HARDENING)
--  * Password  : Argon2id (password_hash)      -> fix T2 (weak hashing)
--  * PII (no_telp, alamat): AES-256-GCM at-rest -> fix Information Disclosure
--  * Akun DB aplikasi: least-privilege          -> fix T9 (over-privilege)
--  Dibuat otomatis oleh gen_init.php (APP_KEY harus cocok dgn env aplikasi).
-- =====================================================================
CREATE DATABASE IF NOT EXISTS portal_akademik CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE portal_akademik;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,            -- Argon2id hash
    role ENUM('mahasiswa','admin') NOT NULL DEFAULT 'mahasiswa',
    nim VARCHAR(15) NULL,
    failed_attempts INT NOT NULL DEFAULT 0,    -- untuk audit/lockout
    locked_until DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS mahasiswa (
    nim VARCHAR(15) PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    prodi VARCHAR(60) NOT NULL,
    angkatan YEAR NOT NULL,
    email VARCHAR(100) NOT NULL,
    ipk DECIMAL(3,2) NOT NULL DEFAULT 0.00,
    no_telp VARCHAR(255) NULL,                 -- ciphertext AES-256-GCM (base64)
    alamat VARCHAR(512) NULL                   -- ciphertext AES-256-GCM (base64)
);
CREATE TABLE IF NOT EXISTS nilai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nim VARCHAR(15) NOT NULL,
    kode_mk VARCHAR(10) NOT NULL,
    nama_mk VARCHAR(100) NOT NULL,
    sks TINYINT NOT NULL,
    semester VARCHAR(10) NOT NULL,
    nilai_huruf CHAR(2) NOT NULL,
    FOREIGN KEY (nim) REFERENCES mahasiswa(nim)
);

-- ---- Seed users (Argon2id) ----
INSERT INTO users (username,password,role,nim) VALUES ('admin','$argon2id$v=19$m=65536,t=4,p=2$T2JuZGlKbnphWGRvRXVWbA$SMKe1sEMGqS3Dc/gehYrQhqJwOIbsLV0U6OG3rkfMlg','admin',NULL);
INSERT INTO users (username,password,role,nim) VALUES ('budi','$argon2id$v=19$m=65536,t=4,p=2$ZWNoaVNmWTV0dlZ1cDFiOA$UZfPwfd0Q7jQv0iPXYmKlnaGwNz1ovSV3tW6UMQblHQ','mahasiswa','160423001');
INSERT INTO users (username,password,role,nim) VALUES ('sari','$argon2id$v=19$m=65536,t=4,p=2$a3V3LzVhNWtYMUEyeUNnQQ$WI5uXQi0a6aQc0qjBwEWO2ya4nZeW2a5z/dnU2jEivA','mahasiswa','160423002');
INSERT INTO users (username,password,role,nim) VALUES ('andi','$argon2id$v=19$m=65536,t=4,p=2$ODh3clZDeTNCRjZOa2dJSA$m5kllncBQb1pcVBKMLogLulbjTZ8zhIsnKqRjZhcWLI','mahasiswa','160423003');

-- ---- Seed mahasiswa (no_telp & alamat terenkripsi AES-256-GCM) ----
INSERT INTO mahasiswa (nim,nama,prodi,angkatan,email,ipk,no_telp,alamat) VALUES ('160423001','Budi Santoso','Teknik Informatika',2023,'budi@student.ac.id',3.45,'jmvQK4DelrnJtBvB0+xU/DLlj20VCWaxXUezw9RsixGOiqb3w/MN6g==','si/JddOXqXZXrMSmETPSMX4hzQMU/oKZGHV7CMDc5JNclgUQJyUUVxhhUQDFZVXlnUES9sT3');
INSERT INTO mahasiswa (nim,nama,prodi,angkatan,email,ipk,no_telp,alamat) VALUES ('160423002','Sari Wulandari','Sistem Informasi',2023,'sari@student.ac.id',3.72,'IJXatSxJ37ADNhpdI7s2WXtjyGJ+qagfYNVXDEPmPuL4rNIepun9Ow==','0hrkt6BvohJBCIcQ95y/NHaNhCTE77FdBWfPuxegoZfPrz6sQM2k+jz2OHHBeEQ6Du3X+Q8=');
INSERT INTO mahasiswa (nim,nama,prodi,angkatan,email,ipk,no_telp,alamat) VALUES ('160423003','Andi Pratama','Teknik Informatika',2023,'andi@student.ac.id',3.1,'XZPZXZS1Qlce7YRYVUTUvbQB4FyK9n8HH61htfZJKVKMrmngR7WCoA==','8nTIhiqBb/hnue4bnN6mN+R9tcXKmiID/DDupDCzZHdtris2YqDO7DIRcT34/A3hnr838mjnQg==');

-- ---- Seed nilai ----
INSERT INTO nilai (nim,kode_mk,nama_mk,sks,semester,nilai_huruf) VALUES ('160423001','IF3110','Pemrograman Web',3,'2024/1','A');
INSERT INTO nilai (nim,kode_mk,nama_mk,sks,semester,nilai_huruf) VALUES ('160423001','IF3120','Basis Data Lanjut',3,'2024/1','AB');
INSERT INTO nilai (nim,kode_mk,nama_mk,sks,semester,nilai_huruf) VALUES ('160423001','IF4110','Advanced Info Security Sys',3,'2024/2','B');
INSERT INTO nilai (nim,kode_mk,nama_mk,sks,semester,nilai_huruf) VALUES ('160423002','SI3210','Analisis Proses Bisnis',3,'2024/1','A');
INSERT INTO nilai (nim,kode_mk,nama_mk,sks,semester,nilai_huruf) VALUES ('160423002','SI3220','Data Warehouse',3,'2024/1','A');
INSERT INTO nilai (nim,kode_mk,nama_mk,sks,semester,nilai_huruf) VALUES ('160423002','IF4110','Advanced Info Security Sys',3,'2024/2','AB');
INSERT INTO nilai (nim,kode_mk,nama_mk,sks,semester,nilai_huruf) VALUES ('160423003','IF3110','Pemrograman Web',3,'2024/1','BC');
INSERT INTO nilai (nim,kode_mk,nama_mk,sks,semester,nilai_huruf) VALUES ('160423003','IF3120','Basis Data Lanjut',3,'2024/1','B');
INSERT INTO nilai (nim,kode_mk,nama_mk,sks,semester,nilai_huruf) VALUES ('160423003','IF4110','Advanced Info Security Sys',3,'2024/2','B');

-- ---- Akun aplikasi LEAST-PRIVILEGE (fix T9) ----
-- Hanya SELECT/INSERT/UPDATE pada tabel yang diperlukan; tanpa DELETE/DROP/ALTER/GRANT.
CREATE USER IF NOT EXISTS 'portal_app'@'%' IDENTIFIED BY 'Str0ng_portal_app_2026_a7F9Kx';
GRANT SELECT, INSERT, UPDATE ON portal_akademik.users TO 'portal_app'@'%';
GRANT SELECT, INSERT, UPDATE ON portal_akademik.mahasiswa TO 'portal_app'@'%';
GRANT SELECT ON portal_akademik.nilai TO 'portal_app'@'%';
FLUSH PRIVILEGES;
