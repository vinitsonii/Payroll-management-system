// PayrollPro — main.js (Responsive Edition)

document.addEventListener('DOMContentLoaded', function () {

  const sidebar   = document.getElementById('sidebar');
  const mainWrap  = document.getElementById('mainWrap');
  const toggleBtn = document.getElementById('sidebarToggle');
  const overlay   = document.getElementById('sidebarOverlay');

  // ── Mobile sidebar ────────────────────────────────────────────
  function openMobileSidebar() {
    if (!sidebar) return;
    sidebar.classList.add('open');
    if (overlay) overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
  function closeMobileSidebar() {
    if (!sidebar) return;
    sidebar.classList.remove('open');
    if (overlay) overlay.classList.remove('active');
    document.body.style.overflow = '';
  }

  // ── Desktop sidebar collapse ──────────────────────────────────
  function toggleDesktopSidebar() {
    if (!sidebar) return;
    const isCollapsed = sidebar.classList.toggle('collapsed');
    if (mainWrap) mainWrap.classList.toggle('sidebar-collapsed', isCollapsed);
    try { localStorage.setItem('sb_collapsed', isCollapsed ? '1' : '0'); } catch(e) {}
    // Resize charts after transition
    setTimeout(function() {
      if (window.Chart) {
        Object.values(Chart.instances || {}).forEach(c => { try { c.resize(); } catch(e){} });
      }
    }, 300);
  }

  // ── Restore collapse state on desktop ────────────────────────
  if (window.innerWidth > 767) {
    try {
      if (localStorage.getItem('sb_collapsed') === '1' && sidebar) {
        sidebar.classList.add('collapsed');
        if (mainWrap) mainWrap.classList.add('sidebar-collapsed');
      }
    } catch(e) {}
  }

  // ── Toggle button handler ─────────────────────────────────────
  if (toggleBtn) {
    toggleBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      if (window.innerWidth <= 767) {
        sidebar && sidebar.classList.contains('open')
          ? closeMobileSidebar()
          : openMobileSidebar();
      } else {
        toggleDesktopSidebar();
      }
    });
  }

  // ── Overlay click closes sidebar ─────────────────────────────
  if (overlay) {
    overlay.addEventListener('click', closeMobileSidebar);
  }

  // ── Nav link click closes on mobile ──────────────────────────
  document.querySelectorAll('.sb-link').forEach(function (link) {
    link.addEventListener('click', function () {
      if (window.innerWidth <= 767) closeMobileSidebar();
    });
  });

  // ── Resize: ensure correct state ─────────────────────────────
  window.addEventListener('resize', function () {
    if (window.innerWidth > 767) {
      closeMobileSidebar();
    }
  });

  // ── Auto-dismiss alerts ───────────────────────────────────────
  document.querySelectorAll('.alert.auto-dismiss').forEach(function (el) {
    setTimeout(function () {
      try { bootstrap.Alert.getOrCreateInstance(el).close(); } catch(e) {}
    }, 5000);
  });

  // ── Confirm dialogs ───────────────────────────────────────────
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      if (!confirm(this.dataset.confirm || 'Are you sure?')) e.preventDefault();
    });
  });

  // ── Select-all checkbox ───────────────────────────────────────
  const selectAll = document.getElementById('selectAll');
  if (selectAll) {
    selectAll.addEventListener('change', function () {
      document.querySelectorAll('.row-check:not(:disabled)')
               .forEach(c => c.checked = this.checked);
    });
  }

  // ── Dept → Designation AJAX ───────────────────────────────────
  const deptSel  = document.getElementById('department_id');
  const desigSel = document.getElementById('designation_id');
  if (deptSel && desigSel) {
    deptSel.addEventListener('change', function () {
      fetch((typeof BASE_URL !== 'undefined' ? BASE_URL : '') + 'employees/get_designations.php?dept=' + this.value)
        .then(r => r.json())
        .then(data => {
          desigSel.innerHTML = '<option value="">-- Select Designation --</option>';
          data.forEach(d => {
            const o = document.createElement('option');
            o.value = d.id; o.textContent = d.title;
            desigSel.appendChild(o);
          });
        }).catch(() => {});
    });
  }

  // ── Live gross salary calculator ──────────────────────────────
  function calcGross() {
    const ids = ['basic_salary','hra','da','ta','medical_allowance','other_allowance'];
    let total = 0;
    ids.forEach(id => {
      const el = document.getElementById(id);
      if (el) total += parseFloat(el.value) || 0;
    });
    const disp = document.getElementById('grossDisplay');
    if (disp) disp.textContent = '₹' + total.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});
  }
  document.querySelectorAll('#basic_salary,#hra,#da,#ta,#medical_allowance,#other_allowance')
          .forEach(el => el.addEventListener('input', calcGross));
  calcGross();

  // ── Mark-all attendance ───────────────────────────────────────
  window.markAllAttendance = function (status) {
    document.querySelectorAll('.att-select').forEach(sel => sel.value = status);
  };

  // ── Wrap action buttons on narrow screens ─────────────────────
  function fixActionBtns() {
    document.querySelectorAll('td .d-flex.gap-1').forEach(function (el) {
      el.style.flexWrap = window.innerWidth < 576 ? 'wrap' : '';
    });
  }
  fixActionBtns();
  window.addEventListener('resize', fixActionBtns);

});
