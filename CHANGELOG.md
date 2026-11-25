# Changelog — CampusOS

Seluruh perubahan penting pada proyek **CampusOS** didokumentasikan pada berkas ini.
Format mengacu pada [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) dan mengikuti [Semantic Versioning](https://semver.org/).

---

## [1.0.0] - 2026-11-26

Rilis perdana MVP **CampusOS — Web-Based Student Assistant** yang mengintegrasikan manajemen akademik, penjadwalan cerdas deterministik, kalender terpadu, dan pengelolaan keuangan pribadi mahasiswa dalam satu antarmuka Bento Grid modern.

### Added
- **Arsitektur & Antarmuka Bento Grid Hybrid SSR:**
  - Tata letak 2-region responsif (Sidebar navigasi + Main Bento Canvas) di `views/layouts/main.php`, `public/assets/css/layout.css`, dan `public/assets/css/components.css`.
  - Desain *Technical High-Contrast Dark Mode* (`#0B0D0E`, `#111317`, `#16191E`, aksen `#C8F538` & `#FB923C`) yang disinkronkan dari kanvas FigCraft (`docs/CampusOS-Dashboard.figcraft`).
- **Dual Theme System (Dark & Light Mode):**
  - Token tema ganda di `public/assets/css/tokens.css` (`[data-theme="dark"]` & `[data-theme="light"]`).
  - Tombol pengalih tema `#themeToggleBtn` dengan animasi *Circular Reveal* berbasis native View Transitions API (`document.startViewTransition`) dan skrip anti-flicker di `<head>`.
- **Autentikasi & Keamanan Multi-User:**
  - Login/Logout berbasis sesi PHP (`AuthController`, `app/helpers/auth.php`) dengan `HttpOnly` cookie, `session_regenerate_id(true)`, verifikasi Bcrypt (`password_verify`), serta akun demo *fallback*.
  - Isolasi data penuh (`WHERE user_id = :user_id`) dan PDO Prepared Statements di seluruh Model.
- **Manajemen Akademik & Deteksi Bentrok (`AcademicController`, `ConflictService`):**
  - CRUD katalog mata kuliah (kode MK, nama, dosen, SKS, warna label) dan matriks jadwal kuliah mingguan (Senin–Minggu) di `views/academic/index.php`.
  - Validasi irisan waktu matematis otomatis dengan toleransi batas tepi (*boundary adjacency*).
- **Kalender Kegiatan Terpadu (`CalendarController`):**
  - Matriks kalender bulanan 7-kolom dengan indikator visual jadwal kuliah tetap, agenda kegiatan, dan tenggat tugas di `views/calendar/index.php`.
  - Panel detail agenda harian dan validasi anti-bentrok saat menambah/mengubah acara.
- **Manajemen Tugas & Smart Planner Deterministik (`TaskController`, `SmartPlannerService`):**
  - CRUD tugas kuliah, filter status/prioritas/pencarian, quick status toggle, dan timeline penugasan di `views/tasks/index.php`.
  - Mesin rekomendasi belajar berbasis rumus Skor Urgensi ($U$), pemetaan celah waktu luang (*free time gaps*) tanpa menimpa jadwal kuliah tetap, kotak alasan transparan (*reason box*), dan persetujuan satu klik ke kalender di `views/planner/index.php`.
- **Keuangan Pribadi & Proyeksi Burn Rate (`FinanceController`, `FinanceService`):**
  - Pencatatan transaksi pemasukan/pengeluaran, distribusi kategori, pengaturan limit anggaran bulanan + ambang peringatan, kalkulasi *daily burn rate*, serta proyeksi pengeluaran akhir bulan di `views/finance/index.php`.
- **Standarisasi Repositori & CI/CD:**
  - GitHub Actions CI pipeline (`.github/workflows/ci.yml`) untuk PHP Syntax Linter, pengecekan keamanan/hygiene, dan verifikasi skema.
  - Template Pull Request & Issue (`.github/`), `CONTRIBUTING.md`, `SECURITY.md`, dan skill `qa-tester`.

### Fixed
- Penyelarasan nama berkas `app/models/CourseModel.php` (PascalCase) agar kompatibel 100% di sistem file *case-sensitive* Linux/CI maupun Windows.
- Perbaikan latar belakang topbar header di Light Mode agar mengikuti CSS variable `var(--bg-sidebar)`.
- Penambahan dukungan fallback parameter rute `$_POST['page']` / `$_POST['action']` di `public/index.php` dan hidden inputs pada seluruh modal form untuk keandalan submit POST.
