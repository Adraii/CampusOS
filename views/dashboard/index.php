<?php
/**
 * views/dashboard/index.php
 * Tampilan Utama Dashboard Overview CampusOS
 */
?>

<div class="page-header">
    <h1 class="page-title">Selamat Datang, <?= e($user['name'] ?? 'Mahasiswa') ?>! 👋</h1>
    <p class="page-subtitle">Berikut ringkasan jadwal perkuliahan, prioritas tugas, dan keuangan pribadimu hari ini.</p>
</div>

<!-- Grid 3 Kolom Dashboard Card -->
<div class="grid grid-3">
    <!-- Kartu 1: Jadwal Kuliah Hari Ini -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <span>🗓️</span> Jadwal Kuliah Hari Ini
            </h2>
            <a href="index.php?page=academic" class="btn btn-secondary btn-sm">Lihat Semua</a>
        </div>
        <div class="card-body">
            <?php if (empty($todaySchedules)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">☕</div>
                    <h3 class="empty-state-title">Tidak Ada Jadwal Kuliah Hari Ini</h3>
                    <p class="empty-state-desc">Gunakan waktu luangmu untuk belajar mandiri atau bersantai.</p>
                    <a href="index.php?page=academic" class="btn btn-primary btn-sm">+ Tambah Jadwal</a>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: var(--space-3);">
                    <?php foreach ($todaySchedules as $schedule): ?>
                        <div style="padding: var(--space-3); border-radius: var(--radius-md); border: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong style="font-size: var(--text-sm);"><?= e($schedule['course_name']) ?></strong>
                                <div style="font-size: var(--text-xs); color: var(--text-secondary);">
                                    <?= e(substr($schedule['start_time'], 0, 5)) ?> - <?= e(substr($schedule['end_time'], 0, 5)) ?> • <?= e($schedule['room'] ?? 'Ruang Teori') ?>
                                </div>
                            </div>
                            <span class="badge badge-low"><?= e($schedule['course_code']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Kartu 2: Tugas Mendesak -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <span>⏳</span> Tugas Mendesak
            </h2>
            <a href="index.php?page=tasks" class="btn btn-secondary btn-sm">Buka Tugas</a>
        </div>
        <div class="card-body">
            <?php if (empty($urgentTasks)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🎉</div>
                    <h3 class="empty-state-title">Semua Tugas Selesai!</h3>
                    <p class="empty-state-desc">Belum ada tugas kuliah aktif dengan tenggat mendesak.</p>
                    <a href="index.php?page=tasks" class="btn btn-primary btn-sm">+ Tambah Tugas</a>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: var(--space-3);">
                    <?php foreach ($urgentTasks as $task): ?>
                        <div style="padding: var(--space-3); border-radius: var(--radius-md); border: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong style="font-size: var(--text-sm);"><?= e($task['title']) ?></strong>
                                <div style="font-size: var(--text-xs); color: var(--text-secondary);">
                                    Deadline: <?= e($task['deadline_date']) ?> (<?= e($task['estimated_hours']) ?> jam)
                                </div>
                            </div>
                            <span class="badge badge-<?= e($task['priority']) ?>">
                                <?= strtoupper(e($task['priority'])) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Kartu 3: Ringkasan Saldo & Anggaran -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <span>💰</span> Sisa Anggaran Bulanan
            </h2>
            <a href="index.php?page=finance" class="btn btn-secondary btn-sm">Kelola</a>
        </div>
        <div class="card-body">
            <div style="background-color: var(--color-primary-subtle); padding: var(--space-4); border-radius: var(--radius-md); text-align: center; margin-bottom: var(--space-4);">
                <div style="font-size: var(--text-xs); color: var(--color-primary); font-weight: var(--font-semibold); text-transform: uppercase;">
                    Sisa Anggaran Tersedia
                </div>
                <div style="font-size: var(--text-2xl); font-weight: var(--font-bold); color: var(--color-primary); margin-top: 4px;">
                    Rp <?= number_format($walletSummary['remainingBudget'] ?? 0, 0, ',', '.') ?>
                </div>
                <div style="font-size: var(--text-xs); color: var(--text-secondary); margin-top: 4px;">
                    Limit Anggaran: Rp <?= number_format($walletSummary['monthlyBudget'] ?? 0, 0, ',', '.') ?>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; font-size: var(--text-xs); color: var(--text-secondary); margin-bottom: var(--space-2);">
                <span>Terpakai (<?= round($walletSummary['percentUsed'] ?? 0) ?>%)</span>
                <span>Rp <?= number_format($walletSummary['totalExpense'] ?? 0, 0, ',', '.') ?></span>
            </div>
            
            <div style="width: 100%; height: 8px; background-color: var(--border-color); border-radius: var(--radius-full); overflow: hidden;">
                <div style="width: <?= min(100, max(0, (float)($walletSummary['percentUsed'] ?? 0))) ?>%; height: 100%; background-color: var(--color-<?= $walletSummary['statusBadge'] ?? 'success' ?>);"></div>
            </div>
        </div>
    </div>
</div>

