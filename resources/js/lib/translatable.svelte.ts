import { localeOptions, t } from '@/lib/i18n';
import { postJson } from '@/lib/post-json';
import { toast } from '@/lib/toast';
import { translate } from '@/wayfinder/routes/admin';

export type FieldFormat = 'html' | 'text';
type Values = Record<string, Record<string, string>>;

const DEFAULT_LOCALE = 'id';

/**
 * State for an admin form whose fields exist in every site language: the language
 * being edited, every field's text per language, and the automatic draft translation.
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
  let translating = $state(false);
  // Bumped when text is replaced from outside the editors, so they reload it.
  let version = $state(0);

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
    get translating() {
      return translating;
    },
    /** Changes whenever translated text is loaded from outside, to refresh the rich-text editor. */
    get contentKey() {
      return `${locale}:${version}`;
    },
    values,
    /** Whether the language has text in its main (first) field. */
    isTranslated: (code: string): boolean => hasText(textOf(fields[0], code)),

    /**
     * Fill the active language from Indonesian with a machine-translated draft. Fields that
     * already have text are only replaced after the writer confirms.
     */
    async translateFromDefault(): Promise<void> {
      const target = locale;

      if (target === DEFAULT_LOCALE || translating) {
        return;
      }

      const pending = fields.filter((field) =>
        hasText(textOf(field, DEFAULT_LOCALE)),
      );

      if (pending.length === 0) {
        toast.error(t('translate.nothing'));

        return;
      }

      if (
        pending.some((field) => hasText(textOf(field, target))) &&
        !window.confirm(t('translate.overwrite'))
      ) {
        return;
      }

      translating = true;

      try {
        for (const field of pending) {
          const { text } = await postJson<{ text: string }>(translate().url, {
            text: textOf(field, DEFAULT_LOCALE),
            from: DEFAULT_LOCALE,
            to: target,
            format: formats[field],
          });

          values[field][target] = text;
          version++;
        }

        toast.success(t('translate.done'));
      } catch (error) {
        toast.error(
          error instanceof Error && error.message
            ? error.message
            : t('translate.failed'),
        );
      } finally {
        translating = false;
      }
    },
  };
}
