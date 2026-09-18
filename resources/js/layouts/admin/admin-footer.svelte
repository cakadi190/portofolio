<script lang="ts">
  const year = new Date().getFullYear();
  const version = import.meta.env.VITE_APP_VERSION ?? 'dev';
</script>

<footer class="footer">
  <div class="footer-start">
    <span
      >Copyright 2026{year > 2026 ? `-${year}` : ''}
      <a href="/">Gettix</a> by PT Batam Experience Indonesia. All rights reserved.</span
    >
  </div>
  <div class="footer-end">
    <span class="version-badge">{version}</span>
  </div>
</footer>
