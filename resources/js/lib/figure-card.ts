import { toast } from '@/lib/toast';

type Action = { label: string; run: () => void };

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

  if (title || credit) {
    popover.append(text('strong', 'wp-figure-menu__title', title || credit));
  }

  if (credit && title) {
    const by = text('p', 'wp-figure-menu__by', 'Foto oleh ');

    by.append(text('b', '', credit));
    popover.append(by);
  }

  const size = `${image.naturalWidth} × ${image.naturalHeight}`;
  const meta = text('p', 'wp-figure-menu__meta', size);

  meta.append(text('span', '', formatOf(src)));
  popover.append(meta);

  if (alt) {
    popover.append(text('p', 'wp-figure-menu__alt', `“${alt}”`));
  }

  const attribution = [credit && `Foto oleh ${credit}`, title, source]
    .filter(Boolean)
    .join(' — ');
  const actions: Action[] = [
    ...(source
      ? [
          {
            label: 'Buka sumber asli',
            run: () => window.open(source, '_blank', 'noopener,noreferrer'),
          },
          {
            label: 'Salin tautan sumber',
            run: () => void copy(source, 'Tautan sumber disalin.'),
          },
        ]
      : []),
    {
      label: 'Salin URL gambar',
      run: () => void copy(absoluteUrl(src), 'URL gambar disalin.'),
    },
    ...(attribution
      ? [
          {
            label: 'Salin atribusi',
            run: () => void copy(attribution, 'Atribusi disalin.'),
          },
        ]
      : []),
    { label: 'Lihat ukuran penuh', run: () => void openLightbox(src, alt) },
  ];

  const list = document.createElement('div');

  list.className = 'wp-figure-menu__actions';
  actions.forEach(({ label, run }) => {
    const button = text('button', 'wp-figure-menu__item', label);

    button.setAttribute('type', 'button');
    button.setAttribute('role', 'menuitem');
    button.addEventListener('click', () => {
      popover.remove();
      run();
    });
    list.append(button);
  });
  popover.append(list);

  return popover;
}

let activePopover: HTMLElement | null = null;

function closePopover(): void {
  activePopover?.remove();
  activePopover = null;
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

  const width = popover.offsetWidth;
  const height = popover.offsetHeight;
  const left = point ? point.x - frame.left : frame.width - width - 12;
  const top = point ? point.y - frame.top : frame.height - height - 56;

  popover.style.left = `${Math.max(8, Math.min(left, frame.width - width - 8))}px`;
  popover.style.top = `${Math.max(8, Math.min(top, frame.height - height - 8))}px`;
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
