<script lang="ts">
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

  let digits = $state<string[]>([]);
  let boxes = $state<HTMLInputElement[]>([]);

  const code = $derived(digits.join(''));

  function sanitize(value: string): string {
    return value.replace(/\D/g, '');
  }

  function handleInput(event: Event, index: number) {
    const box = event.currentTarget as HTMLInputElement;
    const cleaned = sanitize(box.value);

    if (cleaned.length > 1) {
      fill(cleaned, index);

      return;
    }

    digits[index] = cleaned;
    box.value = cleaned;

    if (cleaned && index < length - 1) {
      boxes[index + 1]?.focus();
    }

    submitIfComplete(box);
  }

  function handleKeydown(event: KeyboardEvent, index: number) {
    if (event.key === 'Backspace' && !digits[index] && index > 0) {
      event.preventDefault();
      digits[index - 1] = '';
      boxes[index - 1]?.focus();
    } else if (event.key === 'ArrowLeft' && index > 0) {
      boxes[index - 1]?.focus();
    } else if (event.key === 'ArrowRight' && index < length - 1) {
      boxes[index + 1]?.focus();
    }
  }

  function handlePaste(event: ClipboardEvent, index: number) {
    const pasted = sanitize(event.clipboardData?.getData('text') ?? '');

    if (!pasted) {
      return;
    }

    event.preventDefault();
    fill(pasted, index);
  }

  function fill(value: string, start: number) {
    const characters = value.slice(0, length - start).split('');

    characters.forEach((character, offset) => {
      digits[start + offset] = character;
      boxes[start + offset].value = character;
    });

    const last = Math.min(start + characters.length, length - 1);
    boxes[last]?.focus();
    submitIfComplete(boxes[last]);
  }

  function submitIfComplete(box: HTMLInputElement) {
    if (digits.filter(Boolean).length === length) {
      box.form?.requestSubmit();
    }
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
      maxlength={index === 0 ? length : 1}
      autocomplete="one-time-code"
      autocapitalize="off"
      spellcheck="false"
      class="form-control text-center otp-box"
      class:is-invalid={invalid}
      placeholder="•"
      aria-label="Karakter {index + 1} dari {length}"
      autofocus={autofocus && index === 0}
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
