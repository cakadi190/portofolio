<script lang="ts">
  import Eye from '@lucide/svelte/icons/eye';
  import EyeOff from '@lucide/svelte/icons/eye-off';
  import { Input } from '@/components/ui/input';
  import { cn } from '@/lib/utils';

  let { class: className = '', ...rest } = $props();

  let showPassword = $state(false);
</script>

<div class="position-relative">
  <Input
    type={showPassword ? 'text' : 'password'}
    class={cn('pe-5', className)}
    {...rest}
  />
  <button
    type="button"
    onclick={() => (showPassword = !showPassword)}
    class="text-muted position-absolute top-0 bottom-0 end-0 d-flex align-items-center rounded-end px-3 border-0 bg-transparent"
    aria-label={showPassword ? 'Hide password' : 'Show password'}
    tabindex={-1}
  >
    {#if showPassword}
      <EyeOff class="password-toggle-icon" />
    {:else}
      <Eye class="password-toggle-icon" />
    {/if}
  </button>
</div>

<style lang="scss">
  :global(.password-toggle-icon) {
    width: 1rem;
    height: 1rem;
  }
</style>
