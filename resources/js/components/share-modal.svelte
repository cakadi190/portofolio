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
      color: '#1877f2',
      icon: 'fa6-brands:facebook-f',
      href: `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`,
    },
    {
      name: 'X',
      color: '#000000',
      icon: 'fa6-brands:x-twitter',
      href: `https://twitter.com/intent/tweet?url=${encodedUrl}&text=${encodedTitle}`,
    },
    {
      name: 'WhatsApp',
      color: '#25d366',
      icon: 'fa6-brands:whatsapp',
      href: `https://wa.me/?text=${encodedTitle}%20${encodedUrl}`,
    },
    {
      name: 'LinkedIn',
      color: '#0a66c2',
      icon: 'fa6-brands:linkedin-in',
      href: `https://www.linkedin.com/sharing/share-offsite/?url=${encodedUrl}`,
    },
    {
      name: 'Telegram',
      color: '#229ed9',
      icon: 'fa6-brands:telegram',
      href: `https://t.me/share/url?url=${encodedUrl}&text=${encodedTitle}`,
    },
    {
      name: 'Email',
      color: '#6b7280',
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
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h2 class="modal-title h5 mb-0" id={labelId}>Bagikan artikel</h2>
        <button
          type="button"
          class="btn-close"
          aria-label="Tutup"
          onclick={() => (open = false)}
        ></button>
      </div>
      <div class="modal-body">
        <p class="small text-body-secondary mb-3 share-title">{title}</p>

        <div class="share-grid">
          {#each targets as target (target.name)}
            <a
              href={target.href}
              target="_blank"
              rel="noopener noreferrer"
              class="share-item"
              aria-label={`Bagikan lewat ${target.name}`}
            >
              <span class="share-icon" style={`--share-color: ${target.color}`}>
                {#if target.icon}
                  <Icon icon={target.icon} width={22} height={22} />
                {:else}
                  <Mail size={22} />
                {/if}
              </span>
              <span class="share-label">{target.name}</span>
            </a>
          {/each}
        </div>

        <div class="share-link">
          <input
            type="text"
            class="form-control form-control-sm"
            value={url}
            readonly
            aria-label="Tautan artikel"
            onfocus={(event) => event.currentTarget.select()}
          />
          <button
            type="button"
            class="btn btn-sm btn-primary d-inline-flex align-items-center gap-2"
            aria-label="Salin tautan"
            onclick={copyLink}
          >
            {#if copied}
              <Check size={16} /> Tersalin
            {:else}
              <LinkIcon size={16} /> Salin
            {/if}
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  .share-title {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  .share-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem 0.5rem;
    margin-bottom: 1.25rem;
  }

  .share-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    color: var(--neo-body-color);
    text-decoration: none;
  }

  .share-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 3.25rem;
    height: 3.25rem;
    color: #fff;
    background: var(--share-color);
    border-radius: 50%;
    transition:
      transform 0.15s,
      box-shadow 0.15s;
  }

  .share-item:hover .share-icon,
  .share-item:focus-visible .share-icon {
    transform: translateY(-2px);
    box-shadow: 0 0.375rem 0.75rem rgb(0 0 0 / 0.2);
  }

  .share-label {
    font-size: 0.8125rem;
  }

  .share-link {
    display: flex;
    gap: 0.5rem;
  }
</style>
