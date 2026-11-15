# Spesifikasi Produk: CampusOS (Product Specification)

> **Judul Proyek:** Pengembangan Aplikasi Asisten Mahasiswa Berbasis Web dengan Integrasi Manajemen Akademik, Perencanaan Aktivitas, dan Pengelolaan Keuangan Pribadi.  
> **Versi Dokumen:** 1.0 (Fase MVP)  
> **Status:** Final Baseline

---

## 1. Ringkasan Eksekutif Produk

**CampusOS** adalah platform produktivitas all-in-one berbasis web yang dirancang khusus untuk memecahkan beban kognitif mahasiswa perguruan tinggi. Aplikasi ini menyatukan tiga pilar penting kehidupan perkuliahan:
1. **Manajemen Akademik & Waktu:** Jadwal kuliah mingguan dan agenda fleksibel.
2. **Manajemen Tugas & Rekomendasi Belajar:** Pelacakan tenggat waktu dan mesin **Smart Planner** berbasis *rule-based engine* deterministik.
3. **Pengelolaan Keuangan Pribadi:** Pemantauan arus kas harian dan kesehatan batas anggaran bulanan.

Dengan pendekatan **Hybrid Server-Side Rendering (Hybrid SSR)**, CampusOS menawarkan kecepatan akses lokal, keringanan resource, serta antarmuka *Productivity Dashboard* yang bersih dan modern.

---

## 2. Masalah Nyata yang Diselesaikan

Berdasarkan analisis kehidupan nyata mahasiswa, terdapat 3 kendala utama yang sering dihadapi:

| Kendala Mahasiswa | Dampak Nyata | Solusi di CampusOS |
|---|---|---|
| **Fragmentasi Informasi Jadwal** | Jadwal kuliah di portal akademik, agenda rapat di grup chat WhatsApp, dan catatan pribadi terpisah sehingga sering terjadi bentrok waktu. | Kalender terpadu dengan deteksi otomatis irisan waktu bentrok antara jadwal kuliah dan agenda organisasi/pribadi. |
| **Prokrastinasi & Deadline Blindness** | Tugas dikerjakan dengan sistem kebut semalam (*SKS*) karena tidak memperhitungkan estimasi durasi dan sisa jam luang. | **Smart Planner** yang menghitung skor urgensi dan merekomendasikan slot belajar di waktu luang tanpa menimpa jadwal kuliah tetap. |
| **Krisis Keuangan Akhir Bulan** | Mahasiswa kehabisan uang saku di pertengahan bulan akibat tidak ada kontrol pengeluaran harian dan proyeksi saldo. | Modul keuangan dengan penetapan batas anggaran periodik, rata-rata konsumsi harian, dan peringatan dini batas anggaran. |

---

## 3. Prinsip Desain Produk (Product Principles)

1. **Terintegrasi dalam Satu Layar (Unified Overview):**
   - Mahasiswa tidak perlu berpindah banyak aplikasi untuk mengetahui apa yang harus dihadiri hari ini, tugas apa yang mendesak, dan berapa sisa uang saku yang aman dibelanjakan.
2. **Transparan & Deterministik (No Black-Box AI):**
   - Rekomendasi Smart Planner bekerja menggunakan rumus matematika dan pembobotan yang jelas. Setiap rekomendasi selalu disertai alasan (*reason*) transparan mengapa slot waktu tersebut dipilih.
3. **Pengguna Memegang Kendali Penuh (User in Full Control):**
   - Smart Planner **tidak boleh** memindahkan atau menimpa jadwal kuliah tetap atau agenda yang sudah dibuat pengguna secara sepihak. Rekomendasi bersifat sebagai saran pendukung keputusan.
4. **Ringan & Bebas Dependensi Eksternal:**
   - Beroperasi penuh secara offline di lingkungan lokal (Laragon) tanpa bergantung pada layanan cloud berbayar atau LLM API eksternal.

---

## 4. Ruang Lingkup Fitur (MVP vs Peningkatan Masa Depan)

```text
┌─────────────────────────────────────────────────────────────────┐
│                    RUANG LINGKUP FITUR CAMPUSOS                 │
├────────────────────────────────┬────────────────────────────────┤
│           FASE 1: MVP          │     FASE 2: MASA DEPAN         │
├────────────────────────────────┼────────────────────────────────┤
│ • Autentikasi Pengguna         │ • Notifikasi Web Push / WA Bot │
│ • Dashboard Terpadu            │ • Sinkronisasi Google Calendar │
│ • CRUD Mata Kuliah & Jadwal    │ • Export Laporan PDF/Excel     │
│ • Kalender Agenda & Deteksi    │ • Mode Grup Proyek Mahasiswa   │
│   Bentrok (Conflict Detection) │ • Upload Bukti Struk Transaksi │
│ • Manajemen Tugas & Status     │ • Analisis Prediksi IPK        │
│ • Smart Planner Rule-Based     │ • Dark Mode Otomatis           │
│ • Pencatatan Arus Kas Finansial│                                │
│ • Pengaturan Limit Anggaran    │                                │
└────────────────────────────────┴────────────────────────────────┘
```

---

## 5. Alur Pengguna Utama (Core User Flows)

### Alur 1: Autentikasi & Setup Awal
```
[User Baru] ──> Halaman Register ──> Login ──> Dashboard Kosong
                                                   │
                                                   ▼
                                         Panduan Setup:
                                         1. Tambah Mata Kuliah & Jadwal
                                         2. Atur Preferensi Jam Belajar
                                         3. Atur Batas Anggaran Bulanan
```

### Alur 2: Manajemen Akademik & Deteksi Bentrok
```
[User] ──> Input Agenda Baru (Tanggal & Jam)
             │
             ├──> ConflictService Mengecek Irisan Waktu dengan Jadwal Kuliah
             │       │
             │       ├── [Bentrok Terdeteksi] ──> Tampilkan Peringatan Kuning & Konfirmasi User
             │       └── [Jadwal Bersih]      ──> Simpan Langsung ke Database
             │
             └──> Jadwal Muncul di Kalender & Widget Dashboard Hari Ini
```

### Alur 3: Manajemen Tugas & Rekomendasi Smart Planner
```
[User] ──> Input Tugas Baru (Mata Kuliah, Deadline, Estimasi Jam, Prioritas)
             │
             ├──> Data Tersimpan di Tabel `tasks`
             │
             ├──> Buka Halaman Smart Planner:
             │       1. SmartPlannerService menghitung skor urgensi setiap tugas belum selesai.
             │       2. Menemukan celah waktu luang (free slots) pada jam belajar aktif.
             │       3. Menghasilkan rekomendasi slot pengerjaan + alasan transparan.
             │
             └──> User meninjau dan menerima rekomendasi untuk sesi belajar hari ini.
```

### Alur 4: Pencatatan Keuangan & Pemantauan Anggaran
```
[User] ──> Catat Transaksi Pengeluaran / Pemasukan
             │
             ├──> FinanceService menghitung total pengeluaran bulan berjalan
             ├──> Membandingkan dengan `monthly_budget` di `budget_settings`
             ├──> Menghitung persentase terpakai & sisa hari periode
             │
             └──> Menampilkan indikator kesehatan saldo di Dashboard:
                     - Hijau  : Pengeluaran < 70% batas periode
                     - Kuning : Pengeluaran 70% - 90% batas periode
                     - Merah  : Pengeluaran > 90% (Warning batas anggaran terlampaui)
```
