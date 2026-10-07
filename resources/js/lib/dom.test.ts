import { describe, expect, it } from 'vitest';
import { portal } from '@/lib/dom';

describe('portal', () => {
  it('moves the node to body and removes it on destroy', () => {
    const host = document.createElement('div');
    const node = document.createElement('span');
    host.appendChild(node);
    document.body.appendChild(host);

    const action = portal(node, undefined);

    expect(node.parentElement).toBe(document.body);

    action?.destroy?.();

    expect(document.body.contains(node)).toBe(false);
  });

  it('moves the node into a selector target', () => {
    const target = document.createElement('div');
    target.id = 'modals';
    document.body.appendChild(target);
    const node = document.createElement('span');

    portal(node, '#modals');

    expect(node.parentElement).toBe(target);
  });
});
