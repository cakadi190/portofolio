import FieldGroup from './group.svelte';
import FieldLabel from './label.svelte';
import FieldRow from './row.svelte';
import FieldFeedback from './feedback.svelte';
import { Input } from './input';

const Field = Object.assign(FieldGroup, {
  Group: FieldGroup,
  Label: FieldLabel,
  Row: FieldRow,
  Input,
  Feedback: FieldFeedback,
});

export { Field };
