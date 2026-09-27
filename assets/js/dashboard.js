/**
 * AgriTrak ERP - Dashboard Charts initialization using Chart.js
 */

document.addEventListener('DOMContentLoaded', function () {
  const incomeCtx = document.getElementById('incomeChart');
  if (incomeCtx) {
    new Chart(incomeCtx, {
      type: 'bar',
      data: {
        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        datasets: [{
          label: 'Daily Revenue (₹)',
          data: [4200, 6800, 5250, 9600, 4400, 11200, 8500],
          backgroundColor: '#2b7fff',
          borderRadius: 6,
          borderSkipped: false
        }, {
          label: 'Diesel Expenses (₹)',
          data: [1500, 2200, 1800, 3100, 1600, 4200, 2900],
          backgroundColor: '#ef4444',
          borderRadius: 6,
          borderSkipped: false
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'top',
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function(value) {
                return '₹' + value;
              }
            }
          }
        }
      }
    });
  }

  const usageCtx = document.getElementById('tractorUsageChart');
  if (usageCtx) {
    new Chart(usageCtx, {
      type: 'doughnut',
      data: {
        labels: ['Available', 'Working', 'In Service', 'Breakdown'],
        datasets: [{
          data: [2, 1, 1, 1],
          backgroundColor: [
            '#10b981', // Available (Green)
            '#2b7fff', // Working (Blue)
            '#f59e0b', // Service (Yellow)
            '#ef4444'  // Breakdown (Red)
          ],
          borderWidth: 0,
          hoverOffset: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom'
          }
        },
        cutout: '70%'
      }
    });
  }
});
