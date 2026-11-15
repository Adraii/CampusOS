---
name: smart-planner-logic
description: SOP pengembangan logika bisnis di app/services/ (ConflictService, SmartPlannerService, FinanceService).
---
# SOP Pengembangan System Logic & Smart Planner
Saat menulis kode di dalam `app/services/`:
1. **Pure Business Logic:** File di dalam `app/services/` tidak boleh mencetak HTML (`echo`) atau mengakses `$_POST` secara langsung. Terima data melalui parameter fungsi dan kembalikan array terstruktur.
2. **Aturan ConflictService:** Deteksi bentrok jadwal harus memeriksa hari/tanggal yang sama dengan rumus irisan waktu: `(start_A < end_B) && (end_A > start_B)`.
3. **Aturan SmartPlannerService:**
   - Gunakan perhitungan deterministik berdasarkan sisa waktu menuju deadline, estimasi durasi pengerjaan (jam), dan bobot prioritas.
   - Setiap rekomendasi wajib menyertakan string `reason` (alasan transparan mengapa tugas tersebut diprioritaskan dan mengapa slot waktu tersebut dipilih).
   - Dilarang mengubah jadwal tetap di database secara otomatis tanpa aksi persetujuan pengguna.
4. **Aturan FinanceService:** Proyeksi saldo akhir periode harus menyertakan asumsi rata-rata pengeluaran harian secara eksplisit.
