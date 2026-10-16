import { localeOptions, t } from '@/lib/i18n';

export type FieldFormat = 'html' | 'text';
type Values = Record<string, Record<string, string>>;

const DEFAULT_LOCALE = 'id';

/** Form input name of a field in a language: `title` for Indonesian, `title_en` for English. */
export function fieldName(field: string, locale: string): string {
  return locale === DEFAULT_LOCALE ? field : `${field}_${locale}`;
}

/**
 * State for an admin form whose fields exist in every site language: the language
 * being edited, every field's text per language,.
 * The form submits `field[locale]` inputs, so Indonesian and English travel together.
 */
export function createTranslatable(
  initial: Values,
  formats: Record<string, FieldFormat>,
) {
  const fields = Object.keys(formats);
  const shared = localeOptions().map((option) => option.code);
  const locales = shared.length > 0 ? shared : [DEFAULT_LOCALE, 'en'];
  const values = $state<Values>(
    Object.fromEntries(
      fields.map((field) => [
        field,
        Object.fromEntries(
          locales.map((code) => [code, initial[field]?.[code] ?? '']),
        ),
      ]),
    ),
  );

  let locale = $state(DEFAULT_LOCALE);

  const textOf = (field: string, code: string): string =>
    values[field]?.[code] ?? '';
  const hasText = (value: string): boolean =>
    value.replace(/<[^>]*>/g, '').trim() !== '';

  return {
    get locale() {
      return locale;
    },
    set locale(next: string) {
      locale = next;
    },
    get isDefault() {
      return locale === DEFAULT_LOCALE;
    },
    isDefaultCode: (code: string): boolean => code === DEFAULT_LOCALE,
    /** Changes with the language, to reload the rich-text editor. */
    get contentKey() {
      return locale;
    },
    values,
    /** Languages for the editor's language dropdown, labelled "Indonesian Content" etc. */
    get languages() {
      return localeOptions().map((option) => ({
        code: option.code,
        flag: option.flag,
        label: t(`content.language.${option.code}`),
      }));
    },
    /** Whether the language has text in its main (first) field. */
    isTranslated: (code: string): boolean => hasText(textOf(fields[0], code)),
  };
}
