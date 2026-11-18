# Panduan Design System & UI Guidelines — CampusOS

> **Konsep Desain:** Dual Theme System — Technical High-Contrast Dark Mode (Default) & Clean Daylight Light Mode  
> **Target Pengguna:** Mahasiswa Modern  
> **Filosofi Visual:** Kontras Tinggi, Bersih, Lega (*Spacious*), Ramah Mahasiswa (bebas jargon panel admin teknis), dan Berorientasi Tindakan (*Action-Oriented*).  
> **Transisi Visual:** Circular Reveal Animation via Native View Transitions API (`document.startViewTransition`) yang bertitik tolak langsung dari posisi tombol toggle.  
> **Berkas Master FigCraft:** [`docs/CampusOS-Dashboard.figcraft`](CampusOS-Dashboard.figcraft) (Artboard Dashboard Home & Artboard Tasks/Smart Planner).

---

## 1. Arsitektur Tata Letak (Bento Shell)

Antarmuka CampusOS dibangun di atas tata letak 2-region responsif dengan komposisi Bento Grid berjarak lega (`gap: 20px`):

```text
┌──────────────────────────────────────────────────────────────────────────────────┐
│ APP SHELL (--bg-canvas)                                                          │
├──────────────────┬───────────────────────────────────────────────────────────────┤
│ SIDEBAR KIRI     │ STICKY TOPBAR (--bg-canvas, Border Bawah --border-subtle)     │
│ (248px)          │ • Sapaan / Judul    [🌗 Tema] [+ Catat Transaksi] [+ Tugas]    │
│                  ├───────────────────────────────────────────────────────────────┤
│ • Logo Brand     │ MAIN BENTO CANVAS (Max-Width 1320px, Padding 24px 32px)       │
│ • Menu Navigasi  │                                                               │
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

## 2. Token Desain & Dual Theme (`public/assets/css/tokens.css`)

Seluruh warna, tipografi, radius, dan jarak dikelola secara terpusat di `public/assets/css/tokens.css`. Dilarang menulis warna hex hardcoded berulang kali di berkas antarmuka.

### A. Palet Warna Technical High-Contrast Dark Mode (`[data-theme="dark"]`, Default)

```css
:root,
[data-theme="dark"] {
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

### B. Palet Warna Clean Daylight Light Mode (`[data-theme="light"]`)

```css
[data-theme="light"] {
  /* Surfaces */
  --bg-canvas: #F4F6F8;              /* Soft Cool Grey Canvas */
  --bg-sidebar: #FFFFFF;             /* Clean White Sidebar */
  --bg-card: #FFFFFF;                /* Pure White Bento Cards */
  --bg-card-hover: #F8FAFC;          /* Subtle Light Hover */
  --bg-input: #FFFFFF;               /* Clean Input Field */

  /* Borders & Dividers */
  --border-subtle: #E2E8F0;          /* Crisp Slate-200 Border */
  --border-strong: #CBD5E1;          /* Slate-300 Focus/Hover */
  --border-focus: #84CC16;           /* Fresh Lime-600 Focus */

  /* Typography */
  --text-primary: #0F172A;           /* Deep Slate-900 */
  --text-secondary: #475569;         /* Neutral Slate-600 */
  --text-muted: #64748B;             /* Muted Slate-500 */
  --text-on-accent: #0F172A;         /* Slate-900 High-Contrast on Lime/Orange */

  /* Accents */
  --accent-lime: #84CC16;            /* Fresh Lime (Light friendly contrast) */
  --accent-lime-hover: #65A30D;
  --accent-lime-soft: #ECFCCB;       /* Lime-100 Soft Pill */
  --accent-orange: #EA580C;          /* Orange-600 */
  --accent-orange-hover: #C2410C;
  --accent-orange-soft: #FFEDD5;     /* Orange-100 Soft Pill */
  --accent-cyan: #0284C7;            /* Sky-600 */
  --accent-danger: #DC2626;          /* Red-600 */
  --accent-success: #16A34A;         /* Green-600 */
}
```

### C. Animasi Circular Reveal & Anti-Flicker

1. **Anti-Flicker Initialization:** Di `<head>` setiap layout, skrip kecil dijalankan secara sinkron sebelum render DOM untuk membaca `localStorage.getItem('campusos-theme')` dan menyetel atribut `data-theme` pada `<html>`. Mencegah flash layar putih saat membuka aplikasi.
2. **Circular Reveal Transition:** Saat tombol `#themeToggleBtn` ditekan, fungsi `document.startViewTransition()` menjalankan animasi `clip-path: circle(...)` dari titik koordinat klik pengguna, membuka tema baru secara radial mulus selama `380ms`.
3. **Fallback Graceful:** Jika browser tidak mendukung View Transitions API, tema beralih seketika dengan transisi warna CSS `0.28s ease`.

---

## 3. Arsitektur Berkas Frontend Modular

| Berkas | Tanggung Jawab |
|---|---|
| `public/assets/css/tokens.css` | Deklarasi token desain Dual Theme (`dark` & `light`), alias kompatibilitas lama, dan aturan dasar View Transition. |
| `public/assets/css/layout.css` | Tata letak makro (Sidebar `248px`, Topbar sticky, Bento Grid tracks, dan drawer mobile `<= 860px`). |
| `public/assets/css/components.css` | Komponen UI modular (KPI Cards, CSS Bar Chart, Horizontal Timeline Pill Bars, Reason Box, Course Cards, Calendar Grid, Finance Tables, Modals, Banners, Empty States). |
| `public/assets/js/ui.js` | Interaktivitas murni Vanilla JS (Theme Toggle Circular Reveal, mobile drawer, modal deklaratif `data-open-modal`, populator modal edit tugas/kursus/agenda/transaksi, chart interaktif, auto-dismiss flash alert). |

---

## 4. Cakupan Halaman & Template View Modul

Seluruh modul telah memiliki template view resmi di folder `views/` yang terintegrasi 100% dengan controller backend:

1. **Dashboard Overview (`views/dashboard/index.php`):** Bento grid 3 baris dengan 4 KPI cards, beban belajar 7 hari (CSS Bar Chart), timeline horizontal penugasan, jadwal kuliah hari ini, serta preview rekomendasi Smart Planner.
2. **Jadwal Kuliah & Akademik (`views/academic/index.php`):** Ringkasan SKS & mata kuliah aktif, kartu mata kuliah dengan pill warna, matriks jadwal mingguan (Senin–Minggu) anti-bentrok, modal Tambah/Edit Mata Kuliah, dan modal Tambah Jadwal Kuliah.
3. **Kalender Kegiatan & Agenda (`views/calendar/index.php`):** Navigasi bulan/tahun, matriks grid kalender bulanan dengan indikator dot berwarna (kuliah, agenda, tugas), panel detail agenda hari terpilih, dan modal Tambah/Edit Agenda terintegrasi ConflictService.
4. **Tugas & Prioritas (`views/tasks/index.php`):** KPI status tugas (total, pending, in-progress, selesai, terlambat), timeline penugasan mendesak, filter status/prioritas, tabel tugas, modal Tambah Tugas, dan modal Edit Tugas.
5. **Smart Planner (`views/planner/index.php`):** Kontrol rentang analisis (3, 7, 14 hari), KPI preferensi jam belajar, kartu rekomendasi slot belajar dengan kotak alasan (*reason box*), tabel antrean prioritas berbasis skor urgensi deterministik, dan modal pengaturan jam belajar.
6. **Keuangan Pribadi Mahasiswa (`views/finance/index.php`):** Banner waspada/defisit limit anggaran, 4 KPI finansial (pemasukan, pengeluaran, sisa anggaran, proyeksi akhir bulan), bar progres anggaran bulanan, distribusi per kategori, tabel riwayat transaksi terfilter, modal Catat Transaksi, modal Edit Transaksi, dan modal Atur Batas Anggaran.
7. **Autentikasi Masuk (`views/auth/login.php`):** Tampilan masuk terpusat dengan logo brand, pesan validasi/flash, form kredensial, dan kartu demo instan.

---

## 5. Workflow FigCraft Live Designer (`figcraft-live-designer`)

1. Seluruh pembaruan visual bersumber dari kanvas live FigCraft Desktop (`docs/CampusOS-Dashboard.figcraft`) menggunakan tool resmi MCP Server `figcraft`.
2. **Dilarang keras** membuat skrip `.js` generator manual untuk menulis file `.figcraft`.
3. Setelah diverifikasi via `screenshot` MCP, token dan komponen disinkronisasikan secara eksplisit ke `public/assets/css/` dan template `views/`.
