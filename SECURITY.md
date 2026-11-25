# Kebijakan Keamanan (Security Policy) — CampusOS

Keamanan data akademik, jadwal, dan catatan keuangan pribadi mahasiswa merupakan prioritas utama dalam pengembangan **CampusOS**.

---

## 1. Arsitektur Perlindungan Keamanan Inti

CampusOS menerapkan pertahanan berlapis (*Defense in Depth*) pada setiap lapisan aplikasi PHP Native:

### A. Isolasi Data Multi-User (Anti-IDOR)
- Seluruh tabel operasional (`courses`, `class_schedules`, `events`, `tasks`, `transactions`, `budget_settings`, `planning_preferences`) terikat langsung ke `user_id` melalui Foreign Key `ON DELETE CASCADE`.
- Setiap operasi `SELECT`, `UPDATE`, dan `DELETE` pada seluruh Model di `app/models/` **wajib** menyertakan klausa `WHERE user_id = :user_id` menggunakan ID pengguna terotentikasi dari `currentUserId()` (`app/helpers/auth.php`).
- Mencegah serangan *Insecure Direct Object Reference* (IDOR) sehingga satu mahasiswa tidak dapat membaca atau memodifikasi data milik mahasiswa lain.

### B. Perlindungan SQL Injection (PDO Prepared Statements)
- Seluruh interaksi basis data menggunakan **PHP Data Objects (PDO)** secara eksklusif (`app/config/database.php`) dengan `PDO::ATTR_EMULATE_PREPARES => false` dan `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`.
- Seluruh input dari request (`$_GET`, `$_POST`) diikat menggunakan parameter berlabel (`:param`) via `$stmt->prepare()` dan `$stmt->execute([...])`.
- Ekstensi lawas `mysqli_*` dan konkatenasi string SQL mentah dilarang keras di seluruh repositori.

### C. Perlindungan Cross-Site Scripting (XSS)
- Seluruh output dinamis yang berasal dari input pengguna atau basis data wajib disanitasi sebelum dirender ke HTML menggunakan helper `e()` di `app/helpers/view.php`:
  ```php
  htmlspecialchars((string) ($string ?? ''), ENT_QUOTES, 'UTF-8')
  ```

### D. Perlindungan Session Hijacking & Session Fixation
- Manajemen sesi di `app/helpers/auth.php` (`startSessionSafe()`) mengaktifkan flag cookie aman:
  - `session.cookie_httponly = 1` (melindungi cookie sesi dari akses skrip JavaScript).
  - `session.use_only_cookies = 1` (mencegah pengiriman session ID melalui parameter URL).
- Saat proses login berhasil (`app/controllers/AuthController.php` dan `public/index.php`), aplikasi memanggil `session_regenerate_id(true)` untuk mencegah serangan *Session Fixation*.
- Kata sandi pengguna disimpan menggunakan algoritma hashing **Bcrypt** (`password_hash()`) dan diverifikasi dengan `password_verify()`.

### E. Kebersihan Kredensial & Eksekusi Sistem
- Kredensial basis data lokal wajib disimpan di `app/config/config.local.php` yang diabaikan oleh `.gitignore`.
- Penggunaan fungsi eksekusi kode atau shell dinamis berbahaya seperti `eval()` dan `shell_exec()` dilarang dan dipantau otomatis oleh GitHub Actions CI (`.github/workflows/ci.yml`).

---

## 2. Pelaporan Kerentanan (Reporting a Vulnerability)

Jika Anda menemukan celah keamanan, kebocoran kredensial, atau potensi IDOR/XSS/SQLi di repositori ini:
1. **Jangan** mempublikasikan detail eksploitasi secara terbuka di *Public Issue* sebelum perbaikan diterapkan.
2. Laporkan temuan secara privat kepada tim pengembang utama melalui repositori GitHub (`Adraii/CampusOS`) atau kontak akademik terkait dengan menyertakan:
   - Deskripsi kerentanan dan modul yang terdampak.
   - Langkah reproduksi (*Proof of Concept* minimal).
   - Dampak potensial dan saran mitigasi jika ada.
