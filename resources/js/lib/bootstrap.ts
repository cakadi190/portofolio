// Only the Bootstrap plugins the site uses (dropdown, modal, offcanvas,
// tooltip), imported from their ES sources. `import 'bootstrap'` would pull in
// every plugin (alert, carousel, collapse, popover, scrollspy, tab, toast…).
// Importing a plugin also registers its `data-bs-*` handlers.
import Dropdown from 'bootstrap/js/src/dropdown';
import Modal from 'bootstrap/js/src/modal';
import Offcanvas from 'bootstrap/js/src/offcanvas';
import Tooltip from 'bootstrap/js/src/tooltip';

export { Dropdown, Modal, Offcanvas, Tooltip };
