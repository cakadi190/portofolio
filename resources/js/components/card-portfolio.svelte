<script lang="ts">
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
  };

  let { name, slug, image, shortDesc, services, technologies }: Props = $props();

  const service = $derived(services[0]);
</script>

<div class="wrapper">
  <Link href={`/portofolio/${slug}`} class="card h-100 overflow-hidden rounded-4 card-portfolio">
    <img loading="lazy" src={image} class="rounded-3 card-img-top" alt={name} />

    <div class="card-body p-4">
      <div class="d-flex gap-2 mb-2 justify-content-between">
        <h5 class="card-title mb-0">{name}</h5>
        {#if service}
          <div>
            <span class="badge" style={`background-color: ${service.color ?? '#6c757d'}`}>
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
