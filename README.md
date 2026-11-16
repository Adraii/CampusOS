# CampusOS — Web-based Student Assistant

> **Judul Akademik:** Pengembangan Aplikasi Asisten Mahasiswa Berbasis Web dengan Integrasi Manajemen Akademik, Perencanaan Aktivitas, dan Pengelolaan Keuangan Pribadi.

**CampusOS** adalah aplikasi asisten produktivitas berbasis web yang dirancang khusus untuk mahasiswa. Aplikasi ini mengintegrasikan pengelolaan jadwal kuliah & agenda akademik, pelacakan tugas dengan prioritas, perencanaan waktu otomatis via **Smart Planner** (*rule-based deterministic engine*), serta pencatatan keuangan pribadi dalam satu ekosistem antarmuka terpadu.

---

## Fitur Utama (MVP)

1. **Dashboard Terpadu (Unified Overview):**
   - Ringkasan aktivitas dan kuliah hari ini.
   - Daftar tugas mendesak (*urgent tasks*).
   - Rekomendasi slot belajar dari Smart Planner.
   - Ringkasan cepat keuangan (saldo dan sisa anggaran periodik).

2. **Manajemen Akademik & Kalender:**
   - Pengelolaan data mata kuliah, dosen, ruang, dan jadwal mingguan berulang.
   - Kalender agenda fleksibel untuk kegiatan non-akademik maupun organisasi.
   - Deteksi otomatis konflik jadwal kegiatan (*conflict detection*).

3. **Manajemen Tugas & Prioritas:**
   - Pelacakan tenggat (*deadline*), estimasi waktu pengerjaan, dan tingkat kepentingan tugas.
   - Pengelompokan status pengerjaan (*to-do*, *in-progress*, *completed*).
   - Penilaian bobot urgensi tugas secara otomatis.

4. **Smart Planner (Rule-Based Engine):**
   - Algoritma deterministik transparan untuk menghitung skor prioritas pengerjaan tugas.
   - Pemetaan waktu luang (*free time slots*) mahasiswa di antara jadwal tetap.
   - Rekomendasi pengerjaan tugas tanpa pernah menimpa jadwal kuliah tetap secara sepihak.

5. **Pengelolaan Keuangan Pribadi:**
   - Pencatatan arus kas (pemasukan dan pengeluaran) per kategori.
   - Penetapan dan pemantauan anggaran (*budget tracking*) periodik (bulanan/mingguan).
   - Proyeksi sisa saldo akhir periode untuk menjaga kesehatan finansial mahasiswa.

---

## Tech Stack

- **Backend:** PHP 8.x Native (Modular MVC + Service Pattern, Hybrid SSR)
- **Database:** MySQL / MariaDB (Akses data eksklusif via PDO Prepared Statements)
- **Frontend:** HTML5, Modern CSS3 (Custom Design Tokens & CSS Variables), Vanilla JavaScript (tanpa framework berat)
- **Environment:** Laragon (Apache/Nginx & MySQL), Antigravity / Visual Studio Code, Git & GitHub

---

## Tim Pengembang & Pembimbing

Proyek ini dikembangkan dalam rangka pemenuhan tugas mata kuliah **Pemrograman Web**:

- **Adam Rafiq Arrasyid** — *UI/UX & Frontend* (FigCraft + Antigravity, Design System, Slicing HTML/CSS/JS di `views/` dan `public/assets/`, Hybrid Dashboard UI, Responsivitas & Interaktivitas)
- **Fakhriy Mubarak** — *Backend & System Logic* (Database MySQL, Auth, Smart Planner Engine, Conflict Detection, Finance Calculation di `app/` dan `database/`)

**Dosen Pengampu:**
- **Vera Irma Delianti, M.Pd.T.**

---

## Struktur Direktori

```text
CampusOS/
├── .agents/              # Konfigurasi, aturan, dan guardrails untuk AI Coding Assistant
│   └── rules/            # Aturan spesifik (backend-php.md, frontend-ui.md)
├── app/                  # Inti logika backend aplikasi
│   ├── config/           # Konfigurasi aplikasi & database (config.php, config.local.php)
│   ├── controllers/      # Controller penangan HTTP request & logika alur
│   ├── helpers/          # Fungsi bantuan utilitas global (format uang, tanggal, sanitasi)
│   ├── models/           # Model interaksi database via PDO
│   └── services/         # Service layer (SmartPlannerService, ConflictService, FinanceService)
├── database/             # Skema basis data (schema.sql) dan data awal (seed.sql)
├── docs/                 # Dokumentasi komprehensif proyek
│   ├── Gambar_UI.pdf     # Desain mockup antarmuka acuan dari dosen
│   ├── architecture-and-db.md
│   ├── design-system.md
│   ├── product-spec.md
│   └── setup-and-workflow.md
├── public/               # Document root web server
│   ├── assets/           # Berkas statis (css/, js/, images/)
│   └── index.php         # Front Controller utama (query-based routing)
├── views/                # Berkas template antarmuka pengguna (Hybrid SSR)
│   ├── layouts/          # Layout pembungkus (header, footer, sidebar)
│   ├── partials/         # Komponen UI modular (cards, modal, alerts)
│   └── ...               # Halaman spesifik per modul
├── .editorconfig         # Standar format penulisan kode antar IDE
├── .gitignore            # Daftar pengecualian pelacakan Git
├── AGENTS.md             # Panduan induk dan Hard Guardrails untuk AI assistant
└── README.md             # Dokumentasi utama proyek
```

---

## Panduan Menjalankan di Laragon

1. **Pastikan Web Server Siap:**
   - Pasang dan jalankan [Laragon](https://laragon.org/).
   - Pastikan service **Apache** dan **MySQL** telah berstatus *Running*.

2. **Letakkan Proyek:**
   - Clone atau posisikan repositori ini di dalam direktori root Laragon:
     `C:\laragon\www\CampusOS`

3. **Konfigurasi Database:**
   - Buka phpMyAdmin (`http://localhost/phpmyadmin`) atau HeidiSQL bawaan Laragon.
   - Buat database baru bernama `campusos`.
   - Impor skema tabel dari berkas `database/schema.sql` (dan `database/seed.sql` jika tersedia).

4. **Konfigurasi Lingkungan Lokal:**
   - Salin berkas konfigurasi sampel (jika ada) ke `app/config/config.local.php`.
   - Sesuaikan kredensial koneksi database lokal:
     ```php
     <?php
     return [
         'db_host' => 'localhost',
         'db_name' => 'campusos',
         'db_user' => 'root',
         'db_pass' => '',
     ];
     ```

5. **Akses Aplikasi:**
   - Buka browser dan arahkan ke alamat virtual host Laragon:
     `http://campusos.test` atau `http://localhost/CampusOS/public/`

---

## Dokumentasi Teknis Terkait

Untuk pemahaman arsitektur dan spesifikasi mendalam, silakan baca dokumentasi berikut pada direktori `docs/`:

1. [Product Specification (`docs/product-spec.md`)](docs/product-spec.md) — Definisi problem, profil pengguna, user stories, dan batasan ruang lingkup MVP.
2. [Architecture & Database Design (`docs/architecture-and-db.md`)](docs/architecture-and-db.md) — Arsitektur MVC + Service, query routing, kamus data, dan skema relasional database.
3. [Design System & UI Guidelines (`docs/design-system.md`)](docs/design-system.md) — Palet warna, tipografi, token CSS, komponen visual, dan tata letak responsif.
4. [Setup & Development Workflow (`docs/setup-and-workflow.md`)](docs/setup-and-workflow.md) — Panduan lingkungan Laragon, branching Git, standar commit, dan alur kontribusi tim.
5. [Desain Mockup UI Dosen (`docs/Gambar_UI.pdf`)](docs/Gambar_UI.pdf) — Dokumen acuan desain mockup antarmuka wajib dari dosen pengampu.
