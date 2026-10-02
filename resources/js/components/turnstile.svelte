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

    if (siteKey && container) {
      const target = container;
      loadScript()
        .then(() => {
          widgetId = turnstile()?.render(target, { sitekey: siteKey, theme: 'auto' });
        })
        .catch(() => {});
    }

    return () => {
      stopListening();
      if (widgetId) {
        turnstile()?.remove(widgetId);
      }
    };
  });
</script>

{#if siteKey}
  <div>
    <div bind:this={container}></div>
    {#if error}
      <div class="text-danger small mt-1">{error}</div>
    {/if}
  </div>
{/if}
