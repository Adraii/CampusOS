---
name: scaffold-module
description: SOP pembuatan modul fitur baru (Model, Controller, View) pada arsitektur Hybrid SSR PHP Native CampusOS.
---
# SOP Pembuatan Modul Baru (Scaffold Module)
Saat diminta membuat fitur atau modul baru (misal: Academic, Tasks, Calendar, Finance):
1. **Cek Skema Database:** Baca `database/schema.sql` terlebih dahulu. Jangan mengarang nama tabel atau kolom yang tidak ada di skema.
2. **Buat/Update Model (`app/models/<Entity>Model.php`):**
   - Gunakan koneksi PDO dari `app/config/database.php`.
   - Wajib gunakan prepared statements (`prepare()` & `execute()`).
   - Wajib sertakan filter `WHERE user_id = :user_id` pada setiap query pembacaan/pengubahan data pengguna.
3. **Buat/Update Controller (`app/controllers/<Module>Controller.php`):**
   - Validasi dan sanitasi input `$_POST` / `$_GET`.
   - Panggil Model atau Service terkait.
   - Siapkan variabel dengan nilai default (misal array kosong `[]`) sebelum memuat file di `views/`.
4. **Buat/Update View (`views/<module>/index.php`):**
   - Gunakan layout dari `views/layouts/`.
   - Escape seluruh output dengan `htmlspecialchars($val, ENT_QUOTES, 'UTF-8')`.
   - Sediakan tampilan Empty State jika data kosong.
5. **Daftarkan Route:** Tambahkan pemetaan halaman hanya pada switch/daftar rute di `public/index.php?page=<module>`. Jangan membuat folder `routes/` baru.
