<script lang="ts">
  import Eye from '@lucide/svelte/icons/eye';
  import EyeOff from '@lucide/svelte/icons/eye-off';
  import Input from './regular.svelte';
  import { cn } from '@/lib/utils';
  import { untrack } from 'svelte';
  import type { HTMLInputAttributes } from 'svelte/elements';

  let {
    class: className = '',
    confirmed,
    meter = true,
    ...rest
  }: HTMLInputAttributes & {
    confirmed?: string;
    passwordrules?: string;
    invalid?: boolean;
    /** Show the password strength meter below the field. Defaults to true. */
    meter?: boolean;
  } = $props();

  let showPassword = $state(false);
  let password = $state(untrack(() => String(rest.value ?? '')));
  let passwordConfirmation = $state('');

  const strengthLevels = [
    { label: 'Sangat lemah', color: 'var(--neo-danger)' },
    { label: 'Lemah', color: 'var(--neo-orange, #fd7e14)' },
    { label: 'Cukup', color: 'var(--neo-warning)' },
    { label: 'Kuat', color: 'var(--neo-success)' },
    { label: 'Sangat kuat', color: 'var(--neo-success)' },
  ];

  function scorePassword(value: string): number {
    if (!value) {
      return 0;
    }

    let score = 0;

    if (value.length >= 8) {
      score++;
    }
    if (value.length >= 12) {
      score++;
    }
    if (/[a-z]/.test(value) && /[A-Z]/.test(value)) {
      score++;
    }
    if (/\d/.test(value)) {
      score++;
    }
    if (/[^A-Za-z0-9]/.test(value)) {
      score++;
    }

    return Math.min(score, strengthLevels.length - 1);
  }

  let strengthScore = $derived(scorePassword(password));
  let strength = $derived(strengthLevels[strengthScore]);

  let passwordsMatch = $derived(
    passwordConfirmation.length === 0
      ? null
      : passwordConfirmation === password,
  );

  /** Border/feedback state for the confirmed (two-field) variant, highest priority first. */
  let confirmState = $derived(
    rest.invalid
      ? 'invalid'
      : passwordsMatch === false
        ? 'mismatch'
        : passwordsMatch === true
          ? 'match'
          : 'default',
  );
</script>

{#snippet toggleButton()}
  <button
    type="button"
    onclick={() => (showPassword = !showPassword)}
    class="password-toggle-btn"
    aria-label={showPassword ? 'Hide password' : 'Show password'}
    tabindex={-1}
  >
    {#if showPassword}
      <EyeOff class="password-toggle-icon" />
    {:else}
      <Eye class="password-toggle-icon" />
    {/if}
  </button>
{/snippet}

{#snippet strengthMeter()}
  {#if !!confirmed && meter && password.length > 0}
    <div class="password-meter" style:--neo-meter-color={strength.color}>
      <div class="password-meter-track">
        {#each strengthLevels.slice(1) as _, index}
          <span
            class="password-meter-segment"
            class:is-filled={index <= strengthScore - 1}
          ></span>
        {/each}
      </div>
      <span class="password-meter-label">{strength.label}</span>
    </div>
  {/if}
{/snippet}

{#if !!confirmed}
  <div class="form-confirmed" data-state={confirmState}>
    <Input
      type={showPassword ? 'text' : 'password'}
      class={className}
      {...rest}
      bind:value={password}
    />

    <div class="separator"></div>

    <Input
      type={showPassword ? 'text' : 'password'}
      class={className}
      {...rest}
      invalid={confirmState === 'invalid' || confirmState === 'mismatch'}
      bind:value={passwordConfirmation}
      id={confirmed}
      name={confirmed}
      placeholder="Konfirmasi kata sandi Anda."
    />

    {@render toggleButton()}
  </div>

  {@render strengthMeter()}
{:else}
  <div class="form-password">
    <Input
      type={showPassword ? 'text' : 'password'}
      class={cn('pe-5', className)}
      {...rest}
      bind:value={password}
    />
    {@render toggleButton()}
  </div>
{/if}

<style lang="scss">
  $prefix: 'neo';

  .password-toggle-btn {
    color: var(--neo-secondary-color);
    display: flex;
    align-items: center;
    border-radius: 0 var(--neo-border-radius) var(--neo-border-radius) 0;
    padding-inline: 1rem 0.75rem;
    border: 0;
    background-color: transparent;
    transition: all 0.2s;

    &:hover {
      color: var(--neo-primary);
    }
  }

  :global(.password-toggle-icon) {
    width: 1rem;
    height: 1rem;
  }

  .form-password {
    position: relative;

    .password-toggle-btn {
      position: absolute;
      top: 0;
      bottom: 0;
      right: 0;
    }
  }

  .form-confirmed {
    // Color-per-state variable system: swap one custom property instead of
    // branching every rule that cares about validity.
    --#{$prefix}-state-color: rgba(0, 0, 0, 0.15);

    display: grid;
    grid-template-columns: 1fr auto;
    grid-template-rows: auto auto auto;
    align-items: center;
    border: 1px solid var(--#{$prefix}-state-color);
    border-radius: var(--neo-border-radius);
    padding: 0.5rem;
    gap: 0.25rem;
    transition: border-color 0.15s ease-in-out;

    &[data-state='invalid'] {
      --#{$prefix}-state-color: var(--neo-danger);
    }

    &[data-state='mismatch'] {
      --#{$prefix}-state-color: var(--neo-warning);
    }

    &[data-state='match'] {
      --#{$prefix}-state-color: var(--neo-success);
    }

    .separator {
      grid-column: 1;
      height: 1px;
      margin-inline-end: 0.75rem;
      margin-inline-start: 0.25rem;
      background-color: var(--#{$prefix}-state-color);
    }

    // Strip the border/shadow/validation-icon that .form-control normally
    // renders per-state (default, :focus, .is-invalid, .is-invalid:focus) —
    // the container's own --neo-state-color border/background is what shows instead.
    :global(.form-control) {
      border: 0 !important;
      box-shadow: none !important;
      background-image: none !important;
      padding-inline-end: 0.75rem !important;

      &:focus {
        box-shadow: none !important;
      }
    }

    .password-toggle-btn {
      grid-column: 2;
      grid-row: 1 / 4;
      height: 100%;
      border-left: 1px solid var(--#{$prefix}-state-color);
      border-radius: 0 var(--neo-border-radius-xl) var(--neo-border-radius-xl) 0;
      transition: border-color 0.15s ease-in-out;
    }
  }

  .password-meter {
    // Single source of truth for the meter's color at any strength, set once
    // via style:--neo-meter-color in the markup instead of per-segment styles.
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .password-meter-track {
    display: flex;
    flex: 1;
    gap: 0.25rem;
  }

  .password-meter-segment {
    height: 0.25rem;
    flex: 1;
    border-radius: var(--neo-border-radius-pill, 50rem);
    background-color: var(--neo-border-color);
    transition: background-color 0.15s ease-in-out;

    &.is-filled {
      background-color: var(--#{$prefix}-meter-color);
    }
  }

  .password-meter-label {
    color: var(--#{$prefix}-meter-color);
    font-size: 0.8125em;
    white-space: nowrap;
  }
</style>
