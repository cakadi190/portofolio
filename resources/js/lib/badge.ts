const FALLBACK_COLOR = '#6c757d';

function luminance(hex: string): number | null {
  const match = /^#?([0-9a-f]{3}|[0-9a-f]{6})$/i.exec(hex.trim());

  if (!match) {
    return null;
  }

  const digits =
    match[1].length === 3
      ? match[1]
          .split('')
          .map((digit) => digit + digit)
          .join('')
      : match[1];
  const [red, green, blue] = [0, 2, 4].map((offset) => {
    const channel = parseInt(digits.slice(offset, offset + 2), 16) / 255;

    return channel <= 0.03928
      ? channel / 12.92
      : ((channel + 0.055) / 1.055) ** 2.4;
  });

  return 0.2126 * red + 0.7152 * green + 0.0722 * blue;
}

/**
 * Inline style for a badge whose background colour comes from the database:
 * the text is black or white, whichever has the higher WCAG contrast.
 */
export function badgeStyle(color: string | null | undefined): string {
  const background = color || FALLBACK_COLOR;
  const light = luminance(background);
  // White text beats black text only on backgrounds darker than this luminance.
  const text = light !== null && light < 0.179 ? '#ffffff' : '#000000';

  return `background-color: ${background}; color: ${text}`;
}
