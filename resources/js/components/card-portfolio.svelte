<script lang="ts">
  import Folder from '@lucide/svelte/icons/folder';
  import Icon from '@iconify/svelte';
  import Link from '@/components/link.svelte';
  import { techIcon } from '@/lib/tech-icon';

  type Props = {
    name: string;
    slug: string;
    image: string;
    shortDesc: string | null;
    services: { name: string; color: string | null }[];
    technologies: string[];
    /** First card of a list: loaded eagerly with high priority (LCP candidate). */
    eager?: boolean;
  };

  let { name, slug, image, shortDesc, services, technologies, eager = false }: Props = $props();

  const service = $derived(services[0]);
</script>

<div class="wrapper">
  <Link href={`/portfolio/${slug}`} class="card h-100 overflow-hidden rounded-4 card-portfolio">
    <div class="position-relative">
      <img
        loading={eager ? 'eager' : 'lazy'}
        fetchpriority={eager ? 'high' : undefined}
        decoding="async"
        src={image}
        class="rounded-3 card-img-top"
        alt={name}
      />
      {#if service}
        <span class="post-taxonomy-tag post-card-category position-absolute">
          <Folder size={14} aria-hidden="true" />
          {service.name}
        </span>
      {/if}
    </div>

    <div class="card-body p-4">
      <div class="d-flex gap-2 mb-2 justify-content-between">
        <h2 class="card-title h5 mb-0">{name}</h2>
      </div>
      {#if shortDesc}
        <div class="card-text mb-3 opacity-75">{shortDesc}</div>
      {/if}

      {#if technologies.length}
        <div class="techstacks">
          {#each technologies as tech (tech)}
            <Icon icon={techIcon(tech)} width={24} height={24} />
          {/each}
        </div>
      {/if}
    </div>
  </Link>
</div>

<style>
  :global(.post-card-category) {
    right: 0.75rem;
    bottom: 0.75rem;
    z-index: 2;
    white-space: nowrap;
    background: var(--neo-body-bg);
  }
</style>
