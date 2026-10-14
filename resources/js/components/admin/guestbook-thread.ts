export type GuestbookAdminNode = {
  id: number;
  parent_id: number | null;
  body: string;
  raw_body: string;
  status: string;
  created_at: string;
  user: { id: number; name: string; email: string } | null;
  guest_name: string | null;
  guest_email: string | null;
  replies: GuestbookAdminNode[];
};

/**
 * Editor-ready HTML of a stored message. Legacy entries are plain text, which the
 * editor would otherwise collapse into one line, so those become escaped paragraphs.
 */
export function toEditorHtml(raw: string): string {
  if (/<[a-z][\s\S]*>/i.test(raw)) {
    return raw;
  }

  const escape = (text: string): string =>
    text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

  return raw
    .split(/\r?\n/)
    .filter((line) => line.trim() !== '')
    .map((line) => `<p>${escape(line)}</p>`)
    .join('');
}
