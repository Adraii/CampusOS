<?php
/**
 * views/auth/login.php
 * Halaman Masuk Akun Pengguna CampusOS
 */

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — CampusOS</title>
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <div style="font-size: 2.5rem; margin-bottom: var(--space-2);">🎓</div>
                <h1 class="auth-title">CampusOS</h1>
                <p class="auth-subtitle">Web-based Student Assistant</p>
            </div>

            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger">
                    <span>⚠️</span>
                    <span><?= e($errorMessage) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type'] ?? 'info') ?>">
                    <span><?= e($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <form action="index.php?page=login" method="POST">
                <div class="form-group">
                    <label for="email" class="form-label">Alamat Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-control" 
                        placeholder="contoh: mahasiswa@campusos.test" 
                        value="<?= e($submittedEmail ?? 'mahasiswa@campusos.test') ?>"
                        required 
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Kata Sandi</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        placeholder="Masukkan kata sandi..." 
                        value="password123"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="margin-top: var(--space-4);">
                    Masuk ke Dashboard
                </button>
            </form>

            <div style="margin-top: var(--space-6); padding: var(--space-3); background-color: var(--color-primary-subtle); border-radius: var(--radius-md); font-size: var(--text-xs); color: var(--color-primary);">
                <strong>Akun Demo Tersedia:</strong><br>
                Email: <code>mahasiswa@campusos.test</code><br>
                Password: <code>password123</code>
            </div>
        </div>
    </div>
</body>
</html>

