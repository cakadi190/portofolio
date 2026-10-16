<script lang="ts">
  import Languages from '@lucide/svelte/icons/languages';
  import { Field } from '@/components/ui/field';
  import { canTranslate, localeOptions, t } from '@/lib/i18n';
  import { createTranslatable, fieldName } from '@/lib/translatable.svelte';

  /**
   * A short text field that exists in every site language, for the admin modal forms: one
   * input per language (`title`, `title_en`), a small switch to pick which one is on screen,
   * and a button that drafts the English text from the Indonesian one. Every language is
   * always submitted, whichever is visible.
   */
  let {
    field,
    label,
    id,
    values = undefined,
    placeholder = '',
    required = false,
    multiline = false,
    rows = 3,
    maxlength = undefined,
    errors = {},
  }: {
    field: string;
    label: string;
    id: string;
    /** Current text per language (`{ id: '…', en: '…' }`), from the record's `translations`. */
    values?: Record<string, string>;
    placeholder?: string;
    required?: boolean;
    multiline?: boolean;
    rows?: number;
    maxlength?: number;
    errors?: Record<string, string>;
  } = $props();

  // svelte-ignore state_referenced_locally
  const translatable = createTranslatable({ [field]: values ?? {} }, { [field]: 'text' });
  const codes = $derived(Object.keys(translatable.values[field]));
  const options = $derived(localeOptions());
</script>

<Field.Group>
  <div class="d-flex align-items-center justify-content-between gap-2">
    <Field.Label for={id}>{label}</Field.Label>

    <div class="d-flex align-items-center gap-1">
      {#if !translatable.isDefault && canTranslate()}
        <button
          type="button"
          class="btn btn-sm btn-link p-0"
          title={t('translate.field_auto')}
          aria-label={t('translate.field_auto')}
          disabled={translatable.translating}
          onclick={() => translatable.translateFromDefault()}><Languages size={14} /></button
        >
      {/if}
      <div class="btn-group btn-group-sm" role="tablist" aria-label={t('content.language.label')}>
        {#each codes as code (code)}
          <button
            type="button"
            role="tab"
            class="btn btn-outline-secondary py-0 px-2"
            class:active={translatable.locale === code}
            aria-selected={translatable.locale === code}
            title={options.find((option) => option.code === code)?.label}
            onclick={() => (translatable.locale = code)}
          >
            {code.toUpperCase()}
            {#if !translatable.isTranslated(code)}
              <span class="visually-hidden">({t('translate.missing')})</span>
            {/if}
          </button>
        {/each}
      </div>
    </div>
  </div>

  {#each codes as code (code)}
    <div hidden={code !== translatable.locale}>
      {#if multiline}
        <textarea
          id={code === translatable.locale ? id : `${id}-${code}`}
          name={fieldName(field, code)}
          class="form-control"
          class:is-invalid={!!errors[fieldName(field, code)]}
          {rows}
          {placeholder}
          {maxlength}
          required={required && translatable.isDefaultCode(code)}
          bind:value={translatable.values[field][code]}
        ></textarea>
      {:else}
        <Field.Input
          id={code === translatable.locale ? id : `${id}-${code}`}
          name={fieldName(field, code)}
          {placeholder}
          {maxlength}
          required={required && translatable.isDefaultCode(code)}
          invalid={!!errors[fieldName(field, code)]}
          bind:value={translatable.values[field][code]}
        />
      {/if}
    </div>
  {/each}

  <Field.Feedback message={errors[fieldName(field, translatable.locale)]} />
</Field.Group>
