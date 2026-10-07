import { describe, expect, it } from 'vitest';
import {
  detectMediaType,
  isNativeVideoUrl,
  isNonEmbeddableVideoUrl,
  nativeVideoMime,
  toEmbedUrl,
} from '@/lib/video-embed';

describe('toEmbedUrl', () => {
  it.each([
    [
      'https://youtu.be/abc123',
      'https://www.youtube-nocookie.com/embed/abc123',
    ],
    [
      'https://www.youtube.com/watch?v=abc123',
      'https://www.youtube-nocookie.com/embed/abc123',
    ],
    [
      'https://m.youtube.com/shorts/abc123',
      'https://www.youtube-nocookie.com/embed/abc123',
    ],
    [
      'https://youtube.com/embed/abc123',
      'https://www.youtube-nocookie.com/embed/abc123',
    ],
    ['https://vimeo.com/12345', 'https://player.vimeo.com/video/12345'],
    [
      'https://vidio.com/watch/987-judul',
      'https://www.vidio.com/embed/987-judul',
    ],
    ['https://vidio.com/embed/987', 'https://www.vidio.com/embed/987'],
    [
      'https://www.dailymotion.com/video/x8abc',
      'https://www.dailymotion.com/embed/video/x8abc',
    ],
    ['https://dai.ly/x8abc', 'https://www.dailymotion.com/embed/video/x8abc'],
  ])('converts %s', (input, expected) => {
    expect(toEmbedUrl(input)).toBe(expected);
  });

  it.each([
    'https://example.com/video',
    'https://youtube.com/',
    'https://vidio.com/profile',
    'ftp://youtu.be/abc',
    'javascript:alert(1)',
  ])('returns null for %s', (input) => {
    expect(toEmbedUrl(input)).toBeNull();
  });
});

describe('native video helpers', () => {
  it('detects native video extensions, ignoring query and hash', () => {
    expect(isNativeVideoUrl('/a/b.mp4')).toBe(true);
    expect(isNativeVideoUrl('/a/b.WEBM?x=1')).toBe(true);
    expect(isNativeVideoUrl('/a/b.png')).toBe(false);
  });

  it('guesses a mime type from the extension', () => {
    expect(nativeVideoMime('/a/b.mkv')).toBe('video/x-matroska');
    expect(nativeVideoMime('/a/b.mov?x=1')).toBe('video/quicktime');
    expect(nativeVideoMime('/a/b.txt')).toBeUndefined();
  });

  it('flags non embeddable hosts', () => {
    expect(isNonEmbeddableVideoUrl('https://www.netflix.com/title/1')).toBe(
      true,
    );
    expect(isNonEmbeddableVideoUrl('https://youtube.com/watch?v=1')).toBe(
      false,
    );
  });
});

describe('detectMediaType', () => {
  it('lets an explicit type win', () => {
    expect(detectMediaType('/a.pdf', 'image')).toBe('image');
  });

  it.each([
    ['/doc.pdf?x=1', 'pdf'],
    ['/clip.mp4', 'video'],
    ['https://youtu.be/abc', 'embed'],
    ['https://www.netflix.com/title/1', 'embed'],
    ['/photo.webp', 'image'],
  ])('infers %s as %s', (url, type) => {
    expect(detectMediaType(url)).toBe(type);
  });
});
