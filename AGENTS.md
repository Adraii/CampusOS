# Panduan & Konteks AI Coding Assistant — CampusOS

Dokumen ini memuat konteks arsitektur, pembagian tanggung jawab kode, dan **7 Hard Guardrails (Aturan Mutlak)** yang wajib dipatuhi oleh setiap AI Assistant (Antigravity, Gemini, Copilot, dll.) saat membaca, merancang, memodifikasi, atau menghasilkan kode untuk repositori **CampusOS**.

---

## 1. Konteks Proyek & Arsitektur Utama

CampusOS dibangun dengan fondasi teknologi native yang bersih, terstruktur, dan modular tanpa framework berat:

- **Arsitektur Backend:** PHP 8.x Native dengan pola **Modular MVC + Service Layer**.
  - **Controller** menangani alur request, validasi input dasar, dan memanggil Service/Model.
  - **Service** menangani logika bisnis kompleks (misal: kalkulasi bentrok jadwal, kalkulasi Smart Planner, proyeksi keuangan).
  - **Model** murni berinteraksi dengan basis data melalui PDO.
- **Routing:** Query-based routing terpusat via Front Controller (`public/index.php?page=<modul>&action=<aksi>`).
- **Rendering:** Hybrid Server-Side Rendering (Hybrid SSR). View dirender di sisi server menggunakan PHP native template, diperkaya dengan Vanilla JS untuk interaksi dinamis (seperti modal pop-up, filter instan, atau fetch asynchronous).
- **Database Layer:** Akses data eksklusif menggunakan **PHP Data Objects (PDO)** dengan **Prepared Statements** berparameter.
- **Frontend Styling:** Vanilla CSS terstruktur dengan Design Tokens terpusat di `public/assets/css/variables.css`. Tidak menggunakan tailwind/bootstrap runtime berat.

---

## 2. Pembagian Wilayah Kerja Berkas (Directory Ownership)

Setiap bagian kode memiliki tempatnya masing-masing. Jangan mencampuradukkan logika backend ke dalam template antarmuka:

| Layer | Direktori Utama | Deskripsi & Tanggung Jawab |
|---|---|---|
| **Backend & Logic** | `app/`, `database/` | Logika bisnis, routing controller, model PDO, services, helper functions, serta skema basis data SQL. |
| **Frontend & UI** | `views/`, `public/assets/` | Berkas template antarmuka (`.php`), layout, partials/komponen, stylesheet CSS (`variables.css`, `style.css`), dan script Vanilla JS. |
| **Aturan & Konteks AI** | `.agents/`, `AGENTS.md` | Guardrails, panduan arsitektur, dan aturan pengerjaan kode. |
| **Dokumentasi Teknis**| `docs/`, `README.md` | Dokumentasi spesifikasi, desain sistem, dan alur setup proyek. |

### Pembagian Peran Anggota Tim:
- **Anggota 1 (Adam Rafiq Arrasyid):** *UI/UX & Frontend* (FigCraft + Antigravity, Design System, HTML/CSS/JS di `views/` dan `public/assets/`, responsivitas & interaktivitas antarmuka).
- **Anggota 2 (Fakhriy Mubarak):** *Backend & System Logic* (Database MySQL, Auth, Smart Planner Engine, Conflict Detection, Finance Calculation di `app/` dan `database/`).

---

## 3. 7 Hard Guardrails (Aturan Mutlak yang Dilarang Dilanggar)

Setiap AI Coding Assistant wajib memvalidasi kodenya terhadap 7 aturan ini sebelum menyajikan perubahan:

1. **Dilarang Query SQL Langsung di `views/` atau `public/index.php`**
   - Berkas view hanya boleh menerima data matang dari Controller. View tidak boleh meng-instansiasi koneksi database atau menjalankan `PDO::query` / `PDO::prepare`.
2. **Wajib Menggunakan PDO Prepared Statements & Dilarang Keras `mysqli_`**
   - Seluruh query database harus menggunakan PDO dengan prepared statements (`$stmt->prepare(...)` dan `$stmt->execute([...])`).
   - Dilarang keras melakukan interpolasi string atau konkatenasi langsung input user ke dalam string SQL (mencegah SQL Injection). Dilarang memakai ekstensi usang `mysqli_*`.
3. **Wajib Memfilter Kepemilikan Data (`WHERE user_id = :user_id`)**
   - Seluruh operasi `SELECT`, `UPDATE`, dan `DELETE` yang terkait data pengguna (mata kuliah, tugas, jadwal, transaksi keuangan, anggaran) WAJIB menyertakan klausul `WHERE user_id = :user_id` yang diambil dari session resmi pengguna. Mencegah kerentanan *Insecure Direct Object Reference* (IDOR) antarpengguna.
4. **Dilarang Mengubah `database/schema.sql` Tanpa Izin Eksplisit**
   - Struktur skema tabel DDL di `database/schema.sql` merupakan cetak biru baku. Jangan mengubah tipe data, menghapus kolom, atau merombak relasi tabel tanpa persetujuan eksplisit dari pengembang utama.
5. **Smart Planner Dilarang Menimpa Jadwal Tetap Secara Otomatis**
   - Algoritma Smart Planner hanya berfungsi memberikan **saran/rekomendasi** slot waktu pengerjaan tugas di waktu luang (*free time*).
   - Smart Planner dilarang memindahkan, menggeser, atau menimpa jadwal kuliah tetap, jadwal ujian, atau agenda pasti pengguna tanpa konfirmasi/persetujuan eksplisit user.
6. **Dilarang Menggunakan External AI API untuk Logika Inti Smart Planner**
   - Mesin Smart Planner harus bekerja secara deterministik berbasis aturan matematika/logika kode PHP murni (*rule-based engine* seperti pembobotan bobot deadline, durasi, dan waktu luang), bukan memanggil API OpenAI/Gemini/LLM eksternal. Hal ini menjaga transparansi hasil, kecepatan lokal, dan ketersediaan offline di lingkungan perkuliahan.
7. **Kredensial Lokal Wajib Disimpan di `app/config/config.local.php`**
   - Jangan pernah melakukan hardcode password database atau rahasia sensitif ke dalam repositori umum (`app/config/config.php` hanya memuat default/fallback). Kredensial lokal harus dibaca dari `config.local.php` yang telah diabaikan oleh `.gitignore`.

---

## 4. Referensi Aturan Spesifik

Untuk aturan implementasi teknis lebih detail, rujuk dokumen berikut:
- **Aturan Backend & Keamanan PHP:** [.agents/rules/backend-php.md](file:///.agents/rules/backend-php.md)
- **Aturan Frontend, Styling, & UI:** [.agents/rules/frontend-ui.md](file:///.agents/rules/frontend-ui.md)

