<script lang="ts">
  import { page, router } from '@inertiajs/svelte';
  import { onMount } from 'svelte';

  type TurnstileApi = {
    render: (container: HTMLElement, options: Record<string, unknown>) => string;
    reset: (widgetId?: string) => void;
    remove: (widgetId?: string) => void;
  };

  const SCRIPT_ID = 'cloudflare-turnstile';
  const SCRIPT_SRC = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';

  let { error }: { error?: string } = $props();

  let container: HTMLDivElement | undefined = $state();
  let widgetId: string | undefined;

  const siteKey = $derived(
    (page.props as { turnstileSiteKey?: string | null }).turnstileSiteKey ?? null,
  );

  function turnstile(): TurnstileApi | undefined {
    return (window as unknown as { turnstile?: TurnstileApi }).turnstile;
  }

  function loadScript(): Promise<void> {
    if (turnstile()) {
      return Promise.resolve();
    }

    return new Promise((resolve, reject) => {
      const existing = document.getElementById(SCRIPT_ID);
      const script = existing ?? document.createElement('script');

      script.addEventListener('load', () => resolve(), { once: true });
      script.addEventListener('error', () => reject(new Error('Turnstile gagal dimuat')), { once: true });

      if (!existing) {
        script.id = SCRIPT_ID;
        (script as HTMLScriptElement).src = SCRIPT_SRC;
        (script as HTMLScriptElement).async = true;
        document.head.appendChild(script);
      }
    });
  }

  onMount(() => {
    // A token is single-use, so fetch a fresh one after every submission.
    const stopListening = router.on('finish', () => {
      if (widgetId) {
        turnstile()?.reset(widgetId);
      }
    });

    // The widget (and Cloudflare's ~600 KB challenge script) is fetched only when
    // the field is about to scroll into view or the visitor starts using the
    // form, so it never competes with first paint or counts against TBT.
    let started = false;
    let observer: IntersectionObserver | null = null;
    const form = container?.closest('form') ?? null;

    const start = () => {
      if (started || !siteKey || !container) {
        return;
      }

      started = true;
      observer?.disconnect();
      form?.removeEventListener('focusin', start);
      form?.removeEventListener('pointerdown', start);

      const target = container;
      loadScript()
        .then(() => {
          widgetId = turnstile()?.render(target, { sitekey: siteKey, theme: 'auto' });
        })
        .catch(() => {});
    };

    if (siteKey && container) {
      form?.addEventListener('focusin', start, { once: true });
      form?.addEventListener('pointerdown', start, { once: true });

      if (typeof IntersectionObserver === 'undefined') {
        start();
      } else {
        observer = new IntersectionObserver(
          (entries) => entries.some((entry) => entry.isIntersecting) && start(),
          { rootMargin: '300px' },
        );
        observer.observe(container);
      }
    }

    return () => {
      stopListening();
      observer?.disconnect();
      form?.removeEventListener('focusin', start);
      form?.removeEventListener('pointerdown', start);
      if (widgetId) {
        turnstile()?.remove(widgetId);
      }
    };
  });
</script>

{#if siteKey}
  <div>
    <div bind:this={container} class="turnstile-slot"></div>
    {#if error}
      <div class="text-danger small mt-1">{error}</div>
    {/if}
  </div>
{/if}

<style>
  /* Turnstile's normal widget is 65px tall; reserving it keeps the form from shifting when it loads. */
  .turnstile-slot {
    min-height: 65px;
  }
</style>
