<script lang="ts">
  import ArrowUp from '@lucide/svelte/icons/arrow-up';

  let {
    scroller,
    bottom = '1.5rem',
  }: { scroller?: HTMLElement; bottom?: string } = $props();

  const THRESHOLD = 240;

  let visible = $state(false);

  $effect(() => {
    if (!scroller) {
      return;
    }

    const onScroll = () => {
      visible = scroller.scrollTop > THRESHOLD;
    };

    onScroll();
    scroller.addEventListener('scroll', onScroll, { passive: true });

    return () => scroller.removeEventListener('scroll', onScroll);
  });

  function scrollToTop(): void {
    scroller?.scrollTo({ top: 0, behavior: 'smooth' });
  }
</script>

<button
  type="button"
  class="back-to-top"
  class:back-to-top--visible={visible}
  hidden={!visible}
  aria-label="Back to top"
  title="Back to top"
  style:--neo-back-to-top-bottom={bottom}
  onclick={scrollToTop}
>
  <ArrowUp size={16} aria-hidden="true" />
</button>
