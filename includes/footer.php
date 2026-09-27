    </main> <!-- End main-content -->
    <footer class="mt-auto py-3 bg-surface border-top text-center text-muted fs-7" style="border-color: var(--border-color) !important;">
      <div class="container-fluid">
        <span class="fw-medium">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($company['company_name']); ?></span> — Powered by <strong>AgriTrak ERP System</strong>
      </div>
    </footer>
  </div> <!-- End content-wrapper -->
</div> <!-- End wrapper -->

<!-- jQuery & Bootstrap 5 Bundle -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<!-- Chart.js JS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<!-- FullCalendar JS -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
  window.currentLang = <?php echo json_encode(getCurrentLang()); ?>;
  window.translations = {
    search: <?php echo json_encode(__('search', 'Search')); ?>,
    searchPlaceholder: <?php echo json_encode(__('search_placeholder', 'Search records...')); ?>,
    confirmDeleteTitle: <?php echo json_encode(__('confirm_delete_title', 'Are you sure?')); ?>,
    confirmDeleteMsg: <?php echo json_encode(__('confirm_delete_msg', "You won't be able to revert this!")); ?>,
    deleteConfirmBtn: <?php echo json_encode(__('delete_confirm_btn', 'Yes, delete it!')); ?>,
    cancel: <?php echo json_encode(__('cancel', 'Cancel')); ?>,
    noRecordsFound: <?php echo json_encode(__('no_records_found', 'No records found.')); ?>
  };
</script>
<!-- Custom Main JS -->
<script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
</body>
</html>
