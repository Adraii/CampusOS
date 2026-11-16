<?php
/**
 * views/auth/login.php
 * Halaman Masuk Akun Pengguna CampusOS
 * Tema: Midnight Navy (#1A1B2E) + Electric Lime (#D4F26A) Bento Aesthetic
 */

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Masuk ke CampusOS — Web-based Student Assistant">
  <title>Masuk — CampusOS</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  
  <link rel="stylesheet" href="assets/css/tokens.css">
  <link rel="stylesheet" href="assets/css/layout.css">
  <link rel="stylesheet" href="assets/css/components.css">
  
  <style>
    .auth-page-container {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--color-bg-page);
      padding: 24px;
    }
    
    .auth-bento-shell {
      width: 100%;
      max-width: 440px;
      background: var(--color-brand-dark);
      border-radius: var(--radius-frame);
      padding: 36px 32px;
      box-shadow: 0 20px 40px rgba(26, 27, 46, 0.2);
      color: var(--color-text-inverse);
      position: relative;
      overflow: hidden;
    }
    
    .auth-deco-glow {
      position: absolute;
      top: -60px;
      right: -60px;
      width: 140px;
      height: 140px;
      background: var(--color-brand-lime);
      opacity: 0.12;
      border-radius: var(--radius-full);
      filter: blur(40px);
      pointer-events: none;
    }

    .auth-header-group {
      text-align: center;
      margin-bottom: 28px;
    }
    
    .auth-logo-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 48px;
      height: 48px;
      background: var(--color-brand-lime);
      color: var(--color-brand-dark-text);
      border-radius: var(--radius-pill);
      font-size: 1.5rem;
      margin-bottom: 14px;
      font-weight: 800;
    }
    
    .auth-brand-title {
      font-family: var(--font-family-sans);
      font-size: var(--font-size-2xl);
      font-weight: var(--font-weight-bold);
      color: var(--color-text-inverse);
      letter-spacing: -0.02em;
    }
    
    .auth-brand-subtitle {
      font-size: var(--font-size-sm);
      color: var(--color-sidebar-text);
      margin-top: 4px;
    }

    .auth-form-group {
      margin-bottom: 18px;
    }
    
    .auth-label {
      display: block;
      font-size: var(--font-size-xs);
      font-weight: var(--font-weight-semibold);
      color: var(--color-sidebar-text);
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 8px;
    }
    
    .auth-input-field {
      width: 100%;
      background: rgba(255, 255, 255, 0.06);
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-radius: var(--radius-md);
      padding: 12px 14px;
      font-size: var(--font-size-base);
      font-family: var(--font-family-sans);
      color: var(--color-text-inverse);
      outline: none;
      transition: border-color var(--transition-fast), background-color var(--transition-fast);
    }
    
    .auth-input-field:focus {
      border-color: var(--color-brand-lime);
      background: rgba(255, 255, 255, 0.1);
    }

    .auth-submit-btn {
      width: 100%;
      background: var(--color-brand-lime);
      color: var(--color-brand-dark-text);
      border: none;
      border-radius: var(--radius-full);
      padding: 13px 20px;
      font-size: var(--font-size-base);
      font-family: var(--font-family-sans);
      font-weight: var(--font-weight-bold);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      margin-top: 24px;
      transition: background-color var(--transition-fast), transform var(--transition-fast);
    }
    
    .auth-submit-btn:hover {
      background: var(--color-brand-lime-hover);
      transform: translateY(-1px);
    }

    .auth-demo-box {
      margin-top: 24px;
      padding: 12px 14px;
      background: rgba(212, 242, 106, 0.08);
      border: 1px dashed rgba(212, 242, 106, 0.3);
      border-radius: var(--radius-md);
      font-size: var(--font-size-xs);
      color: var(--color-sidebar-text);
      line-height: 1.6;
    }
    
    .auth-demo-box strong {
      color: var(--color-brand-lime);
    }
    
    .auth-demo-box code {
      background: rgba(255, 255, 255, 0.1);
      padding: 2px 6px;
      border-radius: var(--radius-xs);
      color: var(--color-brand-lime);
      font-family: var(--font-family-mono);
    }
  </style>
</head>
<body>
  <div class="auth-page-container">
    <div class="auth-bento-shell">
      <div class="auth-deco-glow" aria-hidden="true"></div>

      <div class="auth-header-group">
        <div class="auth-logo-badge" aria-hidden="true">🎓</div>
        <h1 class="auth-brand-title">CampusOS</h1>
        <p class="auth-brand-subtitle">Asisten Mahasiswa Berbasis Web Modern</p>
      </div>

      <?php if (!empty($errorMessage)): ?>
        <div style="margin-bottom: 18px; padding: 10px 14px; background: rgba(220, 38, 38, 0.15); border: 1px solid rgba(220, 38, 38, 0.3); border-radius: var(--radius-md); color: #FCA5A5; font-size: var(--font-size-xs); display: flex; align-items: center; gap: 8px;">
          <span>⚠️</span>
          <span><?= e($errorMessage) ?></span>
        </div>
      <?php endif; ?>

      <?php if ($flash): ?>
        <div style="margin-bottom: 18px; padding: 10px 14px; background: rgba(22, 163, 74, 0.15); border: 1px solid rgba(22, 163, 74, 0.3); border-radius: var(--radius-md); color: #86EFAC; font-size: var(--font-size-xs); display: flex; align-items: center; gap: 8px;">
          <span>✓</span>
          <span><?= e($flash['message']) ?></span>
        </div>
      <?php endif; ?>

      <form action="index.php?page=login" method="POST">
        <div class="auth-form-group">
          <label for="email" class="auth-label">Alamat Email Kampus</label>
          <input 
            type="email" 
            id="email" 
            name="email" 
            class="auth-input-field" 
            placeholder="mahasiswa@campusos.test" 
            value="<?= e($submittedEmail ?? 'mahasiswa@campusos.test') ?>"
            required 
            autofocus
          >
        </div>

        <div class="auth-form-group">
          <label for="password" class="auth-label">Kata Sandi</label>
          <input 
            type="password" 
            id="password" 
            name="password" 
            class="auth-input-field" 
            placeholder="Masukkan kata sandi..." 
            value="password123"
            required
          >
        </div>

        <button type="submit" class="auth-submit-btn">
          <span>Masuk ke Dashboard</span>
          <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24" aria-hidden="true" style="stroke: currentColor;">
            <polyline points="9 18 15 12 9 6"></polyline>
          </svg>
        </button>
      </form>

      <div class="auth-demo-box">
        <strong>Akun Demo Terintegrasi:</strong><br>
        Email: <code>mahasiswa@campusos.test</code><br>
        Password: <code>password123</code>
      </div>
    </div>
  </div>
</body>
</html>
