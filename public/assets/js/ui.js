/**
 * CampusOS — UI Interactivity Controller (Vanilla JS ES6+)
 * Hybrid SSR Client Layer: Drawer, Modals, Edit Populate, Interactive Charts, & Flash Alerts
 * Tidak bergantung pada localStorage untuk data inti; seluruh simpan data menggunakan POST PHP / fetch().
 */

document.addEventListener('DOMContentLoaded', () => {
  'use strict';

  // ==========================================================================
  // 1. MOBILE SIDEBAR DRAWER
  // ==========================================================================
  const appSidebar = document.getElementById('appSidebar');
  const sidebarBackdrop = document.getElementById('sidebarBackdrop');
  const mobileNavToggle = document.getElementById('mobileNavToggle');

  function toggleMobileDrawer(forceState) {
    if (!appSidebar || !sidebarBackdrop) return;
    const isOpen = typeof forceState === 'boolean'
      ? forceState
      : !appSidebar.classList.contains('is-open');

    appSidebar.classList.toggle('is-open', isOpen);
    sidebarBackdrop.classList.toggle('is-visible', isOpen);
    document.body.classList.toggle('no-scroll', isOpen);
  }

  if (mobileNavToggle) {
    mobileNavToggle.addEventListener('click', (e) => {
      e.preventDefault();
      toggleMobileDrawer(true);
    });
  }

  if (sidebarBackdrop) {
    sidebarBackdrop.addEventListener('click', () => {
      toggleMobileDrawer(false);
    });
  }

  window.addEventListener('resize', () => {
    if (window.innerWidth > 860 && appSidebar && appSidebar.classList.contains('is-open')) {
      toggleMobileDrawer(false);
    }
  });

  // ==========================================================================
  // 2. DECLARATIVE MODAL SYSTEM (data-open-modal / data-close-modal)
  // ==========================================================================
  function openModalById(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('no-scroll');

    const firstInput = modal.querySelector('input:not([type="hidden"]), select, textarea');
    if (firstInput) {
      setTimeout(() => firstInput.focus(), 60);
    }
  }

  function closeModalElement(modal) {
    if (!modal) return;
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('no-scroll');
  }

  document.querySelectorAll('[data-open-modal]').forEach((trigger) => {
    trigger.addEventListener('click', (e) => {
      e.preventDefault();
      const targetId = trigger.getAttribute('data-open-modal');
      if (targetId) openModalById(targetId);
    });
  });

  document.querySelectorAll('[data-close-modal]').forEach((closer) => {
    closer.addEventListener('click', (e) => {
      e.preventDefault();
      const targetId = closer.getAttribute('data-close-modal');
      const modal = targetId ? document.getElementById(targetId) : closer.closest('.modal-backdrop');
      closeModalElement(modal);
    });
  });

  document.querySelectorAll('.modal-backdrop').forEach((backdrop) => {
    backdrop.addEventListener('click', (e) => {
      if (e.target === backdrop) {
        closeModalElement(backdrop);
      }
    });
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-backdrop.is-open').forEach((modal) => {
        closeModalElement(modal);
      });
    }
  });

  // ==========================================================================
  // 3. EDIT TASK MODAL POPULATOR (views/tasks/index.php)
  // ==========================================================================
  document.querySelectorAll('[data-edit-task]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const raw = btn.getAttribute('data-edit-task');
      if (!raw) return;

      try {
        const task = JSON.parse(raw);
        const modal = document.getElementById('taskEditModal');
        if (!modal) return;

        const setVal = (id, val) => {
          const el = document.getElementById(id);
          if (el) el.value = val ?? '';
        };

        setVal('editTaskId', task.id);
        setVal('editTaskTitle', task.title);
        setVal('editTaskCourseId', task.course_id || '');
        setVal('editTaskDeadlineDate', task.deadline_date);
        setVal('editTaskDeadlineTime', (task.deadline_time || '23:59').substring(0, 5));
        setVal('editTaskEstimatedHours', task.estimated_hours || '2.0');
        setVal('editTaskPriority', task.priority || 'medium');
        setVal('editTaskStatus', task.status || 'pending');
        setVal('editTaskDescription', task.description || '');

        openModalById('taskEditModal');
      } catch (err) {
        console.warn('[CampusOS] Gagal mem-parse data tugas untuk edit:', err);
      }
    });
  });

  // ==========================================================================
  // 4. INTERACTIVE CSS BAR CHART (DASHBOARD)
  // ==========================================================================
  const barCols = document.querySelectorAll('.bar-col[data-day-label]');
  const chartSummaryText = document.getElementById('chartActiveSummary');

  barCols.forEach((col) => {
    col.addEventListener('click', () => {
      barCols.forEach((c) => c.classList.remove('is-active'));
      col.classList.add('is-active');

      if (chartSummaryText) {
        const day = col.getAttribute('data-day-label') || '';
        const hours = col.getAttribute('data-hours') || '0';
        chartSummaryText.textContent = `${day}: ${hours} jam sesi belajar & kuliah`;
      }
    });
  });

  // ==========================================================================
  // 5. FLASH ALERT AUTO-DISMISS & MANUAL CLOSE
  // ==========================================================================
  const flashBanner = document.getElementById('flashAlertBanner');
  if (flashBanner) {
    const closeBtn = flashBanner.querySelector('[data-dismiss-alert]');
    const dismiss = () => {
      flashBanner.style.transition = 'opacity 250ms ease, transform 250ms ease';
      flashBanner.style.opacity = '0';
      flashBanner.style.transform = 'translateY(-6px)';
      setTimeout(() => flashBanner.remove(), 260);
    };

    if (closeBtn) {
      closeBtn.addEventListener('click', dismiss);
    }

    setTimeout(dismiss, 5500);
  }
});
