<script lang="ts">
  import { redirect } from '@/wayfinder/App/Http/Controllers/Auth/SocialiteController';

  let {
    providers = [],
  }: {
    providers: { name: string; label: string }[];
  } = $props();
</script>

{#if providers.length > 0}
  <div class="d-flex flex-column gap-2">
    {#each providers as provider (provider.name)}
      <a
        href={redirect.url(provider.name)}
        class="btn btn-outline-secondary w-100"
        data-test={`login-socialite-${provider.name}`}
      >
        Masuk dengan {provider.label}
      </a>
    {/each}
  </div>
{/if}
