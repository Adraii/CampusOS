## Ringkasan Perubahan
<!-- Jelaskan secara singkat apa yang diubah dan mengapa perubahan ini diperlukan -->

## Jenis Perubahan
Pilih jenis perubahan yang sesuai dengan memberi tanda `[x]`:
- [ ] `feat`: Penambahan fitur baru
- [ ] `fix`: Perbaikan bug atau masalah
- [ ] `ui`: Perubahan antarmuka (CSS/HTML/JS/Dual Theme)
- [ ] `docs`: Pembaruan dokumentasi
- [ ] `refactor`: Restrukturisasi kode tanpa mengubah perilaku fungsional
- [ ] `chore`: Konfigurasi tooling, CI/CD, atau pemeliharaan repo

## Modul yang Terdampak
- [ ] `Frontend/Views` (`views/`, `public/assets/`)
- [ ] `Backend/Controllers` (`app/controllers/`, `public/index.php`)
- [ ] `Models` (`app/models/`)
- [ ] `Services` (`app/services/`)
- [ ] `Database` (`database/`)

## Checklist QA & Keamanan (Wajib Diisi)
- [ ] Tampilan diuji pada **Dark Mode** dan **Light Mode** (tidak ada kontras rusak atau warna hardcoded).
- [ ] Seluruh query database menggunakan **PDO Prepared Statements** (`prepare()` + `execute()`), tanpa `mysqli_*`.
- [ ] Seluruh operasi data pengguna (`SELECT`, `UPDATE`, `DELETE`) menyertakan isolasi `WHERE user_id = :user_id`.
- [ ] Tidak ada syntax error PHP (`php -l` lulus untuk seluruh berkas terkait).
- [ ] Kredensial lokal `app/config/config.local.php` tidak ikut ter-commit.
- [ ] Smart Planner tidak menimpa jadwal kuliah tetap secara otomatis.
