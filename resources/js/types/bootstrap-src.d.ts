// Bootstrap's ES sources (`bootstrap/js/src/*`) ship without types; they export
// the same classes as the `bootstrap` package that `@types/bootstrap` describes.
// Kept in a script-style file (no top-level import) so these are ambient
// module declarations rather than augmentations.
declare module 'bootstrap/js/src/dropdown' {
  import { Dropdown } from 'bootstrap';
  export default Dropdown;
}

declare module 'bootstrap/js/src/modal' {
  import { Modal } from 'bootstrap';
  export default Modal;
}

declare module 'bootstrap/js/src/offcanvas' {
  import { Offcanvas } from 'bootstrap';
  export default Offcanvas;
}

declare module 'bootstrap/js/src/tooltip' {
  import { Tooltip } from 'bootstrap';
  export default Tooltip;
}
