<script lang="ts">
  const year = new Date().getFullYear();
  const version = import.meta.env.VITE_APP_VERSION ?? 'dev';
</script>

<footer class="footer">
  <div class="footer-start">
    <span
      >Hak Cipta 2026{year > 2026 ? `-${year}` : ''}
      <a href="/">Catatan Cak Adi</a>. Hak Cipta Dilindungi Undang-Undang.</span
    >
  </div>
  <div class="footer-end">
    <span class="version-badge">{version}</span>
  </div>
</footer>
