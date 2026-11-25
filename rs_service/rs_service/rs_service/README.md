# RS Service — Server B (Database & REST API Web Service)

Service Provider berbasis PHP 8 Native + MySQLi Prepared Statements untuk Sistem Informasi Rumah Sakit berbasis SOA.

## 1. Instalasi & Setup Lokal (XAMPP)
1. Letakkan folder ini di `C:\xampp\htdocs\rs_service`.
2. Salin `config/config.example.php` menjadi `config/config.php` dan sesuaikan kredensial MySQL serta `api_keys`.
3. Impor `sql/rs_database.sql` ke MySQL (`db_rumah_sakit`).
4. Jalankan seeder sekali via CLI:
   ```bash
   C:\xampp\php\php.exe sql/seed_users.php