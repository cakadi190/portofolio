<script lang="ts">
  import { badgeStyle } from '@/lib/badge';
  import { Link } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import EmptyState from '@/components/empty-state.svelte';
  import HeaderPage from '@/components/header-page.svelte';

  type Service = {
    name: string;
    slug: string;
    color: string | null;
    image: string | null;
    excerpt: string;
    portfoliosCount: number;
  };

  let { services }: { services: Service[] } = $props();
</script>

<AppHead title="Layanan Saya" />

<div>
  <HeaderPage title="Layanan Saya" subtitle="Berikut layanan yang bisa saya berikan dan layani untuk anda." />

  <section class="need-space pt-0">
    <div class="container">
      {#if services.length === 0}
        <EmptyState title="Belum Ada Layanan" text="Belum ada layanan yang tersedia saat ini." />
      {:else}
        <div class="row">
          {#each services as service, index (service.slug)}
            <div class="col-md-6 col-lg-4 mb-4">
              <div class="wrapper h-100">
                <Link href={`/layanan/${service.slug}`} class="card h-100 overflow-hidden rounded-4 card-blog">
                  {#if service.image}
                    <img
                      loading={index === 0 ? 'eager' : 'lazy'}
                      fetchpriority={index === 0 ? 'high' : undefined}
                      decoding="async"
                      src={service.image}
                      class="rounded-3 card-img-top"
                      alt={service.name}
                    />
                  {/if}

                  <div class="card-body p-4">
                    <div class="d-flex gap-2 mb-2 justify-content-between">
                      <h2 class="h5 card-title mb-0">{service.name}</h2>
                      <div>
                        <span class="badge" style={badgeStyle(service.color)}>
                          {service.portfoliosCount} proyek
                        </span>
                      </div>
                    </div>
                    {#if service.excerpt}
                      <div class="card-text mb-3 opacity-75">{service.excerpt}</div>
                    {/if}
                  </div>
                </Link>
              </div>
            </div>
          {/each}
        </div>
      {/if}
    </div>
  </section>
</div>
