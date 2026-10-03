import type { Language, Mode } from './engine';

const ESCAPE: Mode = { scope: 'char.escape', begin: /\\[\s\S]/ };

const NUMBER: Mode = {
  scope: 'number',
  begin:
    /\b(?:0[xX][\da-fA-F_]+|0[bB][01_]+|\d[\d_]*(?:\.\d+)?(?:[eE][+-]?\d+)?)\b/,
};

const C_LINE_COMMENT: Mode = { scope: 'comment', begin: /\/\/.*/ };
const C_BLOCK_COMMENT: Mode = {
  scope: 'comment',
  begin: /\/\*/,
  end: /\*\//,
};
const HASH_COMMENT: Mode = { scope: 'comment', begin: /#.*/ };

const APOS_STRING: Mode = {
  scope: 'string',
  begin: /'/,
  end: /'|$/,
  contains: [ESCAPE],
};
const QUOTE_STRING: Mode = {
  scope: 'string',
  begin: /"/,
  end: /"|$/,
  contains: [ESCAPE],
};

const BRACES: Mode = {
  begin: /\{/,
  end: /\}/,
  contains: ['$root', 'self'],
};

function javascript(typescript: boolean): Language {
  const template: Mode = {
    scope: 'string',
    begin: /`/,
    end: /`/,
    contains: [
      ESCAPE,
      {
        scope: 'subst',
        begin: /\$\{/,
        end: /\}/,
        contains: ['$root', BRACES],
      },
    ],
  };

  return {
    name: typescript ? 'typescript' : 'javascript',
    aliases: typescript ? ['ts', 'tsx'] : ['js', 'jsx', 'mjs', 'cjs', 'node'],
    functionCalls: true,
    keywords: {
      keyword:
        'as async await break case catch class const continue debugger default delete do else export extends finally for from function if import in instanceof let new of return static super switch this throw try typeof var void while with yield get set' +
        (typescript
          ? ' interface type enum implements namespace declare readonly abstract private protected public keyof infer is satisfies'
          : ''),
      literal: 'true false null undefined NaN Infinity',
      built_in:
        'console window document Math JSON Object Array String Number Boolean Promise Map Set Date RegExp Error Symbol parseInt parseFloat require module exports fetch',
      ...(typescript
        ? { type: 'any unknown never string number boolean bigint object' }
        : {}),
    },
    contains: [
      C_LINE_COMMENT,
      C_BLOCK_COMMENT,
      APOS_STRING,
      QUOTE_STRING,
      template,
      NUMBER,
      {
        scope: 'regexp',
        begin:
          /\/(?![*/])(?:\\.|\[(?:\\.|[^\]\\])*\]|[^/\\\n[])+\/[dgimsuvy]*(?=\s*[,;)\].\n]|$)/,
      },
      { scope: 'punctuation', begin: /=>/ },
    ],
  };
}

const php: Language = {
  name: 'php',
  aliases: ['php3', 'php4', 'php5', 'php7', 'php8', 'blade'],
  caseInsensitive: true,
  functionCalls: true,
  keywords: {
    keyword:
      'abstract and as break callable case catch class clone const continue declare default do echo else elseif empty enddeclare endfor endforeach endif endswitch endwhile enum extends final finally fn for foreach function global goto if implements include include_once instanceof insteadof interface isset list match namespace new or print private protected public readonly require require_once return static switch throw trait try unset use var while xor yield',
    literal: 'true false null',
    built_in:
      'array string int float bool void mixed self parent iterable object never die exit',
  },
  contains: [
    { scope: 'meta', begin: /<\?(?:php|=)?|\?>/, relevance: 10 },
    { scope: 'meta', begin: /#\[[^\]]*\]/, relevance: 2 },
    C_LINE_COMMENT,
    HASH_COMMENT,
    C_BLOCK_COMMENT,
    { scope: 'variable', begin: /\$+[A-Za-z_]\w*/, relevance: 2 },
    {
      scope: 'string',
      begin: /'/,
      end: /'/,
      contains: [ESCAPE],
    },
    {
      scope: 'string',
      begin: /"/,
      end: /"/,
      contains: [
        ESCAPE,
        { scope: 'variable', begin: /\$[A-Za-z_]\w*/ },
        { scope: 'subst', begin: /\{\$/, end: /\}/, contains: ['$root'] },
      ],
    },
    NUMBER,
    { scope: 'punctuation', begin: /->|::|=>/, relevance: 1 },
  ],
};

const css: Language = {
  name: 'css',
  aliases: ['scss'],
  contains: [
    C_BLOCK_COMMENT,
    APOS_STRING,
    QUOTE_STRING,
    { scope: 'keyword', begin: /@[\w-]+/, relevance: 2 },
    { scope: 'selector-id', begin: /#[\w-]+/ },
    { scope: 'selector-class', begin: /\.[A-Za-z_-][\w-]*/ },
    { scope: 'selector-pseudo', begin: /::?[A-Za-z-]+/ },
    {
      begin: /\{/,
      end: /\}/,
      relevance: 2,
      contains: [
        C_BLOCK_COMMENT,
        APOS_STRING,
        QUOTE_STRING,
        { scope: 'attribute', begin: /[-\w]+(?=\s*:)/, relevance: 1 },
        { scope: 'number', begin: /#[\da-fA-F]{3,8}\b/ },
        {
          scope: 'number',
          begin: /-?(?:\d+\.?\d*|\.\d+)(?:%|[a-zA-Z]+)?/,
        },
        { scope: 'keyword', begin: /!important/ },
        { scope: 'variable', begin: /--[\w-]+/ },
        'self',
      ],
    },
  ],
};

const xml: Language = {
  name: 'xml',
  aliases: ['html', 'svg', 'xhtml', 'vue'],
  contains: [
    { scope: 'comment', begin: /<!--/, end: /-->/ },
    { scope: 'meta', begin: /<![A-Za-z][^>]*>/, relevance: 5 },
    {
      beginScope: 'tag',
      endScope: 'tag',
      begin: /<script\b[^>]*>/,
      end: /<\/script>/,
      subLanguage: 'javascript',
      relevance: 5,
    },
    {
      beginScope: 'tag',
      endScope: 'tag',
      begin: /<style\b[^>]*>/,
      end: /<\/style>/,
      subLanguage: 'css',
      relevance: 5,
    },
    {
      beginScope: 'tag',
      endScope: 'tag',
      begin: /<\/?[A-Za-z][\w:.-]*/,
      end: /\/?>/,
      relevance: 2,
      contains: [
        { scope: 'attr', begin: /[\w:.@-]+/ },
        APOS_STRING,
        { ...QUOTE_STRING, end: /"/ },
      ],
    },
    { scope: 'symbol', begin: /&#?\w+;/ },
  ],
};

const json: Language = {
  name: 'json',
  aliases: ['jsonc', 'json5'],
  keywords: { literal: 'true false null' },
  contains: [
    { scope: 'attr', begin: /"(?:[^"\\\n]|\\.)*"(?=\s*:)/, relevance: 2 },
    { scope: 'string', begin: /"(?:[^"\\\n]|\\.)*"/ },
    NUMBER,
    { scope: 'punctuation', begin: /[{}[\],:]/ },
  ],
};

const bash: Language = {
  name: 'bash',
  aliases: ['sh', 'shell', 'zsh', 'console'],
  keywords: {
    keyword:
      'if then else elif fi for while until do done case esac in function select return exit break continue',
    built_in:
      'echo cd ls cp mv rm mkdir cat grep sed awk export source printf read test set unset sudo chmod chown curl wget tar git docker npm npx bun yarn pnpm composer php artisan',
  },
  contains: [
    HASH_COMMENT,
    {
      scope: 'string',
      begin: /"/,
      end: /"/,
      contains: [
        ESCAPE,
        { scope: 'variable', begin: /\$(?:\{[^}]*\}|[\w@#?*!$-]+)/ },
      ],
    },
    { scope: 'string', begin: /'/, end: /'/ },
    { scope: 'variable', begin: /\$(?:\{[^}]*\}|[\w@#?*!$-]+)/, relevance: 1 },
    { scope: 'attr', begin: /\B--?[A-Za-z][\w-]*/ },
    NUMBER,
  ],
};

const sql: Language = {
  name: 'sql',
  aliases: ['mysql', 'pgsql', 'postgresql', 'sqlite'],
  caseInsensitive: true,
  keywords: {
    keyword:
      'select from where and or not insert into values update set delete create alter drop table index view database join inner left right outer full cross on as group by order having limit offset union all distinct case when then else end in is like between exists primary key foreign references unique default constraint add column asc desc with begin commit rollback',
    literal: 'true false null',
    type: 'int integer bigint smallint tinyint varchar char text boolean bool date datetime timestamp decimal numeric float double json uuid serial',
    built_in: 'count sum avg min max coalesce now concat length lower upper',
  },
  contains: [
    { scope: 'comment', begin: /--.*/ },
    C_BLOCK_COMMENT,
    { scope: 'string', begin: /'/, end: /'(?!')/, contains: [{ begin: /''/ }] },
    { scope: 'symbol', begin: /`[^`]*`|"[^"]*"/ },
    NUMBER,
  ],
};

const python: Language = {
  name: 'python',
  aliases: ['py', 'python3', 'gyp'],
  functionCalls: true,
  keywords: {
    keyword:
      'and as assert async await break class continue def del elif else except finally for from global if import in is lambda nonlocal not or pass raise return try while with yield match case',
    literal: 'True False None',
    built_in:
      'print len range str int float bool list dict set tuple open input isinstance type super self cls enumerate zip map filter sorted sum min max abs',
  },
  contains: [
    HASH_COMMENT,
    {
      scope: 'string',
      begin: /[rbfuRBFU]{0,2}"""/,
      end: /"""/,
      contains: [ESCAPE],
    },
    {
      scope: 'string',
      begin: /[rbfuRBFU]{0,2}'''/,
      end: /'''/,
      contains: [ESCAPE],
    },
    { ...APOS_STRING, begin: /[rbfuRBFU]{0,2}'/ },
    { ...QUOTE_STRING, begin: /[rbfuRBFU]{0,2}"/ },
    { scope: 'meta', begin: /@[\w.]+/, relevance: 1 },
    NUMBER,
  ],
};

export const builtinLanguages: Language[] = [
  javascript(false),
  javascript(true),
  php,
  css,
  xml,
  json,
  bash,
  sql,
  python,
];
