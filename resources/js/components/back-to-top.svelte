<script lang="ts">
  import { t } from '@/lib/i18n';
  import ChevronUp from '@lucide/svelte/icons/chevron-up';

  let visible = $state(false);

  $effect(() => {
    function onScroll(): void {
      visible = window.scrollY > 50;
    }

    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });

    return () => window.removeEventListener('scroll', onScroll);
  });

  function scrollToTop(): void {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
</script>

<button
  type="button"
  class="btn btn-primary d-flex align-items-center gap-2 back-to-top"
  class:back-to-top--visible={visible}
  onclick={scrollToTop}
  aria-label={t('a11y.back_to_top')}
>
  <ChevronUp size={18} />
</button>
