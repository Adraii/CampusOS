/**
 * CampusOS — Main Client JavaScript
 * Vanilla JS untuk interaksi antarmuka (Modal, Dropdown, Filter, Flash Alerts)
 */

document.addEventListener('DOMContentLoaded', () => {
    // Inisialisasi komponen interaktif CampusOS
    console.log('CampusOS client initialized.');

    // Auto-dismiss flash alerts setelah 4 detik jika ada
    const flashAlert = document.querySelector('.alert');
    if (flashAlert) {
        setTimeout(() => {
            flashAlert.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            flashAlert.style.opacity = '0';
            flashAlert.style.transform = 'translateY(-10px)';
            setTimeout(() => flashAlert.remove(), 400);
        }, 4000);
    }
});

