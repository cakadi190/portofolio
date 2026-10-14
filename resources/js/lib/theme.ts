/** Must match the anti-FOUC boot script in resources/views/app.blade.php. */
export const THEME_STORAGE_KEY = 'catatancakadi:theme';

export type Theme = 'light' | 'dark';

/** Flips the document between light and dark, remembers the choice when storage allows, and returns the new theme. */
export function toggleDocumentTheme(): Theme {
  return setDocumentTheme(
    document.documentElement.getAttribute('data-bs-theme') === 'dark'
      ? 'light'
      : 'dark',
  );
}

/** Applies the given theme to the document, remembers the choice when storage allows, and returns it. */
export function setDocumentTheme(next: Theme): Theme {
  document.documentElement.setAttribute('data-bs-theme', next);

  try {
    window.localStorage.setItem(THEME_STORAGE_KEY, next);
  } catch {
    // Private mode / blocked storage — theme just won't persist.
  }

  return next;
}
