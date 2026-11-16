---
name: figcraft-live-designer
description: Mendesain antarmuka UI/UX secara live dan bertahap di kanvas FigCraft Desktop menggunakan server MCP figcraft, memverifikasi hasil render visual, dan mengonversinya menjadi kode frontend modular (HTML5/CSS3/Vanilla JS). Gunakan skill ini setiap kali diminta mendesain UI di FigCraft atau mengubah desain FigCraft menjadi kode.
---

# FigCraft Live Designer & Frontend Workflow

## Aturan Wajib Sebelum Menggambar
1. **Jangan membuat skrip `.js` generator manual** untuk menulis file `.figcraft` secara mentah karena sering menghasilkan kanvas kosong/korup.
2. **Wajib gunakan tool resmi dari MCP Server `figcraft`** secara langsung.
3. Sebelum mulai menggambar, selalu jalankan `doc.list` untuk memeriksa dokumen yang sedang terbuka di FigCraft Desktop. Jika belum ada dokumen atau kanvas belum siap, buat frame baru menggunakan tool MCP resmi.

## Alur Eksekusi Bertahap (Progressive Canvas Drawing)
Bangun desain dari luar ke dalam agar terlihat real-time di kanvas dan tidak bertumpuk:
1. **Buat Frame Utama:** Buat container utama (misal Desktop `1440x900`) dengan warna latar belakang kanvas.
2. **Struktur Layout (Grid/Kolom):** Buat container untuk Sidebar, Area Utama, dan Panel Pendukung dengan koordinat `(x, y)` dan ukuran `(width, height)` yang dihitung presisi agar tidak saling menimpa.
3. **Komponen & Kartu:** Masukkan kartu-kartu di dalam tiap kolom menggunakan padding konsisten (`20px`) dan gap (`16px`–`20px`).
4. **Tipografi & Ikon:** Tambahkan teks dengan ukuran font proporsional dan pastikan warna teks kontras dengan latar belakangnya (hindari teks putih di atas kartu putih).

## Verifikasi Wajib Setelah Menggambar
Setelah selesai memanggil tool MCP `figcraft`:
1. Panggil tool screenshot / export dari MCP `figcraft` (atau cek layer tree) untuk memastikan kanvas **tidak putih kosong** dan semua layer benar-benar ter-render di dalam koordinat frame utama.
2. Laporkan ringkasan layer yang berhasil dibuat beserta lokasinya di dokumen aktif.

## Sinkronisasi ke Kode Produksi (Design-to-Code)
Setelah desain disetujui di FigCraft:
1. Petakan token desain (warna, font, spacing, radius) ke `public/assets/css/tokens.css`.
2. Tuliskan layout grid makro di `public/assets/css/layout.css`.
3. Slicing komponen kartu, tombol, badge, dan modal ke `public/assets/css/components.css`.
4. Tambahkan interaktivitas dinamis (modal, filter, toast) di `public/assets/js/ui.js` dengan tetap mematuhi kontrak form backend PHP.
