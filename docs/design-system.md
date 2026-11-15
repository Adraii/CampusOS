# Panduan Design System & UI Guidelines — CampusOS

> **Konsep Desain:** Hybrid Productivity Dashboard  
> **Target Pengguna:** Mahasiswa Modern  
> **Filosofi Visual:** Fungsional, Bersih, Kontras Nyaman, dan Berorientasi Tindakan (*Action-Oriented*).

---

## 1. Fondasi Desain & Token CSS (`variables.css`)

Semua styling di CampusOS wajib memanfaatkan variabel CSS terpusat yang dideklarasikan di `public/assets/css/variables.css`. Dilarang menggunakan nilai warna atau ukuran statis secara berulang di dalam file CSS komponen.

### A. Palet Warna (Color Palette)

```css
:root {
  /* Surface & Background */
  --bg-main: #F8FAFC;              /* Latar belakang halaman aplikasi (Slate 50) */
  --bg-surface: #FFFFFF;           /* Latar belakang kartu/kontainer (White) */
  --bg-surface-hover: #F1F5F9;     /* Efek hover item dan baris tabel (Slate 100) */
  --bg-sidebar: #0F172A;           /* Sidebar navigasi gelap (Slate 900) */
  --border-color: #E2E8F0;         /* Garis batas pembatas (Slate 200) */
  --border-focus: #3B82F6;         /* Garis batas saat elemen form aktif (Blue 500) */

  /* Typography / Text */
  --text-primary: #0F172A;         /* Teks judul dan konten utama (Slate 900) */
  --text-secondary: #475569;       /* Teks deskripsi, label, dan metadata (Slate 600) */
  --text-muted: #94A3B8;           /* Teks placeholder dan tanggal lampau (Slate 400) */
  --text-inverted: #FFFFFF;        /* Teks putih untuk tombol atau background gelap */

  /* Brand / Action Colors */
  --color-primary: #2563EB;        /* Warna primer aksi utama (Blue 600) */
  --color-primary-hover: #1D4ED8;  /* Hover warna primer (Blue 700) */
  --color-primary-subtle: #EFF6FF; /* Background subtle badge/tombol (Blue 50) */

  /* Semantic / Status Colors */
  --color-success: #16A34A;        /* Sukses, aman, status done (Green 600) */
  --color-success-subtle: #F0FDF4; /* Background status sukses (Green 50) */
  --color-warning: #D97706;        /* Waspada, konflik jadwal, warning (Amber 600) */
  --color-warning-subtle: #FFFBEB; /* Background status waspada (Amber 50) */
  --color-danger: #DC2626;         /* Bahaya, batas anggaran, urgent (Red 600) */
  --color-danger-subtle: #FEF2F2;  /* Background status bahaya (Red 50) */
  --color-info: #0284C7;           /* Informasi agenda, catatan umum (Sky 600) */
  --color-info-subtle: #F0F9FF;    /* Background info (Sky 50) */
}
```

---

### B. Tipografi & Skala Spasi (Typography & Spacing)

```css
:root {
  /* Font Family */
  --font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;

  /* Font Sizes */
  --text-xs: 0.75rem;    /* 12px - Label badge, footnote */
  --text-sm: 0.875rem;   /* 14px - Teks sekunder, form input, menu */
  --text-base: 1rem;     /* 16px - Paragraf body utama */
  --text-lg: 1.125rem;   /* 18px - Sub-heading, judul kartu */
  --text-xl: 1.25rem;    /* 20px - Judul section */
  --text-2xl: 1.5rem;    /* 24px - Judul halaman utama */

  /* Font Weights */
  --font-normal: 400;
  --font-medium: 500;
  --font-semibold: 600;
  --font-bold: 700;

  /* Spacing Scale */
  --space-1: 0.25rem;   /* 4px */
  --space-2: 0.5rem;    /* 8px */
  --space-3: 0.75rem;   /* 12px */
  --space-4: 1rem;      /* 16px */
  --space-5: 1.25rem;   /* 20px */
  --space-6: 1.5rem;    /* 24px */
  --space-8: 2rem;      /* 32px */

  /* Border Radius */
  --radius-sm: 4px;     /* Checkbox, micro-badge */
  --radius-md: 8px;     /* Button, input field, filter dropdown */
  --radius-lg: 12px;    /* Kartu konten, panel modal */
  --radius-full: 9999px;/* Avatar, pill badge */

  /* Shadows */
  --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
  --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
}
```

---

## 2. Standar Komponen Antarmuka (Core Components)

### A. Kartu Konten (*Content Card*)
Kartu merupakan unit visual utama untuk mengelompokkan informasi:
```css
.card {
  background-color: var(--bg-surface);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-lg);
  padding: var(--space-6);
  box-shadow: var(--shadow-sm);
  transition: box-shadow 0.2s ease, transform 0.2s ease;
}

.card:hover {
  box-shadow: var(--shadow-md);
}
```

### B. Badge Prioritas & Status (*Priority Badges*)
Untuk menampilkan urgensi deadline dan status tugas:
- **Prioritas High / Urgent:**
  `background: var(--color-danger-subtle); color: var(--color-danger); border: 1px solid #FECACA;`
- **Prioritas Medium:**
  `background: var(--color-warning-subtle); color: var(--color-warning); border: 1px solid #FDE68A;`
- **Prioritas Low:**
  `background: var(--color-primary-subtle); color: var(--color-primary); border: 1px solid #BFDBFE;`

### C. State Umpan Balik (*Alerts & Flash Messages*)
Setiap notifikasi aksi pengguna (simpan/hapus/gagal) menggunakan styling alert yang jelas:
```html
<div class="alert alert-success">
  <span class="alert-icon">✓</span>
  <span class="alert-text">Jadwal kuliah berhasil ditambahkan ke kalender!</span>
</div>
```

---

## 3. Kewajiban Empty State

Untuk menjaga *User Experience* tetap ramah ketika database belum memiliki data:
- Tampilkan ilustrasi/ikon representatif berukuran sedang.
- Tuliskan judul ramah dan deskripsi singkat mengapa data belum muncul.
- Sediakan tombol **Call-To-Action (CTA)** langsung agar user dapat menambahkan data pertama kali.

```html
<!-- Contoh Standar Empty State -->
<div class="empty-state">
  <div class="empty-state-icon">🗓️</div>
  <h3 class="empty-state-title">Tidak Ada Jadwal Kuliah Hari Ini</h3>
  <p class="empty-state-desc">Nikmati waktu luangmu atau manfaatkan Smart Planner untuk mencicil tugas mandiri.</p>
  <a href="index.php?page=academic&action=create" class="btn btn-primary">+ Tambah Jadwal Kuliah</a>
</div>
```

---

## 4. Panduan Ekstraksi Desain FigCraft ke Kode

Bagi pengembang yang mengekstraksi mockup desain dari Figma / FigCraft:
1. **Identifikasi Token:** Saat melihat desain di FigCraft, cari kesamaan nilai warna dan padding. Petakan langsung ke token CSS di `variables.css`.
2. **Jangan Copas Hex Code Mentah:** Jika FigCraft menghasilkan `color: #2563eb;`, ubah menjadi `color: var(--color-primary);`.
3. **Cek Responsivitas:**
   - Gunakan layout fleksibel (`display: flex; gap: var(--space-4);`) atau `display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));`.
   - Pastikan viewport mobile (`< 768px`) tidak mengalami overflow horizontal.

