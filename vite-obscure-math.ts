import postcss from 'postcss';
import { obscureMathSelector } from './resources/js/lib/math-classes';

/**
 * Keeps the equation library from announcing itself: its stylesheet and font
 * files are renamed so that neither the DOM (`lib/math.ts` renames the classes
 * it renders), the CSS, nor any requested URL carries the library's name.
 */
const MATH_MODULE = /node_modules[\\/]katex[\\/]/;
const MATH_ASSET = /katex/i;

/** Renames class selectors and font families in the library's stylesheet. */
export function obscureMathCss(css: string): string {
  const root = postcss.parse(css);

  root.walkRules((rule) => {
    if (
      rule.parent?.type === 'atrule' &&
      /keyframes$/i.test((rule.parent as postcss.AtRule).name)
    ) {
      return;
    }

    rule.selector = obscureMathSelector(rule.selector);
  });

  // Font families (also inside the `font` shorthand) and counter names; the
  // `src` urls keep their file names, which the bundler renames on emit.
  root.walkDecls((decl) => {
    if (decl.prop !== 'src') {
      decl.value = decl.value
        .replace(/KaTeX_/g, 'MX_')
        .replace(/katexEqnNo/g, 'mxEqnNo');
    }
  });

  return root.toString();
}

export function obscureMath() {
  return {
    name: 'obscure-math',
    enforce: 'pre' as const,
    config() {
      return {
        build: {
          rollupOptions: {
            output: {
              chunkFileNames: (chunk: {
                name: string;
                moduleIds?: string[];
              }) =>
                MATH_ASSET.test(chunk.name) ||
                chunk.moduleIds?.some((id) => MATH_MODULE.test(id))
                  ? 'assets/m-[hash].js'
                  : 'assets/[name]-[hash].js',
              assetFileNames: (asset: {
                names?: string[];
                originalFileNames?: string[];
              }) =>
                [
                  ...(asset.names ?? []),
                  ...(asset.originalFileNames ?? []),
                ].some((name) => MATH_ASSET.test(name))
                  ? 'assets/m-[hash][extname]'
                  : 'assets/[name]-[hash][extname]',
            },
          },
        },
      };
    },
    transform(code: string, id: string) {
      if (!/katex[\\/]dist[\\/]katex\.min\.css/.test(id.split('?')[0])) {
        return null;
      }

      return { code: obscureMathCss(code), map: null };
    },
  };
}
