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
    { label: 'Sangat lemah', color: 'var(--bs-danger)' },
    { label: 'Lemah', color: 'var(--bs-orange, #fd7e14)' },
    { label: 'Cukup', color: 'var(--bs-warning)' },
    { label: 'Kuat', color: 'var(--bs-success)' },
    { label: 'Sangat kuat', color: 'var(--bs-success)' },
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
    <div class="password-meter" style:--meter-color={strength.color}>
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
  .password-toggle-btn {
    color: var(--bs-secondary-color);
    display: flex;
    align-items: center;
    border-radius: 0 var(--bs-border-radius) var(--bs-border-radius) 0;
    padding-inline: 1rem 0.75rem;
    border: 0;
    background-color: transparent;
    transition: all 0.2s;

    &:hover {
      color: var(--bs-primary);
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
    --state-color: rgba(0, 0, 0, 0.15);

    display: grid;
    grid-template-columns: 1fr auto;
    grid-template-rows: auto auto auto;
    align-items: center;
    border: 1px solid var(--state-color);
    border-radius: var(--bs-border-radius);
    padding: 0.5rem;
    gap: 0.25rem;
    transition: border-color 0.15s ease-in-out;

    &[data-state='invalid'] {
      --state-color: var(--bs-danger);
    }

    &[data-state='mismatch'] {
      --state-color: var(--bs-warning);
    }

    &[data-state='match'] {
      --state-color: var(--bs-success);
    }

    .separator {
      grid-column: 1;
      height: 1px;
      margin-inline-end: 0.75rem;
      margin-inline-start: 0.25rem;
      background-color: var(--state-color);
    }

    // Strip the border/shadow/validation-icon that .form-control normally
    // renders per-state (default, :focus, .is-invalid, .is-invalid:focus) —
    // the container's own --state-color border/background is what shows instead.
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
      border-left: 1px solid var(--state-color);
      border-radius: 0 var(--bs-border-radius-xl) var(--bs-border-radius-xl) 0;
      transition: border-color 0.15s ease-in-out;
    }
  }

  .password-meter {
    // Single source of truth for the meter's color at any strength, set once
    // via style:--meter-color in the markup instead of per-segment styles.
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
    border-radius: var(--bs-border-radius-pill, 50rem);
    background-color: var(--bs-border-color);
    transition: background-color 0.15s ease-in-out;

    &.is-filled {
      background-color: var(--meter-color);
    }
  }

  .password-meter-label {
    color: var(--meter-color);
    font-size: 0.8125em;
    white-space: nowrap;
  }
</style>
