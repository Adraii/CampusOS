---
name: safe-git-sync
description: SOP wajib saat mengeksekusi batch pembuatan/perubahan file dan melakukan sinkronisasi Git hingga tuntas tanpa menyisakan file Modified (M) atau Untracked (U).
---
# SOP Safe Git Sync & Batch Execution
1. **Jangan Berhenti Setengah Jalan:** Jika pengguna meminta pembuatan atau pembaruan beberapa file, selesaikan penulisan SELURUH file terlebih dahulu sebelum meminta konfirmasi akhir.
2. **Dilarang Mengubah Struktur Folder:** Hanya buat file di dalam direktori resmi (`app/`, `database/`, `docs/`, `public/`, `views/`, `.agents/`). Dilarang membuat folder baru di root tanpa izin eksplisit.
3. **Verifikasi Status Kerja:** Setelah semua file selesai ditulis, jalankan `git status -s` untuk memeriksa apakah masih ada file bertanda `M` (Modified) atau `??` (Untracked) yang relevan dengan tugas.
4. **Staging Menyeluruh:** Gunakan `git add <daftar-file>` atau `git add .` (pastikan `config.local.php` tidak ikut masuk).
5. **Commit & Push Tuntas:** Jalankan `git commit -m "<tipe>: <pesan singkat>"` diikuti `git push origin <nama-branch>` (atau `main`), lalu jalankan `git status` sekali lagi untuk memastikan outputnya `working tree clean`.

