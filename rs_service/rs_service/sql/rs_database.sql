CREATE DATABASE IF NOT EXISTS db_rumah_sakit
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_rumah_sakit;

-- 1. Poliklinik
CREATE TABLE poliklinik (
  id_poli      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kode_poli    VARCHAR(10)  NOT NULL UNIQUE,
  nama_poli    VARCHAR(100) NOT NULL,
  lokasi       VARCHAR(100) NULL,
  status       ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Pengguna (akun login semua peran)
CREATE TABLE pengguna (
  id_pengguna  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username     VARCHAR(50)  NOT NULL UNIQUE,
  password     VARCHAR(255) NOT NULL,
  nama_lengkap VARCHAR(100) NOT NULL,
  email        VARCHAR(100) NULL UNIQUE,
  role         ENUM('admin','petugas','dokter','pasien') NOT NULL,
  status       ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  gagal_login  TINYINT UNSIGNED NOT NULL DEFAULT 0,
  blokir_sampai DATETIME NULL,
  last_login   DATETIME NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. Dokter
CREATE TABLE dokter (
  id_dokter    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_pengguna  INT UNSIGNED NULL UNIQUE,
  id_poli      INT UNSIGNED NOT NULL,
  nip          VARCHAR(30)  NULL UNIQUE,
  nama_dokter  VARCHAR(100) NOT NULL,
  spesialis    VARCHAR(100) NULL,
  no_str       VARCHAR(50)  NULL,
  no_hp        VARCHAR(20)  NULL,
  status       ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_dokter_pengguna FOREIGN KEY (id_pengguna) REFERENCES pengguna(id_pengguna)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_dokter_poli FOREIGN KEY (id_poli) REFERENCES poliklinik(id_poli)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 4. Asuransi
CREATE TABLE asuransi (
  id_asuransi    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kode_asuransi  VARCHAR(20)  NOT NULL UNIQUE,
  nama_asuransi  VARCHAR(100) NOT NULL,
  jenis          ENUM('pemerintah','swasta') NOT NULL DEFAULT 'swasta',
  kontak         VARCHAR(50)  NULL,
  status         ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 5. Pasien
CREATE TABLE pasien (
  id_pasien      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_pengguna    INT UNSIGNED NULL UNIQUE,
  id_asuransi    INT UNSIGNED NULL,
  no_rm          VARCHAR(20)  NOT NULL UNIQUE,
  nik            CHAR(16)     NULL UNIQUE,
  nama_pasien    VARCHAR(100) NOT NULL,
  jenis_kelamin  ENUM('L','P') NOT NULL,
  tempat_lahir   VARCHAR(50)  NULL,
  tanggal_lahir  DATE         NOT NULL,
  alamat         TEXT         NULL,
  no_hp          VARCHAR(20)  NULL,
  no_kartu_asuransi VARCHAR(30) NULL,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_pasien_pengguna FOREIGN KEY (id_pengguna) REFERENCES pengguna(id_pengguna)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_pasien_asuransi FOREIGN KEY (id_asuransi) REFERENCES asuransi(id_asuransi)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 6. Kunjungan
CREATE TABLE kunjungan (
  id_kunjungan    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  no_kunjungan    VARCHAR(25)  NOT NULL UNIQUE,
  id_pasien       INT UNSIGNED NOT NULL,
  id_dokter       INT UNSIGNED NOT NULL,
  id_poli         INT UNSIGNED NOT NULL,
  tanggal_kunjungan DATE       NOT NULL,
  keluhan         TEXT         NULL,
  diagnosa        TEXT         NULL,
  cara_bayar      ENUM('umum','asuransi') NOT NULL DEFAULT 'umum',
  status          ENUM('menunggu','diperiksa','selesai','batal') NOT NULL DEFAULT 'menunggu',
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_kj_pasien FOREIGN KEY (id_pasien) REFERENCES pasien(id_pasien)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_kj_dokter FOREIGN KEY (id_dokter) REFERENCES dokter(id_dokter)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_kj_poli FOREIGN KEY (id_poli) REFERENCES poliklinik(id_poli)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 7. Token sesi API
CREATE TABLE api_token (
  id_token     BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_pengguna  INT UNSIGNED NOT NULL,
  token_hash   CHAR(64) NOT NULL UNIQUE,
  ip_address   VARCHAR(45) NULL,
  expires_at   DATETIME NOT NULL,
  revoked      TINYINT(1) NOT NULL DEFAULT 0,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_token_pengguna FOREIGN KEY (id_pengguna) REFERENCES pengguna(id_pengguna)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 8. Audit log
CREATE TABLE audit_log (
  id_log       BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_pengguna  INT UNSIGNED NULL,
  aksi         VARCHAR(50)  NOT NULL,
  modul        VARCHAR(50)  NULL,
  detail       TEXT NULL,
  ip_address   VARCHAR(45) NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_log_pengguna (id_pengguna),
  INDEX idx_log_waktu (created_at)
) ENGINE=InnoDB;

CREATE INDEX idx_kj_tanggal ON kunjungan (tanggal_kunjungan);
CREATE INDEX idx_kj_poli    ON kunjungan (id_poli);
CREATE INDEX idx_kj_dokter  ON kunjungan (id_dokter);

-- Data awal non-user
INSERT INTO poliklinik (kode_poli, nama_poli, lokasi) VALUES
 ('UMUM','Poli Umum','Gedung A Lt.1'),
 ('GIGI','Poli Gigi','Gedung A Lt.1'),
 ('ANAK','Poli Anak','Gedung B Lt.1'),
 ('JANT','Poli Jantung','Gedung B Lt.2'),
 ('MATA','Poli Mata','Gedung C Lt.1');

INSERT INTO asuransi (kode_asuransi, nama_asuransi, jenis, kontak) VALUES
 ('BPJS','BPJS Kesehatan','pemerintah','165'),
 ('PRUD','Prudential','swasta','1500085'),
 ('ALLZ','Allianz','swasta','1500136');