<?php
/**
 * views/auth/login.php
 * Halaman Masuk Akun — Technical High-Contrast Dark Mode
 * Palet: #0B0D0E Canvas, #16191E Card, #242933 Border, #C8F538 Electric Lime
 */

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Masuk ke CampusOS — Asisten Mahasiswa Berbasis Web">
  <title>Masuk — CampusOS</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="assets/css/tokens.css">
  <link rel="stylesheet" href="assets/css/layout.css">
  <link rel="stylesheet" href="assets/css/components.css">

  <style>
    .auth-shell {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: var(--space-6);
      background-color: var(--bg-canvas);
      background-image: radial-gradient(circle at 50% 0%, rgba(200, 245, 56, 0.08), transparent 45%);
    }

    .auth-card {
      width: 100%;
      max-width: 430px;
      background-color: var(--bg-card);
      border: 1px solid var(--border-subtle);
      border-radius: var(--radius-frame);
      padding: var(--space-8);
      box-shadow: var(--shadow-card);
      display: flex;
      flex-direction: column;
      gap: var(--space-5);
    }

    .auth-brand {
      text-align: center;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: var(--space-2);
    }

    .auth-logo {
      width: 48px;
      height: 48px;
      border-radius: var(--radius-md);
      background-color: var(--accent-lime);
      color: var(--text-on-accent);
      font-size: 1.35rem;
      font-weight: 800;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: var(--shadow-glow-lime);
      margin-bottom: var(--space-1);
    }

    .auth-title {
      font-size: var(--font-size-xl);
      font-weight: var(--font-weight-bold);
      color: var(--text-primary);
      letter-spacing: -0.02em;
    }

    .auth-subtitle {
      font-size: var(--font-size-sm);
      color: var(--text-secondary);
    }

    .auth-demo-card {
      padding: var(--space-3) var(--space-4);
      border-radius: var(--radius-md);
      background-color: var(--bg-sidebar);
      border: 1px dashed var(--accent-lime-border);
      font-size: var(--font-size-xs);
      color: var(--text-secondary);
      line-height: 1.65;
    }

    .auth-demo-card strong {
      color: var(--accent-lime);
    }

    .auth-demo-card code {
      font-family: var(--font-family-mono);
      color: var(--text-primary);
      background-color: var(--bg-card);
      border: 1px solid var(--border-subtle);
      padding: 1px 6px;
      border-radius: var(--radius-xs);
    }
  </style>
</head>
<body>
  <div class="auth-shell">
    <div class="auth-card">
      <div class="auth-brand">
        <div class="auth-logo" aria-hidden="true">C</div>
        <h1 class="auth-title">CampusOS</h1>
        <p class="auth-subtitle">Asisten Mahasiswa Terpadu — Akademik, Smart Planner &amp; Keuangan</p>
      </div>

      <?php if (!empty($errorMessage)): ?>
        <div class="alert-banner alert-danger" role="alert">
          <div class="alert-banner-left">
            <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24" aria-hidden="true">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="8" x2="12" y2="12"></line>
              <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <span><?= e($errorMessage) ?></span>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($flash): ?>
        <div class="alert-banner alert-<?= e($flash['type'] ?? 'info') ?>" role="alert">
          <div class="alert-banner-left">
            <span><?= e($flash['message']) ?></span>
          </div>
        </div>
      <?php endif; ?>

      <form action="index.php?page=login" method="POST">
        <div class="form-group">
          <label for="email" class="form-label">Alamat Email Kampus</label>
          <input
            type="email"
            id="email"
            name="email"
            class="form-input"
            placeholder="mahasiswa@campusos.test"
            value="<?= e($submittedEmail ?: 'mahasiswa@campusos.test') ?>"
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
            class="form-input"
            placeholder="Masukkan kata sandi..."
            value="password123"
            required
          >
        </div>

        <button type="submit" class="btn-lime btn-block" style="padding: 12px 18px; margin-top: 8px;">
          <span>Masuk ke Dashboard</span>
          <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24" aria-hidden="true">
            <polyline points="9 18 15 12 9 6"></polyline>
          </svg>
        </button>
      </form>

      <div class="auth-demo-card">
        <strong>Akun Demo Mahasiswa:</strong><br>
        Email: <code>mahasiswa@campusos.test</code><br>
        Password: <code>password123</code>
      </div>
    </div>
  </div>
</body>
</html>
