import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import { svelte } from '@sveltejs/vite-plugin-svelte';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { existsSync, readFileSync, writeFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { purgeUnusedCss } from './vite-purge-css';
import { defineConfig, lazyPlugins } from 'vite-plus';

const isVitest = Boolean(process.env.VITEST);

const isSsrBuild = process.argv.includes('--ssr');

const isSvelteCheck = process.argv.some((argument) =>
  argument.includes('svelte-check'),
);

if (isSvelteCheck) {
  process.env.LARAVEL_BYPASS_ENV_CHECK ??= '1';
}

const WOFF_FACE = /@font-face\s*\{[^}]*format\(["']woff["']\)[^}]*\}\s*/g;

/**
 * The font provider emits a `.woff` @font-face right after every `.woff2` one
 * with identical descriptors, and browsers use the last match: the larger,
 * un-preloaded `.woff` wins. Dropping the `.woff` faces from the generated
 * manifest and stylesheet makes every browser that matters use `.woff2`.
 */
function woff2OnlyFonts() {
  return {
    name: 'woff2-only-fonts',
    apply: 'build' as const,
    enforce: 'post' as const,
    closeBundle: {
      order: 'post' as const,
      handler() {
        const outDir = fileURLToPath(new URL('./public/build', import.meta.url));
        const manifestPath = `${outDir}/fonts-manifest.json`;

        if (!existsSync(manifestPath)) {
          return;
        }

        const manifest = JSON.parse(readFileSync(manifestPath, 'utf8'));
        const familyStyles = manifest.style?.familyStyles ?? {};

        for (const family of Object.keys(familyStyles)) {
          familyStyles[family] = familyStyles[family].replace(WOFF_FACE, '');
        }

        const cssPath = manifest.style?.file && `${outDir}/${manifest.style.file}`;

        if (cssPath && existsSync(cssPath)) {
          writeFileSync(cssPath, readFileSync(cssPath, 'utf8').replace(WOFF_FACE, ''));
        }

        writeFileSync(manifestPath, JSON.stringify(manifest, null, 2));
      },
    },
  };
}

export default defineConfig({
  plugins: lazyPlugins(
    () =>
      [
        // Vitest only needs the Svelte transform; the Laravel/Wayfinder
        // plugins boot PHP and keep watchers alive, delaying exit.
        ...(isVitest
          ? []
          : [
              laravel({
                input: ['resources/css/app.scss', 'resources/js/app.ts'],
                refresh: true,
                fonts: [
                  // Only the weights used above the fold (body text, hero
                  // title and bold emphasis) are preloaded; the rest load on
                  // demand. Without the preload the body font swaps in late
                  // and reflows the hero copy, shifting the photo (CLS).
                  bunny('Signika', {
                    weights: [400, 500, 600, 700],
                    preload: [
                      { weight: 400, style: 'normal' },
                      { weight: 600, style: 'normal' },
                      { weight: 700, style: 'normal' },
                    ],
                  }),
                  bunny('Roboto Slab', {
                    weights: [400, 500, 600],
                    preload: false,
                  }),
                  bunny('JetBrains Mono', {
                    weights: [400],
                    preload: false,
                  }),
                ],
              }),
            ]),
        ...(isVitest || isSsrBuild
          ? []
          : [
              woff2OnlyFonts(),
              ...(process.env.NO_CSS_PURGE
                ? []
                : [
                    purgeUnusedCss({
                root: fileURLToPath(new URL('.', import.meta.url)),
                entry: 'resources/css/app.scss',
              }),
                  ]),
            ]),
        inertia(),
        svelte({
          compilerOptions: {
            // Drops the default `svelte-` class prefix from scoped styles.
            cssHash: ({ hash, css }) => `c${hash(css)}`,
          },
        }),
        // The client build already generated the Wayfinder types
        // (`build:ssr` runs it first); regenerating for SSR boots the
        // whole framework again for nothing.
        ...(isSsrBuild || isVitest ? [] : [wayfinder()]),
      ] as any[],
  ),
  optimizeDeps: {
    // Prebundling svelte-select serves its component <style> as raw file
    // text in dev, leaking global `input { position: absolute }` rules.
    exclude: ['svelte-select'],
  },
  css: {
    devSourcemap: true,
    preprocessorOptions: {
      scss: {
        // Resolves bare `@use 'bootstrap/scss/bootstrap'` against
        // node_modules; without this Sass treats it as a relative path
        // and fails with ENOENT looking for `_bootstrap.scss`.
        loadPaths: ['node_modules'],
        // Bootstrap's SCSS still uses APIs Sass is deprecating
        // (legacy @import, global color functions); silence those
        // so the build output stays readable.
        quietDeps: true,
        silenceDeprecations: ['import', 'color-functions', 'global-builtin'],
      },
    },
  },
  server: {
    port: 5175,
    strictPort: true,
    watch: {
      ignored: [
        '**/.agents/**',
        '**/.claude/**',
        '**/.cursor/**',
        '**/.junie/**',
        '**/vendor/**',
      ],
    },
  },
  ssr: {
    // The runtime image has no node_modules, so Bun resolves externals from
    // its global cache, where tiptap's peers (@tiptap/extensions) are missing.
    noExternal: [/^@tiptap\//, /^prosemirror-/],
  },
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
    },
    // Svelte's package exports fall back to its server entry (where
    // `hydrate()` throws `lifecycle_function_unavailable`) unless the
    // `browser` condition matches, so pin it for Vitest and the client
    // build (the Bun-run Docker build can miss Vite's default).
    conditions:
      process.env.VITEST || !isSsrBuild
        ? ['module', 'browser', 'development|production']
        : [],
  },
  test: {
    environment: 'jsdom',
    include: ['resources/js/**/*.{test,spec}.ts'],
    setupFiles: ['resources/js/tests/setup.ts'],
    clearMocks: true,
    restoreMocks: true,
    coverage: {
      provider: 'v8',
      include: ['resources/js/**/*.{ts,svelte}'],
      exclude: ['resources/js/wayfinder/**', 'resources/js/components/ui/**'],
    },
  },
  lint: {
    ignorePatterns: [
      '.svelte-check/**',
      'vendor/**',
      'node_modules/**',
      'public/**',
      'bootstrap/ssr/**',
      'tailwind.config.js',
      'resources/js/actions/**',
      'resources/js/components/ui/*',
      'resources/js/routes/**',
      'resources/js/wayfinder/**',
    ],
    options: {
      denyWarnings: true,
      typeAware: true,
    },
  },
  fmt: {
    printWidth: 80,
    tabWidth: 4,
    singleQuote: true,
    semi: true,
    singleAttributePerLine: false,
    htmlWhitespaceSensitivity: 'css',
    ignorePatterns: [
      '.github/**',
      'composer.json',
      'resources/js/components/ui/*',
      'resources/views/mail/*',
    ],
    overrides: [
      {
        files: [
          '**/*.{js,jsx,ts,tsx,mjs,cjs,mts,cts,svelte,vue,html,css,scss,sass,less,json,jsonc,yml,yaml}',
        ],
        options: {
          tabWidth: 2,
        },
      },
    ],
  },
});
