import $ from 'jquery';
// @ts-expect-error - select2's dist file has no bundler-friendly module typings
import select2Factory from 'select2/dist/js/select2.js';

declare global {
  interface Window {
    jQuery?: typeof $;
  }
}

// select2's UMD builds (core and i18n) read a *global* jQuery and only
// self-register onto it, which never happens under Vite's ESM bundling. ES
// imports are hoisted, so this setup lives in its own module that select2.ts
// imports before the i18n file (which needs `$.fn.select2` to already exist).
window.jQuery = $;

if (!$.fn.select2) {
  select2Factory(window, $);
}
