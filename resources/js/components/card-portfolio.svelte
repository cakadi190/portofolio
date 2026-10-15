<script lang="ts">
  import { badgeStyle } from '@/lib/badge';
  import Icon from '@iconify/svelte';
  import { Link } from '@inertiajs/svelte';
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
    <img
      loading={eager ? 'eager' : 'lazy'}
      fetchpriority={eager ? 'high' : undefined}
      decoding="async"
      src={image}
      class="rounded-3 card-img-top"
      alt={name}
    />

    <div class="card-body p-4">
      <div class="d-flex gap-2 mb-2 justify-content-between">
        <h2 class="card-title h5 mb-0">{name}</h2>
        {#if service}
          <div>
            <span class="badge" style={badgeStyle(service.color)}>
              {service.name}
            </span>
          </div>
        {/if}
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
