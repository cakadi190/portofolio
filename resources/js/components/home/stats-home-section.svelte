<script lang="ts">
  type Stat = { value: number; suffix: string; label: string };

  let { stats = [] }: { stats?: Stat[] } = $props();
</script>

{#if stats.length}
  <section class="need-space pt-0" id="stats-home-section">
    <div class="container">
      <div class="stats-grid">
        {#each stats as stat (stat.label)}
          <div class="stats-item">
            <div class="stats-value">
              {stat.value}{#if stat.suffix}<span>{stat.suffix}</span>{/if}
            </div>
            <p class="stats-label mb-0 opacity-75">{stat.label}</p>
          </div>
        {/each}
      </div>
    </div>
  </section>
{/if}

<style>
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    border-block: 1px solid var(--neo-border-color);
  }

  .stats-item {
    padding: 2rem 1.5rem;
    border-inline-start: 1px solid var(--neo-border-color);
  }

  .stats-item:first-child {
    padding-inline-start: 0;
    border-inline-start: 0;
  }

  .stats-value {
    font-size: clamp(2.5rem, 6vw, 4rem);
    font-weight: 800;
    line-height: 1;
    letter-spacing: -0.03em;
  }

  .stats-value span {
    color: var(--neo-primary);
  }

  .stats-label {
    max-width: 16rem;
    margin-top: 0.5rem;
    font-size: 0.9rem;
  }

  @media (max-width: 991.98px) {
    .stats-grid {
      grid-template-columns: repeat(2, 1fr);
    }

    .stats-item:nth-child(odd) {
      padding-inline-start: 0;
      border-inline-start: 0;
    }

    .stats-item:nth-child(n + 3) {
      border-block-start: 1px solid var(--neo-border-color);
    }
  }
</style>
