<script lang="ts">
  import { t } from '@/lib/i18n';
  import '../../../css/content.scss';
  import Bold from '@lucide/svelte/icons/bold';
  import Check from '@lucide/svelte/icons/check';
  import Code from '@lucide/svelte/icons/code';
  import Copy from '@lucide/svelte/icons/copy';
  import Heading2 from '@lucide/svelte/icons/heading-2';
  import Italic from '@lucide/svelte/icons/italic';
  import Link from '@lucide/svelte/icons/link';
  import Pilcrow from '@lucide/svelte/icons/pilcrow';
  import WandSparkles from '@lucide/svelte/icons/wand-sparkles';
  import { formatHtml } from '@/lib/format-html';
  import { highlightSource } from '@/lib/highlight';

  /**
   * VS Code-style HTML source editor: dark theme, line numbers, syntax
   * highlighting (a transparent textarea over a highlighted layer), tab
   * indent, auto-indent on Enter, a formatter and tag-wrapping buttons.
   */
  let {
    value = $bindable(''),
    ariaLabel = t('ui.kode_html'),
  }: {
    value?: string;
    ariaLabel?: string;
  } = $props();

  const INDENT = '  ';

  let area = $state<HTMLTextAreaElement>();
  let code = $state<HTMLElement>();
  let cursor = $state({ line: 1, column: 1 });
  let copied = $state(false);

  const lineCount = $derived(value.split('\n').length);

  $effect(() => {
    if (!code) return;

    const result = highlightSource(value, 'xml');

    code.replaceChildren(result ? result.fragment : value);
    code.append('\n');
  });

  function syncCursor(): void {
    if (!area) return;

    const before = area.value.slice(0, area.selectionStart);
    const lines = before.split('\n');

    cursor = { line: lines.length, column: lines[lines.length - 1].length + 1 };
  }

  /** Replaces a range through the browser so native undo/redo keeps working. */
  function edit(
    start: number,
    end: number,
    text: string,
    selectStart = start + text.length,
    selectEnd = selectStart,
  ): void {
    if (!area) return;

    area.focus();
    area.setSelectionRange(start, end);

    const done =
      typeof document.execCommand === 'function' &&
      document.execCommand('insertText', false, text);

    if (!done) {
      area.setRangeText(text, start, end, 'end');
      value = area.value;
    }

    area.setSelectionRange(selectStart, selectEnd);
    syncCursor();
  }

  function lineStart(position: number): number {
    return value.lastIndexOf('\n', position - 1) + 1;
  }

  function shiftLines(outdent: boolean): void {
    if (!area) return;

    const { selectionStart: from, selectionEnd: to } = area;
    const start = lineStart(from);
    const block = value.slice(start, to);
    const next = block
      .split('\n')
      .map((text) =>
        outdent
          ? text.replace(new RegExp(`^(?:${INDENT}|\\t| )`), '')
          : INDENT + text,
      )
      .join('\n');

    edit(start, to, next, start, start + next.length);
  }

  function handleKeydown(event: KeyboardEvent): void {
    if (!area) return;

    const { selectionStart: from, selectionEnd: to } = area;

    if (event.key === 'Tab') {
      event.preventDefault();

      if (event.shiftKey || value.slice(from, to).includes('\n')) {
        shiftLines(event.shiftKey);
      } else {
        edit(from, to, INDENT);
      }
    } else if (
      event.key === 'Enter' &&
      !event.ctrlKey &&
      !event.metaKey &&
      !event.shiftKey
    ) {
      event.preventDefault();

      const indent =
        /^[ \t]*/.exec(value.slice(lineStart(from), from))?.[0] ?? '';
      const opensTag = /<[a-z][^>]*[^/]>$/i.test(value.slice(0, from));
      const closesNext = value.slice(to).startsWith('</');

      if (opensTag && closesNext) {
        edit(
          from,
          to,
          `\n${indent}${INDENT}\n${indent}`,
          from + indent.length + INDENT.length + 1,
        );
      } else {
        edit(from, to, `\n${indent}${opensTag ? INDENT : ''}`);
      }
    } else if (
      event.key.toLowerCase() === 'f' &&
      event.shiftKey &&
      (event.ctrlKey || event.metaKey)
    ) {
      event.preventDefault();
      format();
    }
  }

  function format(): void {
    const formatted = formatHtml(value);

    if (formatted !== value) edit(0, value.length, formatted, 0);
  }

  function wrap(open: string, close: string, placeholder = ''): void {
    if (!area) return;

    const { selectionStart: from, selectionEnd: to } = area;
    const selected = value.slice(from, to) || placeholder;

    edit(
      from,
      to,
      open + selected + close,
      from + open.length,
      from + open.length + selected.length,
    );
  }

  async function copy(): Promise<void> {
    try {
      await navigator.clipboard.writeText(value);
      copied = true;
      setTimeout(() => (copied = false), 1500);
    } catch {
      area?.select();
    }
  }
</script>

<div class="hce">
  <div class="hce-bar" role="toolbar" aria-label={t('ui.alat_kode_html')}>
    <button type="button" title={t('ui.format_kode_ctrl_shift_f')} onclick={format}
      ><WandSparkles size={15} /><span>{t('ui.format')}</span></button
    >
    <span class="hce-sep"></span>
    <button
      type="button"
      title={t('ui.tebal')}
      onclick={() => wrap('<strong>', '</strong>', 'teks')}
      ><Bold size={15} /></button
    >
    <button
      type="button"
      title={t('ui.miring')}
      onclick={() => wrap('<em>', '</em>', 'teks')}><Italic size={15} /></button
    >
    <button
      type="button"
      title={t('ui.kode_inline')}
      onclick={() => wrap('<code>', '</code>', 'kode')}
      ><Code size={15} /></button
    >
    <button
      type="button"
      title={t('ui.tautan')}
      onclick={() => wrap('<a href="https://">', '</a>', 'teks')}
      ><Link size={15} /></button
    >
    <button
      type="button"
      title={t('ui.judul_2')}
      onclick={() => wrap('<h2>', '</h2>', t('ui.judul'))}
      ><Heading2 size={15} /></button
    >
    <button
      type="button"
      title={t('ui.paragraf')}
      onclick={() => wrap('<p>', '</p>', t('ui.teks'))}><Pilcrow size={15} /></button
    >
    <button
      type="button"
      class="hce-copy"
      title={t('ui.salin_semua_kode')}
      onclick={copy}
      >{#if copied}<Check size={15} /><span>{t('ui.tersalin')}</span>{:else}<Copy
          size={15}
        /><span>{t('ui.salin')}</span>{/if}</button
    >
  </div>

  <div class="hce-scroll">
    <div class="hce-gutter" aria-hidden="true">
      {#each { length: lineCount } as _, index (index)}
        <span class:is-current={index + 1 === cursor.line}>{index + 1}</span>
      {/each}
    </div>
    <div class="hce-stack">
      <pre class="hce-pre" aria-hidden="true"><code bind:this={code}
        ></code></pre>
      <textarea
        class="hce-input"
        spellcheck="false"
        autocomplete="off"
        autocapitalize="off"
        wrap="off"
        aria-label={ariaLabel}
        bind:this={area}
        bind:value
        onkeydown={handleKeydown}
        onkeyup={syncCursor}
        onclick={syncCursor}
        oninput={syncCursor}
        onfocus={syncCursor}></textarea>
    </div>
  </div>

  <div class="hce-status">
    <span>Baris {cursor.line}, Kolom {cursor.column}</span>
    <span>{value.length} karakter</span>
    <span>{t('ui.html')}</span>
  </div>
</div>

<style>
  .hce {
    --hce-bg: #1e1e1e;
    --hce-fg: #d4d4d4;
    --hce-muted: #858585;
    --hce-bar: #252526;
    --hce-line: #3c3c3c;

    overflow: hidden;
    border: 1px solid var(--hce-line);
    border-radius: 0.375rem;
    background: var(--hce-bg);
    color: var(--hce-fg);
  }
  .hce-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.125rem;
    padding: 0.375rem 0.5rem;
    border-bottom: 1px solid var(--hce-line);
    background: var(--hce-bar);
  }
  .hce-bar button {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    height: 1.75rem;
    padding: 0 0.5rem;
    border: 0;
    border-radius: 0.25rem;
    background: transparent;
    color: var(--hce-fg);
    font-size: 0.75rem;
  }
  .hce-bar button:hover {
    background: rgb(255 255 255 / 0.1);
  }
  .hce-copy {
    margin-left: auto;
  }
  .hce-sep {
    width: 1px;
    height: 1.125rem;
    margin: 0 0.25rem;
    background: var(--hce-line);
  }
  .hce-scroll {
    display: flex;
    min-height: 24rem;
    overflow-x: auto;
    overflow-y: hidden;
  }
  .hce-gutter,
  .hce-pre,
  .hce-input {
    margin: 0;
    padding-block: 0.75rem;
    font-family: var(--neo-font-monospace, ui-monospace, Consolas, monospace);
    font-size: 0.8125rem;
    line-height: 1.6;
    tab-size: 2;
  }
  .hce-gutter {
    position: sticky;
    left: 0;
    z-index: 1;
    display: flex;
    flex: none;
    flex-direction: column;
    min-width: 3.25rem;
    padding-inline: 0.75rem;
    border-right: 1px solid var(--hce-line);
    background: var(--hce-bg);
    color: var(--hce-muted);
    text-align: right;
    user-select: none;
  }
  .hce-gutter .is-current {
    color: var(--hce-fg);
  }
  .hce-stack {
    display: grid;
    flex: 1;
    grid-template-columns: minmax(max-content, 1fr);
  }
  .hce-pre,
  .hce-input {
    grid-area: 1 / 1;
    padding-inline: 1rem;
    border: 0;
    white-space: pre;
    overflow-wrap: normal;
    word-break: normal;
  }
  .hce-pre {
    pointer-events: none;
    color: var(--hce-fg);
  }
  .hce-pre code {
    font: inherit;
    color: inherit;
  }
  .hce-input {
    resize: none;
    overflow: hidden;
    outline: none;
    background: transparent;
    color: transparent;
    caret-color: #fff;
  }
  .hce-input::selection {
    background: rgb(38 79 120 / 0.9);
  }
  .hce-pre :global(.hljs-tag),
  .hce-pre :global(.hljs-name),
  .hce-pre :global(.hljs-keyword),
  .hce-pre :global(.hljs-meta) {
    color: #569cd6;
  }
  .hce-pre :global(.hljs-attr),
  .hce-pre :global(.hljs-attribute) {
    color: #9cdcfe;
  }
  .hce-pre :global(.hljs-string),
  .hce-pre :global(.hljs-meta .hljs-string) {
    color: #ce9178;
  }
  .hce-pre :global(.hljs-comment) {
    color: #6a9955;
  }
  .hce-pre :global(.hljs-number) {
    color: #b5cea8;
  }
  .hce-status {
    display: flex;
    gap: 1rem;
    padding: 0.25rem 0.75rem;
    background: #007acc;
    color: #fff;
    font-size: 0.75rem;
  }
  .hce-status span:last-child {
    margin-left: auto;
  }
</style>
