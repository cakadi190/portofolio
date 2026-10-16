<script lang="ts">
  import { t } from '@/lib/i18n';
  const year = new Date().getFullYear();
  const version = import.meta.env.VITE_APP_VERSION ?? 'dev';
</script>

<footer class="footer">
  <div class="footer-start">
    <span
      >Hak Cipta 2026{year > 2026 ? `-${year}` : ''}
      <a href="/">{t('admin.catatan_cak_adi')}</a>{t('admin.hak_cipta_dilindungi_undang_undang')}</span
    >
  </div>
  <div class="footer-end">
    <span class="version-badge">{version}</span>
  </div>
</footer>
