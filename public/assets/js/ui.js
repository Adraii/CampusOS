/**
 * CampusOS — UI Interactivity Logic (Vanilla JS ES6+)
 * 100% Client-side State Management, Real-time Modals, Dynamic Chart, Mini Calendar, & Toasts.
 */

document.addEventListener('DOMContentLoaded', () => {
  'use strict';

  // ==========================================================================
  // 1. CONSTANTS & LOCAL STORAGE PREFIX
  // ==========================================================================

  const STORAGE_PREFIX = 'campusos_sandbox_';
  const KEY_TASKS = `${STORAGE_PREFIX}tasks`;
  const KEY_BUDGET = `${STORAGE_PREFIX}budget`;
  const KEY_SCHEDULE = `${STORAGE_PREFIX}schedule`;

  // Default Mock Datasets
  const DEFAULT_TASKS = [
    {
      id: 'task-1',
      title: 'Prototipe UI Web Portofolio',
      course: 'Interaksi Manusia & Komputer',
      deadline: 'Hari ini, 23.59 WIB',
      status: 'In progress',
      priority: 'Tinggi'
    },
    {
      id: 'task-2',
      title: 'Query Optimasi Basis Data',
      course: 'Basis Data Lanjut',
      deadline: 'Kemarin, 14.00 WIB',
      status: 'Completed',
      priority: 'Sedang'
    },
    {
      id: 'task-3',
      title: 'Laporan Analisis Kebutuhan',
      course: 'Rekayasa Perangkat Lunak',
      deadline: '18 Nov, 10.00 WIB',
      status: 'Upcoming',
      priority: 'Tinggi'
    }
  ];

  const DEFAULT_BUDGET = {
    totalWeekly: 600000,
    remaining: 450000,
    spent: 150000,
    status: 'Aman',
    dailyData: [
      { day: 'Min', label: 'Minggu', hours: 3.0, timeStr: '3j 00 mnt', dateStr: '13 Nov 2026', heightPct: 40, spending: 20000 },
      { day: 'Sen', label: 'Senin', hours: 5.25, timeStr: '5j 15 mnt', dateStr: '14 Nov 2026', heightPct: 65, spending: 35000 },
      { day: 'Sel', label: 'Selasa', hours: 2.65, timeStr: '2j 40 mnt', dateStr: '15 Nov 2026', heightPct: 35, spending: 25000 },
      { day: 'Rab', label: 'Rabu', hours: 6.75, timeStr: '6j 45 mnt', dateStr: '16 Nov 2026', heightPct: 85, spending: 45000 },
      { day: 'Kam', label: 'Kamis', hours: 4.0, timeStr: '4j 00 mnt', dateStr: '17 Nov 2026', heightPct: 50, spending: 15000 },
      { day: 'Jum', label: 'Jumat', hours: 2.15, timeStr: '2j 10 mnt', dateStr: '18 Nov 2026', heightPct: 25, spending: 10000 },
      { day: 'Sab', label: 'Sabtu', hours: 4.5, timeStr: '4j 30 mnt', dateStr: '19 Nov 2026', heightPct: 55, spending: 0 }
    ],
    activeDayIndex: 3 // Rabu
  };

  const DEFAULT_SCHEDULE = [
    {
      id: 'sch-1',
      title: 'Pemrograman Web Modern',
      meta: '08.00 - 10.30 • Lab Komputer 2',
      color: 'peach',
      iconSvg: '<polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline>'
    },
    {
      id: 'sch-2',
      title: 'Basis Data Lanjut',
      meta: '10.45 - 12.15 • Ruang Teori 301',
      color: 'lavender',
      iconSvg: '<ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>'
    },
    {
      id: 'sch-3',
      title: 'Interaksi Manusia & Komputer',
      meta: '13.00 - 15.00 • Studio Desain 1',
      color: 'mint',
      iconSvg: '<circle cx="12" cy="12" r="10"></circle><circle cx="12" cy="12" r="4"></circle>'
    },
    {
      id: 'sch-4',
      title: 'Rekayasa Perangkat Lunak',
      meta: '15.30 - 17.00 • Ruang D-204',
      color: 'lime',
      iconSvg: '<path d="M12 2L2 7l10 5 10-5-10-5z"></path><path d="M2 17l10 5 10-5"></path><path d="M2 12l10 5 10-5"></path>'
    }
  ];

  // Helper function: LocalStorage Get & Set
  function getStoredItem(key, fallback) {
    try {
      const data = localStorage.getItem(key);
      return data ? JSON.parse(data) : fallback;
    } catch (e) {
      console.warn(`[CampusOS] Gagal membaca storage untuk ${key}:`, e);
      return fallback;
    }
  }

  function setStoredItem(key, value) {
    try {
      localStorage.setItem(key, JSON.stringify(value));
    } catch (e) {
      console.warn(`[CampusOS] Gagal menyimpan storage untuk ${key}:`, e);
    }
  }

  // Active State Objects (Mendukung Injeksi Data Server-Side PHP via window.CAMPUSOS_INITIAL_DATA)
  const serverData = window.CAMPUSOS_INITIAL_DATA || null;
  let tasks = (serverData && serverData.tasks) ? serverData.tasks : getStoredItem(KEY_TASKS, DEFAULT_TASKS);
  let budget = (serverData && serverData.budget) ? serverData.budget : getStoredItem(KEY_BUDGET, DEFAULT_BUDGET);
  let schedule = (serverData && serverData.schedule) ? serverData.schedule : getStoredItem(KEY_SCHEDULE, DEFAULT_SCHEDULE);

  // Month navigation state
  const MONTH_NAMES = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
  ];
  let calCurrentYear = 2026;
  let calCurrentMonth = 10; // November (0-indexed)
  let calSelectedDate = 16;

  // ==========================================================================
  // 2. DOM REFERENCES
  // ==========================================================================

  // Shell & Layout
  const appShell = document.getElementById('appShell');
  const appSidebar = document.getElementById('appSidebar');
  const sidebarBackdrop = document.getElementById('sidebarBackdrop');
  const sidebarCollapseBtn = document.getElementById('sidebarCollapseBtn');
  const mobileNavToggle = document.getElementById('mobileNavToggle');
  const navLinks = document.querySelectorAll('.sidebar-nav .nav-link');
  const resetDemoBtn = document.getElementById('resetDemoBtn');
  const focusModeBtn = document.getElementById('focusModeBtn');

  // Greeting
  const greetingTitle = document.getElementById('greetingTitle');
  const greetingSubtitle = document.getElementById('greetingSubtitle');

  // Summary Cards
  const summaryTasksCount = document.getElementById('summaryTasksCount');
  const summaryTasksWarning = document.getElementById('summaryTasksWarning');
  const summaryTasksDoneVal = document.getElementById('summaryTasksDoneVal');
  const summaryTasksPriorityVal = document.getElementById('summaryTasksPriorityVal');
  const summaryRemainingBudget = document.getElementById('summaryRemainingBudget');
  const summarySpentBudget = document.getElementById('summarySpentBudget');
  const summaryBudgetStatus = document.getElementById('summaryBudgetStatus');

  // Middle Bento Grid
  const chartBarsTrack = document.getElementById('chartBarsTrack');
  const chartXLabels = document.getElementById('chartXLabels');
  const chartFilterBtn = document.getElementById('chartFilterBtn');
  const scheduleList = document.getElementById('scheduleList');
  const semesterFilterBtn = document.getElementById('semesterFilterBtn');
  const addTargetBtn = document.getElementById('addTargetBtn');

  // Right Panel
  const globalSearchInput = document.getElementById('globalSearchInput');
  const userProfileBtn = document.getElementById('userProfileBtn');
  const recordExpenseBtn = document.getElementById('recordExpenseBtn');
  const addTaskBtn = document.getElementById('addTaskBtn');
  const tasksList = document.getElementById('tasksList');
  const prevMonthBtn = document.getElementById('prevMonthBtn');
  const nextMonthBtn = document.getElementById('nextMonthBtn');
  const calMonthTitle = document.getElementById('calMonthTitle');
  const calendarDatesGrid = document.getElementById('calendarDatesGrid');
  const calendarFooterNote = document.getElementById('calendarFooterNote');

  // Modals & Forms
  const expenseModal = document.getElementById('expenseModal');
  const expenseForm = document.getElementById('expenseForm');
  const closeExpenseModalBtn = document.getElementById('closeExpenseModalBtn');
  const cancelExpenseBtn = document.getElementById('cancelExpenseBtn');

  const taskModal = document.getElementById('taskModal');
  const taskForm = document.getElementById('taskForm');
  const closeTaskModalBtn = document.getElementById('closeTaskModalBtn');
  const cancelTaskBtn = document.getElementById('cancelTaskBtn');

  // Toast Container
  const toastContainer = document.getElementById('toastContainer');

  // ==========================================================================
  // 3. UTILITY HELPERS
  // ==========================================================================

  function formatRupiah(number) {
    return new Intl.NumberFormat('id-ID').format(number);
  }

  function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  function getClosestHeightClass(pct) {
    const validHeights = [10, 15, 20, 25, 30, 35, 40, 45, 50, 55, 60, 65, 70, 75, 80, 85, 90, 95, 100];
    const rounded = Math.round(pct / 5) * 5;
    const clamped = Math.max(10, Math.min(100, rounded));
    return `h-${clamped}`;
  }

  // Toast System
  function showToast(message, iconName = 'info') {
    if (!toastContainer) return;

    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.setAttribute('role', 'alert');

    let iconSvg = '';
    if (iconName === 'success') {
      iconSvg = '<svg class="ui-icon ui-icon-xs toast-icon" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>';
    } else if (iconName === 'wallet') {
      iconSvg = '<svg class="ui-icon ui-icon-xs toast-icon" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line></svg>';
    } else if (iconName === 'task') {
      iconSvg = '<svg class="ui-icon ui-icon-xs toast-icon" viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>';
    } else {
      iconSvg = '<svg class="ui-icon ui-icon-xs toast-icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>';
    }

    toast.innerHTML = `
      ${iconSvg}
      <span class="toast-message">${escapeHtml(message)}</span>
    `;

    toastContainer.appendChild(toast);

    // Trigger enter transition
    requestAnimationFrame(() => {
      toast.classList.add('is-visible');
    });

    // Auto dismiss after 3.2s
    setTimeout(() => {
      toast.classList.remove('is-visible');
      setTimeout(() => {
        toast.remove();
      }, 300);
    }, 3200);
  }

  // Modal open & close
  function openModal(modalElem, focusInputId) {
    if (!modalElem) return;
    modalElem.classList.add('is-open');
    modalElem.setAttribute('aria-hidden', 'false');
    document.body.classList.add('no-scroll');

    if (focusInputId) {
      setTimeout(() => {
        const input = document.getElementById(focusInputId);
        if (input) input.focus();
      }, 50);
    }
  }

  function closeModal(modalElem) {
    if (!modalElem) return;
    modalElem.classList.remove('is-open');
    modalElem.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('no-scroll');
  }

  // ==========================================================================
  // 4. RENDERING MODULES
  // ==========================================================================

  // A. Render Greeting
  function renderGreeting() {
    const now = new Date();
    const hours = now.getHours();

    let salutation = 'Halo';
    if (hours >= 4 && hours < 11) {
      salutation = 'Selamat pagi';
    } else if (hours >= 11 && hours < 15) {
      salutation = 'Selamat siang';
    } else if (hours >= 15 && hours < 18) {
      salutation = 'Selamat sore';
    } else {
      salutation = 'Selamat malam';
    }

    if (greetingTitle) {
      greetingTitle.textContent = `${salutation}, Mahasiswa 👋`;
    }

    if (greetingSubtitle) {
      const activeCount = tasks.filter((t) => t.status !== 'Completed').length;
      greetingSubtitle.textContent = `Senin, 16 November 2026 • 3 Mata Kuliah & ${activeCount} Deadline Menunggu`;
    }
  }

  // B. Render Tasks List & Counters
  function renderTasks() {
    if (!tasksList) return;
    tasksList.innerHTML = '';

    if (tasks.length === 0) {
      tasksList.innerHTML = '<div class="empty-search-note">Belum ada tugas perkuliahan tercatat. Klik "+" untuk menambah tugas.</div>';
    } else {
      tasks.forEach((task) => {
        const article = document.createElement('article');
        const isDone = task.status === 'Completed';
        article.className = `task-item-card ${isDone ? 'is-completed' : ''}`;
        article.setAttribute('data-id', task.id);

        let badgeClass = 'upcoming';
        let iconColor = 'peach';
        if (task.status === 'In progress') {
          badgeClass = 'in-progress';
          iconColor = 'lavender';
        } else if (task.status === 'Completed') {
          badgeClass = 'completed';
          iconColor = 'mint';
        }

        article.innerHTML = `
          <div class="task-left-group">
            <div class="task-icon-box ${iconColor}" aria-hidden="true">
              <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24">
                <polyline points="9 11 12 14 22 4"></polyline>
                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
              </svg>
            </div>
            <div class="task-text-group">
              <h4 class="task-title" title="Klik untuk ubah status tugas">${escapeHtml(task.title)}</h4>
              <span class="task-date">${escapeHtml(task.deadline)} • ${escapeHtml(task.course)}</span>
            </div>
          </div>
          <span class="status-badge-pill ${badgeClass}" data-action="toggle-status" title="Klik untuk ganti status">${escapeHtml(task.status)}</span>
        `;

        tasksList.appendChild(article);
      });
    }

    // Update Summary Card 2 Counters
    const activeTasks = tasks.filter((t) => t.status !== 'Completed');
    const completedTasks = tasks.filter((t) => t.status === 'Completed');
    const urgentTasks = activeTasks.filter((t) => t.priority === 'Tinggi' || t.status === 'In progress');

    if (summaryTasksCount) summaryTasksCount.textContent = activeTasks.length.toString();
    if (summaryTasksWarning) summaryTasksWarning.textContent = urgentTasks.length.toString();
    if (summaryTasksDoneVal) summaryTasksDoneVal.textContent = `${completedTasks.length} Tugas`;
    if (summaryTasksPriorityVal) summaryTasksPriorityVal.textContent = `Tinggi (${urgentTasks.length})`;
  }

  // C. Render Budget Card & Statistics
  function renderBudget() {
    if (summaryRemainingBudget) {
      summaryRemainingBudget.textContent = `Rp ${formatRupiah(budget.remaining)}`;
    }
    if (summarySpentBudget) {
      summarySpentBudget.textContent = `Rp ${formatRupiah(budget.spent)}`;
    }
    if (summaryBudgetStatus) {
      if (budget.remaining > 200000) {
        summaryBudgetStatus.textContent = 'Aman';
        summaryBudgetStatus.className = 'summary-meta-val text-success';
      } else if (budget.remaining > 50000) {
        summaryBudgetStatus.textContent = 'Waspada';
        summaryBudgetStatus.className = 'summary-meta-val text-warning';
      } else {
        summaryBudgetStatus.textContent = 'Kritis';
        summaryBudgetStatus.className = 'summary-meta-val text-danger';
      }
    }
  }

  // D. Render 7-Day Vertical Bar Chart
  function renderChart() {
    if (!chartBarsTrack) return;
    chartBarsTrack.innerHTML = '';

    budget.dailyData.forEach((item, index) => {
      const isActive = index === budget.activeDayIndex;
      const heightClass = getClosestHeightClass(item.heightPct);

      const colDiv = document.createElement('div');
      colDiv.className = `bar-column ${isActive ? 'is-active' : ''}`;
      colDiv.setAttribute('data-index', index.toString());
      colDiv.setAttribute('title', `${item.label}: ${item.timeStr}${item.spending > 0 ? ' (Pengeluaran: Rp ' + formatRupiah(item.spending) + ')' : ''}`);

      let tooltipHtml = '';
      if (isActive) {
        const spendingSub = item.spending > 0 ? ` • Rp ${formatRupiah(item.spending)}` : '';
        tooltipHtml = `
          <div class="chart-floating-tooltip" role="tooltip">
            <div class="tooltip-dot" aria-hidden="true"></div>
            <div class="tooltip-text-group">
              <span class="tooltip-time">${escapeHtml(item.timeStr)}</span>
              <span class="tooltip-date">${escapeHtml(item.dateStr)}${escapeHtml(spendingSub)}</span>
            </div>
          </div>
        `;
      }

      colDiv.innerHTML = `
        ${tooltipHtml}
        <div class="bar-fill ${heightClass}"></div>
      `;

      chartBarsTrack.appendChild(colDiv);
    });

    // Sync X-Axis Labels
    if (chartXLabels) {
      const labels = chartXLabels.querySelectorAll('.x-label');
      labels.forEach((lbl, idx) => {
        lbl.classList.toggle('is-active', idx === budget.activeDayIndex);
      });
    }
  }

  // E. Render Daily Schedule List
  function renderSchedule() {
    if (!scheduleList) return;
    scheduleList.innerHTML = '';

    schedule.forEach((item) => {
      const row = document.createElement('div');
      row.className = 'schedule-row';
      row.setAttribute('role', 'button');
      row.setAttribute('tabindex', '0');
      row.setAttribute('data-id', item.id);

      row.innerHTML = `
        <div class="schedule-icon-badge ${item.color}" aria-hidden="true">
          <svg class="ui-icon ui-icon-sm" viewBox="0 0 24 24">
            ${item.iconSvg}
          </svg>
        </div>
        <div class="schedule-info">
          <span class="schedule-course-title">${escapeHtml(item.title)}</span>
          <span class="schedule-course-meta">${escapeHtml(item.meta)}</span>
        </div>
        <svg class="ui-icon ui-icon-xs text-muted" viewBox="0 0 24 24" aria-hidden="true">
          <polyline points="9 18 15 12 9 6"></polyline>
        </svg>
      `;

      scheduleList.appendChild(row);
    });
  }

  // F. Render Mini Calendar
  function renderCalendar() {
    if (!calendarDatesGrid || !calMonthTitle) return;

    calMonthTitle.textContent = `${MONTH_NAMES[calCurrentMonth]} ${calCurrentYear}`;
    calendarDatesGrid.innerHTML = '';

    // First day of month & total days
    const firstDayIndex = new Date(calCurrentYear, calCurrentMonth, 1).getDay(); // 0 is Sunday
    const daysInMonth = new Date(calCurrentYear, calCurrentMonth + 1, 0).getDate();
    const prevMonthDays = new Date(calCurrentYear, calCurrentMonth, 0).getDate();

    // Previous month filler days
    for (let i = firstDayIndex; i > 0; i--) {
      const prevDay = prevMonthDays - i + 1;
      const span = document.createElement('span');
      span.className = 'cal-date is-muted';
      span.textContent = prevDay.toString();
      calendarDatesGrid.appendChild(span);
    }

    // Current month days
    for (let day = 1; day <= daysInMonth; day++) {
      const span = document.createElement('span');
      const isSelected = day === calSelectedDate && calCurrentMonth === 10 && calCurrentYear === 2026;
      span.className = `cal-date ${isSelected ? 'is-active' : ''}`;
      span.textContent = day.toString();
      span.setAttribute('data-day', day.toString());
      span.setAttribute('title', `Pilih tanggal ${day} ${MONTH_NAMES[calCurrentMonth]}`);

      calendarDatesGrid.appendChild(span);
    }

    // Next month filler days to complete rows (7 * 5 = 35)
    const totalRendered = firstDayIndex + daysInMonth;
    const remainingSlots = (7 - (totalRendered % 7)) % 7;
    for (let nextDay = 1; nextDay <= remainingSlots; nextDay++) {
      const span = document.createElement('span');
      span.className = 'cal-date is-muted';
      span.textContent = nextDay.toString();
      calendarDatesGrid.appendChild(span);
    }

    // Update footer note
    if (calendarFooterNote) {
      calendarFooterNote.textContent = `● Hari ini: ${calSelectedDate} ${MONTH_NAMES[calCurrentMonth].slice(0, 3)} — 2 Jadwal Kuliah & 1 Deadline`;
    }
  }

  // ==========================================================================
  // 5. EVENT LISTENERS & INTERACTION HANDLERS
  // ==========================================================================

  // --- Task Status Cycle (Upcoming -> In progress -> Completed -> Upcoming) ---
  if (tasksList) {
    tasksList.addEventListener('click', (e) => {
      const card = e.target.closest('.task-item-card');
      if (!card) return;

      const taskId = card.getAttribute('data-id');
      const taskIndex = tasks.findIndex((t) => t.id === taskId);
      if (taskIndex === -1) return;

      const currentStatus = tasks[taskIndex].status;
      let nextStatus = 'In progress';
      if (currentStatus === 'Upcoming') {
        nextStatus = 'In progress';
      } else if (currentStatus === 'In progress') {
        nextStatus = 'Completed';
      } else {
        nextStatus = 'Upcoming';
      }

      tasks[taskIndex].status = nextStatus;
      setStoredItem(KEY_TASKS, tasks);
      renderTasks();

      const iconType = nextStatus === 'Completed' ? 'success' : 'task';
      showToast(`Status tugas "${tasks[taskIndex].title}" kini: ${nextStatus}`, iconType);
    });
  }

  // --- Modal Catat Pengeluaran ---
  if (recordExpenseBtn) {
    recordExpenseBtn.addEventListener('click', () => {
      openModal(expenseModal, 'expenseName');
    });
  }

  if (closeExpenseModalBtn) {
    closeExpenseModalBtn.addEventListener('click', () => {
      closeModal(expenseModal);
    });
  }

  if (cancelExpenseBtn) {
    cancelExpenseBtn.addEventListener('click', () => {
      closeModal(expenseModal);
    });
  }

  if (expenseForm) {
    expenseForm.addEventListener('submit', (e) => {
      // BACKEND SYNC HOOK:
      // Jika form diatur untuk submit native POST ke controller PHP,
      // izinkan form dikirim langsung ke server tanpa dicegat preventDefault.
      if (expenseForm.getAttribute('data-native-submit') === 'true') {
        return;
      }

      e.preventDefault();

      const nameInput = document.getElementById('expenseName');
      const amountInput = document.getElementById('expenseAmount');
      const categorySelect = document.getElementById('expenseCategory');
      const daySelect = document.getElementById('expenseDay');

      const name = nameInput ? nameInput.value.trim() : '';
      const amount = amountInput ? parseInt(amountInput.value, 10) : 0;
      const dayCode = daySelect ? daySelect.value : 'Rab';
      const category = categorySelect ? categorySelect.value : 'Lainnya';

      if (!name || isNaN(amount) || amount <= 0) {
        showToast('Mohon masukkan nama pengeluaran dan nominal valid.', 'info');
        return;
      }

      // Update budget state
      budget.spent += amount;
      budget.remaining = Math.max(0, budget.totalWeekly - budget.spent);

      // Increase selected day's spending & height
      const dayIndex = budget.dailyData.findIndex((d) => d.day === dayCode);
      if (dayIndex !== -1) {
        budget.dailyData[dayIndex].spending += amount;
        // Increase bar height by proportional increment (clamped to 95%)
        const heightBoost = Math.min(25, Math.round(amount / 5000));
        budget.dailyData[dayIndex].heightPct = Math.min(95, budget.dailyData[dayIndex].heightPct + heightBoost);
        budget.activeDayIndex = dayIndex;
      }

      setStoredItem(KEY_BUDGET, budget);
      renderBudget();
      renderChart();

      closeModal(expenseModal);
      expenseForm.reset();

      showToast(`✅ Pengeluaran Rp ${formatRupiah(amount)} (${name} - ${category}) berhasil dicatat!`, 'wallet');
    });
  }

  // --- Modal Tambah Tugas Baru ---
  if (addTaskBtn) {
    addTaskBtn.addEventListener('click', () => {
      openModal(taskModal, 'taskName');
    });
  }

  if (closeTaskModalBtn) {
    closeTaskModalBtn.addEventListener('click', () => {
      closeModal(taskModal);
    });
  }

  if (cancelTaskBtn) {
    cancelTaskBtn.addEventListener('click', () => {
      closeModal(taskModal);
    });
  }

  if (taskForm) {
    taskForm.addEventListener('submit', (e) => {
      // BACKEND SYNC HOOK:
      // Jika form diatur untuk submit native POST ke controller PHP,
      // izinkan form dikirim langsung ke server tanpa dicegat preventDefault.
      if (taskForm.getAttribute('data-native-submit') === 'true') {
        return;
      }

      e.preventDefault();

      const nameInput = document.getElementById('taskName');
      const courseInput = document.getElementById('taskCourse');
      const deadlineInput = document.getElementById('taskDeadline');
      const statusSelect = document.getElementById('taskStatusSelect');

      const title = nameInput ? nameInput.value.trim() : '';
      const course = courseInput ? courseInput.value.trim() : 'Mata Kuliah Pilihan';
      const deadline = deadlineInput ? deadlineInput.value.trim() : 'Segera';
      const status = statusSelect ? statusSelect.value : 'In progress';

      if (!title) {
        showToast('Mohon masukkan nama tugas.', 'info');
        return;
      }

      const newTask = {
        id: `task-${Date.now()}`,
        title,
        course,
        deadline,
        status,
        priority: status === 'Completed' ? 'Rendah' : 'Tinggi'
      };

      tasks.unshift(newTask);
      setStoredItem(KEY_TASKS, tasks);
      renderTasks();
      renderGreeting();

      closeModal(taskModal);
      taskForm.reset();

      showToast(`✅ Tugas baru "${title}" berhasil ditambahkan ke daftar!`, 'success');
    });
  }

  // --- Close modals on backdrop click or Escape key ---
  [expenseModal, taskModal].forEach((modal) => {
    if (!modal) return;
    modal.addEventListener('click', (e) => {
      if (e.target === modal) {
        closeModal(modal);
      }
    });
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      if (expenseModal && expenseModal.classList.contains('is-open')) closeModal(expenseModal);
      if (taskModal && taskModal.classList.contains('is-open')) closeModal(taskModal);
    }
  });

  // --- Live Search Filtering ---
  if (globalSearchInput) {
    globalSearchInput.addEventListener('input', (e) => {
      const query = e.target.value.toLowerCase().trim();

      // 1. Filter Schedule items
      const scheduleRows = document.querySelectorAll('.schedule-row');
      let matchedScheduleCount = 0;
      scheduleRows.forEach((row) => {
        const titleElem = row.querySelector('.schedule-course-title');
        const metaElem = row.querySelector('.schedule-course-meta');
        const titleText = titleElem ? titleElem.textContent.toLowerCase() : '';
        const metaText = metaElem ? metaElem.textContent.toLowerCase() : '';

        const isMatch = titleText.includes(query) || metaText.includes(query);
        row.classList.toggle('u-hidden', !isMatch);
        if (isMatch) matchedScheduleCount++;
      });

      // Show/hide schedule empty state
      let scheduleEmpty = document.getElementById('scheduleEmptyNote');
      if (matchedScheduleCount === 0 && query !== '') {
        if (!scheduleEmpty && scheduleList) {
          scheduleEmpty = document.createElement('div');
          scheduleEmpty.id = 'scheduleEmptyNote';
          scheduleEmpty.className = 'empty-search-note';
          scheduleEmpty.textContent = `Tidak ada jadwal kuliah yang cocok dengan "${query}".`;
          scheduleList.appendChild(scheduleEmpty);
        }
      } else if (scheduleEmpty) {
        scheduleEmpty.remove();
      }

      // 2. Filter Tasks items
      const taskCards = document.querySelectorAll('.task-item-card');
      let matchedTasksCount = 0;
      taskCards.forEach((card) => {
        const titleElem = card.querySelector('.task-title');
        const dateElem = card.querySelector('.task-date');
        const titleText = titleElem ? titleElem.textContent.toLowerCase() : '';
        const dateText = dateElem ? dateElem.textContent.toLowerCase() : '';

        const isMatch = titleText.includes(query) || dateText.includes(query);
        card.classList.toggle('u-hidden', !isMatch);
        if (isMatch) matchedTasksCount++;
      });

      // Show/hide task empty state
      let taskEmpty = document.getElementById('taskEmptyNote');
      if (matchedTasksCount === 0 && query !== '') {
        if (!taskEmpty && tasksList) {
          taskEmpty = document.createElement('div');
          taskEmpty.id = 'taskEmptyNote';
          taskEmpty.className = 'empty-search-note';
          taskEmpty.textContent = `Tidak ada tugas yang cocok dengan "${query}".`;
          tasksList.appendChild(taskEmpty);
        }
      } else if (taskEmpty) {
        taskEmpty.remove();
      }
    });
  }

  // --- Weekly Bar Chart Hover & Click ---
  if (chartBarsTrack) {
    chartBarsTrack.addEventListener('click', (e) => {
      const col = e.target.closest('.bar-column');
      if (!col) return;

      const index = parseInt(col.getAttribute('data-index'), 10);
      if (!isNaN(index)) {
        budget.activeDayIndex = index;
        setStoredItem(KEY_BUDGET, budget);
        renderChart();

        const selectedDay = budget.dailyData[index];
        showToast(`📊 Aktivitas ${selectedDay.label}: ${selectedDay.timeStr}`, 'info');
      }
    });

    chartBarsTrack.addEventListener('mouseover', (e) => {
      const col = e.target.closest('.bar-column');
      if (!col) return;

      const index = parseInt(col.getAttribute('data-index'), 10);
      if (!isNaN(index) && index !== budget.activeDayIndex) {
        budget.activeDayIndex = index;
        renderChart();
      }
    });
  }

  // Chart Period Filter toggle
  if (chartFilterBtn) {
    const periods = ['Mingguan', 'Bulanan', 'Semester'];
    let pIdx = 0;
    chartFilterBtn.addEventListener('click', () => {
      pIdx = (pIdx + 1) % periods.length;
      const span = chartFilterBtn.querySelector('span');
      if (span) span.textContent = periods[pIdx];
      showToast(`Filter grafik diubah ke tampilan ${periods[pIdx]}`, 'info');
    });
  }

  // Semester Target Filter toggle
  if (semesterFilterBtn) {
    const sems = ['Semester 5 (Ganjil)', 'Semester 4 (Genap)', 'Semua SKS'];
    let sIdx = 0;
    semesterFilterBtn.addEventListener('click', () => {
      sIdx = (sIdx + 1) % sems.length;
      const span = semesterFilterBtn.querySelector('span');
      if (span) span.textContent = sems[sIdx];
      showToast(`Menampilkan target untuk ${sems[sIdx]}`, 'info');
    });
  }

  if (addTargetBtn) {
    addTargetBtn.addEventListener('click', () => {
      showToast('🎯 Fitur penambahan modul SKS baru akan segera hadir di modul Akademik.', 'info');
    });
  }

  // --- Mini Calendar Navigation & Selection ---
  if (prevMonthBtn) {
    prevMonthBtn.addEventListener('click', () => {
      calCurrentMonth--;
      if (calCurrentMonth < 0) {
        calCurrentMonth = 11;
        calCurrentYear--;
      }
      renderCalendar();
      showToast(`Melihat kalender ${MONTH_NAMES[calCurrentMonth]} ${calCurrentYear}`, 'info');
    });
  }

  if (nextMonthBtn) {
    nextMonthBtn.addEventListener('click', () => {
      calCurrentMonth++;
      if (calCurrentMonth > 11) {
        calCurrentMonth = 0;
        calCurrentYear++;
      }
      renderCalendar();
      showToast(`Melihat kalender ${MONTH_NAMES[calCurrentMonth]} ${calCurrentYear}`, 'info');
    });
  }

  if (calendarDatesGrid) {
    calendarDatesGrid.addEventListener('click', (e) => {
      const dateCell = e.target.closest('.cal-date');
      if (!dateCell || dateCell.classList.contains('is-muted')) return;

      const day = parseInt(dateCell.getAttribute('data-day'), 10);
      if (!isNaN(day)) {
        calSelectedDate = day;
        renderCalendar();
        showToast(`📅 Tanggal ${day} ${MONTH_NAMES[calCurrentMonth]} ${calCurrentYear} dipilih.`, 'info');
      }
    });
  }

  // --- Sidebar Navigation Links & Toast ---
  navLinks.forEach((link) => {
    link.addEventListener('click', (e) => {
      const href = link.getAttribute('href');
      // Jika tautan mengarah ke rute query PHP dinamis, izinkan navigasi HTTP normal
      if (href && href !== '#' && !href.startsWith('javascript:')) {
        return;
      }

      e.preventDefault();

      navLinks.forEach((item) => {
        item.classList.remove('is-active');
        item.removeAttribute('aria-current');
      });

      link.classList.add('is-active');
      link.setAttribute('aria-current', 'page');

      const label = link.getAttribute('data-label') || link.textContent.trim();
      showToast(`📌 Membuka modul: ${label}`, 'info');

      // Auto-close drawer on mobile
      if (window.innerWidth <= 860 && appSidebar && appSidebar.classList.contains('is-open')) {
        toggleMobileDrawer(false);
      }
    });
  });

  // --- Sidebar Collapse (Desktop) ---
  if (sidebarCollapseBtn) {
    sidebarCollapseBtn.addEventListener('click', (e) => {
      e.preventDefault();
      if (!appSidebar) return;

      const isCollapsed = appSidebar.classList.toggle('is-collapsed');
      sidebarCollapseBtn.setAttribute('title', isCollapsed ? 'Perluas Sidebar' : 'Kecilkan Sidebar');
      sidebarCollapseBtn.setAttribute('aria-expanded', (!isCollapsed).toString());

      showToast(isCollapsed ? 'Sidebar dikecilkan ke mode ringkas.' : 'Sidebar diperluas.', 'info');
    });
  }

  // --- Mobile Drawer Toggle ---
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

  // --- Mode Fokus Belajar Button ---
  if (focusModeBtn) {
    let focusActive = false;
    focusModeBtn.addEventListener('click', () => {
      focusActive = !focusActive;
      if (focusActive) {
        focusModeBtn.classList.add('is-active');
        showToast('🎯 Mode Fokus Belajar aktif! Notifikasi diheningkan.', 'success');
      } else {
        focusModeBtn.classList.remove('is-active');
        showToast('Mode Fokus Belajar dinonaktifkan.', 'info');
      }
    });
  }

  // --- User Profile Avatar ---
  if (userProfileBtn) {
    userProfileBtn.addEventListener('click', () => {
      showToast('👤 Mahasiswa: John Doe • NIM: 20240801001 (Semester 5)', 'info');
    });
  }

  // --- Reset Demo Data Button ---
  if (resetDemoBtn) {
    resetDemoBtn.addEventListener('click', () => {
      if (confirm('Apakah Anda yakin ingin mengembalikan seluruh data demo ke kondisi awal?')) {
        localStorage.removeItem(KEY_TASKS);
        localStorage.removeItem(KEY_BUDGET);
        localStorage.removeItem(KEY_SCHEDULE);

        // Reset memory states
        tasks = JSON.parse(JSON.stringify(DEFAULT_TASKS));
        budget = JSON.parse(JSON.stringify(DEFAULT_BUDGET));
        schedule = JSON.parse(JSON.stringify(DEFAULT_SCHEDULE));

        // Re-render
        renderGreeting();
        renderTasks();
        renderBudget();
        renderChart();
        renderSchedule();
        renderCalendar();

        showToast('🔄 Seluruh data demo CampusOS berhasil di-reset ke kondisi awal!', 'success');
      }
    });
  }

  // ==========================================================================
  // 6. INITIALIZATION
  // ==========================================================================

  renderGreeting();
  renderTasks();
  renderBudget();
  renderChart();
  renderSchedule();
  renderCalendar();

  console.log('[CampusOS] UI Interactivity Controller berhasil diinisialisasi.');
});
