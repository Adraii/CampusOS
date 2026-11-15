# Panduan Setup Lingkungan & Alur Kerja Tim — CampusOS

> **Panduan Praktis:** Instalasi Lokal Laragon, Pembagian Wilayah Kerja Tim, dan Prosedur Kolaborasi Git.

---

## 1. Setup Lingkungan Lokal di Laragon

### Langkah 1: Posisikan Repositori
Pastikan folder repositori berada di dalam document root Laragon:
```text
C:\laragon\www\CampusOS
```
Jika belum ada, lakukan clone repositori:
```bash
cd C:\laragon\www
git clone https://github.com/Adraii/CampusOS.git
cd CampusOS
```

### Langkah 2: Buat Basis Data di MySQL Laragon
1. Buka aplikasi **Laragon**, lalu klik tombol **Start All** untuk menyalakan Apache dan MySQL.
2. Buka **phpMyAdmin** (`http://localhost/phpmyadmin`) atau tekan tombol **Database** di Laragon (HeidiSQL).
3. Buat database baru bernama:
   ```sql
   CREATE DATABASE `campusos_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

### Langkah 3: Impor Skema & Data Dummy
Impor berkas SQL secara berurutan:
1. Impor `database/schema.sql` (membuat 8 tabel struktur DDL).
2. Impor `database/seed.sql` (mengisi data demo user, jadwal kuliah, agenda, tugas, dan transaksi).

*Tip via Terminal Laragon:*
```bash
mysql -u root campusos_db < database/schema.sql
mysql -u root campusos_db < database/seed.sql
```

### Langkah 4: Buat Konfigurasi Kredensial Lokal
Buat berkas baru di `app/config/config.local.php` (berkas ini telah diabaikan oleh `.gitignore` sehingga aman dari commit):
```php
<?php
// app/config/config.local.php
return [
    'db' => [
        'host'     => 'localhost',
        'dbname'   => 'campusos_db',
        'username' => 'root',
        'password' => '', // Default password Laragon kosong
        'charset'  => 'utf8mb4',
    ],
    'app' => [
        'base_url' => 'http://localhost/CampusOS/public',
        'debug'    => true,
    ],
];
```

### Langkah 5: Jalankan & Uji Akses di Browser
Akses aplikasi melalui peramban web:
- Melalui URL path: `http://localhost/CampusOS/public/`
- Atau via Virtual Host Laragon: `http://campusos.test`
- Login menggunakan akun demo dari `seed.sql`:
  - **Email:** `mahasiswa@campusos.test`
  - **Password:** `password123`

---

## 2. Pembagian Wilayah Kerja Tim (Ownership Boundaries)

Untuk menghindari tumpang-tindih (*merge conflict*), tanggung jawab berkas dibagi secara tegas:

| Anggota Tim | Peran Utama | Wilayah Direktori Eksklusif | Tanggung Jawab Utama |
|---|---|---|---|
| **Anggota 1** (Adam Rafiq) | *Backend & System Logic* | `app/controllers/`<br>`app/models/`<br>`app/services/`<br>`app/helpers/`<br>`database/` | Arsitektur MVC, query PDO berparameter, mesin Smart Planner deterministik, formula irisan waktu `ConflictService`, dan kalkulasi `FinanceService`. |
| **Anggota 2** (Fakhriy Mubarak) | *UI/UX & Frontend* | `views/`<br>`public/assets/css/`<br>`public/assets/js/`<br>`public/assets/images/` | Slicing layout PHP Native (Hybrid SSR), penerapan CSS design tokens, responsivitas flex/grid, interaktivitas modal & filter, serta state tampilan kosong (*Empty State*). |

> **Catatan Kerja Bersama:**  
> Berkas `public/index.php` (routing utama) dan `README.md` dikelola bersama dengan komunikasi aktif sebelum pengubahan rute baru dilakukan.

---

## 3. Prosedur Harian Git & SOP Kolaborasi

### A. Sebelum Mulai Mengerjakan Fitur
Selalu tarik versi terbaru dari branch utama sebelum menulis kode baru:
```bash
git checkout main
git pull origin main
```

### B. Bekerja pada Cabang Fitur (*Feature Branch*)
Buat branch terpisah untuk setiap fitur yang dikerjakan:
```bash
# Format: feat/<nama-fitur> atau fix/<nama-perbaikan>
git checkout -b feat/academic-schedule-crud
```

### C. Konvensi Pesan Commit (Conventional Commits)
Gunakan format commit terstandarisasi:
- `feat: <penambahan fitur baru>` (misal: `feat: implementasi conflict detection pada jadwal kuliah`)
- `fix: <perbaikan bug>` (misal: `fix: perbaiki query filter user_id pada task model`)
- `style: <perubahan styling/css>` (misal: `style: perbarui badge prioritas dan margin kartu`)
- `refactor: <restrukturisasi kode tanpa mengubah fungsionalitas>`
- `docs: <pembaruan dokumentasi teknis>`
- `chore: <konfigurasi git/build/tooling>`

### D. Menggabungkan & Menyelesaikan Konflik
Ketika fitur selesai dan siap digabungkan kembali ke `main`:
```bash
git checkout main
git pull origin main
git merge feat/academic-schedule-crud
```
Jika terjadi konflik pada berkas:
1. Buka berkas yang berkonflik di editor (tandai baris `<<<<<<< HEAD` dan `>>>>>>>`).
2. Diskusikan dengan rekan tim mengenai versi baris mana yang harus dipertahankan.
3. Simpan berkas yang telah diperbaiki, lalu jalankan:
   ```bash
   git add <nama-file>
   git commit -m "merge: resolve conflict on <nama-file>"
   git push origin main
   ```

### E. Standar Selesai Kerja (SOP Safe Git Sync)
Sebelum meninggalkan sesi kerja, pastikan:
1. `git status` menampilkan status `nothing to commit, working tree clean`.
2. Seluruh perubahan sudah ter-push ke remote `origin main`.
3. Kredensial lokal `app/config/config.local.php` **TIDAK** pernah masuk ke dalam repositori publik.

