  </div><!-- /.page-body -->
</div><!-- /.main-wrap -->

<script src="<?= BASE_URL ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>assets/vendor/chart.umd.min.js"></script>
<script>
if (typeof BASE_URL === 'undefined') var BASE_URL = '<?= BASE_URL ?>';
</script>
<script src="<?= BASE_URL ?>assets/js/main.js"></script>
<?php if (isset($extra_js)) echo $extra_js; ?>
</body>
</html>
