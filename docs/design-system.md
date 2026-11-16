# Panduan Design System & UI Guidelines — CampusOS

> **Konsep Desain:** 3-Column Bento Grid Productivity Dashboard  
> **Target Pengguna:** Mahasiswa Modern  
> **Filosofi Visual:** Fungsional, Playful yet Professional, Berorientasi Tindakan (*Action-Oriented*), dan Terstruktur Rapi.  
> **Berkas Referensi Desain FigCraft:** [`docs/CampusOS-Dashboard.figcraft`](CampusOS-Dashboard.figcraft)

---

## 1. Arsitektur Tata Letak: 3-Column Bento Grid Shell

Dashboard CampusOS menggunakan tata letak bento 3-kolom modern di dalam shell frame desktop (`1440px × 900px`) dengan radius sudut `24px` dan gap `20px`:

```text
┌────────────────────────────────────────────────────────────────────────────────────────┐
│ APP SHELL CONTAINER (Max 1440px, Background #F6F6F8, Padding 18px, Radius 24px)        │
├───────────────────┬──────────────────────────────────────────┬─────────────────────────┤
│ 1. SIDEBAR KIRI   │ 2. KANVAS TENGAH (1fr)                   │ 3. PANEL KANAN (320px)  │
│    (240px)        │                                          │                         │
│ • Deep Midnight   │ • Top Greeting Header                    │ • Kartu Profil & NIM    │
│   Navy (#1A1B2E)  │ • Ringkasan Tugas Menunggu (Banner Lime) │ • Kalender Interaktif   │
│ • Logo & Brand    │ • Jadwal Kuliah Hari Ini (List Row)      │ • Aktivitas Akademik    │
│ • Menu Navigasi   │ • Grid 2-Kolom Bawah:                    │ • Catatan Cepat / Quick │
│ • Quick Stats     │   - Grafik Belajar Mingguan (Bar Chart)  │   Reminders             │
│ • Tombol Collapse │   - Widget Sisa Anggaran (Rp & Meter)    │                         │
└───────────────────┴──────────────────────────────────────────┴─────────────────────────┘
```

---

## 2. Token Desain Terpusat (`public/assets/css/tokens.css`)

Semua styling di CampusOS wajib memanfaatkan variabel CSS terpusat yang dideklarasikan di `public/assets/css/tokens.css`. Dilarang menuliskan kode warna hex statis secara langsung pada file komponen.

### A. Palet Warna (Color Palette)

```css
:root {
  /* Brand Core Palette */
  --color-brand-dark: #1A1B2E;        /* Deep Midnight Navy (Sidebar & High-Contrast Cards) */
  --color-brand-dark-card: #22243C;   /* Varian gelap untuk card di sidebar */
  --color-brand-lime: #D4F26A;        /* Electric Lime (Aksen Tombol Utama & Highlight) */
  --color-brand-lime-hover: #c4e456;
  --color-brand-dark-text: #18181B;

  /* Pastel Accent Tokens (Kategori Matkul & Badge) */
  --color-pastel-peach: #FDE6D8;      /* Peach / Orange Muda */
  --color-pastel-peach-text: #C2410C;
  --color-pastel-peach-icon: #D97706;

  --color-pastel-lavender: #E5DFFF;   /* Lavender / Ungu Lembut */
  --color-pastel-lavender-text: #7E22CE;
  --color-pastel-lavender-icon: #6C5CE7;

  --color-pastel-mint: #E2F5EA;       /* Mint / Hijau Segar */
  --color-pastel-mint-text: #059669;

  --color-pastel-lime: #EBF9B8;       /* Lime Pastel */
  --color-pastel-lime-text: #4D7C0F;

  /* Backgrounds & Surfaces */
  --color-bg-page: #EBE9F5;           /* Wallpaper lembut pembungkus luar */
  --color-bg-canvas: #F6F6F8;         /* Kanvas utama (Warm Off-White) */
  --color-bg-card: #FFFFFF;           /* Latar kartu putih murni */
  --color-bg-subtle: #F8FAFC;
  --color-bg-muted: #F1F5F9;

  /* Borders & Dividers */
  --color-border-subtle: #E2E8F0;
  --color-border-strong: #CBD5E1;

  /* Typography Colors */
  --color-text-main: #1A1B2E;         /* Teks utama (Slate Dark) */
  --color-text-muted: #64748B;        /* Teks sekunder / metadata */
  --color-text-subtle: #94A3B8;       /* Teks placeholder / disable */
  --color-text-inverse: #FFFFFF;      /* Teks putih */
  --color-sidebar-text: #C2C5DE;
  --color-sidebar-icon: #8E92BC;
}
```

---

### B. Tipografi & Skala Sudut (Typography & Border Radii)

```css
:root {
  /* Font Family */
  --font-family-sans: "Plus Jakarta Sans", "Inter", system-ui, -apple-system, sans-serif;
  --font-family-mono: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;

  /* Font Sizes */
  --font-size-2xs: 0.625rem; /* 10px - Micro labels */
  --font-size-xs: 0.75rem;   /* 12px - Badges, sub-text */
  --font-size-sm: 0.8125rem; /* 13px - Meta deskripsi */
  --font-size-base: 0.875rem;/* 14px - Standar body UI */
  --font-size-md: 0.9375rem; /* 15px - Sub-heading */
  --font-size-lg: 1.125rem;  /* 18px - Judul kartu */
  --font-size-xl: 1.25rem;   /* 20px - Section title */
  --font-size-2xl: 1.5rem;   /* 24px - Judul utama halaman */

  /* Border Radii */
  --radius-xs: 6px;
  --radius-sm: 8px;
  --radius-icon: 10px;
  --radius-md: 12px;
  --radius-pill: 14px;
  --radius-card: 20px;       /* Standar kartu Bento */
  --radius-frame: 24px;      /* Standar shell aplikasi */
  --radius-full: 9999px;     /* Tombol pil & avatar */

  /* Dimensions */
  --sidebar-width: 240px;
  --right-panel-width: 320px;
  --max-app-width: 1440px;
  --min-app-height: 900px;
}
```

---

## 3. Struktur Berkas CSS & JS Modular

Arsitektur frontend CampusOS dibagi ke dalam 3 berkas CSS dan 1 pengendali JavaScript interaktif di `public/assets/`:

1. **`public/assets/css/tokens.css`:**
   - Menyimpan seluruh Design Tokens (Warna, Font, Jarak, Sudut, Bayangan, Transisi, dan Mode Gelap).
2. **`public/assets/css/layout.css`:**
   - Mengatur struktur shell makro, tata letak 3 kolom grid, header pencarian, navigasi drawer responsif (`@media max-width: 860px`), dan wrapper kalender.
3. **`public/assets/css/components.css`:**
   - Mengatur komponen atomik: kartu bento (`.card-bento`), bar chart mingguan (`.chart-bar`), item baris jadwal (`.schedule-row`), tombol aksi (`.btn-lime`), badge pastel, modal dialog pop-up (`.modal-backdrop`), serta sistem notifikasi toast (`.toast`).
4. **`public/assets/js/ui.js`:**
   - Pengendali interaktivitas Vanilla JS:
     - Real-time modal pop-up tambah pengeluaran & tugas baru.
     - Interaktivitas grafik batang mingguan (pilihan hari aktif & kalkulasi jam).
     - Kalender mini interaktif (navigasi bulan & seleksi tanggal).
     - Filter pencarian cepat (*live search*) jadwal kuliah & tugas.
     - Auto-dismiss notifikasi toast.
     - **Hook Sinkronisasi Backend:** Mendukung injeksi data server-side PHP melalui `window.CAMPUSOS_INITIAL_DATA` dan submit native POST/fetch ke controller PHP.

---

## 4. Standar Komponen Antarmuka (Core Components)

### A. Kartu Bento (*Bento Card*)
```css
.card-bento {
  background-color: var(--color-bg-card);
  border-radius: var(--radius-card);
  padding: 20px;
  box-shadow: var(--shadow-card);
  border: 1px solid var(--color-border-subtle);
  transition: transform var(--transition-fast), box-shadow var(--transition-fast);
}

.card-bento:hover {
  box-shadow: var(--shadow-hover);
}
```

### B. Tombol Aksen (*Electric Lime Button*)
```css
.btn-lime {
  background-color: var(--color-brand-lime);
  color: var(--color-brand-dark-text);
  font-family: var(--font-family-sans);
  font-size: var(--font-size-base);
  font-weight: var(--font-weight-semibold);
  padding: 10px 18px;
  border-radius: var(--radius-full);
  border: none;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: background-color var(--transition-fast), transform var(--transition-fast);
}

.btn-lime:hover {
  background-color: var(--color-brand-lime-hover);
  transform: translateY(-1px);
}
```

### C. Badge Pastel (*Pastel Badges*)
- **Badge Peach:** `background: var(--color-pastel-peach); color: var(--color-pastel-peach-text);`
- **Badge Lavender:** `background: var(--color-pastel-lavender); color: var(--color-pastel-lavender-text);`
- **Badge Mint:** `background: var(--color-pastel-mint); color: var(--color-pastel-mint-text);`
- **Badge Lime:** `background: var(--color-pastel-lime); color: var(--color-pastel-lime-text);`

---

## 5. Workflow Desain FigCraft (`figcraft-live-designer`)

1. **Sumber Dokumen Master:** Berkas acuan desain utama disimpan di `docs/CampusOS-Dashboard.figcraft`.
2. **Aturan Manipulasi Kanvas:**
   - Dilarang membuat skrip `.js` generator manual untuk menulis berkas `.figcraft`.
   - Wajib memanggil MCP Server `figcraft` secara langsung dengan metode *progressive canvas drawing* (Frame luar $\to$ Kolom layout $\to$ Kartu bento $\to$ Teks & Ikon).
3. **Verifikasi & Ekstraksi:** Gunakan screenshot visual / inspect tree MCP sebelum mengekstraksi token dan markup ke `public/assets/css/` dan `views/`.
