# Aturan Pengembangan Backend PHP — CampusOS

Dokumen ini berisi standar kode, tata kelola keamanan, pemisahan arsitektur MVC + Service, dan kontrak data untuk pengembang backend dan AI Assistant di proyek **CampusOS**.

---

## 1. Standar Keamanan Mutlak (Security Standards)

### A. Session Guard & Autentikasi
- Seluruh endpoint/halaman yang memerlukan login wajib memanggil helper autentikasi di awal Controller:
  ```php
  require_auth(); // Memeriksa keberadaan $_SESSION['user_id'], redirect ke login jika belum login
  ```
- Ambil ID pengguna aktif hanya dari session yang tervalidasi:
  ```php
  $userId = $_SESSION['user_id'] ?? null;
  ```
- Lakukan regenerasi session ID saat login sukses (`session_regenerate_id(true)`) untuk mencegah serangan *session fixation*.

### B. Hashing & Verifikasi Password
- Gunakan algoritma hashing modern bawaan PHP:
  ```php
  $hashedPassword = password_hash($rawPassword, PASSWORD_DEFAULT);
  ```
- Verifikasi password login menggunakan:
  ```php
  if (password_verify($inputPassword, $user['password_hash'])) {
      // Login valid
  }
  ```
- Dilarang keras menggunakan algoritma lemah seperti MD5 atau SHA1 untuk password.

### C. Pencegahan Cross-Site Scripting (XSS)
- Setiap data string dinamis dari user atau database yang dicetak ke dalam HTML WAJIB disanitasi menggunakan `htmlspecialchars`:
  ```php
  <?= htmlspecialchars($task['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>
  ```
- Sediakan atau gunakan helper fungsi global `e($string)` untuk mempersingkat penulisan sanitasi di berkas view.

### D. Manajemen Database & Try-Catch Exception
- Seluruh interaksi PDO yang berpotensi gagal wajib dibungkus dalam blok `try-catch` (`\PDOException`):
  ```php
  try {
      $stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id = :user_id");
      $stmt->execute([':user_id' => $userId]);
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (\PDOException $e) {
      error_log("Database Error: " . $e->getMessage());
      return [];
  }
  ```
- Jangan pernah menampilkan pesan error SQL mentah (`$e->getMessage()`) langsung ke layar pengguna akhir dalam mode produksi.

---

## 2. Pemisahan Tanggung Jawab MVC + Services

Arsitektur aplikasi membagi tanggung jawab secara tegas:

```
[HTTP Request] -> [Front Controller: public/index.php]
                         ↓
               [App Controller]
                ↙             ↘
   [Service Layer (Business)]   [Model Layer (Data Access)]
                ↘             ↙
               [Controller Aggregation]
                         ↓
             [Render View (Hybrid SSR)]
```

### A. Controllers (`app/controllers/`)
- Menerima dan memvalidasi payload request (`$_GET`, `$_POST`).
- Memanggil Service terkait untuk komputasi atau Model untuk pengambilan data.
- Mengirimkan variabel yang sudah siap pakai ke View.
- Dilarang menulis kalkulasi algoritma bisnis yang panjang atau query SQL langsung di dalam Controller.

### B. Service Layer (`app/services/`)
Logika bisnis spesifik dan komputasi kompleks dipusatkan di dalam Service class:
- **`ConflictService`:**
  - Bertanggung jawab mendeteksi bentrok jadwal kuliah dan kegiatan agenda.
  - Membandingkan slot waktu (hari, jam mulai, jam selesai) sebelum kegiatan baru disimpan.
- **`SmartPlannerService`:**
  - Menghitung skor prioritas pengerjaan tugas berdasarkan rumus bobot (urgensi deadline, durasi estimasi, tingkat kesulitan).
  - Mengidentifikasi slot waktu luang (*free time gaps*) pengguna di antara jadwal tetap.
  - Memetakan rekomendasi pengerjaan tugas secara deterministik tanpa mengubah jadwal tetap.
- **`FinanceService`:**
  - Menghitung akumulasi pemasukan vs pengeluaran.
  - Menghitung persentase pemakaian anggaran per kategori dan proyeksi sisa anggaran hingga akhir periode.

### C. Models (`app/models/`)
- Berisi fungsi-fungsi CRUD basis data dengan PDO prepared statements.
- Setiap method wajib menerima parameter yang terikat eksplisit dan selalu memfilter `user_id`.

---

## 3. Kontrak Variabel ke View (Data Contract to Views)

Untuk mencegah error `Undefined variable` atau `TypeError: foreach argument must be of type array`, Controller wajib memenuhi kontrak:

1. **Inisialisasi Nilai Default Array:**
   - Variabel daftar/koleksi data yang dikirim ke View harus selalu bernilai `array`, bukan `null` atau `false` ketika data kosong atau terjadi kegagalan query.
   ```php
   $tasks = $taskModel->getByUserId($userId) ?: [];
   $recommendations = $smartPlannerService->generateSchedule($userId) ?: [];
   ```

2. **Ekstraksi Data Terstruktur:**
   - Saat me-render View, sertakan variabel dalam bentuk array asosiatif terdefinisi:
   ```php
   $data = [
       'pageTitle'       => 'Daftar Tugas Mahasiswa',
       'tasks'           => $tasks,
       'conflicts'       => $conflicts ?: [],
       'user'            => $currentUser ?: [],
       'filters'         => $appliedFilters ?: [],
   ];
   render_view('tasks/index', $data);
   ```

3. **Status Notifikasi (Flash Messages):**
   - Pesan sukses/gagal dikirimkan melalui session flash (`$_SESSION['flash_success']`, `$_SESSION['flash_error']`) dan dibersihkan segera setelah dibaca oleh layout view.

