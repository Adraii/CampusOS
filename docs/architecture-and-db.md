# Arsitektur Sistem & Desain Basis Data — CampusOS

> **Dokumen Teknis:** Arsitektur Perangkat Lunak, Relasi Basis Data, dan Logika Inti Layanan.  
> **Platform:** PHP 8.x Native (Modular MVC + Service Pattern) & MySQL/MariaDB (PDO).

---

## 1. Arsitektur Perangkat Lunak (Hybrid SSR Pattern)

CampusOS menggunakan pola arsitektur **Modular Model-View-Controller (MVC) yang diperkaya dengan Service Layer**. Pola ini memisahkan secara tegas antara alur request HTTP, logika bisnis komputasional, dan representasi antarmuka.

```
                           +------------------------+
                           |     Browser Client     |
                           +------------------------+
                                       |
                                HTTP Request
                  (GET/POST public/index.php?page=...&action=...)
                                       |
                                       v
                     +------------------------------------+
                     | Front Controller (public/index.php)|
                     |  - Global Init (Config & Session)  |
                     |  - Route Dispatching (by ?page=)   |
                     +------------------------------------+
                                       |
                                       v
                         +--------------------------+
                         |       Controllers        |
                         |  (Request validation,    |
                         |   Auth guard, flow ctrl) |
                         +--------------------------+
                               /              \
                              /                \
                             v                  v
                 +----------------------+   +----------------------+
                 |    Service Layer     |   |     Model Layer      |
                 | - ConflictService    |   | - UserModel          |
                 | - SmartPlannerService|   | - CourseModel        |
                 | - FinanceService     |   | - TaskModel, etc.    |
                 | (Pure business math) |   | (PDO Prepared Stmts) |
                 +----------------------+   +----------------------+
                             \                  /
                              \                /
                               v              v
                         +--------------------------+
                         |    Aggregated Data Array |
                         +--------------------------+
                                       |
                                       v
                         +--------------------------+
                         |     View Engine (SSR)    |
                         | - views/layouts/main.php |
                         | - views/<module>/index   |
                         | - views/partials/        |
                         +--------------------------+
                                       |
                                  HTML Output
                                       v
                           +------------------------+
                           |     Browser Client     |
                           +------------------------+
```

### Karakteristik Utama Arsitektur:
1. **Front Controller Routing:** Seluruh request bermuara pada `public/index.php`. Parameter query `page` menentukan controller yang dieksekusi, dan `action` menentukan aksi method di dalamnya.
2. **Service Layer Terisolasi:** Logika rumit seperti perhitungan algoritma prioritas tugas dan deteksi konflik jadwal diisolasi ke dalam class mandiri di `app/services/`.
3. **Hybrid SSR:** Halaman dirender di sisi server menggunakan template PHP asli, namun dapat diperkaya dengan Vanilla JS untuk filter instan, validasi interaktif, dan modal pop-up.

---

## 2. Struktur & Relasi Basis Data (8 Tabel)

Seluruh tabel menggunakan engine **InnoDB** dengan relasi integritas referensial dan klausul `ON DELETE CASCADE` yang terikat pada akun mahasiswa (`user_id`).

```
 +--------------------+       +--------------------+       +--------------------+
 |       users        | 1───* |      courses       | 1───* |  class_schedules   |
 +--------------------+       +--------------------+       +--------------------+
 | id (PK)            |       | id (PK)            |       | id (PK)            |
 | name               |       | user_id (FK)       |       | user_id (FK)       |
 | email              |       | course_code        |       | course_id (FK)     |
 | password_hash      |       | course_name        |       | day_of_week        |
 | semester           |       | credits, color_hex |       | start_time         |
 | created_at         |       +--------------------+       | end_time, room     |
 +--------------------+                  | 1               +--------------------+
       | 1                               |
       |                                 | *
       ├──────────────* +--------------------+
       |                |       tasks        |
       |                +--------------------+
       |                | id (PK)            |
       |                | user_id (FK)       |
       |                | course_id (FK NULL)|
       |                | title, description |
       |                | deadline_date/time |
       |                | estimated_hours    |
       |                | priority, status   |
       |                +--------------------+
       |
       ├──────────────* +--------------------+
       |                |       events       |
       |                +--------------------+
       |                | id (PK)            |
       |                | user_id (FK)       |
       |                | title, category    |
       |                | event_date         |
       |                | start_time/end_time|
       |                +--------------------+
       |
       ├──────────────* +--------------------+
       |                |    transactions    |
       |                +--------------------+
       |                | id (PK)            |
       |                | user_id (FK)       |
       |                | type, category     |
       |                | amount, desc       |
       |                | transaction_date   |
       |                +--------------------+
       |
       ├──────────────1 +--------------------+
       |                |  budget_settings   |
       |                +--------------------+
       |                | id (PK)            |
       |                | user_id (FK UNIQUE)|
       |                | monthly_budget     |
       |                | period_start_day   |
       |                +--------------------+
       |
       └──────────────1 +--------------------+
                        |planning_preferences|
                        +--------------------+
                        | id (PK)            |
                        | user_id (FK UNIQUE)|
                        | study_start_hour   |
                        | study_end_hour     |
                        | max_daily_study_hrs|
                        +--------------------+
```

### Kamus Data Singkat 8 Tabel:
1. `users`: Menyimpan kredensial otentikasi akun mahasiswa.
2. `courses`: Katalog mata kuliah aktif per semester mahasiswa.
3. `class_schedules`: Jadwal perkuliahan mingguan tetap (`day_of_week` 1-7).
4. `events`: Agenda non-akademik / fleksibel pada tanggal tertentu (`event_date`).
5. `tasks`: Daftar tugas kuliah dengan bobot deadline, durasi, dan status.
6. `transactions`: Rekam jejak arus kas pengeluaran dan pemasukan harian.
7. `budget_settings`: Konfigurasi target limit anggaran bulanan per user.
8. `planning_preferences`: Konfigurasi rentang jam belajar aktif untuk mesin Smart Planner.

---

## 3. Rumus Logika Inti Layanan (Core Business Rules)

### A. ConflictService (Deteksi Bentrok Jadwal)
Layanan ini mendeteksi apakah kegiatan baru bersinggungan dengan jadwal kuliah mingguan atau agenda lain.
- **Konversi Tanggal ke Hari:** Ambil hari dari tanggal agenda target menggunakan format `day_of_week` (1 = Senin s/d 7 = Minggu).
- **Rumus Irisan Waktu (Time Overlap):**
  Dua kegiatan (Rentang A: $S_A \to E_A$ dan Rentang B: $S_B \to E_B$) dinyatakan **bentrok** jika dan hanya jika:
  $$\text{IsConflict} = (S_A < E_B) \land (E_A > S_B)$$
- **Penanganan:** Jika ditemukan irisan waktu dengan jadwal kuliah atau agenda yang sudah ada, kembalikan status `has_conflict = true` beserta daftar kegiatan yang bertabrakan agar user dapat mengonfirmasi.

---

### B. SmartPlannerService (Rule-Based Recommendation Engine)
Layanan ini memberikan rekomendasi slot belajar terbaik secara deterministik tanpa menimpa jadwal kuliah tetap.

1. **Perhitungan Skor Urgensi Tugas ($U$):**
   Untuk setiap tugas yang berstatus bukan `done`:
   - Hitung sisa waktu hingga deadline dalam satuan jam ($T_{\text{remaining}}$).
   - Tentukan pengali prioritas ($W_p$):
     - `high` $\to 1.5$
     - `medium` $\to 1.2$
     - `low` $\to 1.0$
   - Hitung Skor Urgensi:
     $$U = \left( \frac{\text{estimated\_hours}}{\max(T_{\text{remaining}}, 1)} \right) \times W_p \times 100$$
   - Tugas dengan nilai $U$ tertinggi akan diprioritaskan terlebih dahulu.

2. **Pemetaan Slot Waktu Luang (Free Slot Search):**
   - Ambil batas jam belajar dari `planning_preferences` (misal 08:00 s/d 21:00).
   - Kumpulkan seluruh jadwal kuliah dan event pada hari yang dievaluasi.
   - Buat interval waktu bebas di antara jadwal-jadwal tersebut.
   - Saring slot bebas yang memiliki durasi $\ge$ `preferred_session_hours` (misal minimal 1-2 jam).
   - Pastikan total jam belajar yang dialokasikan dalam 1 hari tidak melebihi `max_daily_study_hours`.

3. **Output Transparan (`reason`):**
   Setiap rekomendasi wajib menyertakan alasan deskriptif, contoh:
   > *"Tugas 'Proyek Mini Web' diprioritaskan karena deadline tersisa 48 jam dengan estimasi pengerjaan 6 jam (Prioritas: High). Slot pukul 14:00 - 16:00 dipilih karena tidak ada jadwal kuliah dan sesuai jam belajar preferensimu."*

---

### C. FinanceService (Kalkulasi Kesehatan Anggaran)
Layanan ini memantau arus kas dan memproyeksikan sisa saldo hingga akhir periode:
1. **Total Pengeluaran Bulan Berjalan ($E_{\text{total}}$):**
   $$E_{\text{total}} = \sum \text{amount (expense)}$$
2. **Sisa Anggaran ($B_{\text{remaining}}$):**
   $$B_{\text{remaining}} = \text{monthly\_budget} - E_{\text{total}}$$
3. **Rata-Rata Pengeluaran Harian ($E_{\text{daily}}$):**
   $$E_{\text{daily}} = \frac{E_{\text{total}}}{\text{hari yang telah berlalu dalam periode}}$$
4. **Proyeksi Pengeluaran Akhir Periode ($E_{\text{projected}}$):**
   $$E_{\text{projected}} = E_{\text{total}} + (E_{\text{daily}} \times \text{sisa hari periode})$$
5. **Status Kesehatan Anggaran:**
   - **Aman (Hijau):** Rasio $E_{\text{total}} / \text{monthly\_budget} \le 70\%$
   - **Waspada (Kuning):** Rasio $70\% < \text{rasio} \le 90\%$
   - **Bahaya (Merah):** Rasio $> 90\%$ atau melebihi anggaran limit.

---

## 4. Kontrak Variabel Standar (Controller to View)

Setiap Controller wajib menyediakan array data terdefinisi untuk dikirim ke template view. Berikut adalah kontrak variabel utama pada modul Dashboard dan Planner:

```php
// Contoh payload data yang dikirim Controller ke View Dashboard
$viewData = [
    'pageTitle'              => 'Dashboard Overview',
    'currentUser'            => [
        'id'       => (int) $user['id'],
        'name'     => (string) $user['name'],
        'semester' => (int) $user['semester'],
    ],
    'todaySchedules'         => (array) $todaySchedules,         // Array jadwal kuliah hari ini
    'todayEvents'            => (array) $todayEvents,            // Array agenda kegiatan hari ini
    'urgentTasks'            => (array) $urgentTasks,            // 3-5 tugas dengan skor urgensi tertinggi
    'plannerRecommendations' => (array) $plannerRecommendations, // Rekomendasi slot belajar
    'walletSummary'          => [
        'monthlyBudget'   => (float) $budget,
        'totalExpense'    => (float) $totalExpense,
        'remainingBudget' => (float) $remainingBudget,
        'percentUsed'     => (float) $percentUsed,
        'statusBadge'     => (string) $statusBadge,              // 'safe', 'warning', 'danger'
    ],
    'flashMessage'           => [
        'type'    => $_SESSION['flash_type'] ?? null,
        'message' => $_SESSION['flash_message'] ?? null,
    ]
];
```
Semua variabel koleksi wajib bernilai array kosong `[]` jika tidak ada rekaman data, sehingga View dapat langsung menggunakan helper `empty($todaySchedules)` untuk memicu *Empty State*.

