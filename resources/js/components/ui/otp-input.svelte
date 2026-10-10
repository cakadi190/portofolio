<script lang="ts">
  import { tick } from 'svelte';

  interface Props {
    name: string;
    id?: string;
    length?: number;
    invalid?: boolean;
    autofocus?: boolean;
  }

  let {
    name,
    id = name,
    length = 6,
    invalid = false,
    autofocus = false,
  }: Props = $props();

  let digits = $state<string[]>(Array.from({ length }, () => ''));
  let boxes = $state<HTMLInputElement[]>([]);

  const code = $derived(digits.join(''));

  let wasInvalid = false;

  $effect(() => {
    if (invalid && !wasInvalid) {
      reset();
    }

    wasInvalid = invalid;
  });

  function reset() {
    digits = Array.from({ length }, () => '');
    boxes[0]?.focus();
  }

  function sanitize(value: string): string {
    return value.replace(/\D/g, '');
  }

  function handleInput(event: Event, index: number) {
    const box = event.currentTarget as HTMLInputElement;
    const cleaned = sanitize(box.value);

    box.value = digits[index];

    if (cleaned) {
      fill(cleaned, index);
    }
  }

  function handleKeydown(event: KeyboardEvent, index: number) {
    if (event.ctrlKey || event.metaKey || event.altKey) {
      return;
    }

    if (/^\d$/.test(event.key)) {
      event.preventDefault();
      fill(event.key, index);
    } else if (event.key === 'Backspace') {
      event.preventDefault();

      if (digits[index]) {
        digits[index] = '';
      } else if (index > 0) {
        digits[index - 1] = '';
        boxes[index - 1]?.focus();
      }
    } else if (event.key === 'Delete') {
      event.preventDefault();
      digits[index] = '';
    } else if (event.key === 'ArrowLeft' && index > 0) {
      event.preventDefault();
      boxes[index - 1]?.focus();
    } else if (event.key === 'ArrowRight' && index < length - 1) {
      event.preventDefault();
      boxes[index + 1]?.focus();
    } else if (event.key.length === 1) {
      event.preventDefault();
    }
  }

  function handlePaste(event: ClipboardEvent, index: number) {
    const pasted = sanitize(event.clipboardData?.getData('text') ?? '');

    event.preventDefault();

    if (pasted) {
      fill(pasted, index);
    }
  }

  function fill(value: string, start: number) {
    const characters = value.slice(0, length - start).split('');

    characters.forEach((character, offset) => {
      digits[start + offset] = character;
    });

    const next = Math.min(start + characters.length, length - 1);
    boxes[next]?.focus();
    submitIfComplete(boxes[next]);
  }

  async function submitIfComplete(box: HTMLInputElement | undefined) {
    if (!digits.every(Boolean)) {
      return;
    }

    await tick();
    box?.form?.requestSubmit();
  }
</script>

<div
  class="input-group input-group-lg otp-boxes"
  role="group"
  aria-label="Kode OTP {length} karakter"
>
  {#each Array.from({ length }, (_, index) => index) as index (index)}
    <input
      bind:this={boxes[index]}
      id={index === 0 ? id : `${id}-${index}`}
      type="text"
      inputmode="numeric"
      maxlength={index === 0 ? length : 2}
      value={digits[index]}
      autocomplete="one-time-code"
      autocapitalize="off"
      spellcheck="false"
      class="form-control text-center otp-box"
      class:is-invalid={invalid}
      placeholder="•"
      aria-label="Karakter {index + 1} dari {length}"
      autofocus={autofocus && index === 0}
      onfocus={(event) => event.currentTarget.select()}
      oninput={(event) => handleInput(event, index)}
      onkeydown={(event) => handleKeydown(event, index)}
      onpaste={(event) => handlePaste(event, index)}
    />
  {/each}
</div>
<input type="hidden" {name} value={code} />

<style>
  .otp-box {
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
  }

  .otp-box.is-invalid {
    background-image: none;
    padding-right: 0.75rem;
  }
</style>
