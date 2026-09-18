<script lang="ts">
  const locales = [
    { code: 'id', flag: 'fi-id', label: 'Bahasa Indonesia' },
    { code: 'en', flag: 'fi-us', label: 'Bahasa Inggris' },
  ] as const;

  let current = $state(locales[0]);
</script>

<div class="dropdown dropdown-uncaret">
  <button
    type="button"
    class="nav-link square dropdown-toggle"
    data-bs-toggle="dropdown"
    aria-expanded="false"
    aria-label={`Pilih bahasa: ${current.label}`}
  >
    <span class="fi {current.flag} fis border" aria-hidden="true"></span>
  </button>

  <ul class="dropdown-menu dropdown-menu-end">
    {#each locales as locale (locale.code)}
      <li>
        <button
          type="button"
          class="dropdown-item"
          class:active={current.code === locale.code}
          onclick={() => (current = locale)}
        >
          <span class="fi {locale.flag} fis border" aria-hidden="true"></span>
          <span>{locale.label}</span>
        </button>
      </li>
    {/each}
  </ul>
</div>
