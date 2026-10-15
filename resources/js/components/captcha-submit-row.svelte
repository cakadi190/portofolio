<script lang="ts">
  import type { Snippet } from 'svelte';
  import Turnstile from '@/components/turnstile.svelte';

  /**
   * Captcha and submit button on one row: captcha left and button right on desktop; captcha full
   * width with a fit-content button below it on mobile. Wraps instead of overflowing in narrow cards.
   */
  let {
    error,
    children,
  }: {
    error?: string;
    /** The submit button(s). */
    children: Snippet;
  } = $props();
</script>

<div class="captcha-submit-row">
  <div class="captcha-submit-row__captcha">
    <Turnstile {error} />
  </div>
  <div class="captcha-submit-row__submit">
    {@render children()}
  </div>
</div>

<style>
  .captcha-submit-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
  }

  .captcha-submit-row__submit {
    flex: 0 0 auto;
  }

  @media (max-width: 767.98px) {
    .captcha-submit-row__captcha {
      flex: 1 1 100%;
      min-width: 0;
    }

    .captcha-submit-row__submit {
      align-self: flex-start;
    }
  }
</style>
