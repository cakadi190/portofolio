<script lang="ts">
  import Icon from '@iconify/svelte';
  import Check from '@lucide/svelte/icons/check';
  import LinkIcon from '@lucide/svelte/icons/link';
  import Mail from '@lucide/svelte/icons/mail';
  import { portal } from '@/lib/dom';

  let {
    open = $bindable(false),
    url,
    title,
  }: {
    open?: boolean;
    url: string;
    title: string;
  } = $props();

  const labelId = `share-modal-${crypto.randomUUID()}`;

  let element = $state<HTMLElement>();
  let modal: { show(): void; hide(): void } | undefined;
  let copied = $state(false);

  const encodedUrl = $derived(encodeURIComponent(url));
  const encodedTitle = $derived(encodeURIComponent(title));

  const targets = $derived([
    {
      name: 'Facebook',
      icon: 'fa6-brands:facebook-f',
      href: `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`,
    },
    {
      name: 'X',
      icon: 'fa6-brands:x-twitter',
      href: `https://twitter.com/intent/tweet?url=${encodedUrl}&text=${encodedTitle}`,
    },
    {
      name: 'WhatsApp',
      icon: 'fa6-brands:whatsapp',
      href: `https://wa.me/?text=${encodedTitle}%20${encodedUrl}`,
    },
    {
      name: 'LinkedIn',
      icon: 'fa6-brands:linkedin-in',
      href: `https://www.linkedin.com/sharing/share-offsite/?url=${encodedUrl}`,
    },
    {
      name: 'Telegram',
      icon: 'fa6-brands:telegram',
      href: `https://t.me/share/url?url=${encodedUrl}&text=${encodedTitle}`,
    },
    {
      name: 'Email',
      icon: null,
      href: `mailto:?subject=${encodedTitle}&body=${encodedUrl}`,
    },
  ]);

  $effect(() => {
    const node = element;

    if (!node) {
      return;
    }

    let disposed = false;
    const onHidden = (): void => {
      open = false;
      copied = false;
    };

    node.addEventListener('hidden.bs.modal', onHidden);

    void import('bootstrap').then(({ Modal }) => {
      if (disposed) {
        return;
      }

      modal = Modal.getOrCreateInstance(node);

      if (open) {
        modal.show();
      }
    });

    return () => {
      disposed = true;
      node.removeEventListener('hidden.bs.modal', onHidden);
      modal = undefined;
    };
  });

  $effect(() => {
    if (open) {
      modal?.show();
    } else {
      modal?.hide();
    }
  });

  async function copyLink(): Promise<void> {
    try {
      await navigator.clipboard.writeText(url);
      copied = true;
      window.setTimeout(() => (copied = false), 2000);
    } catch {
      window.prompt('Salin tautan ini:', url);
    }
  }
</script>

<div
  use:portal
  bind:this={element}
  class="modal fade share-modal"
  role="dialog"
  aria-modal="true"
  aria-labelledby={labelId}
  tabindex="-1"
  aria-hidden="true"
>
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h2 class="modal-title h6 text-uppercase mb-0" id={labelId}>
          Bagikan
        </h2>
        <button
          type="button"
          class="btn-close"
          aria-label="Tutup"
          onclick={() => (open = false)}
        ></button>
      </div>
      <div class="modal-body">
        <div class="share-grid">
          {#each targets as target (target.name)}
            <a
              href={target.href}
              target="_blank"
              rel="noopener noreferrer"
              class="share-option"
              aria-label={`Bagikan lewat ${target.name}`}
              title={target.name}
            >
              {#if target.icon}
                <Icon icon={target.icon} width={20} height={20} />
              {:else}
                <Mail size={20} />
              {/if}
            </a>
          {/each}
          <button
            type="button"
            class="share-option"
            aria-label="Salin tautan"
            title={copied ? 'Tersalin' : 'Salin tautan'}
            onclick={copyLink}
          >
            {#if copied}
              <Check size={20} />
            {:else}
              <LinkIcon size={20} />
            {/if}
          </button>
        </div>
        <p class="small text-muted text-center mt-3 mb-0" aria-live="polite">
          {copied ? 'Tautan disalin' : ''}
        </p>
      </div>
    </div>
  </div>
</div>

<style>
  .share-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.75rem;
    justify-items: center;
  }

  .share-option {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.75rem;
    height: 2.75rem;
    padding: 0;
    color: var(--bs-body-color);
    background: var(--bs-secondary-bg);
    border: 0;
    border-radius: 50%;
    text-decoration: none;
    transition: background-color 0.15s;
  }

  .share-option:hover,
  .share-option:focus-visible {
    color: var(--bs-primary);
    background: var(--bs-tertiary-bg);
  }
</style>
