# Panduan Design System & UI Guidelines — CampusOS

> **Konsep Desain:** Technical High-Contrast Dark Mode (Dark Bento Dashboard)  
> **Target Pengguna:** Mahasiswa Modern  
> **Filosofi Visual:** Kontras Tinggi, Bersih, Lega (*Spacious*), Ramah Mahasiswa (bebas jargon panel admin teknis), dan Berorientasi Tindakan (*Action-Oriented*).  
> **Berkas Master FigCraft:** [`docs/CampusOS-Dashboard.figcraft`](CampusOS-Dashboard.figcraft) (Artboard Dashboard Home & Artboard Tasks/Smart Planner).

---

## 1. Arsitektur Tata Letak (Dark Bento Shell)

Antarmuka CampusOS dibangun di atas tata letak 2-region responsif dengan komposisi Bento Grid berjarak lega (`gap: 20px`):

```text
┌──────────────────────────────────────────────────────────────────────────────────┐
│ APP SHELL (#0B0D0E)                                                              │
├──────────────────┬───────────────────────────────────────────────────────────────┤
│ SIDEBAR KIRI     │ STICKY TOP HEADER (#0B0D0E, Border Bawah #242933)             │
│ (248px, #111317) │ • Sapaan / Judul Halaman     [+ Catat Transaksi] [+ Tugas]    │
│                  ├───────────────────────────────────────────────────────────────┤
│ • Logo Lime      │ MAIN BENTO CANVAS (Max-Width 1320px, Padding 24px 32px)       │
│ • Menu Utama     │                                                               │
│   - Dashboard    │ Row 1: 4 Metric KPI Cards (SKS, Tugas, Studi, Sisa Anggaran)  │
│   - Jadwal       │ Row 2: Beban Belajar (CSS Bar Chart) | Timeline Penugasan     │
│   - Kalender     │ Row 3: Jadwal Kuliah Hari Ini | Rekomendasi Smart Planner     │
│   - Tugas        │        (beserta kotak alasan transparan & tombol persetujuan) │
│   - Smart Planner│                                                               │
│   - Keuangan     │                                                               │
│ • Profil & Logout│                                                               │
└──────────────────┴───────────────────────────────────────────────────────────────┘
```

---

## 2. Token Desain Terpusat (`public/assets/css/tokens.css`)

Seluruh warna, tipografi, radius, dan jarak dikelola secara terpusat di `public/assets/css/tokens.css`. Dilarang menulis warna hex hardcoded berulang kali di file komponen.

### A. Palet Warna High-Contrast Dark Mode

```css
:root {
  /* Core Dark Bento Surfaces */
  --bg-canvas: #0B0D0E;              /* Latar belakang kanvas utama (Deep Obsidian) */
  --bg-sidebar: #111317;             /* Sidebar navigasi & inner recessed panels */
  --bg-card: #16191E;                /* Permukaan utama Kartu Bento */
  --bg-card-hover: #1C2128;          /* State hover kartu & menu aktif */
  --bg-input: #0F1216;               /* Latar belakang field input form */

  /* Borders & Dividers */
  --border-subtle: #242933;          /* Border tipis 1px khas Technical Dark Bento */
  --border-strong: #323946;          /* Border saat hover / dialog modal */
  --border-focus: #C8F538;           /* Border fokus input (Electric Lime) */

  /* Typography Colors */
  --text-primary: #F3F4F6;           /* Teks utama kontras tinggi */
  --text-secondary: #9CA3AF;         /* Teks deskripsi, label, metadata */
  --text-muted: #6B7280;             /* Placeholder & label halus */
  --text-on-accent: #0B0D0E;         /* Teks gelap pekat di atas tombol Lime/Orange */

  /* Brand & Semantic High-Contrast Accents */
  --accent-lime: #C8F538;            /* Electric Lime — Aksi utama, fokus, slot siap */
  --accent-lime-hover: #D6FA5C;
  --accent-lime-soft: #1F2A16;       /* Latar belakang badge/pill Electric Lime */

  --accent-orange: #FB923C;          /* Warm Orange — Prioritas tinggi, deadline dekat, warning */
  --accent-orange-hover: #FDA45C;
  --accent-orange-soft: #2D1F14;     /* Latar belakang badge/pill Warm Orange */

  --accent-cyan: #38BDF8;            /* Indikator status In Progress & info sekunder */
  --accent-danger: #F87171;          /* Overdue, error validasi, aksi hapus */
  --accent-success: #4ADE80;         /* Status tugas selesai & anggaran sehat */
}
```

### B. Tipografi & Skala Radius

```css
:root {
  --font-family-sans: "Plus Jakarta Sans", "Inter", system-ui, -apple-system, sans-serif;
  --font-family-mono: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;

  --radius-xs: 6px;
  --radius-sm: 8px;
  --radius-md: 12px;
  --radius-card: 16px;               /* Standar Kartu Bento & KPI */
  --radius-frame: 20px;              /* Shell utama & Auth card */
  --radius-full: 9999px;             /* Pill badge, timeline bar, progress bar */
}
```

---

## 3. Arsitektur Berkas Frontend Modular

| Berkas | Tanggung Jawab |
|---|---|
| `public/assets/css/tokens.css` | Deklarasi seluruh CSS Custom Properties (warna, tipografi, radius, spasi, shadow). |
| `public/assets/css/layout.css` | Tata letak makro (Sidebar `248px`, Topbar sticky, Bento Grid tracks, dan drawer mobile `<= 860px`). |
| `public/assets/css/components.css` | Komponen UI modular (KPI Cards, CSS Bar Chart, Horizontal Timeline Pill Bars, Reason Box, Data Tables, Modals, Banners, Empty States). |
| `public/assets/js/ui.js` | Interaktivitas murni Vanilla JS (mobile drawer, modal deklaratif `data-open-modal`, populator modal edit tugas, chart interaktif, auto-dismiss flash alert). Bebas ketergantungan `localStorage` untuk data inti. |

---

## 4. Katalog Komponen Utama

1. **Metric KPI Cards (`.kpi-card`):** Menampilkan metrik ringkas (`SKS Semester Ini`, `Tugas Aktif`, `Beban Studi`, `Sisa Anggaran`) lengkap dengan ikon indikator, nilai bold kontras tinggi, pill semantik, atau progress bar.
2. **Clean CSS Bar Chart (`.bar-chart-wrap`):** Grafik 7 batang vertikal (Sen–Min) murni CSS tanpa library berat, membedakan puncak aktivitas belajar (`#C8F538`), peringatan beban (`#FB923C`), dan hari reguler (`#242933`).
3. **Horizontal Timeline Pill Bars (`.timeline-list`):** Representasi durasi estimasi tugas dan kedekatan deadline menggunakan batang pil horizontal berwarna Warm Orange (prioritas tinggi) dan Electric Lime (prioritas normal).
4. **Smart Planner Recommendation Card (`.planner-rec-card`):** Kartu rekomendasi slot belajar dengan border kiri semantik, pill jadwal (`#C8F538`), kotak alasan transparan (`.planner-reason-box`), dan form POST eksplisit untuk persetujuan simpan ke kalender.
5. **Conflict / Alert Banners (`.alert-banner`) & Friendly Empty States (`.empty-state`):** Setiap halaman wajib menangani pesan umpan balik (sukses, peringatan bentrok/terlambat, gagal) serta tampilan ramah ketika data kosong disertai tombol CTA langsung.

---

## 5. Workflow FigCraft Live Designer (`figcraft-live-designer`)

1. Seluruh pembaruan visual dimulai di kanvas live FigCraft Desktop (`docs/CampusOS-Dashboard.figcraft`) menggunakan tool resmi MCP Server `figcraft`.
2. **Dilarang keras** membuat skrip `.js` generator manual untuk menulis file `.figcraft`.
3. Setelah diverifikasi via `screenshot` MCP, token dan komponen disinkronisasikan secara eksplisit ke `public/assets/css/` dan template `views/`.
