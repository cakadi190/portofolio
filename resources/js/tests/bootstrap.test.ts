import { Dropdown, Modal, Tooltip } from 'bootstrap';
import { describe, expect, it } from 'vitest';

describe('bootstrap setup', () => {
  it('exposes the JS components the app relies on', () => {
    expect(Modal).toBeTypeOf('function');
    expect(Dropdown).toBeTypeOf('function');
    expect(Tooltip).toBeTypeOf('function');
  });

  it('reports a Bootstrap 5 version', () => {
    expect(Modal.VERSION).toMatch(/^5\./);
  });

  it('toggles a dropdown menu through the data API', () => {
    document.body.innerHTML = `
      <div class="dropdown">
        <button id="toggle" data-bs-toggle="dropdown">Menu</button>
        <ul class="dropdown-menu"><li>Item</li></ul>
      </div>`;

    const toggle = document.getElementById('toggle') as HTMLElement;
    const menu = document.querySelector('.dropdown-menu') as HTMLElement;
    const dropdown = new Dropdown(toggle);

    dropdown.show();
    expect(menu.classList.contains('show')).toBe(true);

    dropdown.dispose();
  });

  it('instantiates a modal on a DOM element', () => {
    document.body.innerHTML = '<div id="m" class="modal"></div>';
    const element = document.getElementById('m') as HTMLElement;
    const modal = new Modal(element);

    expect(Modal.getInstance(element)).toBe(modal);

    modal.dispose();
  });
});
