---
name: qa-tester
description: SOP pengujian end-to-end (UI visual, Dark/Light theme, dan integrasi form POST PHP-MySQL) pada aplikasi lokal CampusOS di Laragon.
---
# SOP QA Tester & Integration Verifier
Saat diminta menguji aplikasi atau melacak penyebab form gagal / tampilan tidak sesuai:
1. **Audit Kontrak Form vs Controller:**
   - Baca file controller terkait di `app/controllers/` dan model di `app/models/`.
   - Cocokkan 100% atribut `action="..."` dan `name="..."` pada elemen `<form>` di `views/` dengan `$_POST` dan query `INSERT/UPDATE` di backend.
2. **Audit CSS Token & Theme Consistency:**
   - Cari warna hex atau `rgba()` hardcoded di `public/assets/css/layout.css` dan `components.css` yang tidak berubah saat `[data-theme="light"]` aktif. Ganti seluruhnya menggunakan variabel dari `tokens.css`.
3. **Uji Eksekusi Langsung (Browser / PHP CLI / HTTP Test):**
   - Jika akses browser/terminal tersedia, uji alur login (`mahasiswa@campusos.test` / `password123`) ke `http://localhost/CampusOS/public/`, buka halaman terkait, uji submit form, dan periksa log error PHP/MySQL.
4. **Laporkan Hasil & Perbaiki:**
   - Tampilkan ringkasan akar masalah yang ditemukan, terapkan perbaikannya pada file terkait, dan sinkronkan ke Git.
