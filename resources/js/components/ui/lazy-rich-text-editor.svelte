<script lang="ts">
  import type { Component, ComponentProps } from 'svelte';
  import { onMount } from 'svelte';
  import type RichTextEditor from '@/components/ui/rich-text-editor.svelte';

  /**
   * Public-page wrapper around the Tiptap editor (~440 KB of JavaScript). The
   * editor chunk is fetched only once the field is about to scroll into view
   * or receives focus, so it never competes with the first paint; until then a
   * same-sized skeleton keeps the layout stable and the hidden input keeps the
   * form submittable. Admin screens import the editor directly.
   */
  type Props = ComponentProps<typeof RichTextEditor>;

  let props: Props = $props();

  let Editor = $state<Component<Props> | null>(null);
  let host: HTMLDivElement | undefined;

  onMount(() => {
    let loading = false;

    const load = () => {
      if (loading) {
        return;
      }

      loading = true;
      observer?.disconnect();
      void import('@/components/ui/rich-text-editor.svelte').then((module) => {
        Editor = module.default;
      });
    };

    const observer =
      typeof IntersectionObserver === 'undefined'
        ? null
        : new IntersectionObserver(
            (entries) => entries.some((entry) => entry.isIntersecting) && load(),
            { rootMargin: '200px' },
          );

    if (observer && host) {
      observer.observe(host);
    } else {
      load();
    }

    host?.addEventListener('focusin', load, { once: true });

    return () => observer?.disconnect();
  });
</script>

{#if Editor}
  <Editor {...props} />
{:else}
  <div
    bind:this={host}
    class="lazy-rte"
    class:lazy-rte-minimal={props.minimal}
    class:is-invalid={props.invalid}
    aria-busy="true"
  >
    <input type="hidden" name={props.name} value={props.value ?? ''} />
    <span class="lazy-rte-hint">{props.placeholder ?? 'Mulai menulis cerita Anda...'}</span>
  </div>
{/if}

<style>
  .lazy-rte {
    min-height: 30rem;
    padding: 1rem;
    border: 1px solid var(--neo-border-color);
    border-radius: var(--neo-border-radius, 0.5rem);
    background: var(--neo-body-bg);
    color: var(--neo-secondary-color);
  }

  .lazy-rte-minimal {
    min-height: 12.5rem;
  }

  .lazy-rte.is-invalid {
    border-color: var(--neo-danger);
  }
</style>
