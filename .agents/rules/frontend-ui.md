# Aturan Pengembangan Frontend & UI — CampusOS

Dokumen ini memuat standar desain visual, tata kelola CSS tokens, komponen antarmuka, dan struktur template untuk pengembang frontend dan AI Assistant di repositori **CampusOS**.

---

## 1. Gaya Visual: Hybrid Productivity Dashboard

Antarmuka CampusOS dirancang dengan gaya **Modern Productivity Workspace** (bersih, fungsional, kontras nyaman, berfokus pada efisiensi informasi mahasiswa):

- **Struktur Antarmuka:**
  - Sidebar navigasi tetap/responsif di sebelah kiri.
  - Topbar untuk profil pengguna, pencarian cepat, dan notifikasi.
  - Main content area dengan sistem kartu (*card-based layout*) untuk setiap widget informasi.
- **Hierarki Informasi:**
  - Informasi prioritas tinggi (kuliah terdekat, deadline hari ini, peringatan anggaran) ditempatkan di posisi paling atas dan mencolok.
- **Micro-interactions:**
  - Transisi halus (150ms - 200ms) untuk hover button, pergantian status tugas, dan pembukaan dialog modal.

---

## 2. Penggunaan Wajib CSS Variables (`public/assets/css/variables.css`)

Dilarang keras melakukan *hardcode* nilai warna hex, ukuran spasi, atau radius secara acak di file styling. Semua nilai harus merujuk ke CSS Custom Properties yang terdefinisi di `public/assets/css/variables.css`:

```css
/* Contoh konsumsi token yang benar */
.card-task {
  background-color: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  padding: var(--space-4);
  box-shadow: var(--shadow-sm);
}

.badge-urgent {
  background-color: var(--color-danger-subtle);
  color: var(--color-danger);
  font-size: var(--text-xs);
  font-weight: var(--font-semibold);
}
```

### Palet Dasar Token yang Harus Tersedia:
- **Warna Utama:** `--color-primary`, `--color-primary-hover`, `--color-primary-subtle`
- **Warna Permukaan:** `--color-bg`, `--color-surface`, `--color-surface-hover`
- **Warna Teks:** `--color-text-main`, `--color-text-muted`, `--color-text-inverted`
- **Warna Status:** `--color-success`, `--color-warning`, `--color-danger`, `--color-info`
- **Sistem Jarak (Spacing):** `--space-1` (4px), `--space-2` (8px), `--space-3` (12px), `--space-4` (16px), `--space-6` (24px), `--space-8` (32px)
- **Border Radius:** `--radius-sm` (4px), `--radius-md` (8px), `--radius-lg` (12px), `--radius-full` (9999px)

---

## 3. Komposisi Layout & Reuse Template

Template view mengikuti pola Modular Server-Side Rendering:

- **Layout Utama (`views/layouts/`):**
  - `main.php`: Menyediakan struktur `<html>`, `<head>`, pemanggilan CSS tokens, container sidebar, topbar, dan footer script.
  - `auth.php`: Layout khusus untuk halaman login dan registrasi.
- **Partials Komponen Modular (`views/partials/`):**
  - Gunakan `include` untuk komponen yang digunakan berulang:
    - `sidebar.php` — Menu navigasi utama.
    - `navbar.php` — Header atas dan info user.
    - `flash-messages.php` — Banner peringatan sukses/error.
    - `empty-state.php` — Tampilan state kosong generik.

---

## 4. Wajib Menyediakan Empty State

Setiap tabel, daftar tugas, kalender kegiatan, atau riwayat transaksi keuangan **WAJIB** memiliki tampilan kondisi kosong (*empty state*) ketika data bernilai kosong (`empty($data)`):

```php
<?php if (empty($tasks)): ?>
  <div class="empty-state">
    <div class="empty-state-icon">📋</div>
    <h3 class="empty-state-title">Belum Ada Tugas</h3>
    <p class="empty-state-desc">Semua tugasmu sudah selesai atau kamu belum menambahkan tugas baru.</p>
    <a href="index.php?page=tasks&action=create" class="btn btn-primary">+ Tambah Tugas Baru</a>
  </div>
<?php else: ?>
  <div class="task-list">
    <?php foreach ($tasks as $task): ?>
      <?php include __DIR__ . '/../partials/task-card.php'; ?>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
```

---

## 5. Kesesuaian Form Input Name dengan Backend

Untuk menghindari kegagalan penyimpanan data:
- Setiap elemen form input (`<input>`, `<select>`, `<textarea>`) WAJIB memiliki atribut `name="..."` yang **sama persis** dengan parameter yang diekspektasikan oleh Controller dan skema database.
- Contoh:
  - Input judul tugas: `<input type="text" name="title" required>`
  - Input deadline: `<input type="datetime-local" name="deadline" required>`
  - Input mata kuliah: `<select name="course_id">`
  - Input estimasi waktu: `<input type="number" name="estimated_minutes" min="15">`
- Tambahkan atribut `required`, `min`, `max`, atau `maxlength` di sisi klien sebagai validasi awal sebelum diproses backend.

---

## 6. Responsivitas Flexbox & CSS Grid

Antarmuka harus dapat diakses dengan nyaman baik pada layar desktop mahasiswa maupun perangkat mobile/tablet:
- Gunakan **CSS Grid** untuk tata letak makro (dashboard grid, perbandingan kartu).
- Gunakan **Flexbox** untuk tata letak mikro (elemen navbar, tombol aksi, item list).
- Tetapkan breakpoint standar:
  - Mobile: `< 768px` (Sidebar beralih menjadi drawer/hamburger menu, kolom grid menjadi 1 kolom penuh).
  - Tablet/Laptop: `768px - 1024px`.
  - Desktop: `> 1024px` (Sidebar permanen, multi-kolom dashboard aktif).
- Hindari penggunaan lebar tetap dalam satuan pixel (`width: 500px`) yang dapat memicu horizontal scrolling pada layar kecil. Gunakan `width: 100%`, `max-width`, atau `clamp()`.

