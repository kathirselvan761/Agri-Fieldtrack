/**
 * AgriTrak ERP - Main JavaScript Application File
 */

document.addEventListener('DOMContentLoaded', function () {
  // 0. Sync Language Preference to LocalStorage
  if (window.currentLang) {
    localStorage.setItem('agri_lang', window.currentLang);
  }

  // 1. Dark / Light Mode Switcher
  const themeToggleBtn = document.getElementById('themeToggleBtn');
  const currentTheme = localStorage.getItem('agri_theme') || 'light';
  
  if (currentTheme === 'dark') {
    document.documentElement.setAttribute('data-bs-theme', 'dark');
    if (themeToggleBtn) themeToggleBtn.innerHTML = '<i class="bi bi-sun-fill text-warning"></i>';
  } else {
    document.documentElement.setAttribute('data-bs-theme', 'light');
    if (themeToggleBtn) themeToggleBtn.innerHTML = '<i class="bi bi-moon-stars-fill"></i>';
  }

  if (themeToggleBtn) {
    themeToggleBtn.addEventListener('click', function () {
      const mode = document.documentElement.getAttribute('data-bs-theme');
      const nextMode = mode === 'dark' ? 'light' : 'dark';
      document.documentElement.setAttribute('data-bs-theme', nextMode);
      localStorage.setItem('agri_theme', nextMode);
      this.innerHTML = nextMode === 'dark' 
        ? '<i class="bi bi-sun-fill text-warning"></i>' 
        : '<i class="bi bi-moon-stars-fill"></i>';
    });
  }

  // 2. Sidebar Toggle Mobile & Desktop
  const sidebar = document.getElementById('sidebar');
  const toggleBtn = document.getElementById('sidebarToggleBtn');
  const backdrop = document.createElement('div');
  backdrop.className = 'sidebar-backdrop';
  document.body.appendChild(backdrop);

  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', function () {
      if (window.innerWidth < 992) {
        sidebar.classList.toggle('show');
        backdrop.classList.toggle('show');
      } else {
        sidebar.classList.toggle('collapsed');
      }
    });

    backdrop.addEventListener('click', function () {
      sidebar.classList.remove('show');
      backdrop.classList.remove('show');
    });
  }

  // 3. Initialize DataTables if present
  if (window.jQuery && $.fn.DataTable) {
    const t = window.translations || {};
    $('.dataTable').DataTable({
      responsive: true,
      pageLength: 10,
      language: {
        search: "_INPUT_",
        searchPlaceholder: t.searchPlaceholder || "Search records...",
        emptyTable: t.noRecordsFound || "No records found.",
        zeroRecords: t.noRecordsFound || "No matching records found"
      }
    });
  }

  // 4. Auto calculation logic for Work Entry Form
  const calcQuantity = document.getElementById('calcQuantity');
  const calcRate = document.getElementById('calcRate');
  const calcTotal = document.getElementById('calcTotal');
  const calcAdvance = document.getElementById('calcAdvance');
  const calcBalance = document.getElementById('calcBalance');
  const calcStatus = document.getElementById('calcStatus');

  function calculateWorkEntry() {
    if (calcQuantity && calcRate && calcTotal) {
      const qty = parseFloat(calcQuantity.value) || 0;
      const rate = parseFloat(calcRate.value) || 0;
      const total = qty * rate;
      calcTotal.value = total.toFixed(2);

      const advance = parseFloat(calcAdvance ? calcAdvance.value : 0) || 0;
      const balance = total - advance;

      if (calcBalance) calcBalance.value = balance.toFixed(2);

      if (calcStatus) {
        if (balance <= 0 && total > 0) {
          calcStatus.value = 'Paid';
        } else if (advance > 0 && balance > 0) {
          calcStatus.value = 'Partial';
        } else {
          calcStatus.value = 'Pending';
        }
      }
    }
  }

  if (calcQuantity) calcQuantity.addEventListener('input', calculateWorkEntry);
  if (calcRate) calcRate.addEventListener('input', calculateWorkEntry);
  if (calcAdvance) calcAdvance.addEventListener('input', calculateWorkEntry);

  // 5. Work Type Rate Auto Selection
  const workTypeSelect = document.getElementById('workTypeSelect');
  if (workTypeSelect && calcRate) {
    workTypeSelect.addEventListener('change', function () {
      const selectedOption = this.options[this.selectedIndex];
      const rate = selectedOption.getAttribute('data-rate');
      if (rate !== null) {
        calcRate.value = parseFloat(rate).toFixed(2);
        calculateWorkEntry();
      }
    });
  }
});

// Helper Function for Confirmation Alerts
function confirmDelete(deleteUrl) {
  const t = window.translations || {};
  if (window.Swal) {
    Swal.fire({
      title: t.confirmDeleteTitle || 'Are you sure?',
      text: t.confirmDeleteMsg || "You won't be able to revert this!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#ef4444',
      cancelButtonColor: '#64748b',
      confirmButtonText: t.deleteConfirmBtn || 'Yes, delete it!',
      cancelButtonText: t.cancel || 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = deleteUrl;
      }
    });
  } else {
    if (confirm(t.confirmDeleteTitle || 'Are you sure you want to delete this record?')) {
      window.location.href = deleteUrl;
    }
  }
}

// Print Receipt Helper
function printReceipt(elementId) {
  const printContents = document.getElementById(elementId).innerHTML;
  const originalContents = document.body.innerHTML;

  document.body.innerHTML = '<div style="padding:20px;">' + printContents + '</div>';
  window.print();
  document.body.innerHTML = originalContents;
  window.location.reload();
}
