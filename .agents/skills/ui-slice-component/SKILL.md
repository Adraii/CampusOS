---
name: ui-slice-component
description: SOP implementasi desain UI/UX ke kode HTML, CSS, dan JS di folder views/ dan public/assets/.
---
# SOP Slicing UI/UX & Frontend Component
Saat membantu Anggota 2 (Frontend) membuat halaman atau komponen visual:
1. **Patuhi Design Tokens:** Gunakan variabel CSS dari `public/assets/css/variables.css`. Dilarang menulis warna hex hardcoded berulang-ulang di file CSS komponen.
2. **Jangan Rusak Kontrak PHP:** Jika file `views/` sudah memiliki variabel PHP dari Backend, pertahankan nama variabel dan atribut `name="..."` pada elemen `<form>`.
3. **State Lengkap:** Setiap halaman wajib menangani tampilan ketika data kosong (*Empty State*), tampilan normal, dan pesan umpan balik (*alert/toast* sukses atau gagal).
4. **Responsif & Bersih:** Gunakan CSS Grid/Flexbox yang rapi, hindari penggunaan library CSS eksternal tanpa izin, dan jaga agar kode aman dibuka di desktop maupun mobile.

