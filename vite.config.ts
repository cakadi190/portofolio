import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import { svelte } from '@sveltejs/vite-plugin-svelte';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig, lazyPlugins } from 'vite-plus';

const isSvelteCheck = process.argv.some((argument) =>
  argument.includes('svelte-check'),
);

if (isSvelteCheck) {
  process.env.LARAVEL_BYPASS_ENV_CHECK ??= '1';
}

export default defineConfig({
  plugins: lazyPlugins(
    () =>
      [
        laravel({
          input: ['resources/css/app.scss', 'resources/js/app.ts'],
          refresh: true,
          fonts: [
            bunny('Instrument Sans', {
              weights: [400, 500, 600],
            }),
          ],
        }),
        inertia(),
        svelte(),
        wayfinder({
          formVariants: true,
        }),
      ] as any[],
  ),
  css: {
    preprocessorOptions: {
      scss: {
        // Bootstrap's SCSS still uses APIs Sass is deprecating
        // (legacy @import, global color functions); silence those
        // so the build output stays readable.
        quietDeps: true,
        silenceDeprecations: [
          'import',
          'color-functions',
          'global-builtin',
          'mixed-decls',
        ],
      },
    },
  },
  server: {
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
  lint: {
    ignorePatterns: [
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
