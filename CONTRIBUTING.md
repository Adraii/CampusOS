# Panduan Kontribusi & Kolaborasi — CampusOS

Terima kasih telah berkontribusi pada pengembangan **CampusOS**! Dokumen ini mengatur alur kerja kolaborasi tim, konvensi penamaan branch/commit, serta standar *Code Review* agar kualitas kode tetap terjaga.

---

## 1. Pembagian Wilayah Kerja Tim (2 Developer)

Untuk meminimalkan konflik penggabungan (*merge conflict*), tanggung jawab direktori dibagi secara jelas antara dua pengembang utama:

| Pengembang | Peran | Wilayah Kerja Utama | Tanggung Jawab |
| :--- | :--- | :--- | :--- |
| **Adam Rafiq Arrasyid** | *UI/UX & Frontend Engineer* | `views/`, `public/assets/`, `docs/CampusOS-Dashboard.figcraft`, `docs/design-system.md` | Design System (FigCraft + tokens CSS), Hybrid SSR PHP templates, Dual Theme (Dark & Light Mode), responsivitas Bento Grid, serta interaktivitas Vanilla JS. |
| **Fakhriy Mubarak** | *Backend & System Logic Engineer* | `app/controllers/`, `app/models/`, `app/services/`, `app/helpers/`, `database/` | Skema MySQL (`schema.sql`), otentikasi sesi aman, query PDO berparameter, mesin deteksi bentrok (`ConflictService`), `SmartPlannerService`, dan analitik `FinanceService`. |

> **Area Kolaborasi Bersama:** Berkas `public/index.php` (Front Controller) dan dokumen root (`README.md`, `CHANGELOG.md`) dikoordinasikan bersama sebelum dilakukan perubahan.

---

## 2. Alur Kerja Branch & Git

1. Selalu pastikan branch `main` lokal dalam kondisi terbaru sebelum memulai pekerjaan baru:
   ```bash
   git checkout main
   git pull origin main
   ```
2. Buat branch kerja baru dengan prefiks yang deskriptif:
   - `feat/<nama-fitur>` — penambahan fitur baru (contoh: `feat/finance-export-summary`)
   - `fix/<nama-bug>` — perbaikan bug (contoh: `fix/light-mode-topbar-contrast`)
   - `ui/<nama-komponen>` — pembaruan desain atau CSS (contoh: `ui/calendar-responsive-grid`)
   - `docs/<topik>` — pembaruan dokumentasi (contoh: `docs/update-api-contracts`)
   - `refactor/<area>` — perapian kode tanpa mengubah fungsionalitas
3. Pastikan file sensitif lokal `app/config/config.local.php` **tidak pernah** di-stage atau di-commit.

---

## 3. Format Conventional Commits

Seluruh pesan commit wajib mengikuti standar **Conventional Commits**:

```text
<tipe>(<scope opsional>): <deskripsi singkat dalam huruf kecil>
```

Contoh tipe yang digunakan:
- `feat`: Fitur baru untuk pengguna (contoh: `feat(planner): add urgency score breakdown table`)
- `fix`: Perbaikan bug (contoh: `fix(ui): fix light mode top header and academic course form submission`)
- `ui` / `style`: Perubahan styling CSS, layout, atau token desain
- `docs`: Pembaruan dokumentasi (`README.md`, `docs/`, `CHANGELOG.md`)
- `refactor`: Refactoring struktur kode internal
- `chore`: Pembaruan konfigurasi repository, CI/CD, atau tooling

---

## 4. Aturan Code Review & Pull Request

Sebelum membuat *Pull Request* ke branch `main`:
1. Isi lengkap template PR (`.github/PULL_REQUEST_TEMPLATE.md`).
2. Pastikan GitHub Actions CI (`CampusOS CI`) berjalan hijau (*passing*):
   - Nol syntax error pada seluruh berkas `.php` (`php -l`).
   - Tidak ada fungsi berbahaya (`eval(`, `shell_exec(`).
   - Tidak ada file kredensial lokal yang ter-track di Git.
3. Verifikasi kepatuhan terhadap **7 Hard Guardrails** di [`AGENTS.md`](AGENTS.md):
   - Tidak ada query SQL langsung di `views/` atau `public/index.php`.
   - Seluruh query SQL menggunakan **PDO Prepared Statements** dengan isolasi `WHERE user_id = :user_id`.
   - Smart Planner murni deterministik (rule-based PHP) dan tidak menimpa jadwal kuliah tetap secara otomatis.
4. Uji tampilan visual secara langsung di **Dark Mode** maupun **Light Mode**.
