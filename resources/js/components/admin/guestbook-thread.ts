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
