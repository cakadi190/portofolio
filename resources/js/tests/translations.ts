import { execFileSync } from 'node:child_process';

/**
 * Components read UI strings from the shared `translations` Inertia prop. Load every
 * Indonesian `lang/id/*.php` group once (same flattening as `AppLocale::translations`)
 * so tests see what the server would share.
 */
function loadIndonesianTranslations(): Record<string, string> {
  const script = `
    $strings = [];
    foreach (glob('lang/id/*.php') as $file) {
      $group = basename($file, '.php');
      if ($group === 'locales') { continue; }
      foreach (Illuminate\\Support\\Arr::dot(require $file) as $key => $line) {
        $strings[$group === 'common' ? $key : $group.'.'.$key] = $line;
      }
    }
    echo json_encode($strings);
  `;

  try {
    return JSON.parse(
      execFileSync('php', ['-r', `require 'vendor/autoload.php'; ${script}`], {
        encoding: 'utf8',
      }),
    ) as Record<string, string>;
  } catch {
    return {};
  }
}

export const indonesianTranslations = loadIndonesianTranslations();
