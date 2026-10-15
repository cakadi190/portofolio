import { toast } from '@/lib/toast';

type Action = { label: string; icon: string; run: () => void };

// Lucide icon paths (static, trusted markup).
const ICONS = {
  external:
    '<path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>',
  link: '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
  image:
    '<rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.09-3.09a2 2 0 0 0-2.82 0L6 21"/>',
  quote:
    '<rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>',
  expand:
    '<path d="m21 21-6-6m6 6v-4.8m0 4.8h-4.8"/><path d="M3 16.2V21m0 0h4.8M3 21l6-6"/><path d="M21 7.8V3m0 0h-4.8M21 3l-6 6"/><path d="M3 7.8V3m0 0h4.8M3 3l6 6"/>',
  globe:
    '<circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/>',
} as const;

function icon(path: string): SVGSVGElement {
  const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');

  svg.setAttribute('viewBox', '0 0 24 24');
  svg.setAttribute('width', '16');
  svg.setAttribute('height', '16');
  svg.setAttribute('fill', 'none');
  svg.setAttribute('stroke', 'currentColor');
  svg.setAttribute('stroke-width', '2');
  svg.setAttribute('stroke-linecap', 'round');
  svg.setAttribute('stroke-linejoin', 'round');
  svg.setAttribute('aria-hidden', 'true');
  svg.innerHTML = path;

  return svg;
}

/** Shows an image in the shared full-screen lightbox. */
export async function openLightbox(src: string, alt: string): Promise<void> {
  const [{ mount, unmount }, { default: FigureLightbox }] = await Promise.all([
    import('svelte'),
    import('@/components/ui/figure-lightbox.svelte'),
  ]);
  const target = document.createElement('div');

  document.body.append(target);

  const instance = mount(FigureLightbox, {
    target,
    props: {
      src,
      alt,
      // Leave time for the closing transition before tearing down.
      onclose: () =>
        window.setTimeout(() => {
          void unmount(instance);
          target.remove();
        }, 400),
    },
  });
}

async function copy(text: string, message: string): Promise<void> {
  try {
    await navigator.clipboard.writeText(text);
    toast.success(message);
  } catch {
    toast.error('Tidak dapat menyalin ke papan klip.');
  }
}

function formatOf(src: string): string {
  const extension = /\.([a-z0-9]{2,5})(?:[?#]|$)/i.exec(src)?.[1];

  return extension ? extension.replace('jpg', 'jpeg').toUpperCase() : '';
}

/** Public URL of an image, with the site origin added to relative paths. */
function absoluteUrl(src: string): string {
  return new URL(src, window.location.href).href;
}

function buildPopover(
  figure: HTMLElement,
  image: HTMLImageElement,
): HTMLElement {
  const caption = figure.querySelector('figcaption');
  const credit = caption?.getAttribute('data-credit') ?? '';
  const title = caption?.textContent?.trim() ?? '';
  const source = figure.getAttribute('data-source') ?? '';
  const alt = image.getAttribute('alt') ?? '';
  const src = image.currentSrc || image.src;
  const popover = document.createElement('div');

  popover.className = 'wp-figure-menu';
  popover.setAttribute('role', 'menu');

  const text = (tag: string, className: string, value: string): HTMLElement => {
    const element = document.createElement(tag);

    element.className = className;
    element.textContent = value;

    return element;
  };

  const head = document.createElement('div');

  head.className = 'wp-figure-menu__head';

  if (title || credit) {
    const heading = text('strong', 'wp-figure-menu__title', title || credit);

    heading.prepend(icon(ICONS.globe));
    head.append(heading);
  }

  if (credit && title) {
    const by = text('p', 'wp-figure-menu__by', 'Foto oleh ');

    by.append(text('b', '', credit));
    head.append(by);
  }

  const size = `${image.naturalWidth} × ${image.naturalHeight}`;
  const meta = text('p', 'wp-figure-menu__meta', size);

  meta.append(text('span', '', formatOf(src)));
  head.append(meta);

  if (alt) {
    head.append(text('p', 'wp-figure-menu__alt', `“${alt}”`));
  }

  popover.append(head);

  const attribution = [credit && `Foto oleh ${credit}`, title, source]
    .filter(Boolean)
    .join(' — ');
  const actions: Action[] = [
    ...(source
      ? [
          {
            label: 'Buka sumber asli',
            icon: ICONS.external,
            run: () => window.open(source, '_blank', 'noopener,noreferrer'),
          },
          {
            label: 'Salin tautan sumber',
            icon: ICONS.link,
            run: () => void copy(source, 'Tautan sumber disalin.'),
          },
        ]
      : []),
    {
      label: 'Salin URL gambar',
      icon: ICONS.image,
      run: () => void copy(absoluteUrl(src), 'URL gambar disalin.'),
    },
    ...(attribution
      ? [
          {
            label: 'Salin atribusi',
            icon: ICONS.quote,
            run: () => void copy(attribution, 'Atribusi disalin.'),
          },
        ]
      : []),
    {
      label: 'Lihat ukuran penuh',
      icon: ICONS.expand,
      run: () => void openLightbox(src, alt),
    },
  ];

  const list = document.createElement('div');

  list.className = 'wp-figure-menu__actions';
  actions.forEach(({ label, icon: path, run }) => {
    const button = text('button', 'wp-figure-menu__item', label);

    button.prepend(icon(path));

    button.setAttribute('type', 'button');
    button.setAttribute('role', 'menuitem');
    button.addEventListener('click', () => {
      closePopover();
      run();
    });
    list.append(button);
  });
  popover.append(list);

  return popover;
}

let activePopover: HTMLElement | null = null;

const LEAVE_MS = 150;

/** Fades and zooms the menu out (shadcn leave motion), then removes it. */
function closePopover(): void {
  const closing = activePopover;

  activePopover = null;

  if (!closing) return;

  closing.classList.remove('is-entering');
  closing.classList.add('is-leaving');
  window.setTimeout(() => closing.remove(), LEAVE_MS);
}

function openPopover(
  figure: HTMLElement,
  image: HTMLImageElement,
  point?: { x: number; y: number },
): void {
  closePopover();

  const popover = buildPopover(figure, image);
  const frame = figure.getBoundingClientRect();

  figure.append(popover);
  activePopover = popover;
  popover.classList.add('is-entering');

  const width = popover.offsetWidth;
  const height = popover.offsetHeight;
  const left = point ? point.x - frame.left : frame.width - width - 12;
  const top = point ? point.y - frame.top : frame.height - height - 56;

  const x = Math.max(8, Math.min(left, frame.width - width - 8));
  const y = Math.max(8, Math.min(top, frame.height - height - 8));

  popover.style.left = `${x}px`;
  popover.style.top = `${y}px`;
  // Grow from the corner nearest the pointer, like a context menu.
  popover.style.transformOrigin = `${point && point.x - frame.left > x ? 'left' : 'right'} ${point && point.y - frame.top > y ? 'top' : 'bottom'}`;
  popover.querySelector<HTMLElement>('button')?.focus();
}

let globalListenersBound = false;

function bindGlobalListeners(): void {
  if (globalListenersBound) return;

  globalListenersBound = true;
  document.addEventListener('pointerdown', (event) => {
    if (activePopover && !activePopover.contains(event.target as Node)) {
      closePopover();
    }
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') closePopover();
  });
}

/**
 * Makes published figures interactive: the caption pill and a right click on
 * the photo open a menu (source, copy actions, full size), and "full size"
 * opens a lightbox. Idempotent, so it can run again whenever the HTML changes.
 */
export function enhanceFigures(root: ParentNode = document): void {
  bindGlobalListeners();

  root
    .querySelectorAll<HTMLElement>('figure.wp-block-image')
    .forEach((figure) => {
      const image = figure.querySelector('img');

      if (!image || figure.dataset.enhanced) return;

      figure.dataset.enhanced = 'true';

      figure.querySelector('figcaption')?.addEventListener('click', (event) => {
        if ((event.target as HTMLElement).closest('a')) return;

        openPopover(figure, image);
      });
      image.addEventListener('contextmenu', (event) => {
        event.preventDefault();
        openPopover(figure, image, { x: event.clientX, y: event.clientY });
      });
      image.addEventListener(
        'click',
        () => void openLightbox(image.currentSrc || image.src, image.alt),
      );
      image.style.cursor = 'zoom-in';
    });
}
