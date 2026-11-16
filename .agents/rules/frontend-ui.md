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

## 2. Penggunaan Wajib CSS Tokens & File Modular (`public/assets/css/`)

Dilarang keras melakukan *hardcode* nilai warna hex, ukuran spasi, atau radius secara acak di file styling. Arsitektur CSS CampusOS dipisahkan secara modular:
1. `public/assets/css/tokens.css`: Variabel desain terpusat (warna brand dark `#1A1B2E`, canvas `#F6F6F8`, accent lime `#D4F26A`, pastel badges, font `Plus Jakarta Sans`, radius `24px`/`20px`/`pill`).
2. `public/assets/css/layout.css`: Tata letak makro 3-Column Bento Grid Shell (Sidebar `240px`, Kanvas Tengah `1fr`, Panel Kanan `320px`), header, drawer mobile, dan grid track.
3. `public/assets/css/components.css`: Komponen kartu (`.card-bento`), tombol (`.btn-lime`, `.btn-icon`), badge pastel, modal dialog, empty states, dan alert notifications.

```css
/* Contoh konsumsi token yang benar */
.card-bento {
  background-color: var(--color-bg-card);
  border: 1px solid var(--color-border-subtle);
  border-radius: var(--radius-card);
  padding: 20px;
  box-shadow: var(--shadow-card);
}

.badge-urgent {
  background-color: var(--color-pastel-peach);
  color: var(--color-pastel-peach-text);
  font-size: var(--font-size-xs);
  font-weight: var(--font-weight-semibold);
}
```

---

## 3. Workflow Desain FigCraft (`figcraft-live-designer`)

Setiap kali mendesain atau memperbarui antarmuka menggunakan FigCraft Desktop:
1. **Dilarang Skrip Generator Manual:** Dilarang keras membuat skrip `.js` generator manual untuk menulis file `.figcraft` secara langsung (menghindari kanvas korup/putih kosong).
2. **Wajib Gunakan MCP FigCraft:** Gunakan perintah MCP Server `figcraft` secara langsung dan bertahap dari container luar ke dalam (*progressive canvas drawing*):
   - Frame Utama Desktop (`1440x900`) $\to$ Layout Kolom (Sidebar `240px`, Kanvas Tengah, Panel Kanan `320px`) $\to$ Kartu Bento $\to$ Tipografi & Ikon.
3. **Verifikasi Visual:** Jalankan screenshot / inspect layer tree via MCP untuk memastikan kanvas terisi dengan presisi.
4. **Sinkronisasi Eksplisit ke Kode:** Setelah mockup disetujui, terapkan secara konsisten ke HTML/PHP (`views/`), CSS modular (`tokens.css`, `layout.css`, `components.css`), dan interaktivitas JS (`ui.js`).

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

